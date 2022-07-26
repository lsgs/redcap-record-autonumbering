<?php
/**
 * REDCap External Module: Record Autonumber
 * Specify record auto-numbering rules e.g. including part of DAG name
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

/**
 * AbstractAutonumberGenerator
 *
 * @author luke.stevens
 */
abstract class AbstractAutonumberGenerator {
        protected static $MaxRetryPeriod = 99;
        protected static $RequireDAG = false;
        protected static $RequiredFields = array();
        protected $config;
        protected $module;
        private $retryDelay = 1;
        private $totalRetryTime = 0;

        public function __construct($config, $module) {
                try {
                    $this->config = $config;
                    $this->module = $module;
                    $this->validateConfiguration();
                } catch (RecordAutonumberException $ex) {
                        throw new AutonumberConfigException('Module configuration error for AutonumberGenerator option '.static::getClassNameWithoutNamespace().': "'.$ex->getMessage().'"', 0, $ex);
                }
        }
        
        /**
         * Validate any additional configuration parameters entered in module 
         * configuration
         * Throw an exception if some problem identified.
         */
        abstract protected function validateConfiguration();
        
        /**
         * Generate the next record id according to the desired logic/pattern
         * @return string The next record id 
         */
        abstract public function getNextRecordId($params=null);
        
        /**
         * Check whether the supplied id value matches the format expected for
         * an id generated by this class
         * @param string id to check
         * @return bool 
         */
        abstract public function idMatchesExpectedPattern($id);
        
        /**
         * Require selection of DAG for new records?
         * @return boolean
         */
        abstract public function requireDAG();
        
        /**
         * Return array of field names that are required for generating the 
         * record id.
         * E.g. array('__GROUPID__','diagnosis')
         * @return array
         */
        abstract public function getRequiredDataEntryFields();
        
        /**
         * canRetry()
         * Can we retry getNextRecordId() or should we now give up and use the
         * default record id?
         * @return boolean
         */
        public function canRetry() {
                $thisDelay = $this->retryDelay;
                $this->totalRetryTime =+ $thisDelay;
                $this->retryDelay = 10+$this->retryDelay;
                return $this->totalRetryTime < static::$MaxRetryPeriod;
        }
        
        /**
         * getRetryDelay()
         * @return int retry delay in seconds
         */
        public function getRetryDelay() {
                return $this->retryDelay;
        }
        
        private static function getClassNameWithoutNamespace() {
                $classname = get_called_class();
                if ($pos = strrpos($classname, '\\')) {
                        return substr($classname, $pos + 1);
                }
                return $classname;
        }
}
