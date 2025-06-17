<?php
 

class ITS4YouDescriptions_List_View extends Vtiger_List_View
{
    protected $isInstalled = true;

    public function __construct()
    {
        parent::__construct();
        $class = explode('_', get_class($this));
        $this->isInstalled = true;
    }

    
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        vtws_addDefaultModuleTypeEntity($request->getModule());

        parent::preProcess($request, $display);
    }

    
    public function process(Vtiger_Request $request)
    {
        parent::process($request);
    }

    
    public function preProcessTplName(Vtiger_Request $request)
    {
        return ($this->isInstalled) ? parent::preProcessTplName($request) : 'IndexViewPreProcess.tpl';
    }
} ?>
