<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
$languageStrings = [
    // Basic Field Names
    'LBL_NEW' => 'New',
    'LBL_WORKFLOW' => 'Workflow',
    'LBL_CREATING_WORKFLOW' => 'Creating WorkFlow',
    'LBL_EDITING_WORKFLOW' => 'Editing Workflow',
    'LBL_NEXT' => 'Next',

    // Edit view
    'LBL_STEP_1' => 'Step 1',
    'LBL_ENTER_BASIC_DETAILS_OF_THE_WORKFLOW' => 'Enter basic details of the Workflow',
    'LBL_SPECIFY_WHEN_TO_EXECUTE' => 'Specify when to execute this Workflow',
    'ON_FIRST_SAVE' => 'Only on the first save',
    'ONCE' => 'Until the first time the condition is true',
    'ON_EVERY_SAVE' => 'Every time the record is saved',
    'ON_MODIFY' => 'Every time a record is modified',
    'ON_SCHEDULE' => 'Schedule',
    'MANUAL' => 'System',
    'SCHEDULE_WORKFLOW' => 'Schedule Workflow',
    'ADD_CONDITIONS' => 'Add Conditions',
    'ADD_TASKS'                    => 'Dodaj Akcje',

    // Step2 edit view
    'LBL_EXPRESSION' => 'Expression',
    'LBL_FIELD_NAME' => 'Field',
    'LBL_SET_VALUE' => 'Set Value',
    'LBL_USE_FIELD' => 'Use Field',
    'LBL_USE_FUNCTION' => 'Use Function',
    'LBL_RAW_TEXT' => 'Raw text',
    'LBL_ENABLE_TO_CREATE_FILTERS' => 'Enable to create Filters',
    'LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED' => 'This workflow was created in older look. Conditions created in older look cannot be edited. You can choose to recreate the conditions, or use the existing conditions without changing them.',
    'LBL_USE_EXISTING_CONDITIONS' => 'Use existing conditions',
    'LBL_RECREATE_CONDITIONS' => 'Recreate Conditions',
    'LBL_SAVE_AND_CONTINUE' => 'Save & Continue',

    // Step3 edit view
    'LBL_ACTIVE' => 'Active',
    'LBL_TASK_TYPE'                => 'Rodzaj działania',
    'LBL_TASK_TITLE'               => 'Akcja Tytuł',
    'LBL_ADD_TASKS_FOR_WORKFLOW'   => 'Dodaj działanie dla Workflow',
    'LBL_EXECUTE_TASK'             => 'Wykonaj działania',
    'LBL_SELECT_OPTIONS' => 'Select Options',
    'LBL_ADD_FIELD' => 'Add field',
    'LBL_ADD_TIME' => 'Add time',
    'LBL_TITLE' => 'Title',
    'LBL_PRIORITY' => 'Priority',
    'LBL_ASSIGNED_TO' => 'Assigned to',
    'LBL_TIME' => 'Time',
    'LBL_DUE_DATE' => 'Due Date',
    'LBL_THE_SAME_VALUE_IS_USED_FOR_START_DATE' => 'The same value is used for the start date',
    'LBL_EVENT_NAME' => 'Event Name',
    'LBL_TYPE' => 'Type',
    'LBL_METHOD_NAME' => 'Method Name',
    'LBL_RECEPIENTS' => 'Recepients',
    'LBL_ADD_FIELDS' => 'Add Fields',
    'LBL_SMS_TEXT' => 'Sms Text',
    'LBL_SET_FIELD_VALUES' => 'Set Field Values',
    'LBL_ADD_FIELD' => 'Add Field',
    'LBL_IN_ACTIVE' => 'In Active',
    'LBL_SEND_NOTIFICATION' => 'Send Notification',
    'LBL_START_TIME' => 'Start Time',
    'LBL_START_DATE' => 'Start Date',
    'LBL_END_TIME' => 'End Time',
    'LBL_END_DATE' => 'End Date',
    'LBL_ENABLE_REPEAT' => 'Enable Repeat',
    'LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE' => 'No method is available for this module',
    'LBL_FINISH' => 'Finish',
    'LBL_NO_TASKS_ADDED'           => 'Brak działania',
    'LBL_CANNOT_DELETE_DEFAULT_WORKFLOW' => 'You Cannot delete default Workflow',
    'LBL_MODULES_TO_CREATE_RECORD' => 'Utwórz rekord w',
    'LBL_EXAMPLE_EXPRESSION' => 'Expression',
    'LBL_EXAMPLE_RAWTEXT' => 'Rawtext',
    'LBL_VTIGER' => 'Vtiger',
    'LBL_EXAMPLE_FIELD_NAME' => 'Field',
    'LBL_NOTIFY_OWNER' => 'notify_owner',
    'LBL_ANNUAL_REVENUE' => 'annual_revenue',
    'LBL_EXPRESSION_EXAMPLE2' => "if mailingcountry == 'India' then concat(firstname,' ',lastname) else concat(lastname,' ',firstname) end",
    'LBL_RUN_WORKFLOW' => 'Run Workflow',
    'LBL_AT_TIME' => 'At Time',
    'LBL_HOURLY' => 'Hourly',
    'LBL_DAILY' => 'Daily',
    'LBL_WEEKLY' => 'Weekly',
    'LBL_ON_THESE_DAYS' => 'On these days',
    'LBL_MONTHLY_BY_DATE' => 'Monthly by Date',
    'LBL_MONTHLY_BY_WEEKDAY' => 'Monthly by Weekday',
    'LBL_YEARLY' => 'Yearly',
    'LBL_SPECIFIC_DATE' => 'On Specific Date',
    'LBL_CHOOSE_DATE' => 'Choose Date',
    'LBL_SELECT_MONTH_AND_DAY' => 'Select Month and Date',
    'LBL_SELECTED_DATES' => 'Selected Dates',
    'LBL_EXCEEDING_MAXIMUM_LIMIT' => 'Maximum limit exceeded',
    'LBL_NEXT_TRIGGER_TIME' => 'Next trigger time on',
    'LBL_ADD_TASK'                 => 'Dodaj działanie',
    'Portal Pdf Url' => 'Portal Klienta Link Pdf',
    'LBL_ADD_TEMPLATE' => 'Dodaj szablon',
    'LBL_LINEITEM_BLOCK_GROUP' => 'LineItems Zablokuj podatku grupy',
    'LBL_LINEITEM_BLOCK_INDIVIDUAL' => 'LineItems bloku dla poszczególnych podatku',
    'LBL_ADD_PDF' => 'Dodaj pdf',

    'LBL_FROM' => 'Z',
    'Optional' => 'Fakultatywny',

    // Translation for module
    'Calendar' => 'Uwagi',
    'Send Mail'					   => 'Wyślij mail',
    'Invoke Custom Function'	   => 'Wywołać funkcję indywidualną	',
    'Create Todo'				   => 'Tworzenie Todo',
    'Create Event'				   => 'Utwórz wydarzenie',
    'Update Fields'				   => 'Aktualizacja Pola',
    'Create Entity'                => 'Utwórz rekord',
    'SMS Task'					   => 'Zadanie SMS',
    'Mobile Push Notification'	   => 'Powiadomienia push mobilna',
    'LBL_ACTION_TYPE' => 'Rodzaj działań (Active Hrabia)',
    'LBL_VTEmailTask' => 'E-mail',
    'LBL_VTEntityMethodTask' => 'Funkcja niestandardowych',
    'LBL_VTCreateTodoTask' => 'Zadanie',
    'LBL_VTCreateEventTask' => 'Wydarzenie',
    'LBL_VTUpdateFieldsTask' => 'Pole Aktualizacja',
    'LBL_VTSMSTask' => 'SMS',
    'LBL_VTPushNotificationTask' => 'Powiadomienie mobilna',
    'LBL_VTCreateEntityTask' => 'Utwórz rekord',
    'LBL_MAX_SCHEDULED_WORKFLOWS_EXCEEDED' => 'Maksymalna liczba (%s) regularnych przepływów pracy został przekroczony',

    'LBL_ADD_RECORD' => 'Nowy Workflow',
    'ENTER_FROM_EMAIL_ADDRESS' => 'Wpisz adres E-mail. - mail',
    'LBL_MESSAGE' => 'Komunikat',
    'LBL_WORKFLOW_NAME' => 'Nazwa Procesu Roboczego',
    'LBL_TARGET_MODULE' => 'Cel Modułu',
    'LBL_WORKFLOW_TRIGGER' => 'Proces Roboczy Wyzwalacz',
    'LBL_TRIGGER_WORKFLOW_ON' => 'Uruchomić Biznes Na',
    'LBL_RECORD_CREATION' => 'Podczas Tworzenia Wpisów',
    'LBL_RECORD_UPDATE' => 'Aktualizacja Wpisów',
    'LBL_TIME_INTERVAL' => 'Odstęp Czasu',
    'LBL_RECURRENCE' => 'Powtarzanie',
    'LBL_FIRST_TIME_CONDITION_MET' => 'Tylko po raz pierwszy warunków',
    'LBL_EVERY_TIME_CONDITION_MET' => 'Za każdym razem warunki będą spełnione',
    'LBL_WORKFLOW_CONDITION' => 'Warunków Obiegu Dokumentów',
    'LBL_WORKFLOW_ACTIONS' => 'Działania Przepływu Pracy',
    'LBL_DELAY_ACTION' => 'Opóźnienie Działań',
    'LBL_FREQUENCY' => 'Częstotliwość',
    'LBL_SELECT_FIELDS' => 'Zaznacz Pola',
    'LBL_INCLUDES_CREATION' => 'Obejmuje Tworzenie',
    'LBL_ACTION_FOR_WORKFLOW' => 'Działania dla procesu roboczego',
    'LBL_WORKFLOW_SEARCH' => 'Wyszukiwanie według nazwiska',

];

