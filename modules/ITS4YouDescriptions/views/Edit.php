<?php


class ITS4YouDescriptions_Edit_View extends Vtiger_Edit_View
{
    protected $isInstalled = true;

    public function __construct()
    {
        parent::__construct();
        $class = explode('_', get_class($this));
        $this->isInstalled = true;
    }

    public function process(Vtiger_Request $request)
    {
        parent::process($request);
    }
}
