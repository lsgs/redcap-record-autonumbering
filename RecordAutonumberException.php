<?php

/**
 * REDCap External Module: Record Autonumber
 * Specify record auto-numbering rules e.g. including part of DAG name
 * @author Luke Stevens, Murdoch Children's Research Institute
 */

namespace MCRI\RecordAutonumber;

use Exception;

/**
 * RecordAutonumberException
 * @author luke.stevens
 */
abstract class RecordAutonumberException extends Exception {
}

/**
 * AutonumberConfigException
 * Record auto-numbering module is not properly configured.
 */
class AutonumberConfigException extends RecordAutonumberException {
}

/**
 * AutonumberMissingInputException
 * Auto-number could not be generated due to missing input information.
 */
class AutonumberMissingInputException extends RecordAutonumberException {
}

/**
 * AutonumberGenerateFailedException
 * Auto-number could not be generated: save and use default, temp auto-number.
 */
class AutonumberGenerateFailedException extends RecordAutonumberException {
}