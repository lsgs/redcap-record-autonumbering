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
        const PATTERN = '/^[A-Z0-9]{6}$/';
        const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excludes I, O, 0, 1 to avoid confusion
        const LENGTH = 6;

        protected function validateConfiguration() {
                // no settings required for RandomAlphanumeric
        }

        public function getNextRecordId($params=null) {
                $charset = self::CHARSET;
                $charsetLength = strlen($charset);
                $id = '';

                for ($i = 0; $i < self::LENGTH; $i++) {
                        $randomIndex = random_int(0, $charsetLength - 1);
                        $id .= $charset[$randomIndex];
                }

                return $id;
        }

        public function idMatchesExpectedPattern($id) {
                return preg_match(self::PATTERN, $id);
        }

        public function requireDAG() {
                return false;
        }

        public function getRequiredDataEntryFields() {
                return array();
        }
}
