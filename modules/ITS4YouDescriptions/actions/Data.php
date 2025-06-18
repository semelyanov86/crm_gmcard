<?php

class ITS4YouDescriptions_Data_Action extends Vtiger_BasicAjax_Action
{
    protected $isInstalled = true;

    public function __construct()
    {
        $this->exposeMethod('getFields');
        $this->exposeMethod('getContent');
        $this->exposeMethod('getInventory');

        $class = explode('_', get_class($this));
        $this->isInstalled = true;
    }

    public function checkPermission(Vtiger_Request $request) {}

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        $data = false;

        if (!empty($mode) && $this->isInstalled) {
            $data = $this->invokeExposedMethod($mode, $request);
        }

        $response = new Vtiger_Response();
        $response->setResult($data);
        $response->emit();
    }

    public function getContent(Vtiger_Request $request)
    {
        $return = ITS4YouDescriptions_Record_Model::getTemplateDescription($request->get('descriptionid'));

        return [
            'result' => $return,
            'modulename' => $request->get('formodule'),
            'fieldname' => $request->get('affected_textarea'),
        ];
    }

    public function getFields(Vtiger_Request $request)
    {
        $textAreas = false;
        $moduleName = $request->get('for_module');

        if ($this->isAllowedModule($moduleName)) {
            $textAreas = ITS4YouDescriptions_AllowedFields_Model::getInstance($moduleName)->getUniqueFieldsData();
        }

        return $textAreas;
    }

    public function isAllowedModule($module)
    {
        return ITS4YouDescriptions_AllowedModules_Model::isAllowed($module);
    }

    public function getInventory(Vtiger_Request $request)
    {
        return [
            'Products' => $this->isAllowedModule('Products'),
            'Services' => $this->isAllowedModule('Services'),
        ];
    }
}
