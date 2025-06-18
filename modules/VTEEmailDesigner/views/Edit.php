<?php

ini_set('display_errors', '0');
class VTEEmailDesigner_Edit_View extends Vtiger_Edit_View
{
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $moduleName = 'EmailTemplates';
        $record = $request->get('recordid');
        if (!empty($record)) {
            $viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName));
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($record);
            $viewer->assign('RECORD', $recordModel);
            $duplicateRecordsList = [];
            $duplicateRecords = $request->get('duplicateRecords');
            if (is_array($duplicateRecords)) {
                $duplicateRecordsList = $duplicateRecords;
            }
            $viewer = $this->getViewer($request);
            $viewer->assign('DUPLICATE_RECORDS', $duplicateRecordsList);
            Vtiger_Index_View::preProcess($request, true);
        } else {
            parent::preProcess($request);
        }
        $db = PearDatabase::getInstance();
        $rs_check = $db->query('SELECT count(*) as count_data FROM vteemaildesigner_blocks');
        if ($db->query_result($rs_check, 0, 'count_data') == 0) {
            $this->populateDatabase();
        }
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('QUALIFIED_MODULE', $module);
    }

    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $mode = $request->getMode();
        if ($mode) {
            $this->{$mode}($request);
        } else {
            $this->renderSettingsUI($request);
        }
    }

    public function step2(Vtiger_Request $request)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('SITE_URL', $site_URL);
        $viewer->view('Step2.tpl', $module);
    }

    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Step3.tpl', $module);
    }

    /**
     * Replacing keyword $site_URL with value.
     * @global type $site_URL
     * @param type $content
     * @return type
     */
    public function replaceSiteURLByValue($content)
    {
        global $site_URL;

        return str_replace('{$site_URL}', $site_URL, $content);
    }

    public function initializeContents(Vtiger_Request $request, Vtiger_Viewer $viewer)
    {
        $moduleName = 'EmailTemplates';
        $record = $request->get('recordid');
        if (!empty($record) && $request->get('isDuplicate') == true) {
            $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($record);
            $viewer->assign('MODE', '');
            $viewer->assign('isDuplicate', 'true');
        } else {
            if (!empty($record)) {
                $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($record);
                $viewer->assign('RECORD_ID', $record);
                $viewer->assign('MODE', 'edit');
            } else {
                $recordModel = new VTEEmailDesigner_Record_Model();
                $viewer->assign('MODE', '');
                $recordModel->set('templatename', '');
                $recordModel->set('description', '');
                $recordModel->set('subject', '');
                $recordModel->set('body', '');
            }
        }
        $recordModel->setModule('EmailTemplates');
        if (!$this->record) {
            $body = $recordModel->get('body');
            $body = $this->replaceSiteURLByValue($body);
            $recordModel->set('body', $body);
            $this->record = $recordModel;
        }
        $emailTemplateModuleModel = $this->record->getModule();
        $companyModuleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();
        $moduleFields = $this->record->getEmailTemplateFields();
        $viewer->assign('RECORD', $this->record);
        $viewer->assign('SOURCE_MODULE', $moduleName);
        $viewer->assign('COMPANY_MODEL', $companyModuleModel);
        $viewer->assign('CURRENTDATE', date('Y-n-j'));
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('ALL_FIELDS', $moduleFields);
        $viewer->assign('COMPANY_FIELDS', $emailTemplateModuleModel->getCompanyMergeTagsInfo());
        $viewer->assign('GENERAL_FIELDS', $emailTemplateModuleModel->getCustomMergeTags());
    }

    public function renderSettingsUI(Vtiger_Request $request)
    {
        global $site_URL;
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $module_model = Vtiger_Module_Model::getInstance($module);
        $viewer = $this->getViewer($request);
        $sql = 'select * from vteemaildesigner_block_category ';
        $res = $adb->pquery($sql, []);
        $block_category = [];

        while ($row = $adb->fetch_row($res)) {
            $block_category[] = $row;
        }
        $this->initializeContents($request, $viewer);
        $menuModelsList = Vtiger_Menu_Model::getAll(true);
        $selectedModule = 'EmailTemplates';
        $menuStructure = Vtiger_MenuStructure_Model::getInstanceFromMenuList($menuModelsList, $selectedModule);
        $viewer->assign('MENU_STRUCTURE', $menuStructure);
        $viewer->assign('MENU_SELECTED_MODULENAME', $selectedModule);
        $viewer->assign('MENU_TOPITEMS_LIMIT', $menuStructure->getLimit());
        $viewer->assign('ACTUAL_LINK', $site_URL);
        $viewer->assign('MODULE', $module);
        $viewer->assign('MODULE_MODEL', $module_model);
        $viewer->assign('BLOCK_CATEGORY', $block_category);
        echo $viewer->view('Edit.tpl', $module, true);
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~layouts/v7/modules/VTEEmailDesigner/resources/style.css', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/css/demo.css', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/css/email-editor.bundle.min.css', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/css/colorpicker.css', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/css/editor-color.css', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/vendor/sweetalert2/dist/sweetalert2.min.css', '~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['~layouts/v7/modules/VTEEmailDesigner/resources/assets/vendor/jquery-nicescroll/dist/jquery.nicescroll.min.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/vendor/sweetalert2/dist/sweetalert2.min.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/js/ace.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/js/theme-monokai.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/vendor/tinymce/js/tinymce/tinymce.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/js/colorpicker.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/js/email-editor-plugin.js', '~layouts/v7/modules/VTEEmailDesigner/resources/assets/vendor/bootstrap-tour/build/js/bootstrap-tour.min.js', '~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js', '~layouts/v7/modules/VTEEmailDesigner/canvas/html2canvas.min.js'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function populateDatabase()
    {
        global $adb;
        global $root_directory;
        global $site_URL;
        $adb->pquery('ALTER TABLE vteemaildesigner_blocks CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;', []);
        $script_path = $root_directory . 'modules/VTEEmailDesigner/data/vteemaildesigner_blocks.sql';
        $sql_vteemaildesigner_blocks = file_get_contents($script_path);
        $sql_vteemaildesigner_blocks = str_replace('$site_url$', $site_URL, $sql_vteemaildesigner_blocks);
        $adb->pquery($sql_vteemaildesigner_blocks, []);
    }
}
