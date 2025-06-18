<?php

/**
 * PHPExcel_Reader_Excel5_MD5.
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
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt        LGPL
 * @version    ##VERSION##, ##DATE##
 */
class PHPExcel_Reader_Excel5_MD5
{
    // Context
    private $a;

    private $b;

    private $c;

    private $d;

    /**
     * MD5 stream constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the MD5 stream context.
     */
    public function reset()
    {
        $this->a = 0x67_45_23_01;
        $this->b = 0xEF_CD_AB_89;
        $this->c = 0x98_BA_DC_FE;
        $this->d = 0x10_32_54_76;
    }

    /**
     * Get MD5 stream context.
     *
     * @return string
     */
    public function getContext()
    {
        $s = '';
        foreach (['a', 'b', 'c', 'd'] as $i) {
            $v = $this->{$i};
            $s .= chr($v & 0xFF);
            $s .= chr(($v >> 8) & 0xFF);
            $s .= chr(($v >> 16) & 0xFF);
            $s .= chr(($v >> 24) & 0xFF);
        }

        return $s;
    }

    /**
     * Add data to context.
     *
     * @param string $data Data to add
     */
    public function add($data)
    {
        $words = array_values(unpack('V16', $data));

        $A = $this->a;
        $B = $this->b;
        $C = $this->c;
        $D = $this->d;

        $F = ['PHPExcel_Reader_Excel5_MD5', 'f'];
        $G = ['PHPExcel_Reader_Excel5_MD5', 'g'];
        $H = ['PHPExcel_Reader_Excel5_MD5', 'h'];
        $I = ['PHPExcel_Reader_Excel5_MD5', 'i'];

        /* ROUND 1 */
        self::step($F, $A, $B, $C, $D, $words[0], 7, 0xD7_6A_A4_78);
        self::step($F, $D, $A, $B, $C, $words[1], 12, 0xE8_C7_B7_56);
        self::step($F, $C, $D, $A, $B, $words[2], 17, 0x24_20_70_DB);
        self::step($F, $B, $C, $D, $A, $words[3], 22, 0xC1_BD_CE_EE);
        self::step($F, $A, $B, $C, $D, $words[4], 7, 0xF5_7C_0F_AF);
        self::step($F, $D, $A, $B, $C, $words[5], 12, 0x47_87_C6_2A);
        self::step($F, $C, $D, $A, $B, $words[6], 17, 0xA8_30_46_13);
        self::step($F, $B, $C, $D, $A, $words[7], 22, 0xFD_46_95_01);
        self::step($F, $A, $B, $C, $D, $words[8], 7, 0x69_80_98_D8);
        self::step($F, $D, $A, $B, $C, $words[9], 12, 0x8B_44_F7_AF);
        self::step($F, $C, $D, $A, $B, $words[10], 17, 0xFF_FF_5B_B1);
        self::step($F, $B, $C, $D, $A, $words[11], 22, 0x89_5C_D7_BE);
        self::step($F, $A, $B, $C, $D, $words[12], 7, 0x6B_90_11_22);
        self::step($F, $D, $A, $B, $C, $words[13], 12, 0xFD_98_71_93);
        self::step($F, $C, $D, $A, $B, $words[14], 17, 0xA6_79_43_8E);
        self::step($F, $B, $C, $D, $A, $words[15], 22, 0x49_B4_08_21);

        /* ROUND 2 */
        self::step($G, $A, $B, $C, $D, $words[1], 5, 0xF6_1E_25_62);
        self::step($G, $D, $A, $B, $C, $words[6], 9, 0xC0_40_B3_40);
        self::step($G, $C, $D, $A, $B, $words[11], 14, 0x26_5E_5A_51);
        self::step($G, $B, $C, $D, $A, $words[0], 20, 0xE9_B6_C7_AA);
        self::step($G, $A, $B, $C, $D, $words[5], 5, 0xD6_2F_10_5D);
        self::step($G, $D, $A, $B, $C, $words[10], 9, 0x02_44_14_53);
        self::step($G, $C, $D, $A, $B, $words[15], 14, 0xD8_A1_E6_81);
        self::step($G, $B, $C, $D, $A, $words[4], 20, 0xE7_D3_FB_C8);
        self::step($G, $A, $B, $C, $D, $words[9], 5, 0x21_E1_CD_E6);
        self::step($G, $D, $A, $B, $C, $words[14], 9, 0xC3_37_07_D6);
        self::step($G, $C, $D, $A, $B, $words[3], 14, 0xF4_D5_0D_87);
        self::step($G, $B, $C, $D, $A, $words[8], 20, 0x45_5A_14_ED);
        self::step($G, $A, $B, $C, $D, $words[13], 5, 0xA9_E3_E9_05);
        self::step($G, $D, $A, $B, $C, $words[2], 9, 0xFC_EF_A3_F8);
        self::step($G, $C, $D, $A, $B, $words[7], 14, 0x67_6F_02_D9);
        self::step($G, $B, $C, $D, $A, $words[12], 20, 0x8D_2A_4C_8A);

        /* ROUND 3 */
        self::step($H, $A, $B, $C, $D, $words[5], 4, 0xFF_FA_39_42);
        self::step($H, $D, $A, $B, $C, $words[8], 11, 0x87_71_F6_81);
        self::step($H, $C, $D, $A, $B, $words[11], 16, 0x6D_9D_61_22);
        self::step($H, $B, $C, $D, $A, $words[14], 23, 0xFD_E5_38_0C);
        self::step($H, $A, $B, $C, $D, $words[1], 4, 0xA4_BE_EA_44);
        self::step($H, $D, $A, $B, $C, $words[4], 11, 0x4B_DE_CF_A9);
        self::step($H, $C, $D, $A, $B, $words[7], 16, 0xF6_BB_4B_60);
        self::step($H, $B, $C, $D, $A, $words[10], 23, 0xBE_BF_BC_70);
        self::step($H, $A, $B, $C, $D, $words[13], 4, 0x28_9B_7E_C6);
        self::step($H, $D, $A, $B, $C, $words[0], 11, 0xEA_A1_27_FA);
        self::step($H, $C, $D, $A, $B, $words[3], 16, 0xD4_EF_30_85);
        self::step($H, $B, $C, $D, $A, $words[6], 23, 0x04_88_1D_05);
        self::step($H, $A, $B, $C, $D, $words[9], 4, 0xD9_D4_D0_39);
        self::step($H, $D, $A, $B, $C, $words[12], 11, 0xE6_DB_99_E5);
        self::step($H, $C, $D, $A, $B, $words[15], 16, 0x1F_A2_7C_F8);
        self::step($H, $B, $C, $D, $A, $words[2], 23, 0xC4_AC_56_65);

        /* ROUND 4 */
        self::step($I, $A, $B, $C, $D, $words[0], 6, 0xF4_29_22_44);
        self::step($I, $D, $A, $B, $C, $words[7], 10, 0x43_2A_FF_97);
        self::step($I, $C, $D, $A, $B, $words[14], 15, 0xAB_94_23_A7);
        self::step($I, $B, $C, $D, $A, $words[5], 21, 0xFC_93_A0_39);
        self::step($I, $A, $B, $C, $D, $words[12], 6, 0x65_5B_59_C3);
        self::step($I, $D, $A, $B, $C, $words[3], 10, 0x8F_0C_CC_92);
        self::step($I, $C, $D, $A, $B, $words[10], 15, 0xFF_EF_F4_7D);
        self::step($I, $B, $C, $D, $A, $words[1], 21, 0x85_84_5D_D1);
        self::step($I, $A, $B, $C, $D, $words[8], 6, 0x6F_A8_7E_4F);
        self::step($I, $D, $A, $B, $C, $words[15], 10, 0xFE_2C_E6_E0);
        self::step($I, $C, $D, $A, $B, $words[6], 15, 0xA3_01_43_14);
        self::step($I, $B, $C, $D, $A, $words[13], 21, 0x4E_08_11_A1);
        self::step($I, $A, $B, $C, $D, $words[4], 6, 0xF7_53_7E_82);
        self::step($I, $D, $A, $B, $C, $words[11], 10, 0xBD_3A_F2_35);
        self::step($I, $C, $D, $A, $B, $words[2], 15, 0x2A_D7_D2_BB);
        self::step($I, $B, $C, $D, $A, $words[9], 21, 0xEB_86_D3_91);

        $this->a = ($this->a + $A) & 0xFF_FF_FF_FF;
        $this->b = ($this->b + $B) & 0xFF_FF_FF_FF;
        $this->c = ($this->c + $C) & 0xFF_FF_FF_FF;
        $this->d = ($this->d + $D) & 0xFF_FF_FF_FF;
    }

    private static function f($X, $Y, $Z)
    {
        return ($X & $Y) | ((~ $X) & $Z); // X AND Y OR NOT X AND Z
    }

    private static function g($X, $Y, $Z)
    {
        return ($X & $Z) | ($Y & (~ $Z)); // X AND Z OR Y AND NOT Z
    }

    private static function h($X, $Y, $Z)
    {
        return $X ^ $Y ^ $Z; // X XOR Y XOR Z
    }

    private static function i($X, $Y, $Z)
    {
        return $Y ^ ($X | (~ $Z)); // Y XOR (X OR NOT Z)
    }

    private static function step($func, &$A, $B, $C, $D, $M, $s, $t)
    {
        $A = ($A + call_user_func($func, $B, $C, $D) + $M + $t) & 0xFF_FF_FF_FF;
        $A = self::rotate($A, $s);
        $A = ($B + $A) & 0xFF_FF_FF_FF;
    }

    private static function rotate($decimal, $bits)
    {
        $binary = str_pad(decbin($decimal), 32, '0', STR_PAD_LEFT);

        return bindec(substr($binary, $bits) . substr($binary, 0, $bits));
    }
}
