<?php

/**
 * PHPExcel_Worksheet_Drawing_Shadow.
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
class PHPExcel_Worksheet_Drawing_Shadow implements PHPExcel_IComparable
{
    /* Shadow alignment */
    public const SHADOW_BOTTOM       = 'b';
    public const SHADOW_BOTTOM_LEFT  = 'bl';
    public const SHADOW_BOTTOM_RIGHT = 'br';
    public const SHADOW_CENTER       = 'ctr';
    public const SHADOW_LEFT         = 'l';
    public const SHADOW_TOP          = 't';
    public const SHADOW_TOP_LEFT     = 'tl';
    public const SHADOW_TOP_RIGHT    = 'tr';

    /**
     * Visible.
     *
     * @var bool
     */
    private $visible;

    /**
     * Blur radius.
     *
     * Defaults to 6
     *
     * @var int
     */
    private $blurRadius;

    /**
     * Shadow distance.
     *
     * Defaults to 2
     *
     * @var int
     */
    private $distance;

    /**
     * Shadow direction (in degrees).
     *
     * @var int
     */
    private $direction;

    /**
     * Shadow alignment.
     *
     * @var int
     */
    private $alignment;

    /**
     * Color.
     *
     * @var PHPExcel_Style_Color
     */
    private $color;

    /**
     * Alpha.
     *
     * @var int
     */
    private $alpha;

    /**
     * Create a new PHPExcel_Worksheet_Drawing_Shadow.
     */
    public function __construct()
    {
        // Initialise values
        $this->visible     = false;
        $this->blurRadius  = 6;
        $this->distance    = 2;
        $this->direction   = 0;
        $this->alignment   = PHPExcel_Worksheet_Drawing_Shadow::SHADOW_BOTTOM_RIGHT;
        $this->color       = new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLACK);
        $this->alpha       = 50;
    }

    /**
     * Get Visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set Visible.
     *
     * @param bool $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setVisible($pValue = false)
    {
        $this->visible = $pValue;

        return $this;
    }

    /**
     * Get Blur radius.
     *
     * @return int
     */
    public function getBlurRadius()
    {
        return $this->blurRadius;
    }

    /**
     * Set Blur radius.
     *
     * @param int $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setBlurRadius($pValue = 6)
    {
        $this->blurRadius = $pValue;

        return $this;
    }

    /**
     * Get Shadow distance.
     *
     * @return int
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Set Shadow distance.
     *
     * @param int $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setDistance($pValue = 2)
    {
        $this->distance = $pValue;

        return $this;
    }

    /**
     * Get Shadow direction (in degrees).
     *
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set Shadow direction (in degrees).
     *
     * @param int $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setDirection($pValue = 0)
    {
        $this->direction = $pValue;

        return $this;
    }

    /**
     * Get Shadow alignment.
     *
     * @return int
     */
    public function getAlignment()
    {
        return $this->alignment;
    }

    /**
     * Set Shadow alignment.
     *
     * @param int $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setAlignment($pValue = 0)
    {
        $this->alignment = $pValue;

        return $this;
    }

    /**
     * Get Color.
     *
     * @return PHPExcel_Style_Color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set Color.
     *
     * @return PHPExcel_Worksheet_Drawing_Shadow
     * @throws     PHPExcel_Exception
     */
    public function setColor(?PHPExcel_Style_Color $pValue = null)
    {
        $this->color = $pValue;

        return $this;
    }

    /**
     * Get Alpha.
     *
     * @return int
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * Set Alpha.
     *
     * @param int $pValue
     * @return PHPExcel_Worksheet_Drawing_Shadow
     */
    public function setAlpha($pValue = 0)
    {
        $this->alpha = $pValue;

        return $this;
    }

    /**
     * Get hash code.
     *
     * @return string    Hash code
     */
    public function getHashCode()
    {
        return md5(
            ($this->visible ? 't' : 'f')
            . $this->blurRadius
            . $this->distance
            . $this->direction
            . $this->alignment
            . $this->color->getHashCode()
            . $this->alpha
            . __CLASS__,
        );
    }

    /**
     * Implement PHP __clone to create a deep clone, not just a shallow copy.
     */
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $this->{$key} = clone $value;
            } else {
                $this->{$key} = $value;
            }
        }
    }
}
