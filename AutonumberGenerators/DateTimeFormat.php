<?php
/**
 * REDCap External Module: Record Autonumber
 * Specify record auto-numbering rules e.g. including part of DAG name
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use DateTime;

/**
 * Timestamp
 * Create record id as date/time stamp using the selected format option
 * @author luke.stevens
 */
class DateTimeFormat extends AbstractAutonumberGenerator {
        protected function validateConfiguration() {
                // check additional param is a valid format string
                if (!array_key_exists('option-setting-date-time-format', $this->config)) {
                        throw new AutonumberConfigException('Datetime format string is required');
                }
                if (empty($this->config['option-setting-date-time-format'])) {
                        throw new AutonumberConfigException('Datetime format not specified');
                } 
                
                $now = new DateTime();

                if (false===$now->format($this->config['option-setting-date-time-format'])) {
                        throw new AutonumberConfigException('Invalid date/time format string "'.$this->config['option-setting-date-time-format'].'"');
                }
        }
        
        public function getNextRecordId($params=null) {
                $now = new DateTime();
                return $now->format($this->config['option-setting-date-time-format']);
        }
        
        public function idMatchesExpectedPattern($id) {
                return false!==DateTime::createFromFormat($this->config['option-setting-date-time-format'], $id);
        }

        public function requireDAG() {
                return false;
        }

        public function getRequiredDataEntryFields() {        
                return array();
        }
}
