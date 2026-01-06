<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

/**
 * Timestamp
 * Create record id as unix timestamp (microtime * 1000000)
 * @author luke.stevens
 */
class Timestamp extends AbstractAutonumberGenerator {
        const PATTERN = '/^\d{16}$/';
        
        protected function validateConfiguration() {
                // no settings required for Timestamp
                $this->validateAutoNumberVsPkFieldValidation($this->getNextRecordId());
        }
        
        public function getNextRecordId($params=null) {
                return number_format(1000000*microtime(true), 0, '.', '');
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match(static::PATTERN, $id);
        }

        public function requireDAG() {
                return false;
        }

        public function getRequiredDataEntryFields() {        
                return array();
        }
}
