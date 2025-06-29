<?php

/**
 * PHPExcel_Writer_Abstract.
 *
 * Copyright (c) 2006 - 2015 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    ##VERSION##, ##DATE##
 */
abstract class PHPExcel_Writer_Abstract implements PHPExcel_Writer_IWriter
{
    /**
     * Write charts that are defined in the workbook?
     * Identifies whether the Writer should write definitions for any charts that exist in the PHPExcel object;.
     *
     * @var    bool
     */
    protected $includeCharts = false;

    /**
     * Pre-calculate formulas
     * Forces PHPExcel to recalculate all formulae in a workbook when saving, so that the pre-calculated values are
     *    immediately available to MS Excel or other office spreadsheet viewer when opening the file.
     *
     * @var bool
     */
    protected $preCalculateFormulas = true;

    /**
     * Use disk caching where possible?
     *
     * @var bool
     */
    protected $_useDiskCaching = false;

    /**
     * Disk caching directory.
     *
     * @var string
     */
    protected $_diskCachingDirectory    = './';

    /**
     * Write charts in workbook?
     *        If this is true, then the Writer will write definitions for any charts that exist in the PHPExcel object.
     *        If false (the default) it will ignore any charts defined in the PHPExcel object.
     *
     * @return    bool
     */
    public function getIncludeCharts()
    {
        return $this->includeCharts;
    }

    /**
     * Set write charts in workbook
     *        Set to true, to advise the Writer to include any charts that exist in the PHPExcel object.
     *        Set to false (the default) to ignore charts.
     *
     * @param    bool    $pValue
     * @return    PHPExcel_Writer_IWriter
     */
    public function setIncludeCharts($pValue = false)
    {
        $this->includeCharts = (bool) $pValue;

        return $this;
    }

    /**
     * Get Pre-Calculate Formulas flag
     *     If this is true (the default), then the writer will recalculate all formulae in a workbook when saving,
     *        so that the pre-calculated values are immediately available to MS Excel or other office spreadsheet
     *        viewer when opening the file
     *     If false, then formulae are not calculated on save. This is faster for saving in PHPExcel, but slower
     *        when opening the resulting file in MS Excel, because Excel has to recalculate the formulae itself.
     *
     * @return bool
     */
    public function getPreCalculateFormulas()
    {
        return $this->preCalculateFormulas;
    }

    /**
     * Set Pre-Calculate Formulas
     *        Set to true (the default) to advise the Writer to calculate all formulae on save
     *        Set to false to prevent precalculation of formulae on save.
     *
     * @param bool $pValue    Pre-Calculate Formulas?
     * @return    PHPExcel_Writer_IWriter
     */
    public function setPreCalculateFormulas($pValue = true)
    {
        $this->preCalculateFormulas = (bool) $pValue;

        return $this;
    }

    /**
     * Get use disk caching where possible?
     *
     * @return bool
     */
    public function getUseDiskCaching()
    {
        return $this->_useDiskCaching;
    }

    /**
     * Set use disk caching where possible?
     *
     * @param     bool     $pValue
     * @param    string        $pDirectory        Disk caching directory
     * @return PHPExcel_Writer_Excel2007
     * @throws    PHPExcel_Writer_Exception    when directory does not exist
     */
    public function setUseDiskCaching($pValue = false, $pDirectory = null)
    {
        $this->_useDiskCaching = $pValue;

        if ($pDirectory !== null) {
            if (is_dir($pDirectory)) {
                $this->_diskCachingDirectory = $pDirectory;
            } else {
                throw new PHPExcel_Writer_Exception("Directory does not exist: {$pDirectory}");
            }
        }

        return $this;
    }

    /**
     * Get disk caching directory.
     *
     * @return string
     */
    public function getDiskCachingDirectory()
    {
        return $this->_diskCachingDirectory;
    }
}
