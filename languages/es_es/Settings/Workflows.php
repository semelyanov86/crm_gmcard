<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************
 *  Language     : Español es_es
 *  Version      : 6.0.0
 *  Created Date : 2013-05-10
 *  Author       : JPL TSolucio, S. L. Joe Bordes
 *  Last change  : 2013-05-11
 *  Author       : JPL TSolucio, S. L. Joe Bordes
 */
$languageStrings = [
    // Basic Field Names
    'LBL_NEW' => 'Nuevo',
    'LBL_WORKFLOW' => 'Flujo de Trabajo',
    'LBL_CREATING_WORKFLOW' => 'Creando Flujo de Trabajo',
    'LBL_NEXT' => 'Siguiente',

    // Edit view
    'LBL_STEP_1' => 'Paso 1',
    'LBL_ENTER_BASIC_DETAILS_OF_THE_WORKFLOW' => 'Introduce detalles del Flujo de Trabajo',
    'LBL_SPECIFY_WHEN_TO_EXECUTE' => 'Cuando ejecutar el flujo de trabajo',
    'ON_FIRST_SAVE' => 'Solo al crear',
    'ONCE' => 'Solo la primera vez que se cumple la condición',
    'ON_EVERY_SAVE' => 'Cada vez que se guarda la entidad',
    'ON_MODIFY' => 'Cada vez que se modifica la entidad',
    'MANUAL' => 'Sistema',
    'SCHEDULE_WORKFLOW' => 'Programa Flujo de Trabajo',
    'ADD_CONDITIONS' => 'Añadir Condición',
    'ADD_TASKS'                    => 'Añadir acciones',

    // Step2 edit view
    'LBL_EXPRESSION' => 'Expresión',
    'LBL_FIELD_NAME' => 'Campo',
    'LBL_SET_VALUE' => 'Asignar Valor',
    'LBL_USE_FIELD' => 'Utilizar Campo',
    'LBL_USE_FUNCTION' => 'Utilizar Función',
    'LBL_RAW_TEXT' => 'Texto',
    'LBL_ENABLE_TO_CREATE_FILTERS' => 'Habilitar para crear Filtros',
    'LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED' => 'Este flujo de trabajo se creó con una versión anterior. Sus condiciones no pueden ser editadas. Puedes generar de nuevo las condiciones, o utilizar las que hay sin modificarlas.',
    'LBL_USE_EXISTING_CONDITIONS' => 'Utilizar condiciones existentes',
    'LBL_RECREATE_CONDITIONS' => 'Generar de nuevo las condiciones',
    'LBL_SAVE_AND_CONTINUE' => 'Guardar y Continuar',

    // Step3 edit view
    'LBL_ACTIVE' => 'Activo',
    'LBL_TASK_TYPE'                => 'Tipo de acción',
    'LBL_TASK_TITLE'               => 'Acción Título',
    'LBL_ADD_TASKS_FOR_WORKFLOW'   => 'Agregar acción de flujo de trabajo',
    'LBL_EXECUTE_TASK'             => 'Ejecutar acción',
    'LBL_SELECT_OPTIONS' => 'Seleccionar Opciones',
    'LBL_ADD_FIELD' => 'Añadir campo',
    'LBL_ADD_TIME' => 'Añadir tiempo',
    'LBL_TITLE' => 'Título',
    'LBL_PRIORITY' => 'Prioridad',
    'LBL_ASSIGNED_TO' => 'Asignado a',
    'LBL_TIME' => 'Tiempo',
    'LBL_DUE_DATE' => 'Fecha vencimiento',
    'LBL_THE_SAME_VALUE_IS_USED_FOR_START_DATE' => 'Se utilizará el mismo valor para la fecha de inicio',
    'LBL_EVENT_NAME' => 'Nombre Evento',
    'LBL_TYPE' => 'Tipo',
    'LBL_METHOD_NAME' => 'Nombre Método',
    'LBL_RECEPIENTS' => 'Destinatarios',
    'LBL_ADD_FIELDS' => 'Añadir campos',
    'LBL_SMS_TEXT' => 'Texto SMS',
    'LBL_SET_FIELD_VALUES' => 'Asignar valores a campos',
    'LBL_IN_ACTIVE' => 'Inactivo',
    'LBL_SEND_NOTIFICATION' => 'Enviar Notificación',
    'LBL_START_TIME' => 'Hora Inicio',
    'LBL_START_DATE' => 'Fecha Inicio',
    'LBL_END_TIME' => 'Hora Fin',
    'LBL_END_DATE' => 'Fecha Fin',
    'LBL_ENABLE_REPEAT' => 'Repetir',
    'LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE' => 'No hay métodos disponibles para este módulo',
    'LBL_FINISH' => 'Terminar',
    'LBL_NO_TASKS_ADDED'           => 'No hay acciones',
    'LBL_CANNOT_DELETE_DEFAULT_WORKFLOW' => 'No se puede eliminar el flujo de trabajo por defecto',
    'LBL_MODULES_TO_CREATE_RECORD' => 'Crear un registro en',
    'LBL_EXAMPLE_EXPRESSION' => 'Expresión',
    'LBL_EXAMPLE_RAWTEXT' => 'Texto',
    'LBL_VTIGER' => 'Vtiger',
    'LBL_EXAMPLE_FIELD_NAME' => 'Campo',
    'LBL_NOTIFY_OWNER' => 'notify_owner',
    'LBL_ANNUAL_REVENUE' => 'annual_revenue',
    'LBL_EXPRESSION_EXAMPLE2' => "if mailingcountry == 'India' then concat(firstname,' ',lastname) else concat(lastname,' ',firstname) end",
    'LBL_FROM' => 'De',
    'Optional' => 'Opcional',
    'LBL_ADD_TASK'                 => 'Agregar acción',
    'Portal Pdf Url' => 'Portal del Cliente Pdf Url',
    'LBL_ADD_TEMPLATE' => 'Añadir plantilla',
    'LBL_LINEITEM_BLOCK_GROUP' => 'LineItems bloque de grupo impuesto',
    'LBL_LINEITEM_BLOCK_INDIVIDUAL' => 'LineItems bloque de impuesto Individual',
    'LBL_ADD_PDF' => 'Añadir Pdf',

    // Translation for module
    'Calendar' => 'Para hacer',
    'Send Mail' => 'Enviar correo',
    'Invoke Custom Function' => 'Invocar función personalizada',
    'Create Todo' => 'Crear Todo',
    'Create Event' => 'Crear Evento',
    'Update Fields' => 'Actualizar campos',
    'Create Entity'                => 'Crear registro',
    'SMS Task' => 'SMS de tareas',
    'Mobile Push Notification' => 'Push Notification móvil',
    'LBL_ACTION_TYPE' => 'Tipo de acción (Conde activo)',
    'LBL_VTEmailTask' => 'Email',
    'LBL_VTEntityMethodTask' => 'Función personalizada',
    'LBL_VTCreateTodoTask' => 'Tarea',
    'LBL_VTCreateEventTask' => 'Evento',
    'LBL_VTUpdateFieldsTask' => 'El campo Actualización',
    'LBL_VTSMSTask' => 'SMS',
    'LBL_VTPushNotificationTask' => 'Notificación móvil',
    'LBL_VTCreateEntityTask' => 'Crear Record',
    'LBL_MAX_SCHEDULED_WORKFLOWS_EXCEEDED' => 'Número máximo (%s) de los flujos de trabajo programadas se ha excedido',

    'LBL_EDITING_WORKFLOW' => 'Flujo De Trabajo De Edición',
    'LBL_ADD_RECORD' => 'Nuevo Flujo De Trabajo',
    'ON_SCHEDULE' => 'Horario',
    'LBL_RUN_WORKFLOW' => 'Ejecutar El Flujo De Trabajo',
    'LBL_AT_TIME' => 'En El Momento',
    'LBL_HOURLY' => 'Por hora',
    'ENTER_FROM_EMAIL_ADDRESS' => 'Escribe una dirección de correo electrónico',
    'LBL_DAILY' => 'Diario',
    'LBL_WEEKLY' => 'Semanal',
    'LBL_ON_THESE_DAYS' => 'En estos días',
    'LBL_MONTHLY_BY_DATE' => 'Mensual por Fecha',
    'LBL_MONTHLY_BY_WEEKDAY' => 'Mensual por día de la Semana',
    'LBL_YEARLY' => 'Anual',
    'LBL_SPECIFIC_DATE' => 'En Fecha Específica',
    'LBL_CHOOSE_DATE' => 'Elija La Fecha',
    'LBL_SELECT_MONTH_AND_DAY' => 'Seleccione el Mes y la Fecha',
    'LBL_SELECTED_DATES' => 'Las Fechas Seleccionadas',
    'LBL_EXCEEDING_MAXIMUM_LIMIT' => 'Máximo excedido el límite de',
    'LBL_NEXT_TRIGGER_TIME' => 'Siguiente desencadenar tiempo en',
    'LBL_MESSAGE' => 'Mensaje',
    'LBL_WORKFLOW_NAME' => 'Nombre De Flujo De Trabajo',
    'LBL_TARGET_MODULE' => 'Módulo De Destino',
    'LBL_WORKFLOW_TRIGGER' => 'Flujo De Trabajo De Gatillo',
    'LBL_TRIGGER_WORKFLOW_ON' => 'Trigger De Flujo De Trabajo En',
    'LBL_RECORD_CREATION' => 'Creación De Registro De',
    'LBL_RECORD_UPDATE' => 'Actualización De Registro De',
    'LBL_TIME_INTERVAL' => 'Intervalo De Tiempo',
    'LBL_RECURRENCE' => 'La recurrencia',
    'LBL_FIRST_TIME_CONDITION_MET' => 'Sólo la primera vez que condiciones se cumplen',
    'LBL_EVERY_TIME_CONDITION_MET' => 'Cada vez que condiciones se cumplen',
    'LBL_WORKFLOW_CONDITION' => 'Condición De Flujo De Trabajo',
    'LBL_WORKFLOW_ACTIONS' => 'Acciones De Flujo De Trabajo',
    'LBL_DELAY_ACTION' => 'Retrasar La Acción',
    'LBL_FREQUENCY' => 'Frecuencia',
    'LBL_SELECT_FIELDS' => 'Seleccione Los Campos',
    'LBL_INCLUDES_CREATION' => 'Incluye La Creación De',
    'LBL_ACTION_FOR_WORKFLOW' => 'La acción de Flujo de trabajo',
    'LBL_WORKFLOW_SEARCH' => 'Búsqueda por Nombre',

];

