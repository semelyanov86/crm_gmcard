<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once dirname(__FILE__) . '/Viewer.php';

class Vtiger_PDF_PagerViewer extends Vtiger_PDF_Viewer
{
    protected $model;

    public function setModel($m)
    {
        $this->model = $m;
    }

    public function totalHeight($parent)
    {
        return 10;
    }

    public function initDisplay($parent) {}

    public function display($parent)
    {
        $pdf = $parent->getPDF();
        $contentFrame = $parent->getContentFrame();

        $displayFormat = '-%s-';
        if ($this->model) {
            $displayFormat = $this->model->get('format', $displayFormat);
        }
        $contentHeight = $pdf->GetStringHeight($displayFormat, $contentFrame->w / 2.0);
        $pdf->MultiCell(
            $contentFrame->w / 2.0,
            $contentHeight,
            sprintf($displayFormat, $pdf->getPage()),
            0,
            'L',
            0,
            1,
            $contentFrame->x + $contentFrame->w / 2.0,
            $parent->getTotalHeight(),
        );
    }
}
