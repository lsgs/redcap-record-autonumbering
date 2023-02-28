<?php
/**
 * REDCap External Module: Record Autonumber
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use Exception;
use REDCap;

/**
 * PrefixAndPadCheckdigit
 * Zero-padded integer of specified length following a specified prefix and Check digit using Luhn10
 * @author Joby Joje
 */
class PrefixAndPadCheckdigit extends AbstractAutonumberGenerator {
        private $prefix = '';
        private $seperator = '';
        private $padlen = 2;

        protected function validateConfiguration() {
                // check additional param contains the info expected :
                // 1. prefix
                // 2. seperator
                // 3. padding length of incrementing integer component
            
                $prefixOpt = 'option-setting-prefixandpadchckdigit-prefix';
                $seperator = 'option-setting-prefixandpadchckdigit-separator';
                $padlenOpt = 'option-setting-prefixandpadchckdigit-padlen';
                //Set the Prefix if provided else empty
                if (array_key_exists($prefixOpt, $this->config)) {
                        $this->prefix = trim($this->config[$prefixOpt]);
                } else {
                        $this->prefix = '';
                }
                //Set the Seperator if provided else empty
                if (array_key_exists($seperator, $this->config)) {
                        $this->seperator = trim($this->config[$seperator]);
                } else {
                        $this->seperator = '';
                } 
                //Setting the required padding
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

        /**
         * Generate a check digit for a given input string using Luhn algorithm.
         *
         * @param string $input The input string to generate check digit for.
         * @param string $sep The separator character to remove from the input string before generating check digit.
         *
         * @return int The check digit generated for the input string.
         */
        public function generateCheckDigit($input, $sep) {
                $input = str_replace($sep, '', $input); // Remove any hyphens
                $input = strtoupper($input); // Convert to uppercase
                $checksum = 0;
                $multiplication = 2;
                for ($i = strlen($input) - 1; $i >= 0; $i--) {
                    $code = ord($input[$i]) - ord('0'); // Convert character to code
                    if ($multiplication % 2 == 0) { // Multiply every other digit by 2
                        $code *= 2;
                        if ($code > 9) { // If result is two digits, sum the digits
                            $code -= 9;
                        }
                    }
                    $checksum += $code;
                    $multiplication++;
                }
                $checkdigit = (10 - ($checksum % 10)) % 10; // Calculate check digit
                return $checkdigit;
            }

        /**
         * Validate a given input string using the Luhn10 algorithm.
         *
         * @param string $input The input string to validate.
         * @param string $sep The separator character used in the input string.
         *
         * @return bool True if the input string is valid, false otherwise.
         */
        function validateInput($input, $sep) {
                // Get the check digit from the input string.
                $checkdigit = substr($input, -1);
                // Remove the check digit from the input string.
                $input = substr($input, 0, -1);
                // Generate the check digit for the input string.
                $generatedCheckDigit = $this->generateCheckDigit($input, $sep);
                // Compare the generated check digit with the one in the input string.
                if ($checkdigit == $generatedCheckDigit) {
                return true;
                } else {
                return false;
                }
        }

        public function getNextRecordId($params=null) {

                $pkField = REDCap::getRecordIdField();
                //Initalise local variables
                $prefix = $this->prefix;
                $seperator = $this->seperator;
                $padding = $this->padlen;

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
                        $newRecordId = $prefix.$seperator.sprintf('%0'.$padding.'d', 1);
                        $checkdigit = $this->generateCheckDigit($newRecordId, $seperator);
                        return $newRecordId.$checkdigit;
                }

                // sort array of record ids and find max recordPart to increment
                ksort($prefixedRecords);
                end($prefixedRecords);
                //Fetch the last record from the array
                //Remove the prefix and seperator and extract the number without Check-Digit
                $lastRecordPart = intval(substr(str_replace($prefix.$seperator, '', key($prefixedRecords)), 0, -1));

                //Incrementing the RecordID from the last record number
                do {
                        $newRecordId = $prefix.$seperator.sprintf('%0'.$padding.'d', ++$lastRecordPart);
                        $checkdigit = $this->generateCheckDigit($newRecordId, $seperator);
                        $newRecordId = $newRecordId.$checkdigit;
                        $checkRecord = REDCap::getData('array',$newRecordId,$pkField);
                } while (count($checkRecord)>0) ;

                return $newRecordId;
        }
        
        public function idMatchesExpectedPattern($id) {
                return preg_match('/^'.$this->prefix.$this->seperator.'\d+$/', $id);
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
