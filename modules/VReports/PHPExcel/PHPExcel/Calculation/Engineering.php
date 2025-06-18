<?php

/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
    /**
     * @ignore
     */
    define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../../');
    require PHPEXCEL_ROOT . 'PHPExcel/Autoloader.php';
}

/** EULER */
define('EULER', 2.718_281_828_459_045_235_36);

/**
 * PHPExcel_Calculation_Engineering.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category    PHPExcel
 * @copyright    Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license        http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version        ##VERSION##, ##DATE##
 */
class PHPExcel_Calculation_Engineering
{
    /**
     * Details of the Units of measure that can be used in CONVERTUOM().
     *
     * @var mixed[]
     */
    private static $conversionUnits = [
        'g'     => ['Group' => 'Mass', 'Unit Name' => 'Gram', 'AllowPrefix' => true],
        'sg'    => ['Group' => 'Mass', 'Unit Name' => 'Slug', 'AllowPrefix' => false],
        'lbm'   => ['Group' => 'Mass', 'Unit Name' => 'Pound mass (avoirdupois)', 'AllowPrefix' => false],
        'u'     => ['Group' => 'Mass', 'Unit Name' => 'U (atomic mass unit)', 'AllowPrefix' => true],
        'ozm'   => ['Group' => 'Mass', 'Unit Name' => 'Ounce mass (avoirdupois)', 'AllowPrefix' => false],
        'm'     => ['Group' => 'Distance', 'Unit Name' => 'Meter', 'AllowPrefix' => true],
        'mi'    => ['Group' => 'Distance', 'Unit Name' => 'Statute mile', 'AllowPrefix' => false],
        'Nmi'   => ['Group' => 'Distance', 'Unit Name' => 'Nautical mile', 'AllowPrefix' => false],
        'in'    => ['Group' => 'Distance', 'Unit Name' => 'Inch', 'AllowPrefix' => false],
        'ft'    => ['Group' => 'Distance', 'Unit Name' => 'Foot', 'AllowPrefix' => false],
        'yd'    => ['Group' => 'Distance', 'Unit Name' => 'Yard', 'AllowPrefix' => false],
        'ang'   => ['Group' => 'Distance', 'Unit Name' => 'Angstrom', 'AllowPrefix' => true],
        'Pica'  => ['Group' => 'Distance', 'Unit Name' => 'Pica (1/72 in)', 'AllowPrefix' => false],
        'yr'    => ['Group' => 'Time', 'Unit Name' => 'Year', 'AllowPrefix' => false],
        'day'   => ['Group' => 'Time', 'Unit Name' => 'Day', 'AllowPrefix' => false],
        'hr'    => ['Group' => 'Time', 'Unit Name' => 'Hour', 'AllowPrefix' => false],
        'mn'    => ['Group' => 'Time', 'Unit Name' => 'Minute', 'AllowPrefix' => false],
        'sec'   => ['Group' => 'Time', 'Unit Name' => 'Second', 'AllowPrefix' => true],
        'Pa'    => ['Group' => 'Pressure', 'Unit Name' => 'Pascal', 'AllowPrefix' => true],
        'p'     => ['Group' => 'Pressure', 'Unit Name' => 'Pascal', 'AllowPrefix' => true],
        'atm'   => ['Group' => 'Pressure', 'Unit Name' => 'Atmosphere', 'AllowPrefix' => true],
        'at'    => ['Group' => 'Pressure', 'Unit Name' => 'Atmosphere', 'AllowPrefix' => true],
        'mmHg'  => ['Group' => 'Pressure', 'Unit Name' => 'mm of Mercury', 'AllowPrefix' => true],
        'N'     => ['Group' => 'Force', 'Unit Name' => 'Newton', 'AllowPrefix' => true],
        'dyn'   => ['Group' => 'Force', 'Unit Name' => 'Dyne', 'AllowPrefix' => true],
        'dy'    => ['Group' => 'Force', 'Unit Name' => 'Dyne', 'AllowPrefix' => true],
        'lbf'   => ['Group' => 'Force', 'Unit Name' => 'Pound force', 'AllowPrefix' => false],
        'J'     => ['Group' => 'Energy', 'Unit Name' => 'Joule', 'AllowPrefix' => true],
        'e'     => ['Group' => 'Energy', 'Unit Name' => 'Erg', 'AllowPrefix' => true],
        'c'     => ['Group' => 'Energy', 'Unit Name' => 'Thermodynamic calorie', 'AllowPrefix' => true],
        'cal'   => ['Group' => 'Energy', 'Unit Name' => 'IT calorie', 'AllowPrefix' => true],
        'eV'    => ['Group' => 'Energy', 'Unit Name' => 'Electron volt', 'AllowPrefix' => true],
        'ev'    => ['Group' => 'Energy', 'Unit Name' => 'Electron volt', 'AllowPrefix' => true],
        'HPh'   => ['Group' => 'Energy', 'Unit Name' => 'Horsepower-hour', 'AllowPrefix' => false],
        'hh'    => ['Group' => 'Energy', 'Unit Name' => 'Horsepower-hour', 'AllowPrefix' => false],
        'Wh'    => ['Group' => 'Energy', 'Unit Name' => 'Watt-hour', 'AllowPrefix' => true],
        'wh'    => ['Group' => 'Energy', 'Unit Name' => 'Watt-hour', 'AllowPrefix' => true],
        'flb'   => ['Group' => 'Energy', 'Unit Name' => 'Foot-pound', 'AllowPrefix' => false],
        'BTU'   => ['Group' => 'Energy', 'Unit Name' => 'BTU', 'AllowPrefix' => false],
        'btu'   => ['Group' => 'Energy', 'Unit Name' => 'BTU', 'AllowPrefix' => false],
        'HP'    => ['Group' => 'Power', 'Unit Name' => 'Horsepower', 'AllowPrefix' => false],
        'h'     => ['Group' => 'Power', 'Unit Name' => 'Horsepower', 'AllowPrefix' => false],
        'W'     => ['Group' => 'Power', 'Unit Name' => 'Watt', 'AllowPrefix' => true],
        'w'     => ['Group' => 'Power', 'Unit Name' => 'Watt', 'AllowPrefix' => true],
        'T'     => ['Group' => 'Magnetism', 'Unit Name' => 'Tesla', 'AllowPrefix' => true],
        'ga'    => ['Group' => 'Magnetism', 'Unit Name' => 'Gauss', 'AllowPrefix' => true],
        'C'     => ['Group' => 'Temperature', 'Unit Name' => 'Celsius', 'AllowPrefix' => false],
        'cel'   => ['Group' => 'Temperature', 'Unit Name' => 'Celsius', 'AllowPrefix' => false],
        'F'     => ['Group' => 'Temperature', 'Unit Name' => 'Fahrenheit', 'AllowPrefix' => false],
        'fah'   => ['Group' => 'Temperature', 'Unit Name' => 'Fahrenheit', 'AllowPrefix' => false],
        'K'     => ['Group' => 'Temperature', 'Unit Name' => 'Kelvin', 'AllowPrefix' => false],
        'kel'   => ['Group' => 'Temperature', 'Unit Name' => 'Kelvin', 'AllowPrefix' => false],
        'tsp'   => ['Group' => 'Liquid', 'Unit Name' => 'Teaspoon', 'AllowPrefix' => false],
        'tbs'   => ['Group' => 'Liquid', 'Unit Name' => 'Tablespoon', 'AllowPrefix' => false],
        'oz'    => ['Group' => 'Liquid', 'Unit Name' => 'Fluid Ounce', 'AllowPrefix' => false],
        'cup'   => ['Group' => 'Liquid', 'Unit Name' => 'Cup', 'AllowPrefix' => false],
        'pt'    => ['Group' => 'Liquid', 'Unit Name' => 'U.S. Pint', 'AllowPrefix' => false],
        'us_pt' => ['Group' => 'Liquid', 'Unit Name' => 'U.S. Pint', 'AllowPrefix' => false],
        'uk_pt' => ['Group' => 'Liquid', 'Unit Name' => 'U.K. Pint', 'AllowPrefix' => false],
        'qt'    => ['Group' => 'Liquid', 'Unit Name' => 'Quart', 'AllowPrefix' => false],
        'gal'   => ['Group' => 'Liquid', 'Unit Name' => 'Gallon', 'AllowPrefix' => false],
        'l'     => ['Group' => 'Liquid', 'Unit Name' => 'Litre', 'AllowPrefix' => true],
        'lt'    => ['Group' => 'Liquid', 'Unit Name' => 'Litre', 'AllowPrefix' => true],
    ];

    /**
     * Details of the Multiplier prefixes that can be used with Units of Measure in CONVERTUOM().
     *
     * @var mixed[]
     */
    private static $conversionMultipliers = [
        'Y' => ['multiplier' => 1E24, 'name' => 'yotta'],
        'Z' => ['multiplier' => 1E21, 'name' => 'zetta'],
        'E' => ['multiplier' => 1E18, 'name' => 'exa'],
        'P' => ['multiplier' => 1E15, 'name' => 'peta'],
        'T' => ['multiplier' => 1E12, 'name' => 'tera'],
        'G' => ['multiplier' => 1E9, 'name' => 'giga'],
        'M' => ['multiplier' => 1E6, 'name' => 'mega'],
        'k' => ['multiplier' => 1E3, 'name' => 'kilo'],
        'h' => ['multiplier' => 1E2, 'name' => 'hecto'],
        'e' => ['multiplier' => 1E1, 'name' => 'deka'],
        'd' => ['multiplier' => 1E-1, 'name' => 'deci'],
        'c' => ['multiplier' => 1E-2, 'name' => 'centi'],
        'm' => ['multiplier' => 1E-3, 'name' => 'milli'],
        'u' => ['multiplier' => 1E-6, 'name' => 'micro'],
        'n' => ['multiplier' => 1E-9, 'name' => 'nano'],
        'p' => ['multiplier' => 1E-12, 'name' => 'pico'],
        'f' => ['multiplier' => 1E-15, 'name' => 'femto'],
        'a' => ['multiplier' => 1E-18, 'name' => 'atto'],
        'z' => ['multiplier' => 1E-21, 'name' => 'zepto'],
        'y' => ['multiplier' => 1E-24, 'name' => 'yocto'],
    ];

