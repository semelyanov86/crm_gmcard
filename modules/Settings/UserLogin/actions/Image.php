<?php

class Settings_UserLogin_Image_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        return true;
    }
    public function process(Vtiger_Request $request)
    {
        global $root_directory;
        $settingModel = new Settings_UserLogin_Settings_Model();
        $imagePath = $settingModel->uploadImage($request);
        $result = "";
        if (file_exists($root_directory . "/" . $imagePath)) {
            $mode = $request->get("mode");
            if ($mode == "images") {
                $mode = "images[]";
            }
            $result .= "<span class=\"img_uploaded\">";
            $result .= "<img src=\"" . $imagePath . "\" />";
            $result .= "<input type=\"button\" class=\"remove\" value=\"&nbsp;X&nbsp;\" title=\"Remove\">";
            $result .= "<input type=\"hidden\" name=\"" . $mode . "\" value=\"" . $imagePath . "\">";
            $result .= "</span>";
        }
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
}

?>