<?php

/**
 * PHPExcel.
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
 * @category    PHPExcel
 * @copyright    Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license        http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    ##VERSION##, ##DATE##
 */


/**
 * PHPExcel_Chart_DataSeries.
 *
 * @category    PHPExcel
 * @copyright    Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Chart_DataSeries
{
    public const TYPE_BARCHART        = 'barChart';
    public const TYPE_BARCHART_3D     = 'bar3DChart';
    public const TYPE_LINECHART       = 'lineChart';
    public const TYPE_LINECHART_3D    = 'line3DChart';
    public const TYPE_AREACHART       = 'areaChart';
    public const TYPE_AREACHART_3D    = 'area3DChart';
    public const TYPE_PIECHART        = 'pieChart';
    public const TYPE_PIECHART_3D     = 'pie3DChart';
    public const TYPE_DOUGHTNUTCHART  = 'doughnutChart';
    public const TYPE_DONUTCHART      = self::TYPE_DOUGHTNUTCHART;    //    Synonym
    public const TYPE_SCATTERCHART    = 'scatterChart';
    public const TYPE_SURFACECHART    = 'surfaceChart';
    public const TYPE_SURFACECHART_3D = 'surface3DChart';
    public const TYPE_RADARCHART      = 'radarChart';
    public const TYPE_BUBBLECHART     = 'bubbleChart';
    public const TYPE_STOCKCHART      = 'stockChart';
    public const TYPE_CANDLECHART     = self::TYPE_STOCKCHART;       //    Synonym
    public const GROUPING_CLUSTERED       = 'clustered';
    public const GROUPING_STACKED         = 'stacked';
    public const GROUPING_PERCENT_STACKED = 'percentStacked';
    public const GROUPING_STANDARD        = 'standard';
    public const DIRECTION_BAR        = 'bar';
    public const DIRECTION_HORIZONTAL = self::DIRECTION_BAR;
    public const DIRECTION_COL        = 'col';
    public const DIRECTION_COLUMN     = self::DIRECTION_COL;
    public const DIRECTION_VERTICAL   = self::DIRECTION_COL;
    public const STYLE_LINEMARKER   = 'lineMarker';
    public const STYLE_SMOOTHMARKER = 'smoothMarker';
    public const STYLE_MARKER       = 'marker';
    public const STYLE_FILLED       = 'filled';

    /**
     * Series Plot Type.
     *
     * @var string
     */
    private $plotType;

    /**
     * Plot Grouping Type.
     *
     * @var bool
     */
    private $plotGrouping;

    /**
     * Plot Direction.
     *
     * @var bool
     */
    private $plotDirection;

    /**
     * Plot Style.
     *
     * @var string
     */
    private $plotStyle;

    /**
     * Order of plots in Series.
     *
     * @var array of integer
     */
    private $plotOrder = [];

    /**
     * Plot Label.
     *
     * @var array of PHPExcel_Chart_DataSeriesValues
     */
    private $plotLabel = [];

    /**
     * Plot Category.
     *
     * @var array of PHPExcel_Chart_DataSeriesValues
     */
    private $plotCategory = [];

    /**
     * Smooth Line.
     *
     * @var string
     */
    private $smoothLine;

    /**
     * Plot Values.
     *
     * @var array of PHPExcel_Chart_DataSeriesValues
     */
    private $plotValues = [];

    /**
     * Create a new PHPExcel_Chart_DataSeries.
     */
    public function __construct($plotType = null, $plotGrouping = null, $plotOrder = [], $plotLabel = [], $plotCategory = [], $plotValues = [], $plotDirection = null, $smoothLine = null, $plotStyle = null)
    {
        $this->plotType = $plotType;
        $this->plotGrouping = $plotGrouping;
        $this->plotOrder = $plotOrder;
        $keys = array_keys($plotValues);
        $this->plotValues = $plotValues;
        if ((count($plotLabel) == 0) || is_null($plotLabel[$keys[0]])) {
            $plotLabel[$keys[0]] = new PHPExcel_Chart_DataSeriesValues();
        }

        $this->plotLabel = $plotLabel;
        if ((count($plotCategory) == 0) || is_null($plotCategory[$keys[0]])) {
            $plotCategory[$keys[0]] = new PHPExcel_Chart_DataSeriesValues();
        }
        $this->plotCategory = $plotCategory;
        $this->smoothLine = $smoothLine;
        $this->plotStyle = $plotStyle;

        if (is_null($plotDirection)) {
            $plotDirection = self::DIRECTION_COL;
        }
        $this->plotDirection = $plotDirection;
    }

    /**
     * Get Plot Type.
     *
     * @return string
     */
    public function getPlotType()
    {
        return $this->plotType;
    }

    /**
     * Set Plot Type.
     *
     * @param string $plotType
     * @return PHPExcel_Chart_DataSeries
     */
    public function setPlotType($plotType = '')
    {
        $this->plotType = $plotType;

        return $this;
    }

    /**
     * Get Plot Grouping Type.
     *
     * @return string
     */
    public function getPlotGrouping()
    {
        return $this->plotGrouping;
    }

    /**
     * Set Plot Grouping Type.
     *
     * @param string $groupingType
     * @return PHPExcel_Chart_DataSeries
     */
    public function setPlotGrouping($groupingType = null)
    {
        $this->plotGrouping = $groupingType;

        return $this;
    }

    /**
     * Get Plot Direction.
     *
     * @return string
     */
    public function getPlotDirection()
    {
        return $this->plotDirection;
    }

    /**
     * Set Plot Direction.
     *
     * @param string $plotDirection
     * @return PHPExcel_Chart_DataSeries
     */
    public function setPlotDirection($plotDirection = null)
    {
        $this->plotDirection = $plotDirection;

        return $this;
    }

    /**
     * Get Plot Order.
     *
     * @return string
     */
    public function getPlotOrder()
    {
        return $this->plotOrder;
    }

    /**
     * Get Plot Labels.
     *
     * @return array of PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotLabels()
    {
        return $this->plotLabel;
    }

    /**
     * Get Plot Label by Index.
     *
     * @return PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotLabelByIndex($index)
    {
        $keys = array_keys($this->plotLabel);
        if (in_array($index, $keys)) {
            return $this->plotLabel[$index];
        }
        if (isset($keys[$index])) {
            return $this->plotLabel[$keys[$index]];
        }

        return false;
    }

    /**
     * Get Plot Categories.
     *
     * @return array of PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotCategories()
    {
        return $this->plotCategory;
    }

    /**
     * Get Plot Category by Index.
     *
     * @return PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotCategoryByIndex($index)
    {
        $keys = array_keys($this->plotCategory);
        if (in_array($index, $keys)) {
            return $this->plotCategory[$index];
        }
        if (isset($keys[$index])) {
            return $this->plotCategory[$keys[$index]];
        }

        return false;
    }

    /**
     * Get Plot Style.
     *
     * @return string
     */
    public function getPlotStyle()
    {
        return $this->plotStyle;
    }

    /**
     * Set Plot Style.
     *
     * @param string $plotStyle
     * @return PHPExcel_Chart_DataSeries
     */
    public function setPlotStyle($plotStyle = null)
    {
        $this->plotStyle = $plotStyle;

        return $this;
    }

    /**
     * Get Plot Values.
     *
     * @return array of PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotValues()
    {
        return $this->plotValues;
    }

    /**
     * Get Plot Values by Index.
     *
     * @return PHPExcel_Chart_DataSeriesValues
     */
    public function getPlotValuesByIndex($index)
    {
        $keys = array_keys($this->plotValues);
        if (in_array($index, $keys)) {
            return $this->plotValues[$index];
        }
        if (isset($keys[$index])) {
            return $this->plotValues[$keys[$index]];
        }

        return false;
    }

    /**
     * Get Number of Plot Series.
     *
     * @return int
     */
    public function getPlotSeriesCount()
    {
        return count($this->plotValues);
    }

    /**
     * Get Smooth Line.
     *
     * @return bool
     */
    public function getSmoothLine()
    {
        return $this->smoothLine;
    }

    /**
     * Set Smooth Line.
     *
     * @param bool $smoothLine
     * @return PHPExcel_Chart_DataSeries
     */
    public function setSmoothLine($smoothLine = true)
    {
        $this->smoothLine = $smoothLine;

        return $this;
    }

    public function refresh(PHPExcel_Worksheet $worksheet)
    {
        foreach ($this->plotValues as $plotValues) {
            if ($plotValues !== null) {
                $plotValues->refresh($worksheet, true);
            }
        }
        foreach ($this->plotLabel as $plotValues) {
            if ($plotValues !== null) {
                $plotValues->refresh($worksheet, true);
            }
        }
        foreach ($this->plotCategory as $plotValues) {
            if ($plotValues !== null) {
                $plotValues->refresh($worksheet, false);
            }
        }
    }
}