    /**
     * Details of the Units of measure conversion factors, organised by group.
     *
     * @var mixed[]
     */
    private static $unitConversions = [
        'Mass' => [
            'g' => [
                'g'   => 1.0,
                'sg'  => 6.852_205_000_534_78E-05,
                'lbm' => 2.204_622_914_691_34E-03,
                'u'   => 6.022_170_000_000_00,
                'ozm' => 3.527_397_180_036_27E-02,
            ],
            'sg' => [
                'g'   => 1.459_384_241_892_87,
                'sg'  => 1.0,
                'lbm' => 3.217_391_941_016_47,
                'u'   => 8.788_660_000_000_00,
                'ozm' => 5.147_827_859_442_29,
            ],
            'lbm' => [
                'g'   => 4.535_923_097_488_114_8,
                'sg'  => 3.108_107_493_064_93E-02,
                'lbm' => 1.0,
                'u'   => 2.731_610_000_000_00,
                'ozm' => 1.600_000_234_294_10,
            ],
            'u' => [
                'g'   => 1.660_531_004_604_65E-24,
                'sg'  => 1.137_829_885_329_50E-28,
                'lbm' => 3.660_844_703_306_84E-27,
                'u'   => 1.0,
                'ozm' => 5.857_352_383_005_24E-26,
            ],
            'ozm' => [
                'g'   => 2.834_951_520_797_32,
                'sg'  => 1.942_566_898_708_11E-03,
                'lbm' => 6.249_999_084_788_82E-02,
                'u'   => 1.707_256_000_000_00,
                'ozm' => 1.0,
            ],
        ],
        'Distance' => [
            'm' => [
                'm'    => 1.0,
                'mi'   => 6.213_711_922_373_34E-04,
                'Nmi'  => 5.399_568_034_557_24E-04,
                'in'   => 3.937_007_874_015_75,
                'ft'   => 3.280_839_895_013_12,
                'yd'   => 1.093_613_297_978_91,
                'ang'  => 1.000_000_000_000_00,
                'Pica' => 2.834_645_669_291_16,
            ],
            'mi' => [
                'm'    => 1.609_344_000_000_00,
                'mi'   => 1.0,
                'Nmi'  => 8.689_762_419_006_48E-01,
                'in'   => 6.336_000_000_000_00,
                'ft'   => 5.280_000_000_000_00,
                'yd'   => 1.760_000_000_000_00,
                'ang'  => 1.609_344_000_000_00,
                'Pica' => 4.561_919_999_999_71,
            ],
            'Nmi' => [
                'm'    => 1.852_000_000_000_00,
                'mi'   => 1.150_779_448_023_54,
                'Nmi'  => 1.0,
                'in'   => 7.291_338_582_677_17,
                'ft'   => 6.076_115_485_564_30,
                'yd'   => 2.025_371_827_856_94,
                'ang'  => 1.852_000_000_000_00,
                'Pica' => 5.249_763_779_527_23,
            ],
            'in' => [
                'm'    => 2.540_000_000_000_00E-02,
                'mi'   => 1.578_282_828_282_83E-05,
                'Nmi'  => 1.371_490_280_777_54E-05,
                'in'   => 1.0,
                'ft'   => 8.333_333_333_333_33E-02,
                'yd'   => 2.777_777_776_866_43E-02,
                'ang'  => 2.540_000_000_000_00,
                'Pica' => 7.199_999_999_999_55,
            ],
            'ft' => [
                'm'    => 3.048_000_000_000_00E-01,
                'mi'   => 1.893_939_393_939_39E-04,
                'Nmi'  => 1.645_788_336_933_05E-04,
                'in'   => 1.200_000_000_000_00,
                'ft'   => 1.0,
                'yd'   => 3.333_333_332_239_72E-01,
                'ang'  => 3.048_000_000_000_00,
                'Pica' => 8.639_999_999_999_46,
            ],
            'yd' => [
                'm'    => 9.144_000_003_000_00E-01,
                'mi'   => 5.681_818_183_682_30E-04,
                'Nmi'  => 4.937_365_012_419_01E-04,
                'in'   => 3.600_000_001_181_10,
                'ft'   => 3.000_000_000_000_00,
                'yd'   => 1.0,
                'ang'  => 9.144_000_003_000_00,
                'Pica' => 2.592_000_000_850_23,
            ],
            'ang' => [
                'm'    => 1.000_000_000_000_00E-10,
                'mi'   => 6.213_711_922_373_34E-14,
                'Nmi'  => 5.399_568_034_557_24E-14,
                'in'   => 3.937_007_874_015_75E-09,
                'ft'   => 3.280_839_895_013_12E-10,
                'yd'   => 1.093_613_297_978_91E-10,
                'ang'  => 1.0,
                'Pica' => 2.834_645_669_291_16E-07,
            ],
            'Pica' => [
                'm'    => 3.527_777_777_778_00E-04,
                'mi'   => 2.192_059_483_726_29E-07,
                'Nmi'  => 1.904_847_612_191_14E-07,
                'in'   => 1.388_888_888_888_98E-02,
                'ft'   => 1.157_407_407_407_48E-03,
                'yd'   => 3.858_024_690_092_51E-04,
                'ang'  => 3.527_777_777_778_00,
                'Pica' => 1.0,
            ],
        ],
        'Time' => [
            'yr' => [
                'yr'  => 1.0,
                'day' => 365.25,
                'hr'  => 8_766.0,
                'mn'  => 525_960.0,
                'sec' => 31_557_600.0,
            ],
            'day' => [
                'yr'  => 2.737_850_787_132_10E-03,
                'day' => 1.0,
                'hr'  => 24.0,
                'mn'  => 1_440.0,
                'sec' => 86_400.0,
            ],
            'hr' => [
                'yr'  => 1.140_771_161_305_04E-04,
                'day' => 4.166_666_666_666_67E-02,
                'hr'  => 1.0,
                'mn'  => 60.0,
                'sec' => 3_600.0,
            ],
            'mn' => [
                'yr'  => 1.901_285_268_841_74E-06,
                'day' => 6.944_444_444_444_44E-04,
                'hr'  => 1.666_666_666_666_67E-02,
                'mn'  => 1.0,
                'sec' => 60.0,
            ],
            'sec' => [
                'yr'  => 3.168_808_781_402_89E-08,
                'day' => 1.157_407_407_407_41E-05,
                'hr'  => 2.777_777_777_777_78E-04,
                'mn'  => 1.666_666_666_666_67E-02,
                'sec' => 1.0,
            ],
        ],
        'Pressure' => [
            'Pa' => [
                'Pa'   => 1.0,
                'p'    => 1.0,
                'atm'  => 9.869_232_999_981_93E-06,
                'at'   => 9.869_232_999_981_93E-06,
                'mmHg' => 7.500_617_079_986_27E-03,
            ],
            'p' => [
                'Pa'   => 1.0,
                'p'    => 1.0,
                'atm'  => 9.869_232_999_981_93E-06,
                'at'   => 9.869_232_999_981_93E-06,
                'mmHg' => 7.500_617_079_986_27E-03,
            ],
            'atm' => [
                'Pa'   => 1.013_249_965_830_00,
                'p'    => 1.013_249_965_830_00,
                'atm'  => 1.0,
                'at'   => 1.0,
                'mmHg' => 760.0,
            ],
            'at' => [
                'Pa'   => 1.013_249_965_830_00,
                'p'    => 1.013_249_965_830_00,
                'atm'  => 1.0,
                'at'   => 1.0,
                'mmHg' => 760.0,
            ],
            'mmHg' => [
                'Pa'   => 1.333_223_639_250_00,
                'p'    => 1.333_223_639_250_00,
                'atm'  => 1.315_789_473_684_21E-03,
                'at'   => 1.315_789_473_684_21E-03,
                'mmHg' => 1.0,
            ],
        ],
        'Force' => [
            'N' => [
                'N'   => 1.0,
                'dyn' => 1.0,
                'dy'  => 1.0,
                'lbf' => 2.248_089_236_553_39E-01,
            ],
            'dyn' => [
                'N'   => 1.0E-5,
                'dyn' => 1.0,
                'dy'  => 1.0,
                'lbf' => 2.248_089_236_553_39E-06,
            ],
            'dy' => [
                'N'   => 1.0E-5,
                'dyn' => 1.0,
                'dy'  => 1.0,
                'lbf' => 2.248_089_236_553_39E-06,
            ],
            'lbf' => [
                'N'   => 4.448_222,
                'dyn' => 4.448_222,
                'dy'  => 4.448_222,
                'lbf' => 1.0,
            ],
        ],
        'Energy' => [
            'J' => [
                'J'   => 1.0,
                'e'   => 9.999_995_193_432_31,
                'c'   => 2.390_062_494_734_67E-01,
                'cal' => 2.388_461_906_420_17E-01,
                'eV'  => 6.241_457_000_000_00,
                'ev'  => 6.241_457_000_000_00,
                'HPh' => 3.725_064_308_010_00E-07,
                'hh'  => 3.725_064_308_010_00E-07,
                'Wh'  => 2.777_779_162_387_11E-04,
                'wh'  => 2.777_779_162_387_11E-04,
                'flb' => 2.373_042_221_926_51,
                'BTU' => 9.478_150_673_490_15E-04,
                'btu' => 9.478_150_673_490_15E-04,
            ],
            'e' => [
                'J'   => 1.000_000_480_657_00E-07,
                'e'   => 1.0,
                'c'   => 2.390_063_643_534_94E-08,
                'cal' => 2.388_463_054_451_11E-08,
                'eV'  => 6.241_460_000_000_00,
                'ev'  => 6.241_460_000_000_00,
                'HPh' => 3.725_066_098_488_24E-14,
                'hh'  => 3.725_066_098_488_24E-14,
                'Wh'  => 2.777_780_497_546_11E-11,
                'wh'  => 2.777_780_497_546_11E-11,
                'flb' => 2.373_043_362_545_86E-06,
                'BTU' => 9.478_155_229_229_62E-11,
                'btu' => 9.478_155_229_229_62E-11,
            ],
            'c' => [
                'J'   => 4.183_991_013_636_72,
                'e'   => 4.183_989_002_573_12,
                'c'   => 1.0,
                'cal' => 9.993_303_152_875_63E-01,
                'eV'  => 2.611_420_000_000_00,
                'ev'  => 2.611_420_000_000_00,
                'HPh' => 1.558_563_558_993_27E-06,
                'hh'  => 1.558_563_558_993_27E-06,
                'Wh'  => 1.162_220_305_329_50E-03,
                'wh'  => 1.162_220_305_329_50E-03,
                'flb' => 9.928_787_331_521_02,
                'BTU' => 3.965_649_724_377_76E-03,
                'btu' => 3.965_649_724_377_76E-03,
            ],
            'cal' => [
                'J'   => 4.186_794_846_139_29,
                'e'   => 4.186_792_833_728_01,
                'c'   => 1.000_670_133_490_59,
                'cal' => 1.0,
                'eV'  => 2.613_170_000_000_00,
                'ev'  => 2.613_170_000_000_00,
                'HPh' => 1.559_608_004_631_37E-06,
                'hh'  => 1.559_608_004_631_37E-06,
                'Wh'  => 1.162_999_148_079_55E-03,
                'wh'  => 1.162_999_148_079_55E-03,
                'flb' => 9.935_440_944_432_83,
                'BTU' => 3.968_307_239_070_02E-03,
                'btu' => 3.968_307_239_070_02E-03,
            ],
            'eV' => [
                'J'   => 1.602_190_001_469_21E-19,
                'e'   => 1.602_189_231_365_74E-12,
                'c'   => 3.829_334_231_950_43E-20,
                'cal' => 3.826_769_785_356_48E-20,
                'eV'  => 1.0,
                'ev'  => 1.0,
                'HPh' => 5.968_260_789_123_44E-26,
                'hh'  => 5.968_260_789_123_44E-26,
                'Wh'  => 4.450_530_000_266_14E-23,
                'wh'  => 4.450_530_000_266_14E-23,
                'flb' => 3.802_064_521_034_92E-18,
                'BTU' => 1.518_579_824_148_46E-22,
                'btu' => 1.518_579_824_148_46E-22,
            ],
            'ev' => [
                'J'   => 1.602_190_001_469_21E-19,
                'e'   => 1.602_189_231_365_74E-12,
                'c'   => 3.829_334_231_950_43E-20,
                'cal' => 3.826_769_785_356_48E-20,
                'eV'  => 1.0,
                'ev'  => 1.0,
                'HPh' => 5.968_260_789_123_44E-26,
                'hh'  => 5.968_260_789_123_44E-26,
                'Wh'  => 4.450_530_000_266_14E-23,
                'wh'  => 4.450_530_000_266_14E-23,
                'flb' => 3.802_064_521_034_92E-18,
                'BTU' => 1.518_579_824_148_46E-22,
                'btu' => 1.518_579_824_148_46E-22,
            ],
            'HPh' => [
                'J'   => 2.684_517_413_161_70,
                'e'   => 2.684_516_122_830_24,
                'c'   => 6.416_164_385_659_91,
                'cal' => 6.411_867_578_458_35,
                'eV'  => 1.675_530_000_000_00,
                'ev'  => 1.675_530_000_000_00,
                'HPh' => 1.0,
                'hh'  => 1.0,
                'Wh'  => 7.456_996_531_345_93,
                'wh'  => 7.456_996_531_345_93,
                'flb' => 6.370_473_166_929_64,
                'BTU' => 2.544_426_052_755_46,
                'btu' => 2.544_426_052_755_46,
            ],
            'hh' => [
                'J'   => 2.684_517_413_161_70,
                'e'   => 2.684_516_122_830_24,
                'c'   => 6.416_164_385_659_91,
                'cal' => 6.411_867_578_458_35,
                'eV'  => 1.675_530_000_000_00,
                'ev'  => 1.675_530_000_000_00,
                'HPh' => 1.0,
                'hh'  => 1.0,
                'Wh'  => 7.456_996_531_345_93,
                'wh'  => 7.456_996_531_345_93,
                'flb' => 6.370_473_166_929_64,
                'BTU' => 2.544_426_052_755_46,
                'btu' => 2.544_426_052_755_46,
            ],
            'Wh' => [
                'J'   => 3.599_998_205_547_20,
                'e'   => 3.599_996_475_183_69,
                'c'   => 8.604_220_692_190_46,
                'cal' => 8.598_458_577_130_46,
                'eV'  => 2.246_923_400_000_00,
                'ev'  => 2.246_923_400_000_00,
                'HPh' => 1.341_022_482_438_39E-03,
                'hh'  => 1.341_022_482_438_39E-03,
                'Wh'  => 1.0,
                'wh'  => 1.0,
                'flb' => 8.542_947_740_623_16,
                'BTU' => 3.412_132_541_647_05,
                'btu' => 3.412_132_541_647_05,
            ],
            'wh' => [
                'J'   => 3.599_998_205_547_20,
                'e'   => 3.599_996_475_183_69,
                'c'   => 8.604_220_692_190_46,
                'cal' => 8.598_458_577_130_46,
                'eV'  => 2.246_923_400_000_00,
                'ev'  => 2.246_923_400_000_00,
                'HPh' => 1.341_022_482_438_39E-03,
                'hh'  => 1.341_022_482_438_39E-03,
                'Wh'  => 1.0,
                'wh'  => 1.0,
                'flb' => 8.542_947_740_623_16,
                'BTU' => 3.412_132_541_647_05,
                'btu' => 3.412_132_541_647_05,
            ],
            'flb' => [
                'J'   => 4.214_000_032_364_24E-02,
                'e'   => 4.213_998_006_876_60,
                'c'   => 1.007_172_343_016_44E-02,
                'cal' => 1.006_497_855_095_54E-02,
                'eV'  => 2.630_150_000_000_00,
                'ev'  => 2.630_150_000_000_00,
                'HPh' => 1.569_742_111_451_30E-08,
                'hh'  => 1.569_742_111_451_30E-08,
                'Wh'  => 1.170_556_148_020_00E-05,
                'wh'  => 1.170_556_148_020_00E-05,
                'flb' => 1.0,
                'BTU' => 3.994_092_724_484_06E-05,
                'btu' => 3.994_092_724_484_06E-05,
            ],
            'BTU' => [
                'J'   => 1.055_058_137_867_49,
                'e'   => 1.055_057_630_746_65,
                'c'   => 2.521_654_885_081_68,
                'cal' => 2.519_966_171_355_10,
                'eV'  => 6.585_100_000_000_00,
                'ev'  => 6.585_100_000_000_00,
                'HPh' => 3.930_159_412_245_68E-04,
                'hh'  => 3.930_159_412_245_68E-04,
                'Wh'  => 2.930_718_510_475_26E-01,
                'wh'  => 2.930_718_510_475_26E-01,
                'flb' => 2.503_697_507_746_71,
                'BTU' => 1.0,
                'btu' => 1.0,
            ],
            'btu' => [
                'J'   => 1.055_058_137_867_49,
                'e'   => 1.055_057_630_746_65,
                'c'   => 2.521_654_885_081_68,
                'cal' => 2.519_966_171_355_10,
                'eV'  => 6.585_100_000_000_00,
                'ev'  => 6.585_100_000_000_00,
                'HPh' => 3.930_159_412_245_68E-04,
                'hh'  => 3.930_159_412_245_68E-04,
                'Wh'  => 2.930_718_510_475_26E-01,
                'wh'  => 2.930_718_510_475_26E-01,
                'flb' => 2.503_697_507_746_71,
                'BTU' => 1.0,
                'btu' => 1.0,
            ],
        ],
        'Power' => [
            'HP' => [
                'HP' => 1.0,
                'h'  => 1.0,
                'W'  => 7.457_010_000_000_00,
                'w'  => 7.457_010_000_000_00,
            ],
            'h' => [
                'HP' => 1.0,
                'h'  => 1.0,
                'W'  => 7.457_010_000_000_00,
                'w'  => 7.457_010_000_000_00,
            ],
            'W' => [
                'HP' => 1.341_020_060_319_08E-03,
                'h'  => 1.341_020_060_319_08E-03,
                'W'  => 1.0,
                'w'  => 1.0,
            ],
            'w' => [
                'HP' => 1.341_020_060_319_08E-03,
                'h'  => 1.341_020_060_319_08E-03,
                'W'  => 1.0,
                'w'  => 1.0,
            ],
        ],
        'Magnetism' => [
            'T' => [
                'T'  => 1.0,
                'ga' => 10_000.0,
            ],
            'ga' => [
                'T'  => 0.000_1,
                'ga' => 1.0,
            ],
        ],
        'Liquid' => [
            'tsp' => [
                'tsp'   => 1.0,
                'tbs'   => 3.333_333_333_333_33E-01,
                'oz'    => 1.666_666_666_666_67E-01,
                'cup'   => 2.083_333_333_333_33E-02,
                'pt'    => 1.041_666_666_666_67E-02,
                'us_pt' => 1.041_666_666_666_67E-02,
                'uk_pt' => 8.675_585_168_219_60E-03,
                'qt'    => 5.208_333_333_333_33E-03,
                'gal'   => 1.302_083_333_333_33E-03,
                'l'     => 4.929_994_084_007_10E-03,
                'lt'    => 4.929_994_084_007_10E-03,
            ],
            'tbs' => [
                'tsp'   => 3.000_000_000_000_00,
                'tbs'   => 1.0,
                'oz'    => 5.000_000_000_000_00E-01,
                'cup'   => 6.250_000_000_000_00E-02,
                'pt'    => 3.125_000_000_000_00E-02,
                'us_pt' => 3.125_000_000_000_00E-02,
                'uk_pt' => 2.602_675_550_465_88E-02,
                'qt'    => 1.562_500_000_000_00E-02,
                'gal'   => 3.906_250_000_000_00E-03,
                'l'     => 1.478_998_225_202_13E-02,
                'lt'    => 1.478_998_225_202_13E-02,
            ],
            'oz' => [
                'tsp'   => 6.000_000_000_000_00,
                'tbs'   => 2.000_000_000_000_00,
                'oz'    => 1.0,
                'cup'   => 1.250_000_000_000_00E-01,
                'pt'    => 6.250_000_000_000_00E-02,
                'us_pt' => 6.250_000_000_000_00E-02,
                'uk_pt' => 5.205_351_100_931_76E-02,
                'qt'    => 3.125_000_000_000_00E-02,
                'gal'   => 7.812_500_000_000_00E-03,
                'l'     => 2.957_996_450_404_26E-02,
                'lt'    => 2.957_996_450_404_26E-02,
            ],
            'cup' => [
                'tsp'   => 4.800_000_000_000_00,
                'tbs'   => 1.600_000_000_000_00,
                'oz'    => 8.000_000_000_000_00,
                'cup'   => 1.0,
                'pt'    => 5.000_000_000_000_00E-01,
                'us_pt' => 5.000_000_000_000_00E-01,
                'uk_pt' => 4.164_280_880_745_41E-01,
                'qt'    => 2.500_000_000_000_00E-01,
                'gal'   => 6.250_000_000_000_00E-02,
                'l'     => 2.366_397_160_323_41E-01,
                'lt'    => 2.366_397_160_323_41E-01,
            ],
            'pt' => [
                'tsp'   => 9.600_000_000_000_00,
                'tbs'   => 3.200_000_000_000_00,
                'oz'    => 1.600_000_000_000_00,
                'cup'   => 2.000_000_000_000_00,
                'pt'    => 1.0,
                'us_pt' => 1.0,
                'uk_pt' => 8.328_561_761_490_81E-01,
                'qt'    => 5.000_000_000_000_00E-01,
                'gal'   => 1.250_000_000_000_00E-01,
                'l'     => 4.732_794_320_646_82E-01,
                'lt'    => 4.732_794_320_646_82E-01,
            ],
            'us_pt' => [
                'tsp'   => 9.600_000_000_000_00,
                'tbs'   => 3.200_000_000_000_00,
                'oz'    => 1.600_000_000_000_00,
                'cup'   => 2.000_000_000_000_00,
                'pt'    => 1.0,
                'us_pt' => 1.0,
                'uk_pt' => 8.328_561_761_490_81E-01,
                'qt'    => 5.000_000_000_000_00E-01,
                'gal'   => 1.250_000_000_000_00E-01,
                'l'     => 4.732_794_320_646_82E-01,
                'lt'    => 4.732_794_320_646_82E-01,
            ],
            'uk_pt' => [
                'tsp'   => 1.152_660_000_000_00,
                'tbs'   => 3.842_200_000_000_00,
                'oz'    => 1.921_100_000_000_00,
                'cup'   => 2.401_375_000_000_00,
                'pt'    => 1.200_687_500_000_00,
                'us_pt' => 1.200_687_500_000_00,
                'uk_pt' => 1.0,
                'qt'    => 6.003_437_500_000_00E-01,
                'gal'   => 1.500_859_375_000_00E-01,
                'l'     => 5.682_606_980_871_62E-01,
                'lt'    => 5.682_606_980_871_62E-01,
            ],
            'qt' => [
                'tsp'   => 1.920_000_000_000_00,
                'tbs'   => 6.400_000_000_000_00,
                'oz'    => 3.200_000_000_000_00,
                'cup'   => 4.000_000_000_000_00,
                'pt'    => 2.000_000_000_000_00,
                'us_pt' => 2.000_000_000_000_00,
                'uk_pt' => 1.665_712_352_298_16,
                'qt'    => 1.0,
                'gal'   => 2.500_000_000_000_00E-01,
                'l'     => 9.465_588_641_293_63E-01,
                'lt'    => 9.465_588_641_293_63E-01,
            ],
            'gal' => [
                'tsp'   => 7.680_000_000_000_00,
                'tbs'   => 2.560_000_000_000_00,
                'oz'    => 1.280_000_000_000_00,
                'cup'   => 1.600_000_000_000_00,
                'pt'    => 8.000_000_000_000_00,
                'us_pt' => 8.000_000_000_000_00,
                'uk_pt' => 6.662_849_409_192_65,
                'qt'    => 4.000_000_000_000_00,
                'gal'   => 1.0,
                'l'     => 3.786_235_456_517_45,
                'lt'    => 3.786_235_456_517_45,
            ],
            'l' => [
                'tsp'   => 2.028_400_000_000_00,
                'tbs'   => 6.761_333_333_333_33,
                'oz'    => 3.380_666_666_666_67,
                'cup'   => 4.225_833_333_333_33,
                'pt'    => 2.112_916_666_666_67,
                'us_pt' => 2.112_916_666_666_67,
                'uk_pt' => 1.759_755_695_521_66,
                'qt'    => 1.056_458_333_333_33,
                'gal'   => 2.641_145_833_333_33E-01,
                'l'     => 1.0,
                'lt'    => 1.0,
            ],
            'lt' => [
                'tsp'   => 2.028_400_000_000_00,
                'tbs'   => 6.761_333_333_333_33,
                'oz'    => 3.380_666_666_666_67,
                'cup'   => 4.225_833_333_333_33,
                'pt'    => 2.112_916_666_666_67,
                'us_pt' => 2.112_916_666_666_67,
                'uk_pt' => 1.759_755_695_521_66,
                'qt'    => 1.056_458_333_333_33,
                'gal'   => 2.641_145_833_333_33E-01,
                'l'     => 1.0,
                'lt'    => 1.0,
            ],
        ],
    ];

