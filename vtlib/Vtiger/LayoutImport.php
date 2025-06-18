<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/LayoutExport.php';

/**
 * Provides API to import layout into vtiger CRM.
 */
class Vtiger_LayoutImport extends Vtiger_LayoutExport
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_export_tmpdir;
    }

    /**
     * Initialize Import.
     */
    public function initImport($zipfile, $overwrite = true)
    {
        $this->__initSchema();
        $name = $this->getModuleNameFromZip($zipfile);

        return $name;
    }

    /**
     * Import Module from zip file.
     * @param string Zip file name
     * @param bool True for overwriting existing module
     */
    public function import($zipfile, $overwrite = false)
    {
        $this->initImport($zipfile, $overwrite);

        // Call module import function
        $this->import_Layout($zipfile);
    }

    /**
     * Update Layout from zip file.
     * @param object Instance of Layout
     * @param string Zip file name
     * @param bool True for overwriting existing module
     */
    public function update($instance, $zipfile, $overwrite = true)
    {
        $this->import($zipfile, $overwrite);
    }

    /**
     * Import Layout.
     */
    public function import_Layout($zipfile)
    {
        $name = $this->_modulexml->name;
        $label = $this->_modulexml->label;

        self::log("Importing {$name} ... STARTED");
        $unzip = new Vtiger_Unzip($zipfile);
        $filelist = $unzip->getList();
        $vtiger6format = false;

        foreach ($filelist as $filename => $fileinfo) {
            if (!$unzip->isdir($filename)) {

                if (strpos($filename, '/') === false) {
                    continue;
                }


                $targetdir  = substr($filename, 0, strripos($filename, '/'));
                $targetfile = basename($filename);
                $dounzip = false;
                // Case handling for jscalendar
                if (stripos($targetdir, "layouts/{$name}/skins") === 0) {
                    $dounzip = true;
                    $vtiger6format = true;
                }
                // vtiger6 format
                elseif (stripos($targetdir, "layouts/{$name}/modules") === 0) {
                    $vtiger6format = true;
                    $dounzip = true;
                }
                // case handling for the  special library files
                elseif (stripos($targetdir, "layouts/{$name}/libraries") === 0) {
                    $vtiger6format = true;
                    $dounzip = true;
                }
                if ($dounzip) {
                    // vtiger6 format
                    if ($vtiger6format) {
                        $targetdir = "layouts/{$name}/" . str_replace("layouts/{$name}", '', $targetdir);
                        @mkdir($targetdir, 0o755, true);
                    }

                    global $upload_badext;
                    $badFileExtensions = array_diff($upload_badext, ['js']);
                    $filepath = 'zip://' . $zipfile . '#' . $filename;
                    $fileValidation = Vtiger_Functions::verifyClaimedMIME($filepath, $badFileExtensions);

                    $imageContents = file_get_contents('zip://' . $zipfile . '#' . $filename);
                    // Check for php code injection
                    if (preg_match('/(<\?php?(.*?))/i', $imageContents) == 1) {
                        $fileValidation = false;
                    }

                    if ($fileValidation) {
                        if ($unzip->unzip($filename, "{$targetdir}/{$targetfile}") !== false) {
                            self::log("Copying file {$filename} ... DONE");
                        } else {
                            self::log("Copying file {$filename} ... FAILED");
                        }
                    }
                } else {
                    self::log("Copying file {$filename} ... SKIPPED");
                }
            }
        }
        if ($unzip) {
            $unzip->close();
        }

        self::register($name, $label);

        self::log("Importing {$name}({$label}) ... DONE");


    }
}
