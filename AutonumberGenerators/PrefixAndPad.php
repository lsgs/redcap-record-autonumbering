<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use Exception;
use REDCap;

/**
 * PrefixAndPad
 * Zero-padded integer of specified length following a specified prefix
 * @author luke.stevens
 */
class PrefixAndPad extends AbstractAutonumberGenerator {
        private $prefix = '';
        private $padlen = 2;

        protected function validateConfiguration() {
                // check additional param contains the info expected :
                // 1. prefix
                // 2. padding length of incrementing integer component
            
                $prefixOpt = 'option-setting-prefixandpad-prefix';
                $padlenOpt = 'option-setting-prefixandpad-padlen';
                
                if (!array_key_exists($prefixOpt, $this->config)) {
                        throw new AutonumberConfigException('Prefix configuration required');
                }
                if (!empty($this->config[$prefixOpt])) {
                        $this->prefix = trim($this->config[$prefixOpt]);
                } 
            
                if (!array_key_exists($padlenOpt, $this->config)) {
                        throw new AutonumberConfigException('Incrementing part padding length required');
                }
                if (empty($this->config[$padlenOpt])) {
                        throw new AutonumberConfigException('Incrementing part length is required');
                } 
                if (!ctype_digit($this->config[$padlenOpt]) || intval($this->config[$padlenOpt])<1 || intval($this->config[$padlenOpt])>19) {
                        throw new AutonumberConfigException('Incrementing part length must be an integer from 1 to 19');
                } else {
                        $this->padlen = intval($this->config[$padlenOpt]);
                }
        }
        
        public function getNextRecordId($params=null) {

                $pkField = REDCap::getRecordIdField();

                // read records that begin with dag component
                // - not by dag in case records have been moved or renamed
                $prefixedRecords = REDCap::getData(
                        'array',    // * PARAM: return_format 
                        null,       // * PARAM: records
                        $pkField,   // * PARAM: fields
                        null,       // * PARAM: events
                        null,       // * PARAM: groups
                        false,      // * PARAM: combine_checkbox_values
                        false,      // * PARAM: exportDataAccessGroups
                        false,      // * PARAM: exportSurveyFields
                        "starts_with([$pkField], '$this->prefix')" // * filterLogic
                );

                if (count($prefixedRecords) === 0) {
                        return $this->prefix.sprintf('%0'.$this->padlen.'d', 1);
                }

                // sort array of record ids and find max recordPart to increment
                ksort($prefixedRecords);
                end($prefixedRecords);
                $lastRecordPart = intval(str_replace($this->prefix, '', key($prefixedRecords)));

                do {
                        $newRecordId = $this->prefix.sprintf('%0'.$this->padlen.'d', ++$lastRecordPart);
                        $checkRecord = REDCap::getData('array',$newRecordId,$pkField);
                } while (count($checkRecord)>0) ;

                return $newRecordId;
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match('/^'.$this->prefix.'\d+$/', $id);
        }
        
        /**
         * Require selection of DAG for new records?
         * @return boolean
         */
        public function requireDAG() {
                return false;
        }

        public function getRequiredDataEntryFields() {
                return array();
        }
}