    /**
     * parseComplex.
     *
     * Parses a complex number into its real and imaginary parts, and an I or J suffix
     *
     * @param    string        $complexNumber    The complex number
     * @return    string[]    Indexed on "real", "imaginary" and "suffix"
     */
    public static function parseComplex($complexNumber)
    {
        $workString = (string) $complexNumber;

        $realNumber = $imaginary = 0;
        //    Extract the suffix, if there is one
        $suffix = substr($workString, -1);
        if (!is_numeric($suffix)) {
            $workString = substr($workString, 0, -1);
        } else {
            $suffix = '';
        }

        //    Split the input into its Real and Imaginary components
        $leadingSign = 0;
        if (strlen($workString) > 0) {
            $leadingSign = (($workString[0] == '+') || ($workString[0] == '-')) ? 1 : 0;
        }
        $power = '';
        $realNumber = strtok($workString, '+-');
        if (strtoupper(substr($realNumber, -1)) == 'E') {
            $power = strtok('+-');
            ++$leadingSign;
        }

        $realNumber = substr($workString, 0, strlen($realNumber) + strlen($power) + $leadingSign);

        if ($suffix != '') {
            $imaginary = substr($workString, strlen($realNumber));

            if (($imaginary == '') && (($realNumber == '') || ($realNumber == '+') || ($realNumber == '-'))) {
                $imaginary = $realNumber . '1';
                $realNumber = '0';
            } elseif ($imaginary == '') {
                $imaginary = $realNumber;
                $realNumber = '0';
            } elseif (($imaginary == '+') || ($imaginary == '-')) {
                $imaginary .= '1';
            }
        }

        return [
            'real'   => $realNumber,
            'imaginary' => $imaginary,
            'suffix' => $suffix,
        ];
    }

