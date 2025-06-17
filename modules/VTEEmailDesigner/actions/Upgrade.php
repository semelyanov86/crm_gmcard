<?php

class VTECustomHeader_Upgrade_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("upgradeModule");
        $this->exposeMethod("releaseLicense");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function releaseLicense(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $response = new Vtiger_Response();
        try {
            $vTELicense = new VTECustomHeader_VTELicense_Model($module);
            $licenseInfo = $vTELicense->getLicenseInfo();
            $vTELicense->releaseLicense($licenseInfo);
            $result = array("success" => true);
            $response->setResult($result);
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    public function upgradeModule(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        $module = $request->getModule();
        try {
            $uploadDir = Settings_ModuleManager_Extension_Model::getUploadDirectory();
            $phpVersion = phpversion();
            $phpVersion = substr($phpVersion, 0, 3);
            $phpVersion = str_replace(".", "", $phpVersion);
            $uploadFileName = $uploadDir . "/" . $module . "-PHP" . $phpVersion . ".zip";
            $fileUrl = "http://license.vtexperts.com/modules_zip/" . $module . "/" . $module . "_PHP" . $phpVersion . ".zip";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_URL, $fileUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $file_content = curl_exec($ch);
            curl_close($ch);
            $downloaded_file = fopen($uploadFileName, "w");
            fwrite($downloaded_file, $file_content);
            fclose($downloaded_file);
            checkFileAccess($uploadFileName);
            $package = new Vtiger_Package();
            $package->update(Vtiger_Module::getInstance($module), $uploadFileName);
            checkFileAccessForDeletion($uploadFileName);
            unlink($uploadFileName);
            $result = array("success" => true, "importModuleName" => $module);
            $response->setResult($result);
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
}

?>