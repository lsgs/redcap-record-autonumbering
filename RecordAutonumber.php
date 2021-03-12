<?php
/**
 * REDCap External Module: Record Autonumber
 * Specify record auto-numbering rules e.g. including part of DAG name, date format
 * Does not work for records created via survey response or randomisation.
 * @author Luke Stevens, Murdoch Children's Research Institute
 * 
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * TODO
 * 
 * http://php.net/manual/en/function.override-function.php autoIncSet()
 * 
 * When enabling check that all custom classes used (read project settings) are 
 * present in AutonumberGenerators directory
 *  - do not permit enabling without that!
 *  - is it automateable?
 */
namespace MCRI\RecordAutonumber;

require_once 'AutonumberGenerators/AbstractAutonumberGenerator.php';
require_once 'AutonumberGenerators/AutonumberGeneratorFactory.php';
require_once 'RecordAutonumberException.php';

use ExternalModules\AbstractExternalModule;
use REDCap;

/**
 * REDCap External Module: Record Autonumber
 */
class RecordAutonumber extends AbstractExternalModule
{
        const TEMP_RECORD_STEM = 'auto_';
        const MODULE_VARNAME = 'MCRI_Record_Autonumber';
        const DAG_ELEMENT_NAME = '__GROUPID__';
        
        private $autonumberGenerator;
        
        private $lang;
        private $page;
        private $project_id;
        private $super_user;
        private $user;
        private $user_rights;

        public function __construct() {
                parent::__construct();
                global $Proj, $lang, $user_rights;
                $this->page = PAGE;
                $this->project_id = intval(PROJECT_ID);
                $this->super_user = SUPER_USER;
                $this->user = strtolower(USERID);
                $this->Proj = $Proj;
                $this->lang = &$lang;
                $this->user_rights = &$user_rights;
                $this->autonumberGenerator = null;
                
                if ($this->project_id > 0) {
                        // read all the config options
                        $settingsArray = $this->getProjectSettings($this->project_id);

                        // only create object when module is actually enabled for project!
                        if (!$settingsArray['enabled']['value']) return;

                        try {

                                $autonumberClassName = '';
                                foreach ($settingsArray as $settingKey => $settingValues) {
                                        if ($settingKey==='autonumber-option') {
                                                $autonumberClassName = $settingValues['value'];
                                        } else if (strpos($settingKey, 'option-setting-')===0) {
                                                $autonumberSettings[$settingKey] = $settingValues['value'];
                                        }
                                }

                                if ($autonumberClassName==='Custom') {
                                        $autonumberClassName = $autonumberSettings['option-setting-custom-class-name'];
                                        unset($autonumberSettings['option-setting-custom-class-name']);
                                }

                                if ($autonumberClassName!=='') { // ... and is configured
                                        $this->autonumberGenerator = AutonumberGeneratorFactory::make(
                                                $autonumberClassName,
                                                $autonumberSettings
                                        );
                                }
                        } catch (AutonumberConfigException $e) {
                                $this->setCrossPageMessage($e->getMessage());
                        }
                }
        }

