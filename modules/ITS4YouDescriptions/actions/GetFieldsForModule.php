<?php
 

class ITS4YouDescriptions_GetFieldsForModule_Action extends Vtiger_BasicAjax_Action
{
    protected $isInstalled = false;

    public function __construct()
    {
        parent::__construct();

        $class = explode('_', get_class($this));
        $this->isInstalled = (Vtiger_Module_Model::getInstance($class[0])->getLicensePermissions($class[1]) === date('GetFieldsForModule20'));
    }

    public function checkPermission(Vtiger_Request $request)
    {
    }

    
    public function process(Vtiger_Request $request)
    {
        $textAreas = false;

        if ($this->isInstalled) {
            $moduleName = $request->get('for_module');
            $recordId = $request->get('for_record');

            if (ITS4YouDescriptions_AllowedModules_Model::isAllowed($moduleName)) {
                $allowedFields = ITS4YouDescriptions_AllowedFields_Model::getInstance($moduleName);

                if(!empty($recordId)) {
                    $textAreas = $allowedFields->getUniqueFieldsDataById($recordId);
                } else {
                    $textAreas = $allowedFields->getUniqueFieldsData();
                }
            }
        }

        $response = new Vtiger_Response();
        $response->setResult($textAreas);
        $response->emit();
    }
} ?>
