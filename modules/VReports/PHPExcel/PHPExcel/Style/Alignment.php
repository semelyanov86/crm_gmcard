<?php

/**
 * PHPExcel_Style_Alignment.
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
class PHPExcel_Style_Alignment extends PHPExcel_Style_Supervisor implements PHPExcel_IComparable
{
    /* Horizontal alignment styles */
    public const HORIZONTAL_GENERAL           = 'general';
    public const HORIZONTAL_LEFT              = 'left';
    public const HORIZONTAL_RIGHT             = 'right';
    public const HORIZONTAL_CENTER            = 'center';
    public const HORIZONTAL_CENTER_CONTINUOUS = 'centerContinuous';
    public const HORIZONTAL_JUSTIFY           = 'justify';
    public const HORIZONTAL_FILL              = 'fill';
    public const HORIZONTAL_DISTRIBUTED       = 'distributed';        // Excel2007 only

    /* Vertical alignment styles */
    public const VERTICAL_BOTTOM      = 'bottom';
    public const VERTICAL_TOP         = 'top';
    public const VERTICAL_CENTER      = 'center';
    public const VERTICAL_JUSTIFY     = 'justify';
    public const VERTICAL_DISTRIBUTED = 'distributed';        // Excel2007 only

    /* Read order */
    public const READORDER_CONTEXT = 0;
    public const READORDER_LTR     = 1;
    public const READORDER_RTL     = 2;

    /**
     * Horizontal alignment.
     *
     * @var string
     */
    protected $horizontal = PHPExcel_Style_Alignment::HORIZONTAL_GENERAL;

    /**
     * Vertical alignment.
     *
     * @var string
     */
    protected $vertical = PHPExcel_Style_Alignment::VERTICAL_BOTTOM;

    /**
     * Text rotation.
     *
     * @var int
     */
    protected $textRotation = 0;

    /**
     * Wrap text.
     *
     * @var bool
     */
    protected $wrapText = false;

    /**
     * Shrink to fit.
     *
     * @var bool
     */
    protected $shrinkToFit = false;

    /**
     * Indent - only possible with horizontal alignment left and right.
     *
     * @var int
     */
    protected $indent = 0;

    /**
     * Read order.
     *
     * @var int
     */
    protected $readorder = 0;

    /**
     * Create a new PHPExcel_Style_Alignment.
     *
     * @param    bool    $isSupervisor    Flag indicating if this is a supervisor or not
     *                                       Leave this value at default unless you understand exactly what
     *                                          its ramifications are
     * @param    bool    $isConditional   Flag indicating if this is a conditional style or not
     *                                       Leave this value at default unless you understand exactly what
     *                                          its ramifications are
     */
    public function __construct($isSupervisor = false, $isConditional = false)
    {
        // Supervisor?
        parent::__construct($isSupervisor);

        if ($isConditional) {
            $this->horizontal   = null;
            $this->vertical     = null;
            $this->textRotation = null;
        }
    }

    /**
     * Get the shared style component for the currently active cell in currently active sheet.
     * Only used for style supervisor.
     *
     * @return PHPExcel_Style_Alignment
     */
    public function getSharedComponent()
    {
        return $this->parent->getSharedComponent()->getAlignment();
    }

    /**
     * Build style array from subcomponents.
     *
     * @param array $array
     * @return array
     */
    public function getStyleArray($array)
    {
        return ['alignment' => $array];
    }

    /**
     * Apply styles from array.
     *
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->getAlignment()->applyFromArray(
     *        array(
     *            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
     *            'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     *            'rotation'   => 0,
     *            'wrap'            => TRUE
     *        )
     * );
     * </code>
     *
     * @param    array    $pStyles    Array containing style information
     * @return PHPExcel_Style_Alignment
     * @throws    PHPExcel_Exception
     */
    public function applyFromArray($pStyles = null)
    {
        if (is_array($pStyles)) {
            if ($this->isSupervisor) {
                $this->getActiveSheet()->getStyle($this->getSelectedCells())
                    ->applyFromArray($this->getStyleArray($pStyles));
            } else {
                if (isset($pStyles['horizontal'])) {
                    $this->setHorizontal($pStyles['horizontal']);
                }
                if (isset($pStyles['vertical'])) {
                    $this->setVertical($pStyles['vertical']);
                }
                if (isset($pStyles['rotation'])) {
                    $this->setTextRotation($pStyles['rotation']);
                }
                if (isset($pStyles['wrap'])) {
                    $this->setWrapText($pStyles['wrap']);
                }
                if (isset($pStyles['shrinkToFit'])) {
                    $this->setShrinkToFit($pStyles['shrinkToFit']);
                }
                if (isset($pStyles['indent'])) {
                    $this->setIndent($pStyles['indent']);
                }
                if (isset($pStyles['readorder'])) {
                    $this->setReadorder($pStyles['readorder']);
                }
            }
        } else {
            throw new PHPExcel_Exception('Invalid style array passed.');
        }

        return $this;
    }

    /**
     * Get Horizontal.
     *
     * @return string
     */
    public function getHorizontal()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHorizontal();
        }

        return $this->horizontal;
    }

    /**
     * Set Horizontal.
     *
     * @param string $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setHorizontal($pValue = PHPExcel_Style_Alignment::HORIZONTAL_GENERAL)
    {
        if ($pValue == '') {
            $pValue = PHPExcel_Style_Alignment::HORIZONTAL_GENERAL;
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['horizontal' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->horizontal = $pValue;
        }

        return $this;
    }

    /**
     * Get Vertical.
     *
     * @return string
     */
    public function getVertical()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getVertical();
        }

        return $this->vertical;
    }

    /**
     * Set Vertical.
     *
     * @param string $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setVertical($pValue = PHPExcel_Style_Alignment::VERTICAL_BOTTOM)
    {
        if ($pValue == '') {
            $pValue = PHPExcel_Style_Alignment::VERTICAL_BOTTOM;
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['vertical' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->vertical = $pValue;
        }

        return $this;
    }

    /**
     * Get TextRotation.
     *
     * @return int
     */
    public function getTextRotation()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getTextRotation();
        }

        return $this->textRotation;
    }

    /**
     * Set TextRotation.
     *
     * @param int $pValue
     * @return PHPExcel_Style_Alignment
     * @throws PHPExcel_Exception
     */
    public function setTextRotation($pValue = 0)
    {
        // Excel2007 value 255 => PHPExcel value -165
        if ($pValue == 255) {
            $pValue = -165;
        }

        // Set rotation
        if (($pValue >= -90 && $pValue <= 90) || $pValue == -165) {
            if ($this->isSupervisor) {
                $styleArray = $this->getStyleArray(['rotation' => $pValue]);
                $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            } else {
                $this->textRotation = $pValue;
            }
        } else {
            throw new PHPExcel_Exception('Text rotation should be a value between -90 and 90.');
        }

        return $this;
    }

    /**
     * Get Wrap Text.
     *
     * @return bool
     */
    public function getWrapText()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getWrapText();
        }

        return $this->wrapText;
    }

    /**
     * Set Wrap Text.
     *
     * @param bool $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setWrapText($pValue = false)
    {
        if ($pValue == '') {
            $pValue = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['wrap' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->wrapText = $pValue;
        }

        return $this;
    }

    /**
     * Get Shrink to fit.
     *
     * @return bool
     */
    public function getShrinkToFit()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getShrinkToFit();
        }

        return $this->shrinkToFit;
    }

    /**
     * Set Shrink to fit.
     *
     * @param bool $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setShrinkToFit($pValue = false)
    {
        if ($pValue == '') {
            $pValue = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['shrinkToFit' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->shrinkToFit = $pValue;
        }

        return $this;
    }

    /**
     * Get indent.
     *
     * @return int
     */
    public function getIndent()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getIndent();
        }

        return $this->indent;
    }

    /**
     * Set indent.
     *
     * @param int $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setIndent($pValue = 0)
    {
        if ($pValue > 0) {
            if ($this->getHorizontal() != self::HORIZONTAL_GENERAL
                && $this->getHorizontal() != self::HORIZONTAL_LEFT
                && $this->getHorizontal() != self::HORIZONTAL_RIGHT) {
                $pValue = 0; // indent not supported
            }
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['indent' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->indent = $pValue;
        }

        return $this;
    }

    /**
     * Get read order.
     *
     * @return int
     */
    public function getReadorder()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getReadorder();
        }

        return $this->readorder;
    }

    /**
     * Set read order.
     *
     * @param int $pValue
     * @return PHPExcel_Style_Alignment
     */
    public function setReadorder($pValue = 0)
    {
        if ($pValue < 0 || $pValue > 2) {
            $pValue = 0;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['readorder' => $pValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->readorder = $pValue;
        }

        return $this;
    }

    /**
     * Get hash code.
     *
     * @return string    Hash code
     */
    public function getHashCode()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->horizontal
            . $this->vertical
            . $this->textRotation
            . ($this->wrapText ? 't' : 'f')
            . ($this->shrinkToFit ? 't' : 'f')
            . $this->indent
            . $this->readorder
            . __CLASS__,
        );
    }
}