$jsLanguageStrings = [
    'JS_STATUS_CHANGED_SUCCESSFULLY' => 'Status changed Successfully',
    'JS_TASK_DELETED_SUCCESSFULLY' => 'Akcja została usunięta',
    'JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE' => 'Same fields selected more than once',
    'JS_WORKFLOW_SAVED_SUCCESSFULLY' => 'Workflow saved successfully',
    'JS_CHECK_START_AND_END_DATE' => 'Data zakończenia i czas powinna być większa lub równa Zacznij daty i czasu',

    'JS_TASK_STATUS_CHANGED' => 'Zadaniem status pomyślnie zmieniony.',
    'JS_WORKFLOWS_STATUS_CHANGED' => '"Stan pracy" zmieniono pomyślnie.',
    'VTEmailTask' => 'Wyślij E-Mail',
    'VTEntityMethodTask' => 'Wywołać Funkcję Zdefiniowaną Przez Użytkownika',
    'VTCreateTodoTask' => 'Tworzenie Zadania',
    'VTCreateEventTask' => 'Utwórz Wydarzenie',
    'VTUpdateFieldsTask' => 'Aktualizacja Pól',
    'VTSMSTask' => 'SMS zadania',
    'VTPushNotificationTask' => 'Mobilnych Powiadomień Push',
    'VTCreateEntityTask' => 'Nowy Wpis',
    'LBL_EXPRESSION_INVALID' => 'Wyrażenie Nieważne',

];
