<?php

class VTEPayments_Detail_View extends Vtiger_Detail_View
{
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        if ($this->isVtiger7()) {
            $cssFileNames = ['~layouts/v7/modules/VTEPayments/resources/VTEPayments.css'];
        }
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }

    /**
     * Check Vtiger version.
     */
    public function isVtiger7()
    {
        $current_version = $_SESSION['vtiger_version'];
        if (!empty($current_version)) {
            return version_compare($current_version, '7.0.0', '>=');
        }
        require_once 'vtlib/Vtiger/Version.php';

        return Vtiger_Version::check('7.0.0', '>=');
    }
}