    /**
     * Cleans the leading characters in a complex number string.
     *
     * @param    string        $complexNumber    The complex number to clean
     * @return    string        The "cleaned" complex number
     */
    private static function cleanComplex($complexNumber)
    {
        if ($complexNumber[0] == '+') {
            $complexNumber = substr($complexNumber, 1);
        }
        if ($complexNumber[0] == '0') {
            $complexNumber = substr($complexNumber, 1);
        }
        if ($complexNumber[0] == '.') {
            $complexNumber = '0' . $complexNumber;
        }
        if ($complexNumber[0] == '+') {
            $complexNumber = substr($complexNumber, 1);
        }

        return $complexNumber;
    }

    /**
     * Formats a number base string value with leading zeroes.
     *
     * @param    string        $xVal        The "number" to pad
     * @param    int        $places        The length that we want to pad this value
     * @return    string        The padded "number"
     */
    private static function nbrConversionFormat($xVal, $places)
    {
        if (!is_null($places)) {
            if (strlen($xVal) <= $places) {
                return substr(str_pad($xVal, $places, '0', STR_PAD_LEFT), -10);
            }

            return PHPExcel_Calculation_Functions::NaN();

        }

        return substr($xVal, -10);
    }

    /**
     *    BESSELI.
     *
     *    Returns the modified Bessel function In(x), which is equivalent to the Bessel function evaluated
     *        for purely imaginary arguments
     *
     *    Excel Function:
     *        BESSELI(x,ord)
     *
     *    @category Engineering Functions
     *    @param    float        $x        The value at which to evaluate the function.
     *                                If x is nonnumeric, BESSELI returns the #VALUE! error value.
     *    @param    int        $ord    The order of the Bessel function.
     *                                If ord is not an integer, it is truncated.
     *                                If $ord is nonnumeric, BESSELI returns the #VALUE! error value.
     *                                If $ord < 0, BESSELI returns the #NUM! error value.
     *    @return    float
     */
    public static function BESSELI($x, $ord)
    {
        $x    = (is_null($x)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $ord    = (is_null($ord)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($ord);

        if (is_numeric($x) && is_numeric($ord)) {
            $ord    = floor($ord);
            if ($ord < 0) {
                return PHPExcel_Calculation_Functions::NaN();
            }

            if (abs($x) <= 30) {
                $fResult = $fTerm = pow($x / 2, $ord) / PHPExcel_Calculation_MathTrig::FACT($ord);
                $ordK = 1;
                $fSqrX = ($x * $x) / 4;
                do {
                    $fTerm *= $fSqrX;
                    $fTerm /= ($ordK * ($ordK + $ord));
                    $fResult += $fTerm;
                } while ((abs($fTerm) > 1e-12) && (++$ordK < 100));
            } else {
                $f_2_PI = 2 * M_PI;

                $fXAbs = abs($x);
                $fResult = exp($fXAbs) / sqrt($f_2_PI * $fXAbs);
                if (($ord & 1) && ($x < 0)) {
                    $fResult = -$fResult;
                }
            }

            return (is_nan($fResult)) ? PHPExcel_Calculation_Functions::NaN() : $fResult;
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    /**
     *    BESSELJ.
     *
     *    Returns the Bessel function
     *
     *    Excel Function:
     *        BESSELJ(x,ord)
     *
     *    @category Engineering Functions
     *    @param    float        $x        The value at which to evaluate the function.
     *                                If x is nonnumeric, BESSELJ returns the #VALUE! error value.
     *    @param    int        $ord    The order of the Bessel function. If n is not an integer, it is truncated.
     *                                If $ord is nonnumeric, BESSELJ returns the #VALUE! error value.
     *                                If $ord < 0, BESSELJ returns the #NUM! error value.
     *    @return    float
     */
    public static function BESSELJ($x, $ord)
    {
        $x    = (is_null($x)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $ord    = (is_null($ord)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($ord);

        if (is_numeric($x) && is_numeric($ord)) {
            $ord    = floor($ord);
            if ($ord < 0) {
                return PHPExcel_Calculation_Functions::NaN();
            }

            $fResult = 0;
            if (abs($x) <= 30) {
                $fResult = $fTerm = pow($x / 2, $ord) / PHPExcel_Calculation_MathTrig::FACT($ord);
                $ordK = 1;
                $fSqrX = ($x * $x) / -4;
                do {
                    $fTerm *= $fSqrX;
                    $fTerm /= ($ordK * ($ordK + $ord));
                    $fResult += $fTerm;
                } while ((abs($fTerm) > 1e-12) && (++$ordK < 100));
            } else {
                $f_PI_DIV_2 = M_PI / 2;
                $f_PI_DIV_4 = M_PI / 4;

                $fXAbs = abs($x);
                $fResult = sqrt(M_2DIVPI / $fXAbs) * cos($fXAbs - $ord * $f_PI_DIV_2 - $f_PI_DIV_4);
                if (($ord & 1) && ($x < 0)) {
                    $fResult = -$fResult;
                }
            }

            return (is_nan($fResult)) ? PHPExcel_Calculation_Functions::NaN() : $fResult;
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    private static function besselK0($fNum)
    {
        if ($fNum <= 2) {
            $fNum2 = $fNum * 0.5;
            $y = ($fNum2 * $fNum2);
            $fRet = -log($fNum2) * self::BESSELI($fNum, 0)
                + (-0.577_215_66 + $y * (0.422_784_20 + $y * (0.230_697_56 + $y * (0.348_859_0e-1 + $y * (0.262_698e-2 + $y
                * (0.107_50e-3 + $y * 0.74e-5))))));
        } else {
            $y = 2 / $fNum;
            $fRet = exp(-$fNum) / sqrt($fNum)
                * (1.253_314_14 + $y * (-0.783_235_8e-1 + $y * (0.218_956_8e-1 + $y * (-0.106_244_6e-1 + $y
                * (0.587_872e-2 + $y * (-0.251_540e-2 + $y * 0.532_08e-3))))));
        }

        return $fRet;
    }

    private static function besselK1($fNum)
    {
        if ($fNum <= 2) {
            $fNum2 = $fNum * 0.5;
            $y = ($fNum2 * $fNum2);
            $fRet = log($fNum2) * self::BESSELI($fNum, 1)
                + (1 + $y * (0.154_431_44 + $y * (-0.672_785_79 + $y * (-0.181_568_97 + $y * (-0.191_940_2e-1 + $y
                * (-0.110_404e-2 + $y * (-0.468_6e-4))))))) / $fNum;
        } else {
            $y = 2 / $fNum;
            $fRet = exp(-$fNum) / sqrt($fNum)
                * (1.253_314_14 + $y * (0.234_986_19 + $y * (-0.365_562_0e-1 + $y * (0.150_426_8e-1 + $y * (-0.780_353e-2 + $y
                * (0.325_614e-2 + $y * (-0.682_45e-3)))))));
        }

        return $fRet;
    }

    /**
     *    BESSELK.
     *
     *    Returns the modified Bessel function Kn(x), which is equivalent to the Bessel functions evaluated
     *        for purely imaginary arguments.
     *
     *    Excel Function:
     *        BESSELK(x,ord)
     *
     *    @category Engineering Functions
     *    @param    float        $x        The value at which to evaluate the function.
     *                                If x is nonnumeric, BESSELK returns the #VALUE! error value.
     *    @param    int        $ord    The order of the Bessel function. If n is not an integer, it is truncated.
     *                                If $ord is nonnumeric, BESSELK returns the #VALUE! error value.
     *                                If $ord < 0, BESSELK returns the #NUM! error value.
     *    @return    float
     */
    public static function BESSELK($x, $ord)
    {
        $x        = (is_null($x)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $ord    = (is_null($ord)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($ord);

        if (is_numeric($x) && is_numeric($ord)) {
            if (($ord < 0) || ($x == 0.0)) {
                return PHPExcel_Calculation_Functions::NaN();
            }

            switch (floor($ord)) {
                case 0:
                    return self::besselK0($x);
                case 1:
                    return self::besselK1($x);

                default:
                    $fTox    = 2 / $x;
                    $fBkm    = self::besselK0($x);
                    $fBk    = self::besselK1($x);
                    for ($n = 1; $n < $ord; ++$n) {
                        $fBkp    = $fBkm + $n * $fTox * $fBk;
                        $fBkm    = $fBk;
                        $fBk    = $fBkp;
                    }
            }

            return (is_nan($fBk)) ? PHPExcel_Calculation_Functions::NaN() : $fBk;
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    private static function besselY0($fNum)
    {
        if ($fNum < 8.0) {
            $y = ($fNum * $fNum);
            $f1 = -2_957_821_389.0 + $y * (7_062_834_065.0 + $y * (-512_359_803.6 + $y * (10_879_881.29 + $y * (-86_327.927_57 + $y * 228.462_273_3))));
            $f2 = 40_076_544_269.0 + $y * (745_249_964.8 + $y * (7_189_466.438 + $y * (47_447.264_70 + $y * (226.103_024_4 + $y))));
            $fRet = $f1 / $f2 + 0.636_619_772 * self::BESSELJ($fNum, 0) * log($fNum);
        } else {
            $z = 8.0 / $fNum;
            $y = ($z * $z);
            $xx = $fNum - 0.785_398_164;
            $f1 = 1 + $y * (-0.109_862_862_7e-2 + $y * (0.273_451_040_7e-4 + $y * (-0.207_337_063_9e-5 + $y * 0.209_388_721_1e-6)));
            $f2 = -0.156_249_999_5e-1 + $y * (0.143_048_876_5e-3 + $y * (-0.691_114_765_1e-5 + $y * (0.762_109_516_1e-6 + $y * (-0.934_945_152e-7))));
            $fRet = sqrt(0.636_619_772 / $fNum) * (sin($xx) * $f1 + $z * cos($xx) * $f2);
        }

        return $fRet;
    }

    private static function besselY1($fNum)
    {
        if ($fNum < 8.0) {
            $y = ($fNum * $fNum);
            $f1 = $fNum * (-0.490_060_494_3e13 + $y * (0.127_527_439_0e13 + $y * (-0.515_343_813_9e11 + $y * (0.734_926_455_1e9 + $y
                * (-0.423_792_272_6e7 + $y * 0.851_193_793_5e4)))));
            $f2 = 0.249_958_057_0e14 + $y * (0.424_441_966_4e12 + $y * (0.373_365_036_7e10 + $y * (0.224_590_400_2e8 + $y
                * (0.102_042_605_0e6 + $y * (0.354_963_288_5e3 + $y)))));
            $fRet = $f1 / $f2 + 0.636_619_772 * (self::BESSELJ($fNum, 1) * log($fNum) - 1 / $fNum);
        } else {
            $fRet = sqrt(0.636_619_772 / $fNum) * sin($fNum - 2.356_194_491);
        }

        return $fRet;
    }

    /**
     *    BESSELY.
     *
     *    Returns the Bessel function, which is also called the Weber function or the Neumann function.
     *
     *    Excel Function:
     *        BESSELY(x,ord)
     *
     *    @category Engineering Functions
     *    @param    float        $x        The value at which to evaluate the function.
     *                                If x is nonnumeric, BESSELK returns the #VALUE! error value.
     *    @param    int        $ord    The order of the Bessel function. If n is not an integer, it is truncated.
     *                                If $ord is nonnumeric, BESSELK returns the #VALUE! error value.
     *                                If $ord < 0, BESSELK returns the #NUM! error value.
     *
     *    @return    float
     */
    public static function BESSELY($x, $ord)
    {
        $x        = (is_null($x)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $ord    = (is_null($ord)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($ord);

        if (is_numeric($x) && is_numeric($ord)) {
            if (($ord < 0) || ($x == 0.0)) {
                return PHPExcel_Calculation_Functions::NaN();
            }

            switch (floor($ord)) {
                case 0:
                    return self::besselY0($x);
                case 1:
                    return self::besselY1($x);

                default:
                    $fTox    = 2 / $x;
                    $fBym    = self::besselY0($x);
                    $fBy    = self::besselY1($x);
                    for ($n = 1; $n < $ord; ++$n) {
                        $fByp    = $n * $fTox * $fBy - $fBym;
                        $fBym    = $fBy;
                        $fBy    = $fByp;
                    }
            }

            return (is_nan($fBy)) ? PHPExcel_Calculation_Functions::NaN() : $fBy;
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    /**
     * BINTODEC.
     *
     * Return a binary value as decimal.
     *
     * Excel Function:
     *        BIN2DEC(x)
     *
     * @category Engineering Functions
     * @param    string        $x        The binary number (as a string) that you want to convert. The number
     *                                cannot contain more than 10 characters (10 bits). The most significant
     *                                bit of number is the sign bit. The remaining 9 bits are magnitude bits.
     *                                Negative numbers are represented using two's-complement notation.
     *                                If number is not a valid binary number, or if number contains more than
     *                                10 characters (10 bits), BIN2DEC returns the #NUM! error value.
     * @return    string
     */
    public static function BINTODEC($x)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_GNUMERIC) {
            $x = floor($x);
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[01]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) > 10) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) == 10) {
            //    Two's Complement
            $x = substr($x, -9);

            return '-' . (512 - bindec($x));
        }

        return bindec($x);
    }

    /**
     * BINTOHEX.
     *
     * Return a binary value as hex.
     *
     * Excel Function:
     *        BIN2HEX(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x        The binary number (as a string) that you want to convert. The number
     *                                cannot contain more than 10 characters (10 bits). The most significant
     *                                bit of number is the sign bit. The remaining 9 bits are magnitude bits.
     *                                Negative numbers are represented using two's-complement notation.
     *                                If number is not a valid binary number, or if number contains more than
     *                                10 characters (10 bits), BIN2HEX returns the #NUM! error value.
     * @param    int        $places    The number of characters to use. If places is omitted, BIN2HEX uses the
     *                                minimum number of characters necessary. Places is useful for padding the
     *                                return value with leading 0s (zeros).
     *                                If places is not an integer, it is truncated.
     *                                If places is nonnumeric, BIN2HEX returns the #VALUE! error value.
     *                                If places is negative, BIN2HEX returns the #NUM! error value.
     * @return    string
     */
    public static function BINTOHEX($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_GNUMERIC) {
            $x = floor($x);
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[01]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) > 10) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) == 10) {
            //    Two's Complement
            return str_repeat('F', 8) . substr(strtoupper(dechex(bindec(substr($x, -9)))), -2);
        }
        $hexVal = (string) strtoupper(dechex(bindec($x)));

        return self::nbrConversionFormat($hexVal, $places);
    }

    /**
     * BINTOOCT.
     *
     * Return a binary value as octal.
     *
     * Excel Function:
     *        BIN2OCT(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x        The binary number (as a string) that you want to convert. The number
     *                                cannot contain more than 10 characters (10 bits). The most significant
     *                                bit of number is the sign bit. The remaining 9 bits are magnitude bits.
     *                                Negative numbers are represented using two's-complement notation.
     *                                If number is not a valid binary number, or if number contains more than
     *                                10 characters (10 bits), BIN2OCT returns the #NUM! error value.
     * @param    int        $places    The number of characters to use. If places is omitted, BIN2OCT uses the
     *                                minimum number of characters necessary. Places is useful for padding the
     *                                return value with leading 0s (zeros).
     *                                If places is not an integer, it is truncated.
     *                                If places is nonnumeric, BIN2OCT returns the #VALUE! error value.
     *                                If places is negative, BIN2OCT returns the #NUM! error value.
     * @return    string
     */
    public static function BINTOOCT($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_GNUMERIC) {
            $x = floor($x);
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[01]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) > 10) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (strlen($x) == 10) {
            //    Two's Complement
            return str_repeat('7', 7) . substr(strtoupper(decoct(bindec(substr($x, -9)))), -3);
        }
        $octVal = (string) decoct(bindec($x));

        return self::nbrConversionFormat($octVal, $places);
    }

    /**
     * DECTOBIN.
     *
     * Return a decimal value as binary.
     *
     * Excel Function:
     *        DEC2BIN(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x        The decimal integer you want to convert. If number is negative,
     *                                valid place values are ignored and DEC2BIN returns a 10-character
     *                                (10-bit) binary number in which the most significant bit is the sign
     *                                bit. The remaining 9 bits are magnitude bits. Negative numbers are
     *                                represented using two's-complement notation.
     *                                If number < -512 or if number > 511, DEC2BIN returns the #NUM! error
     *                                value.
     *                                If number is nonnumeric, DEC2BIN returns the #VALUE! error value.
     *                                If DEC2BIN requires more than places characters, it returns the #NUM!
     *                                error value.
     * @param    int        $places    The number of characters to use. If places is omitted, DEC2BIN uses
     *                                the minimum number of characters necessary. Places is useful for
     *                                padding the return value with leading 0s (zeros).
     *                                If places is not an integer, it is truncated.
     *                                If places is nonnumeric, DEC2BIN returns the #VALUE! error value.
     *                                If places is zero or negative, DEC2BIN returns the #NUM! error value.
     * @return    string
     */
    public static function DECTOBIN($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[-0123456789.]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) floor($x);
        $r = decbin($x);
        if (strlen($r) == 32) {
            //    Two's Complement
            $r = substr($r, -10);
        } elseif (strlen($r) > 11) {
            return PHPExcel_Calculation_Functions::NaN();
        }

        return self::nbrConversionFormat($r, $places);
    }

    /**
     * DECTOHEX.
     *
     * Return a decimal value as hex.
     *
     * Excel Function:
     *        DEC2HEX(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x        The decimal integer you want to convert. If number is negative,
     *                                places is ignored and DEC2HEX returns a 10-character (40-bit)
     *                                hexadecimal number in which the most significant bit is the sign
     *                                bit. The remaining 39 bits are magnitude bits. Negative numbers
     *                                are represented using two's-complement notation.
     *                                If number < -549,755,813,888 or if number > 549,755,813,887,
     *                                DEC2HEX returns the #NUM! error value.
     *                                If number is nonnumeric, DEC2HEX returns the #VALUE! error value.
     *                                If DEC2HEX requires more than places characters, it returns the
     *                                #NUM! error value.
     * @param    int        $places    The number of characters to use. If places is omitted, DEC2HEX uses
     *                                the minimum number of characters necessary. Places is useful for
     *                                padding the return value with leading 0s (zeros).
     *                                If places is not an integer, it is truncated.
     *                                If places is nonnumeric, DEC2HEX returns the #VALUE! error value.
     *                                If places is zero or negative, DEC2HEX returns the #NUM! error value.
     * @return    string
     */
    public static function DECTOHEX($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[-0123456789.]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) floor($x);
        $r = strtoupper(dechex($x));
        if (strlen($r) == 8) {
            //    Two's Complement
            $r = 'FF' . $r;
        }

        return self::nbrConversionFormat($r, $places);
    }

    /**
     * DECTOOCT.
     *
     * Return an decimal value as octal.
     *
     * Excel Function:
     *        DEC2OCT(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x        The decimal integer you want to convert. If number is negative,
     *                                places is ignored and DEC2OCT returns a 10-character (30-bit)
     *                                octal number in which the most significant bit is the sign bit.
     *                                The remaining 29 bits are magnitude bits. Negative numbers are
     *                                represented using two's-complement notation.
     *                                If number < -536,870,912 or if number > 536,870,911, DEC2OCT
     *                                returns the #NUM! error value.
     *                                If number is nonnumeric, DEC2OCT returns the #VALUE! error value.
     *                                If DEC2OCT requires more than places characters, it returns the
     *                                #NUM! error value.
     * @param    int        $places    The number of characters to use. If places is omitted, DEC2OCT uses
     *                                the minimum number of characters necessary. Places is useful for
     *                                padding the return value with leading 0s (zeros).
     *                                If places is not an integer, it is truncated.
     *                                If places is nonnumeric, DEC2OCT returns the #VALUE! error value.
     *                                If places is zero or negative, DEC2OCT returns the #NUM! error value.
     * @return    string
     */
    public static function DECTOOCT($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            if (PHPExcel_Calculation_Functions::getCompatibilityMode() == PHPExcel_Calculation_Functions::COMPATIBILITY_OPENOFFICE) {
                $x = (int) $x;
            } else {
                return PHPExcel_Calculation_Functions::VALUE();
            }
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[-0123456789.]/', $x, $out)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) floor($x);
        $r = decoct($x);
        if (strlen($r) == 11) {
            //    Two's Complement
            $r = substr($r, -10);
        }

        return self::nbrConversionFormat($r, $places);
    }

    /**
     * HEXTOBIN.
     *
     * Return a hex value as binary.
     *
     * Excel Function:
     *        HEX2BIN(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x            the hexadecimal number you want to convert. Number cannot
     *                                    contain more than 10 characters. The most significant bit of
     *                                    number is the sign bit (40th bit from the right). The remaining
     *                                    9 bits are magnitude bits. Negative numbers are represented
     *                                    using two's-complement notation.
     *                                    If number is negative, HEX2BIN ignores places and returns a
     *                                    10-character binary number.
     *                                    If number is negative, it cannot be less than FFFFFFFE00, and
     *                                    if number is positive, it cannot be greater than 1FF.
     *                                    If number is not a valid hexadecimal number, HEX2BIN returns
     *                                    the #NUM! error value.
     *                                    If HEX2BIN requires more than places characters, it returns
     *                                    the #NUM! error value.
     * @param    int        $places        The number of characters to use. If places is omitted,
     *                                    HEX2BIN uses the minimum number of characters necessary. Places
     *                                    is useful for padding the return value with leading 0s (zeros).
     *                                    If places is not an integer, it is truncated.
     *                                    If places is nonnumeric, HEX2BIN returns the #VALUE! error value.
     *                                    If places is negative, HEX2BIN returns the #NUM! error value.
     * @return    string
     */
    public static function HEXTOBIN($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/', strtoupper($x), $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        $binVal = decbin(hexdec($x));

        return substr(self::nbrConversionFormat($binVal, $places), -10);
    }

    /**
     * HEXTODEC.
     *
     * Return a hex value as decimal.
     *
     * Excel Function:
     *        HEX2DEC(x)
     *
     * @category Engineering Functions
     * @param    string        $x        The hexadecimal number you want to convert. This number cannot
     *                                contain more than 10 characters (40 bits). The most significant
     *                                bit of number is the sign bit. The remaining 39 bits are magnitude
     *                                bits. Negative numbers are represented using two's-complement
     *                                notation.
     *                                If number is not a valid hexadecimal number, HEX2DEC returns the
     *                                #NUM! error value.
     * @return    string
     */
    public static function HEXTODEC($x)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/', strtoupper($x), $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }

        return hexdec($x);
    }

    /**
     * HEXTOOCT.
     *
     * Return a hex value as octal.
     *
     * Excel Function:
     *        HEX2OCT(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x            The hexadecimal number you want to convert. Number cannot
     *                                    contain more than 10 characters. The most significant bit of
     *                                    number is the sign bit. The remaining 39 bits are magnitude
     *                                    bits. Negative numbers are represented using two's-complement
     *                                    notation.
     *                                    If number is negative, HEX2OCT ignores places and returns a
     *                                    10-character octal number.
     *                                    If number is negative, it cannot be less than FFE0000000, and
     *                                    if number is positive, it cannot be greater than 1FFFFFFF.
     *                                    If number is not a valid hexadecimal number, HEX2OCT returns
     *                                    the #NUM! error value.
     *                                    If HEX2OCT requires more than places characters, it returns
     *                                    the #NUM! error value.
     * @param    int        $places        The number of characters to use. If places is omitted, HEX2OCT
     *                                    uses the minimum number of characters necessary. Places is
     *                                    useful for padding the return value with leading 0s (zeros).
     *                                    If places is not an integer, it is truncated.
     *                                    If places is nonnumeric, HEX2OCT returns the #VALUE! error
     *                                    value.
     *                                    If places is negative, HEX2OCT returns the #NUM! error value.
     * @return    string
     */
    public static function HEXTOOCT($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/', strtoupper($x), $out)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        $octVal = decoct(hexdec($x));

        return self::nbrConversionFormat($octVal, $places);
    }    //    function HEXTOOCT()

    /**
     * OCTTOBIN.
     *
     * Return an octal value as binary.
     *
     * Excel Function:
     *        OCT2BIN(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x            The octal number you want to convert. Number may not
     *                                    contain more than 10 characters. The most significant
     *                                    bit of number is the sign bit. The remaining 29 bits
     *                                    are magnitude bits. Negative numbers are represented
     *                                    using two's-complement notation.
     *                                    If number is negative, OCT2BIN ignores places and returns
     *                                    a 10-character binary number.
     *                                    If number is negative, it cannot be less than 7777777000,
     *                                    and if number is positive, it cannot be greater than 777.
     *                                    If number is not a valid octal number, OCT2BIN returns
     *                                    the #NUM! error value.
     *                                    If OCT2BIN requires more than places characters, it
     *                                    returns the #NUM! error value.
     * @param    int        $places        The number of characters to use. If places is omitted,
     *                                    OCT2BIN uses the minimum number of characters necessary.
     *                                    Places is useful for padding the return value with
     *                                    leading 0s (zeros).
     *                                    If places is not an integer, it is truncated.
     *                                    If places is nonnumeric, OCT2BIN returns the #VALUE!
     *                                    error value.
     *                                    If places is negative, OCT2BIN returns the #NUM! error
     *                                    value.
     * @return    string
     */
    public static function OCTTOBIN($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (preg_match_all('/[01234567]/', $x, $out) != strlen($x)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        $r = decbin(octdec($x));

        return self::nbrConversionFormat($r, $places);
    }

    /**
     * OCTTODEC.
     *
     * Return an octal value as decimal.
     *
     * Excel Function:
     *        OCT2DEC(x)
     *
     * @category Engineering Functions
     * @param    string        $x        The octal number you want to convert. Number may not contain
     *                                more than 10 octal characters (30 bits). The most significant
     *                                bit of number is the sign bit. The remaining 29 bits are
     *                                magnitude bits. Negative numbers are represented using
     *                                two's-complement notation.
     *                                If number is not a valid octal number, OCT2DEC returns the
     *                                #NUM! error value.
     * @return    string
     */
    public static function OCTTODEC($x)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (preg_match_all('/[01234567]/', $x, $out) != strlen($x)) {
            return PHPExcel_Calculation_Functions::NaN();
        }

        return octdec($x);
    }

    /**
     * OCTTOHEX.
     *
     * Return an octal value as hex.
     *
     * Excel Function:
     *        OCT2HEX(x[,places])
     *
     * @category Engineering Functions
     * @param    string        $x            The octal number you want to convert. Number may not contain
     *                                    more than 10 octal characters (30 bits). The most significant
     *                                    bit of number is the sign bit. The remaining 29 bits are
     *                                    magnitude bits. Negative numbers are represented using
     *                                    two's-complement notation.
     *                                    If number is negative, OCT2HEX ignores places and returns a
     *                                    10-character hexadecimal number.
     *                                    If number is not a valid octal number, OCT2HEX returns the
     *                                    #NUM! error value.
     *                                    If OCT2HEX requires more than places characters, it returns
     *                                    the #NUM! error value.
     * @param    int        $places        The number of characters to use. If places is omitted, OCT2HEX
     *                                    uses the minimum number of characters necessary. Places is useful
     *                                    for padding the return value with leading 0s (zeros).
     *                                    If places is not an integer, it is truncated.
     *                                    If places is nonnumeric, OCT2HEX returns the #VALUE! error value.
     *                                    If places is negative, OCT2HEX returns the #NUM! error value.
     * @return    string
     */
    public static function OCTTOHEX($x, $places = null)
    {
        $x    = PHPExcel_Calculation_Functions::flattenSingleValue($x);
        $places    = PHPExcel_Calculation_Functions::flattenSingleValue($places);

        if (is_bool($x)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $x = (string) $x;
        if (preg_match_all('/[01234567]/', $x, $out) != strlen($x)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        $hexVal = strtoupper(dechex(octdec($x)));

        return self::nbrConversionFormat($hexVal, $places);
    }

    /**
     * COMPLEX.
     *
     * Converts real and imaginary coefficients into a complex number of the form x + yi or x + yj.
     *
     * Excel Function:
     *        COMPLEX(realNumber,imaginary[,places])
     *
     * @category Engineering Functions
     * @param    float        $realNumber        the real coefficient of the complex number
     * @param    float        $imaginary        the imaginary coefficient of the complex number
     * @param    string        $suffix            The suffix for the imaginary component of the complex number.
     *                                        If omitted, the suffix is assumed to be "i".
     * @return    string
     */
    public static function COMPLEX($realNumber = 0.0, $imaginary = 0.0, $suffix = 'i')
    {
        $realNumber = (is_null($realNumber)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($realNumber);
        $imaginary  = (is_null($imaginary)) ? 0.0 : PHPExcel_Calculation_Functions::flattenSingleValue($imaginary);
        $suffix     = (is_null($suffix)) ? 'i' : PHPExcel_Calculation_Functions::flattenSingleValue($suffix);

        if ((is_numeric($realNumber) && is_numeric($imaginary))
            && (($suffix == 'i') || ($suffix == 'j') || ($suffix == ''))) {
            $realNumber    = (float) $realNumber;
            $imaginary    = (float) $imaginary;

            if ($suffix == '') {
                $suffix = 'i';
            }
            if ($realNumber == 0.0) {
                if ($imaginary == 0.0) {
                    return (string) '0';
                }
                if ($imaginary == 1.0) {
                    return (string) $suffix;
                }
                if ($imaginary == -1.0) {
                    return (string) '-' . $suffix;
                }

                return (string) $imaginary . $suffix;
            }
            if ($imaginary == 0.0) {
                return (string) $realNumber;
            }
            if ($imaginary == 1.0) {
                return (string) $realNumber . '+' . $suffix;
            }
            if ($imaginary == -1.0) {
                return (string) $realNumber . '-' . $suffix;
            }
            if ($imaginary > 0) {
                $imaginary = (string) '+' . $imaginary;
            }

            return (string) $realNumber . $imaginary . $suffix;
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    /**
     * IMAGINARY.
     *
     * Returns the imaginary coefficient of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMAGINARY(complexNumber)
     *
     * @category Engineering Functions
     * @param    string        $complexNumber    the complex number for which you want the imaginary
     *                                         coefficient
     * @return    float
     */
    public static function IMAGINARY($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        return $parsedComplex['imaginary'];
    }

    /**
     * IMREAL.
     *
     * Returns the real coefficient of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMREAL(complexNumber)
     *
     * @category Engineering Functions
     * @param    string        $complexNumber    the complex number for which you want the real coefficient
     * @return    float
     */
    public static function IMREAL($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        return $parsedComplex['real'];
    }

    /**
     * IMABS.
     *
     * Returns the absolute value (modulus) of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMABS(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the absolute value
     * @return    float
     */
    public static function IMABS($complexNumber)
    {
        $complexNumber = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        return sqrt(
            ($parsedComplex['real'] * $parsedComplex['real'])
            + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']),
        );
    }

    /**
     * IMARGUMENT.
     *
     * Returns the argument theta of a complex number, i.e. the angle in radians from the real
     * axis to the representation of the number in polar coordinates.
     *
     * Excel Function:
     *        IMARGUMENT(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the argument theta
     * @return    float
     */
    public static function IMARGUMENT($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if ($parsedComplex['real'] == 0.0) {
            if ($parsedComplex['imaginary'] == 0.0) {
                return 0.0;
            }
            if ($parsedComplex['imaginary'] < 0.0) {
                return M_PI / -2;
            }

            return M_PI / 2;

        } elseif ($parsedComplex['real'] > 0.0) {
            return atan($parsedComplex['imaginary'] / $parsedComplex['real']);
        } elseif ($parsedComplex['imaginary'] < 0.0) {
            return 0 - (M_PI - atan(abs($parsedComplex['imaginary']) / abs($parsedComplex['real'])));
        }

        return M_PI - atan($parsedComplex['imaginary'] / abs($parsedComplex['real']));

    }

    /**
     * IMCONJUGATE.
     *
     * Returns the complex conjugate of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMCONJUGATE(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the conjugate
     * @return    string
     */
    public static function IMCONJUGATE($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if ($parsedComplex['imaginary'] == 0.0) {
            return $parsedComplex['real'];
        }

        return self::cleanComplex(
            self::COMPLEX(
                $parsedComplex['real'],
                0 - $parsedComplex['imaginary'],
                $parsedComplex['suffix'],
            ),
        );

    }

    /**
     * IMCOS.
     *
     * Returns the cosine of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMCOS(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the cosine
     * @return    string|float
     */
    public static function IMCOS($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if ($parsedComplex['imaginary'] == 0.0) {
            return cos($parsedComplex['real']);
        }

        return self::IMCONJUGATE(
            self::COMPLEX(
                cos($parsedComplex['real']) * cosh($parsedComplex['imaginary']),
                sin($parsedComplex['real']) * sinh($parsedComplex['imaginary']),
                $parsedComplex['suffix'],
            ),
        );

    }

    /**
     * IMSIN.
     *
     * Returns the sine of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMSIN(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the sine
     * @return    string|float
     */
    public static function IMSIN($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if ($parsedComplex['imaginary'] == 0.0) {
            return sin($parsedComplex['real']);
        }

        return self::COMPLEX(
            sin($parsedComplex['real']) * cosh($parsedComplex['imaginary']),
            cos($parsedComplex['real']) * sinh($parsedComplex['imaginary']),
            $parsedComplex['suffix'],
        );

    }

    /**
     * IMSQRT.
     *
     * Returns the square root of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMSQRT(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the square root
     * @return    string
     */
    public static function IMSQRT($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        $theta = self::IMARGUMENT($complexNumber);
        $d1 = cos($theta / 2);
        $d2 = sin($theta / 2);
        $r = sqrt(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));

        if ($parsedComplex['suffix'] == '') {
            return self::COMPLEX($d1 * $r, $d2 * $r);
        }

        return self::COMPLEX($d1 * $r, $d2 * $r, $parsedComplex['suffix']);

    }

    /**
     * IMLN.
     *
     * Returns the natural logarithm of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMLN(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the natural logarithm
     * @return    string
     */
    public static function IMLN($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return PHPExcel_Calculation_Functions::NaN();
        }

        $logR = log(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));
        $t = self::IMARGUMENT($complexNumber);

        if ($parsedComplex['suffix'] == '') {
            return self::COMPLEX($logR, $t);
        }

        return self::COMPLEX($logR, $t, $parsedComplex['suffix']);

    }

    /**
     * IMLOG10.
     *
     * Returns the common logarithm (base 10) of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMLOG10(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the common logarithm
     * @return    string
     */
    public static function IMLOG10($complexNumber)
    {
        $complexNumber = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return log10($parsedComplex['real']);
        }

        return self::IMPRODUCT(log10(EULER), self::IMLN($complexNumber));
    }

    /**
     * IMLOG2.
     *
     * Returns the base-2 logarithm of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMLOG2(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the base-2 logarithm
     * @return    string
     */
    public static function IMLOG2($complexNumber)
    {
        $complexNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return log($parsedComplex['real'], 2);
        }

        return self::IMPRODUCT(log(EULER, 2), self::IMLN($complexNumber));
    }

    /**
     * IMEXP.
     *
     * Returns the exponential of a complex number in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMEXP(complexNumber)
     *
     * @param    string        $complexNumber    the complex number for which you want the exponential
     * @return    string
     */
    public static function IMEXP($complexNumber)
    {
        $complexNumber = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);

        $parsedComplex = self::parseComplex($complexNumber);

        if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
            return '1';
        }

        $e = exp($parsedComplex['real']);
        $eX = $e * cos($parsedComplex['imaginary']);
        $eY = $e * sin($parsedComplex['imaginary']);

        if ($parsedComplex['suffix'] == '') {
            return self::COMPLEX($eX, $eY);
        }

        return self::COMPLEX($eX, $eY, $parsedComplex['suffix']);

    }

