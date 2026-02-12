<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 * @author Eric Weber, UF PAIN Lab
 */
namespace MCRI\RecordAutonumber;

/**
 * RandomAlphanumeric
 * Create record id as a 6-character randomized alphanumeric string
 * @author Eric Weber, UF PAIN Lab
 */
class RandomAlphanumeric extends AbstractAutonumberGenerator {
        const PATTERN = '/^[||CHARSET||]{||LENGTH||}$/';
        const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excludes I, O, 0, 1 to avoid confusion
        const ATTEMPT_LIMIT = 10000000;
        protected $length;

        protected function validateConfiguration() {
                // check additional param is an integer value
                if (!array_key_exists('option-setting-random-alphanumeric', $this->config)) {
                        throw new AutonumberConfigException('You must specify the number of characters required for record ID length');
                }
                $length = intval($this->config['option-setting-random-alphanumeric']);
                if ($length < 1 || $length > 99) {
                        throw new AutonumberConfigException('Specified length must be from 1 to 99 characters');
                }
                $id = $this->getNextRecordId();
                if (!$this->idMatchesExpectedPattern($id)) {
                        throw new AutonumberGenerateFailedException('ID does not match expected pattern');
                }
                $this->validateAutoNumberVsPkFieldValidation($id);
        }

        public function getNextRecordId($params=null) {
                $charsetLength = strlen(static::CHARSET);
                $length = intval($this->config['option-setting-random-alphanumeric']);
                $id = '';

                $lengthToExceedAttemptLimit = ceil(log(self::ATTEMPT_LIMIT) / log($charsetLength));
                if ($length > $lengthToExceedAttemptLimit) {
                    $maxAttempts = self::ATTEMPT_LIMIT; // don't need to go above 10m attempts - unlikely to be that many records in a redcap project
                } else {
                    $maxAttempts = pow($charsetLength, $length); // number of possible alphanumeric strings of length using charset
                }

                $a = 0;
                $idsAttempted = array();
                do {
                        $id = $this->makeID();
                        if (array_key_exists($id, $idsAttempted)) { // duplicate attempt
                                $idsAttempted[$id]++;
                                $id = '';
                        } else if ($this->module->recordExists($id)) { // new attempt but already used
                                $idsAttempted[$id]++;   
                                $id = '';
                                $a++;
                        }
                } while ($a < $maxAttempts && $id==='');

                if ($id==='') {
                        throw new AutonumberConfigException("No unused IDs of length $length available. Increase the ID length in the settings for the Custom Record Auto-numbering external module.");
                }
                return $id;
        }

        protected function makeID(): string {
                $charset = static::CHARSET;
                $charsetLength = strlen($charset);
                $length = intval($this->config['option-setting-random-alphanumeric']);

                for ($i = 0; $i < $length; $i++) {
                        $randomIndex = random_int(0, $charsetLength - 1);
                        $id .= $charset[$randomIndex];
                }
                return $id;
        }

        public function idMatchesExpectedPattern($id) {
                $length = intval($this->config['option-setting-random-alphanumeric']);
                $pattern = str_replace(['||CHARSET||','||LENGTH||'],[static::CHARSET,$length], self::PATTERN);
                return preg_match($pattern, $id);
        }

        public function requireDAG() {
                return false;
        }

        public function getRequiredDataEntryFields() {
                return array();
        }
}