        function redcap_module_project_enable($version, $project_id) {
                // Turn record autonumbering on if it is not already on
                if (!$Proj->project['auto_inc_set']) {
                        $sql = "update redcap_projects set auto_inc_set=1 where auto_inc_set=0 and project_id=".db_escape($project_id);
                        $q = db_query($sql);
                        $nrows = db_affected_rows();
                        if ($nrows==1) {
                                \Logging::logEvent($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Modify project settings");
                        }
                }
        }
        
        /**
         * redcap_every_page_top
         * Perform three functions:
         * 1. ProjectSetup page: alter  "auto-numbering" button to indicate
         *    usage of this module.
         * 2. ExternalModules/manager/project auto-open config when selected 
         *    "Configure" from ProjectSetup page button
         * 3. Detect auto-number generation error and display dialog box to 
         *    inform user
         * @param int project_id
         */
        public function redcap_every_page_top($project_id) {
            
                if (defined('USERID') && isset($this->project_id) && $this->project_id > 0) {
                        if (strpos($this->page, 'ProjectSetup/index.php')!==false) {
                                $this->includeProjectSetupPageContent();
                        } else if (strpos($this->page, 'ExternalModules/manager/project.php')>0) {
                                $this->includeModuleManagerPageContent();
                        } 
                        if ($this->crossPageMessageIsSet()) {
                                $this->includeMessagePopup($this->getCrossPageMessage());
                                $this->clearCrossPageMessage();
                        }
                        if ($this->page==='DataEntry/record_home.php' && isset($_GET['id']) && isset($_GET['auto'])) { 

                                if (isset($_GET['arm']) && array_key_exists($_GET['arm'], $this->Proj->events)) {
                                        $armFirstEventId = key($this->Proj->events[$_GET['arm']]['events']);
                                        $armFirstForm = $this->Proj->eventsForms[$armFirstEventId][0];
                                } else {
                                        $armFirstEventId = $this->Proj->firstEventId;
                                        $armFirstForm = $this->Proj->firstForm;
                                }
                                $tempRecId = $_GET['id'];
                                $gotoUrl = APP_PATH_WEBROOT."DataEntry/index.php?pid={$this->project_id}&id=$tempRecId&event_id=$armFirstEventId&page=$armFirstForm";
                                
                                // use javascript to redirect to first form
                                // can't use redirect($loc) due to EM framework exceptions
                                echo "<script type=\"text/javascript\">window.location.href=\"$gotoUrl\";</script>";
                        }
                }
        }

        /**
         * redcap_every_page_before_render
         * Detect when new record is being saved and attempt to generate a new
         * record id.
         * I.e. data entry form page has been sumbmitted and is reloading after
         * the submit POST.
         * @param int project_id
         */
        public function redcap_every_page_before_render($project_id) {
                // is this is a new data entry record (not survey) that is not yet saved?
                if ($this->page==='DataEntry/index.php' && isset($_POST['submit-action']) && $_POST['submit-action']!=='submit-btn-cancel') {
                        $pkField = REDCap::getRecordIdField();
                        $postRec = $_POST[$pkField];
                        if (isset($postRec) && !$this->recordExists($postRec)) {
                                try {
                                        $_POST[$pkField] = $this->getNextRecordId();
                                } catch (AutonumberGenerateFailedException $e) {
                                        $msg = 'Could not generate record number in the expected format: '.$e->getMessage();
                                        $this->handleAutonumberException($postRec, $msg);
                                } catch (AutonumberMissingInputException $e) {
                                        $msg = 'Could not generate record number due to missing required data: '.$e->getMessage();
                                        $this->handleAutonumberException($postRec, $msg);
                                } catch (RecordAutonumberException $e) {
                                        $msg = 'Auto-number generation failed: '.$e->getMessage();
                                        $this->handleAutonumberException($postRec, $msg);
                                }
                                unset($_GET['auto']);
                                // continue and save record (with default, temp id if could not generate)...
                        }
                }
                else if ($this->page==='DataEntry/record_home.php' && isset($_GET['id']) && isset($_GET['auto'])) { 

                        if (isset($_GET['arm']) && array_key_exists($_GET['arm'], $this->Proj->events)) {
                                $armFirstEventId = key($this->Proj->events[$_GET['arm']]['events']);
                                $armFirstForm = $this->Proj->eventsForms[$armFirstEventId][0];
                        } else {
                                $armFirstEventId = $this->Proj->firstEventId;
                                $armFirstForm = $this->Proj->firstForm;
                        }
                        $tempRecId = $_GET['id'];
                        $gotoUrl = APP_PATH_WEBROOT."DataEntry/index.php?pid={$this->project_id}&id=$tempRecId&event_id=$armFirstEventId&page=$armFirstForm";
                        redirect( $gotoUrl);
                }
        }
        
        protected function handleAutonumberException($postRec, $msg) {
                $msg.'<br><br>The record has been saved using a temporary record id.<br><b>You must rename this record manually.</b>';
                $this->setCrossPageMessage($msg);
                REDCap::logEvent($msg, "", "", $postRec, $_GET['event_id'], $this->project_id);
        }

        /**
         * redcap_add_edit_records_page
         * Disable "Add new record" if error in module configuration
         */
        public function redcap_add_edit_records_page($project_id, $instrument, $event_id) {
                if ($this->user_rights['record_create'] && !isset($this->autonumberGenerator)) {
                        ?>
                        <script type='text/javascript'>
                            $(document).ready( function() { 
                                $('button:contains("<?php echo $this->lang['data_entry_46'];?>")').prop('disabled', 'disabled').attr('title', 'Fix the module configuration first!');
                            });
                        </script>
                        <?php
                }
        }
        
        /**
         * redcap_data_entry_form_top
         * Tweaks to data entry form
         *  1. Hide temp record id
         *  2. Prevent submit when essential data (e.g. DAG) is missing
         */
        public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
                if (is_null($this->autonumberGenerator) || !is_subclass_of($this->autonumberGenerator, 'MCRI\RecordAutonumber\AbstractAutonumberGenerator')) { return; }
                if ($this->user_rights['record_create'] &&
                    !$this->recordExists($record)) {
                        $this->includeDataEntryPageContent();
                }
        }

