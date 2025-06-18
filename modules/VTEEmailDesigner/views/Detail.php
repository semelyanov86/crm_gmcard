<?php

class VTEEmailDesigner_Detail_View extends Vtiger_Index_View
{
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);
        $recordId = $request->get('record');
        $moduleName = $request->getModule();
        if (!$this->record) {
            $this->record = VTEEmailDesigner_DetailView_Model::getInstance($moduleName, $recordId);
        }
        $recordModel = $this->record->getRecord();
        $detailViewLinkParams = ['MODULE' => $moduleName, 'RECORD' => $recordId];
        $detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
        $viewer = $this->getViewer($request);
        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('MODULE_MODEL', $this->record->getModule());
        $viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
        $viewer->assign('IS_EDITABLE', $this->record->getRecord()->isEditable($moduleName));
        $viewer->assign('IS_DELETABLE', $this->record->getRecord()->isDeletable($moduleName));
        $linkParams = ['MODULE' => $moduleName, 'ACTION' => $request->get('view')];
        $linkModels = $this->record->getSideBarLinks($linkParams);
        $viewer->assign('QUICK_LINKS', $linkModels);
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $viewer->assign('DEFAULT_RECORD_VIEW', $currentUserModel->get('default_record_view'));
        $viewer->assign('NO_PAGINATION', true);
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function preProcessTplName(Vtiger_Request $request)
    {
        return 'DetailViewPreProcess.tpl';
    }

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $viewer = $this->getViewer($request);
        $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($record);
        $recordModel->setModule($moduleName);
        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_NAME', $moduleName);
        if ($request->isAjax()) {
            $viewer->assign('MODULE_MODEL', $recordModel->getModule());
        }
        $viewer->view('DetailViewFullContents.tpl', $moduleName);
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $jsFileNames = ['modules.Vtiger.resources.Detail', 'modules.VTEEmailDesigner.resources.Detail', 'modules.Settings.Vtiger.resources.Index', '~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    /**
     * Setting module related Information to $viewer (for Vtiger7).
     * @param type $request
     * @param type $moduleModel
     */
    public function setModuleInfo($request, $moduleModel)
    {
        $fieldsInfo = [];
        $basicLinks = [];
        $settingLinks = [];
        $moduleFields = $moduleModel->getFields();
        foreach ($moduleFields as $fieldName => $fieldModel) {
            $fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
        }
        $moduleBasicLinks = $moduleModel->getModuleBasicLinks();
        if ($moduleBasicLinks) {
            foreach ($moduleBasicLinks as $basicLink) {
                $basicLinks[] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
            }
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
        $viewer->assign('MODULE_BASIC_ACTIONS', $basicLinks);
        $viewer->assign('MODULE_SETTING_ACTIONS', $settingLinks);
    }
}
