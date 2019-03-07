<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use REDCap;

/**
 * IncrementFromSeed
 * Create record id as integer incrementing from first id specified.
 * Ids are project-wide (irrespective of DAG)
 * @author luke.stevens
 */
class IncrementFromSeed extends AbstractAutonumberGenerator {
        const PATTERN = '/^\d+$/';
        
        protected function validateConfiguration() {
                // check additional param is an integer value
                if (!array_key_exists('option-setting-increment-from-seed', $this->config)) {
                        throw new AutonumberConfigException('Seed value required');
                }
                if (empty($this->config['option-setting-increment-from-seed'])) {
                        throw new AutonumberConfigException('Seed value not specified');
                }
                if (!ctype_digit($this->config['option-setting-increment-from-seed'])) {
                        throw new AutonumberConfigException('Integer seed value required');
                }
        }
        
        public function getNextRecordId($params=null) {
                $seed = floatval($this->config['option-setting-increment-from-seed']); // floatval() handles larger integers than intval(), have already checked seed is an integer
                
                $allRecords = REDCap::getData('array',null,REDCap::getRecordIdField());

                if (count(allRecords) === 0) { return $seed; }

                // find the current max integer id
                $currentMax = 0;
                foreach (array_keys($allRecords) as $recId) {
                        if (is_int($recId) || ctype_digit($recId)){
                            if ($recId*1 > $currentMax) { $currentMax = $recId*1; }
                        }
                }
                
                $result = number_format(($currentMax >= $seed) ? $currentMax+1 : $seed, 0, '.', '');
                
                if ($result==$currentMax) {
                        throw new AutonumberGenerateFailedException("Current max id ($currentMax) is too large to increment.");
                }
                
                return $result;
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match(static::PATTERN, $id);
        }
}