        /**
         * When records are created by a survey response, it is not possible to 
         * prevent integer incrementing. The only way to generate custom record
         * numbers is to catch a new record after it is first saved and rename
         * it.
         * NOT YET IMPLEMENTED
         */
        public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
                //
        }
        
        protected function recordExists($recId) {
                if (is_null($recId)) { return false; }
                return count(REDCap::getData('array', $recId))>0;
        }
        
        protected function getNextRecordId() {
                do {
                        $nextId = $this->autonumberGenerator->getNextRecordId();
                        if ($this->autonumberGenerator->idMatchesExpectedPattern($nextId) &&
                            !$this->recordExists($nextId)) {
                                return $nextId;
                        }
                        sleep($this->autonumberGenerator->getRetryDelay());
                } while ($this->autonumberGenerator->canRetry());

                throw new AutonumberGenerateFailedException('Attempts to generate new unique record id number on save timed out.');
        }
        
        protected function includeMessagePopup($message) {
                $content = REDCap::escapeHtml($message);
                $title = 'External Module: '.REDCap::escapeHtml($this->getModuleName());
                ?>
                <script type="text/javascript">
                    simpleDialog('<?php echo $content;?>', '<?php echo $title;?>');
                </script>
                <?php
        }
        
        protected function includeProjectSetupPageContent() {
                // Change the "Record autonumbering" enable/disble button to point to the module config page
                $moduleConfigUrl = APP_PATH_WEBROOT.'ExternalModules/manager/project.php?pid='.$this->project_id.'&autonumber_config=1';
                $disabled = (SUPER_USER || $this->user_rights['design']==='1') ? '' : 'disabled=""';
                ?>
                <div id="emAutoNumConfigDiv" style="text-indent:-75px;margin-left:75px;margin-bottom:2px;color:green;display:none;">
                    <button class="btn btn-defaultrc btn-xs fs11" id="emAutoNumConfigBtn" <?php echo $disabled;?>>Configure</button>
                    <img src="<?php echo APP_PATH_IMAGES;?>accept.png" style="margin-left:8px;">
                    <?php echo $this->lang['setup_94'];?><img src="<?php echo APP_PATH_IMAGES;?>puzzle_small.png" style="margin-left:5px;"><a href="javascript:;" class="help" title="Tell me more" id="emAutoNumQuestionDialog">?</a>
                </div>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $('#emAutoNumConfigBtn').click(function() {
                            window.location.href = '<?php echo $moduleConfigUrl;?>';
                        });
                        $('#emAutoNumQuestionDialog').click(function() {
                            simpleDialog(
                                '<div title="<?php echo REDCap::escapeHtml($this->getModuleName());?>"><?php echo REDCap::escapeHtml($this->getProjectSetting('project-setup-dialog-text'));?></div>',
                                '<img src="'+app_path_images+'puzzle_small.png"/> <?php echo REDCap::escapeHtml($this->getModuleName());?>'
                            );
                        });
                        $('button[onclick*="auto_inc_set"]').parent('div').replaceWith($('#emAutoNumConfigDiv'));
                        $('#emAutoNumConfigDiv').show();
                    });
                </script>
                <?php
        }

        /**
         * Module manager page content
         * If url contains autonumber_config=1 then trigger a click on the 
         * Custom Record Auto-numbering module "Configure" button
         */
        protected function includeModuleManagerPageContent() {
                if (isset($_GET['autonumber_config'])) {
                        ?>
                        <script type="text/javascript">
                            $(window).on('load', function() {
                                history.pushState({}, null, location.href.split("&autonumber_config")[0]);
                                $('tr[data-module="record_autonumber"] button.external-modules-configure-button').trigger('click');
                            });
                        </script>
                        <?php
                }
        }

        /**
         * Tweaks to data entry form
         *  1. Hide display of temporary record id
         *  2. Prevent submit when essential data (e.g. DAG) is missing
         */
        protected function includeDataEntryPageContent() {
                $pkField = REDCap::getRecordIdField();
                ?>
                <style type="text/css">
                    /* Hide the "Save changes and leave" button in the unsaved changes dialog */
                    .dataEntrySaveLeavePageBtn { display:none; }
                </style>
                <script type="text/javascript">
                    $(document).ready(function() {
                        // Hide elements that display the temporary record id
                        $('#contextMsg').find('div.darkgreen:contains("Adding new")').find('b').hide();
                        $('#<?php echo $pkField;?>-tr').hide();
                        $('div.menuboxsub').hide();
                        $('div.formMenuList').hide();
                        
                    });
                </script>
                <?php
                
                $requiredFieldList = $this->autonumberGenerator->getRequiredDataEntryFields();
                if (count($requiredFieldList) > 0 || $this->autonumberGenerator->requireDAG()) {
                        $this->includeRequiredFieldsCheck($requiredFieldList);
                }
        }
        
        protected function includeRequiredFieldsCheck($requiredFieldList) {
                $requiredFields = array();
                
                if (count($requiredFieldList) > 0) {
                        $requiredFields = array();
                        $dd = REDCap::getDataDictionary('array', false, $requiredFieldList);
                        foreach ($dd as $fName => $fConfig) {
                                $requiredFields[$fName]['field_label'] = REDCap::escapeHtml($fConfig['field_label']);
                        }
                }
                
                if ($this->autonumberGenerator->requireDAG()) {
                        $requiredFields[self::DAG_ELEMENT_NAME] = array(
                                'field_label' => $this->lang['global_78'] // Data Access Group
                        );
                }
           
                ?>
<script type='text/javascript'>
(function() {
    var requiredFields = JSON.parse('<?php echo json_encode($requiredFields);?>');   
    
    // Check required fields are set (alert and stop if not) before saving
    var customSaveForm = function (sendOb) {
        
        var missingMsgTitle = '<?php echo $this->lang['data_entry_71'];?>'; // "Some fields are required!"
        var missingMsg = '<?php echo $this->lang['data_entry_73'];?>'; // "Provide a value for..."'
        var allGood = true;
        Object.getOwnPropertyNames(requiredFields).forEach(function(fName) {
            var fValue = $('[name='+fName+']').val();
            if (fValue==='') {
                missingMsg += '<br><b>'+requiredFields[fName].field_label+'</b>';
                allGood = false;
            }
        });

        if (allGood) {
            // disable buttons to prevent double-click
            setTimeout(function(){ $('#form :button').prop('disabled',true); },10);
            // Then do the regular form save stuff
            dataEntrySubmit(sendOb);
        } else {
            simpleDialog(missingMsg, missingMsgTitle);
        }
        return false;
    };


    $(window).on('load', function() {
        // Adjust the Save button click events 
        var saveBtns = $('#__SUBMITBUTTONS__-div [id^=submit-btn-save], #formSaveTip [id^=submit-btn-save]');
        $.each(saveBtns, function ( btnIndex, thisBtn ) {
            // Alter the onclick event of the button to our custom save function
            $(thisBtn).onclick = null;
            $(thisBtn).removeAttr('onclick').prop('onclick', null).off('click'); // make sure!
            $(thisBtn).click(function() {
                customSaveForm(thisBtn);
                return false;
            });
        });
    });
}());
</script>
<?php

        }
       
        protected function getCrossPageMessage() {
                return ($this->crossPageMessageIsSet()) ? $_SESSION[self::MODULE_VARNAME] : null;
        }
       
        protected function setCrossPageMessage($msg) {
                $_SESSION[self::MODULE_VARNAME] = $msg;
        }
       
        protected function clearCrossPageMessage() {
                unset($_SESSION[self::MODULE_VARNAME]);
        }
       
        protected function crossPageMessageIsSet() {
                return isset($_SESSION[self::MODULE_VARNAME]);
        }
}
