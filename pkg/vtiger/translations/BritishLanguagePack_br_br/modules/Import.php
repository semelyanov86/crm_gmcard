<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
$languageStrings = [
    'LBL_IMPORT_STEP_1'            => 'Step 1',
    'LBL_IMPORT_STEP_1_DESCRIPTION' => 'Select File',
    'LBL_IMPORT_SUPPORTED_FILE_TYPES' => 'Supported File Type(s): .CSV, .VCF',
    'LBL_IMPORT_STEP_2'            => 'Step 2',
    'LBL_IMPORT_STEP_2_DESCRIPTION' => 'Specify Format',
    'LBL_FILE_TYPE'                => 'File Type',
    'LBL_CHARACTER_ENCODING'       => 'Character Encoding',
    'LBL_DELIMITER'                => 'Delimiter:',
    'LBL_HAS_HEADER'               => 'Has Header',
    'LBL_IMPORT_STEP_3'            => 'Step 3',
    'LBL_IMPORT_STEP_3_DESCRIPTION' => 'Duplicate Record Handling',
    'LBL_IMPORT_STEP_3_DESCRIPTION_DETAILED' => 'Select this option to enable and set duplicate merge criteria',
    'LBL_SPECIFY_MERGE_TYPE'       => 'Select how duplicate records should be handled',
    'LBL_SELECT_MERGE_FIELDS'      => 'Select the matching fields to find duplicate records',
    'LBL_AVAILABLE_FIELDS'         => 'Available Fields',
    'LBL_SELECTED_FIELDS'          => 'Fields to be matched on',
    'LBL_NEXT_BUTTON_LABEL'        => 'Next',
    'LBL_IMPORT_STEP_4'            => 'Step 4',
    'LBL_IMPORT_STEP_4_DESCRIPTION' => 'Map the Columns to Module Fields',
    'LBL_FILE_COLUMN_HEADER'       => 'Header',
    'LBL_ROW_1'                    => 'Row 1',
    'LBL_CRM_FIELDS'               => 'CRM Fields',
    'LBL_DEFAULT_VALUE'            => 'Default Value',
    'LBL_SAVE_AS_CUSTOM_MAPPING'   => 'Save as Custom Mapping',
    'LBL_IMPORT_BUTTON_LABEL'      => 'Import',
    'LBL_RESULT'                   => 'Result',
    'LBL_TOTAL_RECORDS_IMPORTED'   => 'Total number of records imported',
    'LBL_NUMBER_OF_RECORDS_CREATED' => 'Number of records created',
    'LBL_NUMBER_OF_RECORDS_UPDATED' => 'Number of records overwritten',
    'LBL_NUMBER_OF_RECORDS_SKIPPED' => 'Number of records skipped',
    'LBL_NUMBER_OF_RECORDS_MERGED' => 'Number of records merged',
    'LBL_TOTAL_RECORDS_FAILED'     => 'Total number of records failed',
    'LBL_IMPORT_MORE'              => 'Import More',
    'LBL_VIEW_LAST_IMPORTED_RECORDS' => 'Last Imported Records',
    'LBL_UNDO_LAST_IMPORT'         => 'Undo Last Import',
    'LBL_FINISH_BUTTON_LABEL'      => 'Finish',
    'LBL_UNDO_RESULT'              => 'Undo Import Result',
    'LBL_TOTAL_RECORDS'            => 'Total Number of Records',
    'LBL_NUMBER_OF_RECORDS_DELETED' => 'Number of records deleted',
    'LBL_OK_BUTTON_LABEL'          => 'OK',
    'LBL_IMPORT_SCHEDULED'         => 'Import Scheduled',
    'LBL_RUNNING'                  => 'Running',
    'LBL_CANCEL_IMPORT'            => 'Cancel Import',
    'LBL_ERROR'                    => 'Error:',
    'LBL_CLEAR_DATA'               => 'Clear Data',
    'ERR_UNIMPORTED_RECORDS_EXIST' => 'There are still some unimported records in your import queue, blocking you from importing more data. Clear data to clean it up and start with fresh import ',
    'ERR_IMPORT_INTERRUPTED'       => 'Current Import has been interrupted. Please try again later.',
    'ERR_FAILED_TO_LOCK_MODULE'    => 'Failed to lock the module for import. Re-try again later',
    'LBL_SELECT_SAVED_MAPPING'     => 'Select Saved Mapping',
    'LBL_IMPORT_ERROR_LARGE_FILE'  => 'Import Error Large file ', // TODO: Review
    'LBL_FILE_UPLOAD_FAILED'       => 'File Upload Failed', // TODO: Review
    'LBL_IMPORT_CHANGE_UPLOAD_SIZE' => 'Import Change Upload Size', // TODO: Review
    'LBL_IMPORT_DIRECTORY_NOT_WRITABLE' => 'Import Directory is not writable', // TODO: Review
    'LBL_IMPORT_FILE_COPY_FAILED'  => 'Import File copy failed', // TODO: Review
    'LBL_INVALID_FILE'             => 'Invalid File', // TODO: Review
    'LBL_NO_ROWS_FOUND'            => 'No rows found', // TODO: Review
    'LBL_SCHEDULED_IMPORT_DETAILS' => 'Your import has been scheduled and will start within 15 minutes. You will receive an email after import is completed.  <br> <br>
										Please make sure that the Outgoing server and your email address is configured to receive email notification', // TODO: Review
    'LBL_DETAILS'                  => 'Details', // TODO: Review
    'skipped'                      => 'Skipped Records', // TODO: Review
    'failed'                       => 'Failed Records', // TODO: Review

    'LBL_IMPORT_LINEITEMS_CURRENCY' => 'Currency(Line Items)',

    'LBL_SKIP_THIS_STEP' => 'Skip this step',
    'LBL_UPLOAD_ICS' => 'Upload ICS File',
    'LBL_ICS_FILE' => 'ICS File',
    'LBL_IMPORT_FROM_ICS_FILE' => 'Import from ICS file',
    'LBL_SELECT_ICS_FILE' => 'Select ICS file',

    'LBL_USE_SAVED_MAPS' => 'Use Saved Maps',
    'LBL_IMPORT_MAP_FIELDS' => 'Map the coloumns to CRM fields',
    'LBL_UPLOAD_CSV' => 'Upload CSV File',
    'LBL_UPLOAD_VCF' => 'Upload VCF File',
    'LBL_DUPLICATE_HANDLING' => 'Duplicate Handling',
    'LBL_FIELD_MAPPING' => 'Field Mapping',
    'LBL_IMPORT_FROM_CSV_FILE' => 'Import from CSV file',
    'LBL_SELECT_IMPORT_FILE_FORMAT' => 'Where would you like to import from ?',
    'LBL_CSV_FILE' => 'CSV File',
    'LBL_VCF_FILE' => 'VCF File',
    'LBL_GOOGLE' => 'Google',
    'LBL_IMPORT_COMPLETED' => 'Import Completed',
    'LBL_IMPORT_SUMMARY' => 'Import summary',
    'LBL_DELETION_COMPLETED' => 'Deletion Completed',
    'LBL_TOTAL_RECORDS_SCANNED' => 'Total records scanned',
    'LBL_SKIP_BUTTON' => 'Skip',
    'LBL_DUPLICATE_RECORD_HANDLING' => 'Duplicate record handling',
    'LBL_IMPORT_FROM_VCF_FILE' => 'Import from VCF file',
    'LBL_SELECT_VCF_FILE' => 'Select VCF file',
    'LBL_DONE_BUTTON' => 'Done',
    'LBL_DELETION_SUMMARY' => 'Delete summary',

];
