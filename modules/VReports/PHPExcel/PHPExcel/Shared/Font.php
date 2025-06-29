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
 * @category   PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    ##VERSION##, ##DATE##
 */


/**
 * PHPExcel_Shared_Font.
 *
 * @category   PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_Font
{
    /* Methods for resolving autosize value */
    public const AUTOSIZE_METHOD_APPROX    = 'approx';
    public const AUTOSIZE_METHOD_EXACT     = 'exact';

    private static $autoSizeMethods = [
        self::AUTOSIZE_METHOD_APPROX,
        self::AUTOSIZE_METHOD_EXACT,
    ];

    /** Character set codes used by BIFF5-8 in Font records */
    public const CHARSET_ANSI_LATIN                = 0x00;
    public const CHARSET_SYSTEM_DEFAULT            = 0x01;
    public const CHARSET_SYMBOL                    = 0x02;
    public const CHARSET_APPLE_ROMAN               = 0x4D;
    public const CHARSET_ANSI_JAPANESE_SHIFTJIS    = 0x80;
    public const CHARSET_ANSI_KOREAN_HANGUL        = 0x81;
    public const CHARSET_ANSI_KOREAN_JOHAB         = 0x82;
    public const CHARSET_ANSI_CHINESE_SIMIPLIFIED  = 0x86;        //    gb2312
    public const CHARSET_ANSI_CHINESE_TRADITIONAL  = 0x88;        //    big5
    public const CHARSET_ANSI_GREEK                = 0xA1;
    public const CHARSET_ANSI_TURKISH              = 0xA2;
    public const CHARSET_ANSI_VIETNAMESE           = 0xA3;
    public const CHARSET_ANSI_HEBREW               = 0xB1;
    public const CHARSET_ANSI_ARABIC               = 0xB2;
    public const CHARSET_ANSI_BALTIC               = 0xBA;
    public const CHARSET_ANSI_CYRILLIC             = 0xCC;
    public const CHARSET_ANSI_THAI                 = 0xDD;
    public const CHARSET_ANSI_LATIN_II             = 0xEE;
    public const CHARSET_OEM_LATIN_I               = 0xFF;

    //  XXX: Constants created!
    /** Font filenames */
    public const ARIAL                             = 'arial.ttf';
    public const ARIAL_BOLD                        = 'arialbd.ttf';
    public const ARIAL_ITALIC                      = 'ariali.ttf';
    public const ARIAL_BOLD_ITALIC                 = 'arialbi.ttf';
    public const CALIBRI                           = 'CALIBRI.TTF';
    public const CALIBRI_BOLD                      = 'CALIBRIB.TTF';
    public const CALIBRI_ITALIC                    = 'CALIBRII.TTF';
    public const CALIBRI_BOLD_ITALIC               = 'CALIBRIZ.TTF';
    public const COMIC_SANS_MS                     = 'comic.ttf';
    public const COMIC_SANS_MS_BOLD                = 'comicbd.ttf';
    public const COURIER_NEW                       = 'cour.ttf';
    public const COURIER_NEW_BOLD                  = 'courbd.ttf';
    public const COURIER_NEW_ITALIC                = 'couri.ttf';
    public const COURIER_NEW_BOLD_ITALIC           = 'courbi.ttf';
    public const GEORGIA                           = 'georgia.ttf';
    public const GEORGIA_BOLD                      = 'georgiab.ttf';
    public const GEORGIA_ITALIC                    = 'georgiai.ttf';
    public const GEORGIA_BOLD_ITALIC               = 'georgiaz.ttf';
    public const IMPACT                            = 'impact.ttf';
    public const LIBERATION_SANS                   = 'LiberationSans-Regular.ttf';
    public const LIBERATION_SANS_BOLD              = 'LiberationSans-Bold.ttf';
    public const LIBERATION_SANS_ITALIC            = 'LiberationSans-Italic.ttf';
    public const LIBERATION_SANS_BOLD_ITALIC       = 'LiberationSans-BoldItalic.ttf';
    public const LUCIDA_CONSOLE                    = 'lucon.ttf';
    public const LUCIDA_SANS_UNICODE               = 'l_10646.ttf';
    public const MICROSOFT_SANS_SERIF              = 'micross.ttf';
    public const PALATINO_LINOTYPE                 = 'pala.ttf';
    public const PALATINO_LINOTYPE_BOLD            = 'palab.ttf';
    public const PALATINO_LINOTYPE_ITALIC          = 'palai.ttf';
    public const PALATINO_LINOTYPE_BOLD_ITALIC     = 'palabi.ttf';
    public const SYMBOL                            = 'symbol.ttf';
    public const TAHOMA                            = 'tahoma.ttf';
    public const TAHOMA_BOLD                       = 'tahomabd.ttf';
    public const TIMES_NEW_ROMAN                   = 'times.ttf';
    public const TIMES_NEW_ROMAN_BOLD              = 'timesbd.ttf';
    public const TIMES_NEW_ROMAN_ITALIC            = 'timesi.ttf';
    public const TIMES_NEW_ROMAN_BOLD_ITALIC       = 'timesbi.ttf';
    public const TREBUCHET_MS                      = 'trebuc.ttf';
    public const TREBUCHET_MS_BOLD                 = 'trebucbd.ttf';
    public const TREBUCHET_MS_ITALIC               = 'trebucit.ttf';
    public const TREBUCHET_MS_BOLD_ITALIC          = 'trebucbi.ttf';
    public const VERDANA                           = 'verdana.ttf';
    public const VERDANA_BOLD                      = 'verdanab.ttf';
    public const VERDANA_ITALIC                    = 'verdanai.ttf';
    public const VERDANA_BOLD_ITALIC               = 'verdanaz.ttf';

    /**
     * AutoSize method.
     *
     * @var string
     */
    private static $autoSizeMethod = self::AUTOSIZE_METHOD_APPROX;

    /**
     * Path to folder containing TrueType font .ttf files.
     *
     * @var string
     */
    private static $trueTypeFontPath;

    /**
     * How wide is a default column for a given default font and size?
     * Empirical data found by inspecting real Excel files and reading off the pixel width
     * in Microsoft Office Excel 2007.
     *
     * @var array
     */
    public static $defaultColumnWidths = [
        'Arial' => [
            1 => ['px' => 24, 'width' => 12.000_000_00],
            2 => ['px' => 24, 'width' => 12.000_000_00],
            3 => ['px' => 32, 'width' => 10.664_062_50],
            4 => ['px' => 32, 'width' => 10.664_062_50],
            5 => ['px' => 40, 'width' => 10.000_000_00],
            6 => ['px' => 48, 'width' =>  9.597_656_25],
            7 => ['px' => 48, 'width' =>  9.597_656_25],
            8 => ['px' => 56, 'width' =>  9.332_031_25],
            9 => ['px' => 64, 'width' =>  9.140_625_00],
            10 => ['px' => 64, 'width' =>  9.140_625_00],
        ],
        'Calibri' => [
            1 => ['px' => 24, 'width' => 12.000_000_00],
            2 => ['px' => 24, 'width' => 12.000_000_00],
            3 => ['px' => 32, 'width' => 10.664_062_50],
            4 => ['px' => 32, 'width' => 10.664_062_50],
            5 => ['px' => 40, 'width' => 10.000_000_00],
            6 => ['px' => 48, 'width' =>  9.597_656_25],
            7 => ['px' => 48, 'width' =>  9.597_656_25],
            8 => ['px' => 56, 'width' =>  9.332_031_25],
            9 => ['px' => 56, 'width' =>  9.332_031_25],
            10 => ['px' => 64, 'width' =>  9.140_625_00],
            11 => ['px' => 64, 'width' =>  9.140_625_00],
        ],
        'Verdana' => [
            1 => ['px' => 24, 'width' => 12.000_000_00],
            2 => ['px' => 24, 'width' => 12.000_000_00],
            3 => ['px' => 32, 'width' => 10.664_062_50],
            4 => ['px' => 32, 'width' => 10.664_062_50],
            5 => ['px' => 40, 'width' => 10.000_000_00],
            6 => ['px' => 48, 'width' =>  9.597_656_25],
            7 => ['px' => 48, 'width' =>  9.597_656_25],
            8 => ['px' => 64, 'width' =>  9.140_625_00],
            9 => ['px' => 72, 'width' =>  9.000_000_00],
            10 => ['px' => 72, 'width' =>  9.000_000_00],
        ],
    ];

    /**
     * Set autoSize method.
     *
     * @param string $pValue
     * @return     bool                    Success or failure
     */
    public static function setAutoSizeMethod($pValue = self::AUTOSIZE_METHOD_APPROX)
    {
        if (!in_array($pValue, self::$autoSizeMethods)) {
            return false;
        }
        self::$autoSizeMethod = $pValue;

        return true;
    }

    /**
     * Get autoSize method.
     *
     * @return string
     */
    public static function getAutoSizeMethod()
    {
        return self::$autoSizeMethod;
    }

    /**
     * Set the path to the folder containing .ttf files. There should be a trailing slash.
     * Typical locations on variout some platforms:
     *    <ul>
     *        <li>C:/Windows/Fonts/</li>
     *        <li>/usr/share/fonts/truetype/</li>
     *        <li>~/.fonts/</li>
     *    </ul>.
     *
     * @param string $pValue
     */
    public static function setTrueTypeFontPath($pValue = '')
    {
        self::$trueTypeFontPath = $pValue;
    }

    /**
     * Get the path to the folder containing .ttf files.
     *
     * @return string
     */
    public static function getTrueTypeFontPath()
    {
        return self::$trueTypeFontPath;
    }

    /**
     * Calculate an (approximate) OpenXML column width, based on font size and text contained.
     *
     * @param     PHPExcel_Style_Font            $font            Font object
     * @param     PHPExcel_RichText|string    $cellText        Text to calculate width
     * @param     int                        $rotation        Rotation angle
     * @param     PHPExcel_Style_Font|null    $defaultFont    Font object
     * @return     int        Column width
     */
    public static function calculateColumnWidth(PHPExcel_Style_Font $font, $cellText = '', $rotation = 0, ?PHPExcel_Style_Font $defaultFont = null)
    {
        // If it is rich text, use plain text
        if ($cellText instanceof PHPExcel_RichText) {
            $cellText = $cellText->getPlainText();
        }

        // Special case if there are one or more newline characters ("\n")
        if (strpos($cellText, "\n") !== false) {
            $lineTexts = explode("\n", $cellText);
            $lineWidths = [];
            foreach ($lineTexts as $lineText) {
                $lineWidths[] = self::calculateColumnWidth($font, $lineText, $rotation = 0, $defaultFont);
            }

            return max($lineWidths); // width of longest line in cell
        }

        // Try to get the exact text width in pixels
        $approximate = self::$autoSizeMethod == self::AUTOSIZE_METHOD_APPROX;
        if (!$approximate) {
            $columnWidthAdjust = ceil(self::getTextWidthPixelsExact('n', $font, 0) * 1.07);

            try {
                // Width of text in pixels excl. padding
                // and addition because Excel adds some padding, just use approx width of 'n' glyph
                $columnWidth = self::getTextWidthPixelsExact($cellText, $font, $rotation) + $columnWidthAdjust;
            } catch (PHPExcel_Exception $e) {
                $approximate = true;
            }
        }

        if ($approximate) {
            $columnWidthAdjust = self::getTextWidthPixelsApprox('n', $font, 0);
            // Width of text in pixels excl. padding, approximation
            // and addition because Excel adds some padding, just use approx width of 'n' glyph
            $columnWidth = self::getTextWidthPixelsApprox($cellText, $font, $rotation) + $columnWidthAdjust;
        }

        // Convert from pixel width to column width
        $columnWidth = PHPExcel_Shared_Drawing::pixelsToCellDimension($columnWidth, $defaultFont);

        // Return
        return round($columnWidth, 6);
    }

    /**
     * Get GD text width in pixels for a string of text in a certain font at a certain rotation angle.
     *
     * @param string $text
     * @param PHPExcel_Style_Font
     * @param int $rotation
     * @return int
     * @throws PHPExcel_Exception
     */
    public static function getTextWidthPixelsExact($text, PHPExcel_Style_Font $font, $rotation = 0)
    {
        if (!function_exists('imagettfbbox')) {
            throw new PHPExcel_Exception('GD library needs to be enabled');
        }

        // font size should really be supplied in pixels in GD2,
        // but since GD2 seems to assume 72dpi, pixels and points are the same
        $fontFile = self::getTrueTypeFontFileFromFont($font);
        $textBox = imagettfbbox($font->getSize(), $rotation, $fontFile, $text);

        // Get corners positions
        $lowerLeftCornerX  = $textBox[0];
        //        $lowerLeftCornerY  = $textBox[1];
        $lowerRightCornerX = $textBox[2];
        //        $lowerRightCornerY = $textBox[3];
        $upperRightCornerX = $textBox[4];
        //        $upperRightCornerY = $textBox[5];
        $upperLeftCornerX  = $textBox[6];
        //        $upperLeftCornerY  = $textBox[7];

        // Consider the rotation when calculating the width
        $textWidth = max($lowerRightCornerX - $upperLeftCornerX, $upperRightCornerX - $lowerLeftCornerX);

        return $textWidth;
    }

    /**
     * Get approximate width in pixels for a string of text in a certain font at a certain rotation angle.
     *
     * @param string $columnText
     * @param int $rotation
     * @return int Text width in pixels (no padding added)
     */
    public static function getTextWidthPixelsApprox($columnText, ?PHPExcel_Style_Font $font = null, $rotation = 0)
    {
        $fontName = $font->getName();
        $fontSize = $font->getSize();

        // Calculate column width in pixels. We assume fixed glyph width. Result varies with font name and size.
        switch ($fontName) {
            case 'Calibri':
                // value 8.26 was found via interpolation by inspecting real Excel files with Calibri 11 font.
                $columnWidth = (int) (8.26 * PHPExcel_Shared_String::CountCharacters($columnText));
                $columnWidth = $columnWidth * $fontSize / 11; // extrapolate from font size
                break;

            case 'Arial':
                // value 7 was found via interpolation by inspecting real Excel files with Arial 10 font.
                //                $columnWidth = (int) (7 * PHPExcel_Shared_String::CountCharacters($columnText));
                // value 8 was set because of experience in different exports at Arial 10 font.
                $columnWidth = (int) (8 * PHPExcel_Shared_String::CountCharacters($columnText));
                $columnWidth = $columnWidth * $fontSize / 10; // extrapolate from font size
                break;

            case 'Verdana':
                // value 8 was found via interpolation by inspecting real Excel files with Verdana 10 font.
                $columnWidth = (int) (8 * PHPExcel_Shared_String::CountCharacters($columnText));
                $columnWidth = $columnWidth * $fontSize / 10; // extrapolate from font size
                break;

            default:
                // just assume Calibri
                $columnWidth = (int) (8.26 * PHPExcel_Shared_String::CountCharacters($columnText));
                $columnWidth = $columnWidth * $fontSize / 11; // extrapolate from font size
                break;
        }

        // Calculate approximate rotated column width
        if ($rotation !== 0) {
            if ($rotation == -165) {
                // stacked text
                $columnWidth = 4; // approximation
            } else {
                // rotated text
                $columnWidth = $columnWidth * cos(deg2rad($rotation))
                                + $fontSize * abs(sin(deg2rad($rotation))) / 5; // approximation
            }
        }

        // pixel width is an integer
        return (int) $columnWidth;
    }

    /**
     * Calculate an (approximate) pixel size, based on a font points size.
     *
     * @param     int        $fontSizeInPoints    Font size (in points)
     * @return     int        Font size (in pixels)
     */
    public static function fontSizeToPixels($fontSizeInPoints = 11)
    {
        return (int) ((4 / 3) * $fontSizeInPoints);
    }

    /**
     * Calculate an (approximate) pixel size, based on inch size.
     *
     * @param     int        $sizeInInch    Font size (in inch)
     * @return     int        Size (in pixels)
     */
    public static function inchSizeToPixels($sizeInInch = 1)
    {
        return $sizeInInch * 96;
    }

    /**
     * Calculate an (approximate) pixel size, based on centimeter size.
     *
     * @param     int        $sizeInCm    Font size (in centimeters)
     * @return     int        Size (in pixels)
     */
    public static function centimeterSizeToPixels($sizeInCm = 1)
    {
        return $sizeInCm * 37.795_275_591;
    }

    /**
     * Returns the font path given the font.
     *
     * @param PHPExcel_Style_Font
     * @return string Path to TrueType font file
     */
    public static function getTrueTypeFontFileFromFont($font)
    {
        if (!file_exists(self::$trueTypeFontPath) || !is_dir(self::$trueTypeFontPath)) {
            throw new PHPExcel_Exception('Valid directory to TrueType Font files not specified');
        }

        $name        = $font->getName();
        $bold        = $font->getBold();
        $italic      = $font->getItalic();

        // Check if we can map font to true type font file
        switch ($name) {
            case 'Arial':
                $fontFile = (
                    $bold ? ($italic ? self::ARIAL_BOLD_ITALIC : self::ARIAL_BOLD)
                          : ($italic ? self::ARIAL_ITALIC : self::ARIAL)
                );
                break;
            case 'Calibri':
                $fontFile = (
                    $bold ? ($italic ? self::CALIBRI_BOLD_ITALIC : self::CALIBRI_BOLD)
                          : ($italic ? self::CALIBRI_ITALIC : self::CALIBRI)
                );
                break;
            case 'Courier New':
                $fontFile = (
                    $bold ? ($italic ? self::COURIER_NEW_BOLD_ITALIC : self::COURIER_NEW_BOLD)
                          : ($italic ? self::COURIER_NEW_ITALIC : self::COURIER_NEW)
                );
                break;
            case 'Comic Sans MS':
                $fontFile = (
                    $bold ? self::COMIC_SANS_MS_BOLD : self::COMIC_SANS_MS
                );
                break;
            case 'Georgia':
                $fontFile = (
                    $bold ? ($italic ? self::GEORGIA_BOLD_ITALIC : self::GEORGIA_BOLD)
                          : ($italic ? self::GEORGIA_ITALIC : self::GEORGIA)
                );
                break;
            case 'Impact':
                $fontFile = self::IMPACT;
                break;
            case 'Liberation Sans':
                $fontFile = (
                    $bold ? ($italic ? self::LIBERATION_SANS_BOLD_ITALIC : self::LIBERATION_SANS_BOLD)
                          : ($italic ? self::LIBERATION_SANS_ITALIC : self::LIBERATION_SANS)
                );
                break;
            case 'Lucida Console':
                $fontFile = self::LUCIDA_CONSOLE;
                break;
            case 'Lucida Sans Unicode':
                $fontFile = self::LUCIDA_SANS_UNICODE;
                break;
            case 'Microsoft Sans Serif':
                $fontFile = self::MICROSOFT_SANS_SERIF;
                break;
            case 'Palatino Linotype':
                $fontFile = (
                    $bold ? ($italic ? self::PALATINO_LINOTYPE_BOLD_ITALIC : self::PALATINO_LINOTYPE_BOLD)
                          : ($italic ? self::PALATINO_LINOTYPE_ITALIC : self::PALATINO_LINOTYPE)
                );
                break;
            case 'Symbol':
                $fontFile = self::SYMBOL;
                break;
            case 'Tahoma':
                $fontFile = (
                    $bold ? self::TAHOMA_BOLD : self::TAHOMA
                );
                break;
            case 'Times New Roman':
                $fontFile = (
                    $bold ? ($italic ? self::TIMES_NEW_ROMAN_BOLD_ITALIC : self::TIMES_NEW_ROMAN_BOLD)
                          : ($italic ? self::TIMES_NEW_ROMAN_ITALIC : self::TIMES_NEW_ROMAN)
                );
                break;
            case 'Trebuchet MS':
                $fontFile = (
                    $bold ? ($italic ? self::TREBUCHET_MS_BOLD_ITALIC : self::TREBUCHET_MS_BOLD)
                          : ($italic ? self::TREBUCHET_MS_ITALIC : self::TREBUCHET_MS)
                );
                break;
            case 'Verdana':
                $fontFile = (
                    $bold ? ($italic ? self::VERDANA_BOLD_ITALIC : self::VERDANA_BOLD)
                          : ($italic ? self::VERDANA_ITALIC : self::VERDANA)
                );
                break;

            default:
                throw new PHPExcel_Exception('Unknown font name "' . $name . '". Cannot map to TrueType font file');
                break;
        }

        $fontFile = self::$trueTypeFontPath . $fontFile;

        // Check if file actually exists
        if (!file_exists($fontFile)) {
            throw new PHPExcel_Exception('TrueType Font file not found');
        }

        return $fontFile;
    }

    /**
     * Returns the associated charset for the font name.
     *
     * @param string $name Font name
     * @return int Character set code
     */
    public static function getCharsetFromFontName($name)
    {
        switch ($name) {
            // Add more cases. Check FONT records in real Excel files.
            case 'EucrosiaUPC':
                return self::CHARSET_ANSI_THAI;
            case 'Wingdings':
                return self::CHARSET_SYMBOL;
            case 'Wingdings 2':
                return self::CHARSET_SYMBOL;
            case 'Wingdings 3':
                return self::CHARSET_SYMBOL;

            default:
                return self::CHARSET_ANSI_LATIN;
        }
    }

    /**
     * Get the effective column width for columns without a column dimension or column with width -1
     * For example, for Calibri 11 this is 9.140625 (64 px).
     *
     * @param PHPExcel_Style_Font $font The workbooks default font
     * @param bool $pPixels true = return column width in pixels, false = return in OOXML units
     * @return mixed Column width
     */
    public static function getDefaultColumnWidthByFont(PHPExcel_Style_Font $font, $pPixels = false)
    {
        if (isset(self::$defaultColumnWidths[$font->getName()][$font->getSize()])) {
            // Exact width can be determined
            $columnWidth = $pPixels
                ? self::$defaultColumnWidths[$font->getName()][$font->getSize()]['px']
                    : self::$defaultColumnWidths[$font->getName()][$font->getSize()]['width'];

        } else {
            // We don't have data for this particular font and size, use approximation by
            // extrapolating from Calibri 11
            $columnWidth = $pPixels
                ? self::$defaultColumnWidths['Calibri'][11]['px']
                    : self::$defaultColumnWidths['Calibri'][11]['width'];
            $columnWidth = $columnWidth * $font->getSize() / 11;

            // Round pixels to closest integer
            if ($pPixels) {
                $columnWidth = (int) round($columnWidth);
            }
        }

        return $columnWidth;
    }

    /**
     * Get the effective row height for rows without a row dimension or rows with height -1
     * For example, for Calibri 11 this is 15 points.
     *
     * @param PHPExcel_Style_Font $font The workbooks default font
     * @return float Row height in points
     */
    public static function getDefaultRowHeightByFont(PHPExcel_Style_Font $font)
    {
        switch ($font->getName()) {
            case 'Arial':
                switch ($font->getSize()) {
                    case 10:
                        // inspection of Arial 10 workbook says 12.75pt ~17px
                        $rowHeight = 12.75;
                        break;
                    case 9:
                        // inspection of Arial 9 workbook says 12.00pt ~16px
                        $rowHeight = 12;
                        break;
                    case 8:
                        // inspection of Arial 8 workbook says 11.25pt ~15px
                        $rowHeight = 11.25;
                        break;
                    case 7:
                        // inspection of Arial 7 workbook says 9.00pt ~12px
                        $rowHeight = 9;
                        break;
                    case 6:
                    case 5:
                        // inspection of Arial 5,6 workbook says 8.25pt ~11px
                        $rowHeight = 8.25;
                        break;
                    case 4:
                        // inspection of Arial 4 workbook says 6.75pt ~9px
                        $rowHeight = 6.75;
                        break;
                    case 3:
                        // inspection of Arial 3 workbook says 6.00pt ~8px
                        $rowHeight = 6;
                        break;
                    case 2:
                    case 1:
                        // inspection of Arial 1,2 workbook says 5.25pt ~7px
                        $rowHeight = 5.25;
                        break;

                    default:
                        // use Arial 10 workbook as an approximation, extrapolation
                        $rowHeight = 12.75 * $font->getSize() / 10;
                        break;
                }
                break;

            case 'Calibri':
                switch ($font->getSize()) {
                    case 11:
                        // inspection of Calibri 11 workbook says 15.00pt ~20px
                        $rowHeight = 15;
                        break;
                    case 10:
                        // inspection of Calibri 10 workbook says 12.75pt ~17px
                        $rowHeight = 12.75;
                        break;
                    case 9:
                        // inspection of Calibri 9 workbook says 12.00pt ~16px
                        $rowHeight = 12;
                        break;
                    case 8:
                        // inspection of Calibri 8 workbook says 11.25pt ~15px
                        $rowHeight = 11.25;
                        break;
                    case 7:
                        // inspection of Calibri 7 workbook says 9.00pt ~12px
                        $rowHeight = 9;
                        break;
                    case 6:
                    case 5:
                        // inspection of Calibri 5,6 workbook says 8.25pt ~11px
                        $rowHeight = 8.25;
                        break;
                    case 4:
                        // inspection of Calibri 4 workbook says 6.75pt ~9px
                        $rowHeight = 6.75;
                        break;
                    case 3:
                        // inspection of Calibri 3 workbook says 6.00pt ~8px
                        $rowHeight = 6.00;
                        break;
                    case 2:
                    case 1:
                        // inspection of Calibri 1,2 workbook says 5.25pt ~7px
                        $rowHeight = 5.25;
                        break;

                    default:
                        // use Calibri 11 workbook as an approximation, extrapolation
                        $rowHeight = 15 * $font->getSize() / 11;
                        break;
                }
                break;

            case 'Verdana':
                switch ($font->getSize()) {
                    case 10:
                        // inspection of Verdana 10 workbook says 12.75pt ~17px
                        $rowHeight = 12.75;
                        break;
                    case 9:
                        // inspection of Verdana 9 workbook says 11.25pt ~15px
                        $rowHeight = 11.25;
                        break;
                    case 8:
                        // inspection of Verdana 8 workbook says 10.50pt ~14px
                        $rowHeight = 10.50;
                        break;
                    case 7:
                        // inspection of Verdana 7 workbook says 9.00pt ~12px
                        $rowHeight = 9.00;
                        break;
                    case 6:
                    case 5:
                        // inspection of Verdana 5,6 workbook says 8.25pt ~11px
                        $rowHeight = 8.25;
                        break;
                    case 4:
                        // inspection of Verdana 4 workbook says 6.75pt ~9px
                        $rowHeight = 6.75;
                        break;
                    case 3:
                        // inspection of Verdana 3 workbook says 6.00pt ~8px
                        $rowHeight = 6;
                        break;
                    case 2:
                    case 1:
                        // inspection of Verdana 1,2 workbook says 5.25pt ~7px
                        $rowHeight = 5.25;
                        break;

                    default:
                        // use Verdana 10 workbook as an approximation, extrapolation
                        $rowHeight = 12.75 * $font->getSize() / 10;
                        break;
                }
                break;

            default:
                // just use Calibri as an approximation
                $rowHeight = 15 * $font->getSize() / 11;
                break;
        }

        return $rowHeight;
    }
}