$jsLanguageStrings = [
    'JS_STATUS_CHANGED_SUCCESSFULLY' => 'Se ha cambiado el estado correctamente',
    'JS_TASK_DELETED_SUCCESSFULLY' => 'Acción elimina con éxito',
    'JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE' => 'Se ha seleccionado el mismo campo más de una vez',
    'JS_WORKFLOW_SAVED_SUCCESSFULLY' => 'Se ha guardado el flujo de trabajo',
    'JS_CHECK_START_AND_END_DATE' => 'Fin Fecha y hora debe ser mayor o igual a Inicio Fecha y hora',

    'JS_TASK_STATUS_CHANGED' => 'El estado de la tarea cambiado correctamente.',
    'JS_WORKFLOWS_STATUS_CHANGED' => 'Estado del flujo de trabajo ha cambiado correctamente.',
    'VTEmailTask' => 'Enviar Correo',
    'VTEntityMethodTask' => 'Invocar La Función Personalizada',
    'VTCreateTodoTask' => 'Crear Tarea',
    'VTCreateEventTask' => 'Crear Evento',
    'VTUpdateFieldsTask' => 'Actualización De Campos',
    'VTSMSTask' => 'SMS Tarea',
    'VTPushNotificationTask' => 'Móvil De Notificación De Inserción',
    'VTCreateEntityTask' => 'Crear Registro',
    'LBL_EXPRESSION_INVALID' => 'La Expresión No Válida',

];
