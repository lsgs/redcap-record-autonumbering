<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use Exception;
use REDCap;

/**
 * DAGIncrement
 * Increment within DAG using part of DAG name
 * @author luke.stevens
 */
class DAGIncrement extends AbstractAutonumberGenerator {
        const PATTERN = '/^\w+.*\d+$/';
        
        protected function validateConfiguration() {
                // check additional param contains the info expected :
                // 1. chars from dag name
                // 2. separator
                // 3. length of incrementing integer component
            
                $dag = 'option-setting-dag-part-len';
                $sep = 'option-setting-separator';
                $inc = 'option-setting-inc-part-len';
            
                if (!array_key_exists($dag, $this->config)) {
                        throw new AutonumberConfigException('DAG part configuration required');
                }
                if (empty($this->config[$dag])) {
                        throw new AutonumberConfigException('DAG part configuration is required');
                } 

                if (!array_key_exists($sep, $this->config)) {
                        $this->config[$sep] = '';
                }
            
                if (!array_key_exists($inc, $this->config)) {
                        throw new AutonumberConfigException('Incrementing part length required');
                }
                if (empty($this->config[$inc])) {
                        throw new AutonumberConfigException('Incrementing part length is required');
                } 
                if (!ctype_digit($this->config[$inc]) || intval($this->config[$inc])<1 || intval($this->config[$inc])>19) {
                        throw new AutonumberConfigException('Incrementing part length must be an integer from 1 to 19');
                } else {
                        $this->config[$inc] = intval($this->config[$inc]);
                }
        }
        
        public function getNextRecordId($params=null) {

                $configDagPartLen = $this->config['option-setting-dag-part-len'];
                $configSeparator = $this->config['option-setting-separator'];
                $configIncPartLen = $this->config['option-setting-inc-part-len'];
            
                $pkField = REDCap::getRecordIdField();

                // ensure dag is for project
                $dagId = $_POST['__GROUPID__'];
                $dagUniqueName = REDCap::getGroupNames(false, $dagId);
                if ($dagUniqueName===false) {
                        throw new AutonumberMissingInputException('bad group_id '.$dagId);
                }

                $dagFullName = REDCap::getGroupNames(false, $dagId);

                // Make next id for dag
                $idDagPart = false;
                if ($configDagPartLen==='id') {
                        $idDagPart = $dagId;
                } else if ($configDagPartLen==='un') {
                        $idDagPart = REDCap::getGroupNames(true, $dagId);
                } else if (intval($configDagPartLen) > 0) {
                        $idDagPart = substr($dagFullName, 0, $configDagPartLen);
                } else if (intval($configDagPartLen) < 0){
                        $idDagPart = substr($dagFullName, $configDagPartLen);
                }
                if ($idDagPart===false) {
                        throw new AutonumberGenerateFailedException("Could not take substring of $configDagPartLen chars from '$dagFullName'");
                }

                // read records that begin with dag component
                // - not by dag in case records have been moved or renamed
                $dagRecords = REDCap::getData(
                        'array',    // * PARAM: return_format 
                        null,       // * PARAM: records
                        $pkField,   // * PARAM: fields
                        null,       // * PARAM: events
                        null,       // * PARAM: groups
                        false,      // * PARAM: combine_checkbox_values
                        false,      // * PARAM: exportDataAccessGroups
                        false,      // * PARAM: exportSurveyFields
                        "starts_with([$pkField], '$idDagPart')" // * filterLogic
                );

                if (count($dagRecords) === 0) {
                        return $idDagPart.$configSeparator.sprintf('%0'.$configIncPartLen.'d', 1);
                }

                // sort array of record ids and find max recordPart to increment
                ksort($dagRecords);
                end($dagRecords);
                $lastRecordPart = intval(str_replace($idDagPart.$configSeparator, '', key($dagRecords)));

                do {
                        $newRecordId = $idDagPart.$configSeparator.sprintf('%0'.$configIncPartLen.'d', ++$lastRecordPart);
                        $checkRecord = REDCap::getData('array',$newRecordId,$pkField);
                } while (count($checkRecord)>0) ;

                return $newRecordId;
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match(static::PATTERN, $id);
        }

        /**
         * Require selection of DAG for new records?
         * @return boolean
         */
        public function requireDAG() {
                return true;
        }

        public function getRequiredDataEntryFields() {        
                return array();
        }
}
