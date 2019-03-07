<?php
/**
 * REDCap External Module: Record Autonumber
 * Specify record auto-numbering rules e.g. including part of DAG name
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\RecordAutonumber;

use REDCap;

/**
 * AutonumberGeneratorFactory
 *
 * @author luke.stevens
 */
class AutonumberGeneratorFactory {
        public static function make($className, $config) {

                $dir = dirname(__FILE__);
                $classFile = $dir.'/'.$className.'.php';
                if (file_exists($classFile)) {
                        require_once $className.'.php';
                        $classWithNS = __NAMESPACE__.'\\'.$className;//'MCRI\\RecordAutonumber\\'.$className;
                        $ang = new $classWithNS($config);
                } else {
                        $msg = "ERROR in ".__CLASS__.": Class '$className' not found";
                        REDCap::logEvent($msg);
                        throw new AutonumberConfigException($msg);
                }
                
                return $ang;
        }
}
