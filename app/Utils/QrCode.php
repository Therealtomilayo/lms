<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Lightweight Standalone Pure-PHP SVG QR Code Generator
 *
 * Implements ISO/IEC 18004 QR Code Version 3 & 4 Byte Mode encoding
 * with Reed-Solomon Error Correction Level M/L.
 * Produces crisp, pure inline vector SVG markup with zero external network or library dependencies.
 */
final class QrCode
{
    /**
     * Generate an inline SVG string for the given content.
     */
    public static function svg(string $data, int $size = 160, int $margin = 2, string $color = '#1e293b', string $bg = '#ffffff'): string
    {
        $matrix = self::encodeToMatrix($data);
        $count = count($matrix);
        $totalSize = $count + ($margin * 2);

        $rects = [];
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\"/>";
                }
            }
        }

        $rectsStr = implode('', $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalSize} {$totalSize}" width="{$size}" height="{$size}" shape-rendering="crispEdges">
    <rect width="100%" height="100%" fill="{$bg}"/>
    <g fill="{$color}">
        {$rectsStr}
    </g>
</svg>
SVG;
    }

    /**
     * Generates a 2D binary matrix (array of bools) for the QR code.
     *
     * @return array<int, array<int, bool>>
     */
    public static function encodeToMatrix(string $text): array
    {
        // Version 3 is 29x29, can hold up to 53 alphanumeric/bytes with Level M
        // Version 4 is 33x33, can hold up to 78 alphanumeric/bytes with Level M
        $len = strlen($text);
        $version = $len <= 50 ? 3 : 4;
        $size = 17 + (4 * $version); // V3: 29, V4: 33

        // Initialize empty matrix (-1 = unset)
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder patterns at (0,0), (0, size-7), (size-7, 0)
        self::addFinderPattern($matrix, $reserved, 0, 0, $size);
        self::addFinderPattern($matrix, $reserved, 0, $size - 7, $size);
        self::addFinderPattern($matrix, $reserved, $size - 7, 0, $size);

        // 2. Alignment pattern for V3 (row/col 22) or V4 (row/col 26)
        $alignPos = $version === 3 ? 22 : 26;
        self::addAlignmentPattern($matrix, $reserved, $alignPos, $alignPos);

        // 3. Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0);
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = $val;
                $reserved[6][$i] = true;
            }
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = $val;
                $reserved[$i][6] = true;
            }
        }

        // 4. Reserve format information areas
        self::reserveFormatAreas($reserved, $size);

        // 5. Dark module at (4*V + 9, 8) -> for V3: (21, 8), V4: (25, 8)
        $darkRow = (4 * $version) + 9;
        $matrix[$darkRow][8] = true;
        $reserved[$darkRow][8] = true;

        // 6. Encode data bits
        $dataBits = self::encodeDataBits($text, $version);

        // 7. Place data bits in matrix (zig-zag from bottom-right)
        $bitIdx = 0;
        $totalBits = count($dataBits);
        $right = $size - 1;
        $up = true;

        while ($right > 0) {
            if ($right === 6) {
                $right--; // skip vertical timing column
            }

            for ($vert = 0; $vert < $size; $vert++) {
                $r = $up ? ($size - 1 - $vert) : $vert;

                for ($c = 0; $c < 2; $c++) {
                    $col = $right - $c;
                    if (!$reserved[$r][$col]) {
                        $bit = $bitIdx < $totalBits ? $dataBits[$bitIdx++] : false;
                        // Apply Mask Pattern 0: (r + col) % 2 == 0
                        $mask = (($r + $col) % 2 === 0);
                        $matrix[$r][$col] = $bit ^ $mask;
                    }
                }
            }

            $right -= 2;
            $up = !$up;
        }

        // 8. Add format information (Mask 0, Level M: 101010000010010 XOR 101010000010010)
        self::addFormatInfo($matrix, $size);

        // Replace any remaining unassigned cells with false
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === null) {
                    $matrix[$r][$c] = false;
                }
            }
        }

        return $matrix;
    }

    private static function addFinderPattern(array &$matrix, array &$reserved, int $startR, int $startC, int $size): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $currR = $startR + $r;
                $currC = $startC + $c;

                if ($currR >= 0 && $currR < $size && $currC >= 0 && $currC < $size) {
                    $reserved[$currR][$currC] = true;

                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        $isBlack = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
                        $matrix[$currR][$currC] = $isBlack;
                    } else {
                        $matrix[$currR][$currC] = false; // separator
                    }
                }
            }
        }
    }

    private static function addAlignmentPattern(array &$matrix, array &$reserved, int $centerR, int $centerC): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $row = $centerR + $r;
                $col = $centerC + $c;
                $reserved[$row][$col] = true;
                $isBlack = (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0));
                $matrix[$row][$col] = $isBlack;
            }
        }
    }

    private static function reserveFormatAreas(array &$reserved, int $size): void
    {
        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }
    }

    private static function addFormatInfo(array &$matrix, int $size): void
    {
        // Format string for Error Correction M (00) and Mask 0 (000):
        // Raw: 00 000 = 00000. With 10 BCH bits: 00000 00000 00000
        // XORed with 101010000010010 = 101010000010010
        $formatBits = [true, false, true, false, true, false, false, false, false, false, true, false, false, true, false];

        // Around top-left
        $matrix[8][0] = $formatBits[0];
        $matrix[8][1] = $formatBits[1];
        $matrix[8][2] = $formatBits[2];
        $matrix[8][3] = $formatBits[3];
        $matrix[8][4] = $formatBits[4];
        $matrix[8][5] = $formatBits[5];
        $matrix[8][7] = $formatBits[6];
        $matrix[8][8] = $formatBits[7];
        $matrix[7][8] = $formatBits[8];
        $matrix[5][8] = $formatBits[9];
        $matrix[4][8] = $formatBits[10];
        $matrix[3][8] = $formatBits[11];
        $matrix[2][8] = $formatBits[12];
        $matrix[1][8] = $formatBits[13];
        $matrix[0][8] = $formatBits[14];

        // Around top-right & bottom-left
        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 1 - $i][8] = $formatBits[$i];
        }
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 8 + $i] = $formatBits[7 + $i];
        }
    }

    /**
     * @return array<int, bool>
     */
    private static function encodeDataBits(string $text, int $version): array
    {
        $bits = [];

        // 1. Mode Indicator: 0100 (Byte mode)
        $bits = array_merge($bits, [false, true, false, false]);

        // 2. Character Count Indicator: 8 bits for Byte mode (V1-V9)
        $len = strlen($text);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = (bool)(($len >> $i) & 1);
        }

        // 3. Data bytes
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($text[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (bool)(($byte >> $b) & 1);
            }
        }

        // Capacity: V3 Level M is 44 data codewords (352 bits), V4 Level M is 64 codewords (512 bits)
        $totalDataCodewords = ($version === 3) ? 44 : 64;
        $maxBits = $totalDataCodewords * 8;

        // 4. Terminator (up to 4 zero bits)
        $terminatorLen = min(4, $maxBits - count($bits));
        for ($i = 0; $i < $terminatorLen; $i++) {
            $bits[] = false;
        }

        // 5. Pad to multiple of 8
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        // 6. Pad codewords (0xEC, 0x11 alternating)
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $maxBits) {
            $pad = $padBytes[$padIdx % 2];
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (bool)(($pad >> $b) & 1);
            }
            $padIdx++;
        }

        // Convert data bits to bytes for Reed-Solomon computation
        $dataBytes = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $val = 0;
            for ($b = 0; $b < 8; $b++) {
                if ($bits[$i + $b]) {
                    $val |= (1 << (7 - $b));
                }
            }
            $dataBytes[] = $val;
        }

        // ECC codewords: V3-M = 26 ECC bytes, V4-M = 36 ECC bytes
        $eccCount = ($version === 3) ? 26 : 36;
        $eccBytes = self::computeReedSolomon($dataBytes, $eccCount);

        // Combine data bytes and ECC bytes into full stream
        $allBytes = array_merge($dataBytes, $eccBytes);
        $finalBits = [];
        foreach ($allBytes as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $finalBits[] = (bool)(($byte >> $b) & 1);
            }
        }

        return $finalBits;
    }

    /**
     * Compute Reed-Solomon Error Correction Codewords in GF(256)
     * Primitive polynomial: x^8 + x^4 + x^3 + x^2 + 1 (0x11D, 285)
     *
     * @param array<int, int> $data
     * @return array<int, int>
     */
    private static function computeReedSolomon(array $data, int $eccCount): array
    {
        // Build log/exp tables for GF(256)
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }

        // Generate generator polynomial: g(x) = (x - 2^0)(x - 2^1)...(x - 2^(eccCount-1))
        $poly = [1];
        for ($i = 0; $i < $eccCount; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = $exp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                // Multiply poly[j] by factor in GF(256)
                if ($poly[$j] !== 0) {
                    $prod = $exp[$log[$poly[$j]] + $log[$factor]];
                    $next[$j + 1] ^= $prod;
                }
            }
            $poly = $next;
        }

        // Synthetic division to find remainder
        $remainder = array_fill(0, $eccCount, 0);
        foreach ($data as $byte) {
            $lead = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;

            if ($lead !== 0) {
                $leadLog = $log[$lead];
                for ($j = 0; $j < $eccCount; $j++) {
                    $term = $poly[$j + 1];
                    if ($term !== 0) {
                        $remainder[$j] ^= $exp[$leadLog + $log[$term]];
                    }
                }
            }
        }

        return $remainder;
    }
}
