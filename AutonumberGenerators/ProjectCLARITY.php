<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use REDCap;

/**
 * ProjectCLARITY
 * Increment within DAG using part of DAG name
 * @author luke.stevens
 */
class ProjectCLARITY extends AbstractAutonumberGenerator {
        const PATTERN = '/^([JM][0-5]|C0)\d{4}$/';
        const INC_PART_LENGTH = 4;
        private $requiredFields = array();
        
        private static $PopFieldValueMap = array(
                '1' => 'J',
                '2' => 'M',
                '3' => 'C'
        );
        
        private static $StemSeedValues = array(
                'J0' => 605,
                'C0' => 787
        );
        
        public function getRequiredDataEntryFields() {        
                return $this->requiredFields;
        }
        
        /**
         * Only onfig required is the names of the required fields
         */
        protected function validateConfiguration() {
                $configObj = json_decode($this->config['option-setting-custom-params']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new AutonumberConfigException('Configuration parameter is not a valid JSON string: '.$this->config);
                }
                if (!property_exists($configObj, 'requiredFields')) {
                        throw new AutonumberConfigException('Required fields expected as \"requiredFields\"');
                }
                if (!is_array($configObj->requiredFields)) {
                        throw new AutonumberConfigException('Array of required field names expected');
                }
                $this->requiredFields = $configObj->requiredFields;
        }
        
        public function getNextRecordId($params=null) {
                $pkField = REDCap::getRecordIdField();
                
                // ensure required fields have values
                $loc = $_POST[$this->requiredFields[0]];
                if (!ctype_digit($loc) && strlen($loc)!==1) {
                        throw new AutonumberGenerateFailedException("Unexpected value for location: '$loc'");
                }
                
                $pop = $_POST[$this->requiredFields[1]];
                if (!array_key_exists($pop, static::$PopFieldValueMap)) {
                        throw new AutonumberGenerateFailedException("Unexpected value for study population: '$pop'");
                }

                $stem = static::$PopFieldValueMap[$pop].$loc;
                $seed = (array_key_exists($stem, static::$StemSeedValues)) 
                        ? static::$StemSeedValues[$stem]
                        : 1;
                
                // read records that begin with stem
                // - not by location/population field values in case records have been moved or renamed
                $stemRecords = REDCap::getData(
                        'array',    // * PARAM: return_format 
                        null,       // * PARAM: records
                        $pkField,   // * PARAM: fields
                        null,       // * PARAM: events
                        null,       // * PARAM: groups
                        false,      // * PARAM: combine_checkbox_values
                        false,      // * PARAM: exportDataAccessGroups
                        false,      // * PARAM: exportSurveyFields
                        "starts_with([$pkField], '$stem')" // * filterLogic
                );

                if (count($stemRecords) === 0) {
                        return $stem.sprintf('%0'.self::INC_PART_LENGTH.'d', $seed);
                }

                // sort array of record ids and find max recordPart to increment
                ksort($stemRecords);
                end($stemRecords);
                $lastRecordPart = intval(str_replace($stem, '', key($stemRecords)));

                do {
                        $newRecordId = $stem.sprintf('%0'.self::INC_PART_LENGTH.'d', ++$lastRecordPart);
                        $checkRecord = REDCap::getData('array',$newRecordId,$pkField);
                } while (count($checkRecord)>0) ;

                return $newRecordId;
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match(static::PATTERN, $id);
        }
}