    /**
     * IMPOWER.
     *
     * Returns a complex number in x + yi or x + yj text format raised to a power.
     *
     * Excel Function:
     *        IMPOWER(complexNumber,realNumber)
     *
     * @param    string        $complexNumber    the complex number you want to raise to a power
     * @param    float        $realNumber        the power to which you want to raise the complex number
     * @return    string
     */
    public static function IMPOWER($complexNumber, $realNumber)
    {
        $complexNumber = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber);
        $realNumber    = PHPExcel_Calculation_Functions::flattenSingleValue($realNumber);

        if (!is_numeric($realNumber)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }

        $parsedComplex = self::parseComplex($complexNumber);

        $r = sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']));
        $rPower = pow($r, $realNumber);
        $theta = self::IMARGUMENT($complexNumber) * $realNumber;
        if ($theta == 0) {
            return 1;
        }
        if ($parsedComplex['imaginary'] == 0.0) {
            return self::COMPLEX($rPower * cos($theta), $rPower * sin($theta), $parsedComplex['suffix']);
        }

        return self::COMPLEX($rPower * cos($theta), $rPower * sin($theta), $parsedComplex['suffix']);

    }

    /**
     * IMDIV.
     *
     * Returns the quotient of two complex numbers in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMDIV(complexDividend,complexDivisor)
     *
     * @param    string        $complexDividend    the complex numerator or dividend
     * @param    string        $complexDivisor        the complex denominator or divisor
     * @return    string
     */
    public static function IMDIV($complexDividend, $complexDivisor)
    {
        $complexDividend    = PHPExcel_Calculation_Functions::flattenSingleValue($complexDividend);
        $complexDivisor    = PHPExcel_Calculation_Functions::flattenSingleValue($complexDivisor);

        $parsedComplexDividend = self::parseComplex($complexDividend);
        $parsedComplexDivisor = self::parseComplex($complexDivisor);

        if (($parsedComplexDividend['suffix'] != '') && ($parsedComplexDivisor['suffix'] != '')
            && ($parsedComplexDividend['suffix'] != $parsedComplexDivisor['suffix'])) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (($parsedComplexDividend['suffix'] != '') && ($parsedComplexDivisor['suffix'] == '')) {
            $parsedComplexDivisor['suffix'] = $parsedComplexDividend['suffix'];
        }

        $d1 = ($parsedComplexDividend['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['imaginary']);
        $d2 = ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['real']) - ($parsedComplexDividend['real'] * $parsedComplexDivisor['imaginary']);
        $d3 = ($parsedComplexDivisor['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDivisor['imaginary'] * $parsedComplexDivisor['imaginary']);

        $r = $d1 / $d3;
        $i = $d2 / $d3;

        if ($i > 0.0) {
            return self::cleanComplex($r . '+' . $i . $parsedComplexDivisor['suffix']);
        }
        if ($i < 0.0) {
            return self::cleanComplex($r . $i . $parsedComplexDivisor['suffix']);
        }

        return $r;

    }

    /**
     * IMSUB.
     *
     * Returns the difference of two complex numbers in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMSUB(complexNumber1,complexNumber2)
     *
     * @param    string        $complexNumber1        the complex number from which to subtract complexNumber2
     * @param    string        $complexNumber2        the complex number to subtract from complexNumber1
     * @return    string
     */
    public static function IMSUB($complexNumber1, $complexNumber2)
    {
        $complexNumber1    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber1);
        $complexNumber2    = PHPExcel_Calculation_Functions::flattenSingleValue($complexNumber2);

        $parsedComplex1 = self::parseComplex($complexNumber1);
        $parsedComplex2 = self::parseComplex($complexNumber2);

        if ((($parsedComplex1['suffix'] != '') && ($parsedComplex2['suffix'] != ''))
            && ($parsedComplex1['suffix'] != $parsedComplex2['suffix'])) {
            return PHPExcel_Calculation_Functions::NaN();
        }
        if (($parsedComplex1['suffix'] == '') && ($parsedComplex2['suffix'] != '')) {
            $parsedComplex1['suffix'] = $parsedComplex2['suffix'];
        }

        $d1 = $parsedComplex1['real'] - $parsedComplex2['real'];
        $d2 = $parsedComplex1['imaginary'] - $parsedComplex2['imaginary'];

        return self::COMPLEX($d1, $d2, $parsedComplex1['suffix']);
    }

    /**
     * IMSUM.
     *
     * Returns the sum of two or more complex numbers in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMSUM(complexNumber[,complexNumber[,...]])
     *
     * @return    string
     */
    public static function IMSUM()
    {
        // Return value
        $returnValue = self::parseComplex('0');
        $activeSuffix = '';

        // Loop through the arguments
        $aArgs = PHPExcel_Calculation_Functions::flattenArray(func_get_args());
        foreach ($aArgs as $arg) {
            $parsedComplex = self::parseComplex($arg);

            if ($activeSuffix == '') {
                $activeSuffix = $parsedComplex['suffix'];
            } elseif (($parsedComplex['suffix'] != '') && ($activeSuffix != $parsedComplex['suffix'])) {
                return PHPExcel_Calculation_Functions::VALUE();
            }

            $returnValue['real'] += $parsedComplex['real'];
            $returnValue['imaginary'] += $parsedComplex['imaginary'];
        }

        if ($returnValue['imaginary'] == 0.0) {
            $activeSuffix = '';
        }

        return self::COMPLEX($returnValue['real'], $returnValue['imaginary'], $activeSuffix);
    }

    /**
     * IMPRODUCT.
     *
     * Returns the product of two or more complex numbers in x + yi or x + yj text format.
     *
     * Excel Function:
     *        IMPRODUCT(complexNumber[,complexNumber[,...]])
     *
     * @return    string
     */
    public static function IMPRODUCT()
    {
        // Return value
        $returnValue = self::parseComplex('1');
        $activeSuffix = '';

        // Loop through the arguments
        $aArgs = PHPExcel_Calculation_Functions::flattenArray(func_get_args());
        foreach ($aArgs as $arg) {
            $parsedComplex = self::parseComplex($arg);

            $workValue = $returnValue;
            if (($parsedComplex['suffix'] != '') && ($activeSuffix == '')) {
                $activeSuffix = $parsedComplex['suffix'];
            } elseif (($parsedComplex['suffix'] != '') && ($activeSuffix != $parsedComplex['suffix'])) {
                return PHPExcel_Calculation_Functions::NaN();
            }
            $returnValue['real'] = ($workValue['real'] * $parsedComplex['real']) - ($workValue['imaginary'] * $parsedComplex['imaginary']);
            $returnValue['imaginary'] = ($workValue['real'] * $parsedComplex['imaginary']) + ($workValue['imaginary'] * $parsedComplex['real']);
        }

        if ($returnValue['imaginary'] == 0.0) {
            $activeSuffix = '';
        }

        return self::COMPLEX($returnValue['real'], $returnValue['imaginary'], $activeSuffix);
    }

    /**
     *    DELTA.
     *
     *    Tests whether two values are equal. Returns 1 if number1 = number2; returns 0 otherwise.
     *    Use this function to filter a set of values. For example, by summing several DELTA
     *    functions you calculate the count of equal pairs. This function is also known as the
     *    Kronecker Delta function.
     *
     *    Excel Function:
     *        DELTA(a[,b])
     *
     *    @param    float        $a    the first number
     *    @param    float        $b    The second number. If omitted, b is assumed to be zero.
     *    @return    int
     */
    public static function DELTA($a, $b = 0)
    {
        $a = PHPExcel_Calculation_Functions::flattenSingleValue($a);
        $b = PHPExcel_Calculation_Functions::flattenSingleValue($b);

        return (int) ($a == $b);
    }

    /**
     *    GESTEP.
     *
     *    Excel Function:
     *        GESTEP(number[,step])
     *
     *    Returns 1 if number >= step; returns 0 (zero) otherwise
     *    Use this function to filter a set of values. For example, by summing several GESTEP
     *    functions you calculate the count of values that exceed a threshold.
     *
     *    @param    float        $number        the value to test against step
     *    @param    float        $step        The threshold value.
     *                                    If you omit a value for step, GESTEP uses zero.
     *    @return    int
     */
    public static function GESTEP($number, $step = 0)
    {
        $number    = PHPExcel_Calculation_Functions::flattenSingleValue($number);
        $step    = PHPExcel_Calculation_Functions::flattenSingleValue($step);

        return (int) ($number >= $step);
    }

    //
    //    Private method to calculate the erf value
    //
    private static $twoSqrtPi = 1.128_379_167_095_512_574;

    public static function erfVal($x)
    {
        if (abs($x) > 2.2) {
            return 1 - self::erfcVal($x);
        }
        $sum = $term = $x;
        $xsqr = ($x * $x);
        $j = 1;
        do {
            $term *= $xsqr / $j;
            $sum -= $term / (2 * $j + 1);
            ++$j;
            $term *= $xsqr / $j;
            $sum += $term / (2 * $j + 1);
            ++$j;
            if ($sum == 0.0) {
                break;
            }
        } while (abs($term / $sum) > PRECISION);

        return self::$twoSqrtPi * $sum;
    }

    /**
     *    ERF.
     *
     *    Returns the error function integrated between the lower and upper bound arguments.
     *
     *    Note: In Excel 2007 or earlier, if you input a negative value for the upper or lower bound arguments,
     *            the function would return a #NUM! error. However, in Excel 2010, the function algorithm was
     *            improved, so that it can now calculate the function for both positive and negative ranges.
     *            PHPExcel follows Excel 2010 behaviour, and accepts nagative arguments.
     *
     *    Excel Function:
     *        ERF(lower[,upper])
     *
     *    @param    float        $lower    lower bound for integrating ERF
     *    @param    float        $upper    upper bound for integrating ERF.
     *                                If omitted, ERF integrates between zero and lower_limit
     *    @return    float
     */
    public static function ERF($lower, $upper = null)
    {
        $lower    = PHPExcel_Calculation_Functions::flattenSingleValue($lower);
        $upper    = PHPExcel_Calculation_Functions::flattenSingleValue($upper);

        if (is_numeric($lower)) {
            if (is_null($upper)) {
                return self::erfVal($lower);
            }
            if (is_numeric($upper)) {
                return self::erfVal($upper) - self::erfVal($lower);
            }
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    //
    //    Private method to calculate the erfc value
    //
    private static $oneSqrtPi = 0.564_189_583_547_756_287;

    private static function erfcVal($x)
    {
        if (abs($x) < 2.2) {
            return 1 - self::erfVal($x);
        }
        if ($x < 0) {
            return 2 - self::ERFC(-$x);
        }
        $a = $n = 1;
        $b = $c = $x;
        $d = ($x * $x) + 0.5;
        $q1 = $q2 = $b / $d;
        $t = 0;
        do {
            $t = $a * $n + $b * $x;
            $a = $b;
            $b = $t;
            $t = $c * $n + $d * $x;
            $c = $d;
            $d = $t;
            $n += 0.5;
            $q1 = $q2;
            $q2 = $b / $d;
        } while ((abs($q1 - $q2) / $q2) > PRECISION);

        return self::$oneSqrtPi * exp(-$x * $x) * $q2;
    }

    /**
     *    ERFC.
     *
     *    Returns the complementary ERF function integrated between x and infinity
     *
     *    Note: In Excel 2007 or earlier, if you input a negative value for the lower bound argument,
     *        the function would return a #NUM! error. However, in Excel 2010, the function algorithm was
     *        improved, so that it can now calculate the function for both positive and negative x values.
     *            PHPExcel follows Excel 2010 behaviour, and accepts nagative arguments.
     *
     *    Excel Function:
     *        ERFC(x)
     *
     *    @param    float    $x    The lower bound for integrating ERFC
     *    @return    float
     */
    public static function ERFC($x)
    {
        $x = PHPExcel_Calculation_Functions::flattenSingleValue($x);

        if (is_numeric($x)) {
            return self::erfcVal($x);
        }

        return PHPExcel_Calculation_Functions::VALUE();
    }

    /**
     *    getConversionGroups
     *    Returns a list of the different conversion groups for UOM conversions.
     *
     *    @return    array
     */
    public static function getConversionGroups()
    {
        $conversionGroups = [];
        foreach (self::$conversionUnits as $conversionUnit) {
            $conversionGroups[] = $conversionUnit['Group'];
        }

        return array_merge(array_unique($conversionGroups));
    }

    /**
     *    getConversionGroupUnits
     *    Returns an array of units of measure, for a specified conversion group, or for all groups.
     *
     *    @param    string    $group    The group whose units of measure you want to retrieve
     *    @return    array
     */
    public static function getConversionGroupUnits($group = null)
    {
        $conversionGroups = [];
        foreach (self::$conversionUnits as $conversionUnit => $conversionGroup) {
            if (is_null($group) || ($conversionGroup['Group'] == $group)) {
                $conversionGroups[$conversionGroup['Group']][] = $conversionUnit;
            }
        }

        return $conversionGroups;
    }

    /**
     *    getConversionGroupUnitDetails.
     *
     *    @param    string    $group    The group whose units of measure you want to retrieve
     *    @return    array
     */
    public static function getConversionGroupUnitDetails($group = null)
    {
        $conversionGroups = [];
        foreach (self::$conversionUnits as $conversionUnit => $conversionGroup) {
            if (is_null($group) || ($conversionGroup['Group'] == $group)) {
                $conversionGroups[$conversionGroup['Group']][] = [
                    'unit'        => $conversionUnit,
                    'description' => $conversionGroup['Unit Name'],
                ];
            }
        }

        return $conversionGroups;
    }

    /**
     *    getConversionMultipliers
     *    Returns an array of the Multiplier prefixes that can be used with Units of Measure in CONVERTUOM().
     *
     *    @return    array of mixed
     */
    public static function getConversionMultipliers()
    {
        return self::$conversionMultipliers;
    }

    /**
     *    CONVERTUOM.
     *
     *    Converts a number from one measurement system to another.
     *    For example, CONVERT can translate a table of distances in miles to a table of distances
     *    in kilometers.
     *
     *    Excel Function:
     *        CONVERT(value,fromUOM,toUOM)
     *
     *    @param    float        $value        the value in fromUOM to convert
     *    @param    string        $fromUOM    the units for value
     *    @param    string        $toUOM        the units for the result
     *
     *    @return    float
     */
    public static function CONVERTUOM($value, $fromUOM, $toUOM)
    {
        $value   = PHPExcel_Calculation_Functions::flattenSingleValue($value);
        $fromUOM = PHPExcel_Calculation_Functions::flattenSingleValue($fromUOM);
        $toUOM   = PHPExcel_Calculation_Functions::flattenSingleValue($toUOM);

        if (!is_numeric($value)) {
            return PHPExcel_Calculation_Functions::VALUE();
        }
        $fromMultiplier = 1.0;
        if (isset(self::$conversionUnits[$fromUOM])) {
            $unitGroup1 = self::$conversionUnits[$fromUOM]['Group'];
        } else {
            $fromMultiplier = substr($fromUOM, 0, 1);
            $fromUOM = substr($fromUOM, 1);
            if (isset(self::$conversionMultipliers[$fromMultiplier])) {
                $fromMultiplier = self::$conversionMultipliers[$fromMultiplier]['multiplier'];
            } else {
                return PHPExcel_Calculation_Functions::NA();
            }
            if ((isset(self::$conversionUnits[$fromUOM])) && self::$conversionUnits[$fromUOM]['AllowPrefix']) {
                $unitGroup1 = self::$conversionUnits[$fromUOM]['Group'];
            } else {
                return PHPExcel_Calculation_Functions::NA();
            }
        }
        $value *= $fromMultiplier;

        $toMultiplier = 1.0;
        if (isset(self::$conversionUnits[$toUOM])) {
            $unitGroup2 = self::$conversionUnits[$toUOM]['Group'];
        } else {
            $toMultiplier = substr($toUOM, 0, 1);
            $toUOM = substr($toUOM, 1);
            if (isset(self::$conversionMultipliers[$toMultiplier])) {
                $toMultiplier = self::$conversionMultipliers[$toMultiplier]['multiplier'];
            } else {
                return PHPExcel_Calculation_Functions::NA();
            }
            if ((isset(self::$conversionUnits[$toUOM])) && self::$conversionUnits[$toUOM]['AllowPrefix']) {
                $unitGroup2 = self::$conversionUnits[$toUOM]['Group'];
            } else {
                return PHPExcel_Calculation_Functions::NA();
            }
        }
        if ($unitGroup1 != $unitGroup2) {
            return PHPExcel_Calculation_Functions::NA();
        }

        if (($fromUOM == $toUOM) && ($fromMultiplier == $toMultiplier)) {
            //    We've already factored $fromMultiplier into the value, so we need
            //        to reverse it again
            return $value / $fromMultiplier;
        }
        if ($unitGroup1 == 'Temperature') {
            if (($fromUOM == 'F') || ($fromUOM == 'fah')) {
                if (($toUOM == 'F') || ($toUOM == 'fah')) {
                    return $value;
                }
                $value = (($value - 32) / 1.8);
                if (($toUOM == 'K') || ($toUOM == 'kel')) {
                    $value += 273.15;
                }

                return $value;

            } elseif ((($fromUOM == 'K') || ($fromUOM == 'kel'))
                      && (($toUOM == 'K') || ($toUOM == 'kel'))) {
                return $value;
            } elseif ((($fromUOM == 'C') || ($fromUOM == 'cel'))
                      && (($toUOM == 'C') || ($toUOM == 'cel'))) {
                return $value;
            }
            if (($toUOM == 'F') || ($toUOM == 'fah')) {
                if (($fromUOM == 'K') || ($fromUOM == 'kel')) {
                    $value -= 273.15;
                }

                return ($value * 1.8) + 32;
            }
            if (($toUOM == 'C') || ($toUOM == 'cel')) {
                return $value - 273.15;
            }

            return $value + 273.15;
        }

        return ($value * self::$unitConversions[$unitGroup1][$fromUOM][$toUOM]) / $toMultiplier;
    }
}
