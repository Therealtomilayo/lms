<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Lightweight Standalone Pure-PHP SVG QR Code Generator
 *
 * Implements ISO/IEC 18004 compliant QR Code generation for Versions 1–5 (Error Correction Level M, Mask Pattern 0).
 * Produces crisp, standalone SVG markup with 4-module quiet zone margin and zero external dependencies.
 */
final class QrCode
{
    /**
     * Generate an inline SVG string for the given content.
     */
    public static function svg(string $data, int $size = 160, int $margin = 4, string $color = '#1e293b', string $bg = '#ffffff'): string
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
        $len = strlen($text);

        // Determine smallest fitting version for Error Correction M (Byte Mode)
        // V1: 14 bytes, V2: 26 bytes, V3: 42 bytes, V4: 62 bytes, V5: 84 bytes
        if ($len <= 26) {
            $version = 2;
        } elseif ($len <= 42) {
            $version = 3;
        } elseif ($len <= 62) {
            $version = 4;
        } else {
            $version = 5;
        }

        $size = 17 + (4 * $version);

        // Initialize empty matrix
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder patterns at top-left (0,0), top-right (0, size-7), bottom-left (size-7, 0)
        self::addFinderPattern($matrix, $reserved, 0, 0, $size);
        self::addFinderPattern($matrix, $reserved, 0, $size - 7, $size);
        self::addFinderPattern($matrix, $reserved, $size - 7, 0, $size);

        // 2. Alignment patterns (Versions >= 2)
        $alignPosList = match ($version) {
            2 => [18],
            3 => [22],
            4 => [26],
            5 => [30],
            default => [22],
        };

        foreach ($alignPosList as $rowPos) {
            foreach ($alignPosList as $colPos) {
                // Do not overlap with finder patterns
                if (($rowPos <= 8 && $colPos <= 8) ||
                    ($rowPos <= 8 && $colPos >= $size - 8) ||
                    ($rowPos >= $size - 8 && $colPos <= 8)) {
                    continue;
                }
                self::addAlignmentPattern($matrix, $reserved, $rowPos, $colPos);
            }
        }

        // 3. Timing patterns (Row 6 and Col 6)
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

        // 5. Dark module at (4*V + 9, 8)
        $darkRow = (4 * $version) + 9;
        $matrix[$darkRow][8] = true;
        $reserved[$darkRow][8] = true;

        // 6. Encode data & EC bits
        $dataBits = self::encodeDataBits($text, $version);

        // 7. Place data bits into matrix in 2-column zig-zag traversal from bottom-right
        $bitIdx = 0;
        $totalBits = count($dataBits);
        $right = $size - 1;
        $up = true;

        while ($right > 0) {
            if ($right === 6) {
                $right--; // Skip vertical timing column
            }

            for ($vert = 0; $vert < $size; $vert++) {
                $r = $up ? ($size - 1 - $vert) : $vert;

                for ($c = 0; $c < 2; $c++) {
                    $col = $right - $c;
                    if (!$reserved[$r][$col]) {
                        $bit = $bitIdx < $totalBits ? $dataBits[$bitIdx++] : false;
                        // Mask Pattern 0: (row + col) % 2 === 0
                        $mask = (($r + $col) % 2 === 0);
                        $matrix[$r][$col] = $bit ^ $mask;
                    }
                }
            }

            $right -= 2;
            $up = !$up;
        }

        // 8. Add format information for Level M and Mask 0
        self::addFormatInfo($matrix, $size);

        // Fallback: fill any unassigned cells
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
                        $matrix[$currR][$currC] = false; // Separator
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

    /**
     * Format information for Error Correction Level M (00) and Mask Pattern 0 (000)
     * BCH (15,5) code: raw 00000 with 10 error-correction bits XORed with 101010000010010 = 21522.
     */
    private static function addFormatInfo(array &$matrix, int $size): void
    {
        $bits = 21522; // (0b101010000010010)

        // Vertical placement
        for ($i = 0; $i < 15; $i++) {
            $mod = (($bits >> $i) & 1) === 1;

            if ($i < 6) {
                $matrix[$i][8] = $mod;
            } elseif ($i < 8) {
                $matrix[$i + 1][8] = $mod;
            } else {
                $matrix[$size - 15 + $i][8] = $mod;
            }
        }

        // Horizontal placement
        for ($i = 0; $i < 15; $i++) {
            $mod = (($bits >> $i) & 1) === 1;

            if ($i < 8) {
                $matrix[8][$size - $i - 1] = $mod;
            } elseif ($i < 9) {
                $matrix[8][15 - $i - 1 + 1] = $mod;
            } else {
                $matrix[8][15 - $i - 1] = $mod;
            }
        }

        // Fixed dark module
        $matrix[$size - 8][8] = true;
    }

    /**
     * Encodes text and returns data & EC bitstream with proper block interleaving.
     *
     * @return array<int, bool>
     */
    private static function encodeDataBits(string $text, int $version): array
    {
        $bits = [];

        // 1. Mode indicator: 0100 (8-bit Byte mode)
        $bits = array_merge($bits, [false, true, false, false]);

        // 2. Character count indicator: 8 bits for Versions 1-9 in Byte mode
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

        // ISO 18004 Table 9 (Error Correction Characteristics for Level M):
        // V2-M: 1 block of 28 data CW, 16 EC CW (total 44 CW)
        // V3-M: 1 block of 44 data CW, 26 EC CW (total 70 CW)
        // V4-M: 2 blocks of 32 data CW, 18 EC CW (total 100 CW)
        // V5-M: 2 blocks of 43 data CW, 24 EC CW (total 134 CW)
        [$numBlocks, $dataCwPerBlock, $ecCwPerBlock] = match ($version) {
            2 => [1, 28, 16],
            3 => [1, 44, 26],
            4 => [2, 32, 18],
            5 => [2, 43, 24],
            default => [1, 44, 26],
        };

        $totalDataCodewords = $numBlocks * $dataCwPerBlock;
        $maxBits = $totalDataCodewords * 8;

        // 4. Terminator (up to 4 zeroes)
        $terminatorLen = min(4, $maxBits - count($bits));
        for ($i = 0; $i < $terminatorLen; $i++) {
            $bits[] = false;
        }

        // 5. Pad to whole byte
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        // 6. Pad codewords alternating 0xEC and 0x11
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $maxBits) {
            $pad = $padBytes[$padIdx % 2];
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (bool)(($pad >> $b) & 1);
            }
            $padIdx++;
        }

        // Convert data bits to bytes
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

        // Partition into blocks and calculate Reed-Solomon EC for each block
        $dataBlocks = [];
        $ecBlocks = [];

        for ($b = 0; $b < $numBlocks; $b++) {
            $blockData = array_slice($dataBytes, $b * $dataCwPerBlock, $dataCwPerBlock);
            $dataBlocks[] = $blockData;
            $ecBlocks[] = self::computeReedSolomon($blockData, $ecCwPerBlock);
        }

        // Interleave data codewords across blocks
        $interleavedBytes = [];
        for ($i = 0; $i < $dataCwPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $interleavedBytes[] = $dataBlocks[$b][$i];
            }
        }

        // Interleave error correction codewords across blocks
        for ($i = 0; $i < $ecCwPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $interleavedBytes[] = $ecBlocks[$b][$i];
            }
        }

        // Convert interleaved bytes back to bitstream
        $finalBits = [];
        foreach ($interleavedBytes as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $finalBits[] = (bool)(($byte >> $b) & 1);
            }
        }

        // Remainder bits for versions that have them (V1: 0, V2: 7, V3: 7, V4: 7, V5: 7)
        $remainderBits = ($version >= 2 && $version <= 6) ? 7 : 0;
        for ($i = 0; $i < $remainderBits; $i++) {
            $finalBits[] = false;
        }

        return $finalBits;
    }

    /**
     * Compute Reed-Solomon error correction codewords in Galois Field GF(256)
     * Primitive polynomial: 0x11D (285)
     *
     * @param array<int, int> $data
     * @return array<int, int>
     */
    private static function computeReedSolomon(array $data, int $eccCount): array
    {
        static $exp = null;
        static $log = null;

        if ($exp === null) {
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
        }

        // Generate generator polynomial: g(x) = (x - α^0)(x - α^1)...(x - α^(eccCount-1))
        $poly = [1];
        for ($i = 0; $i < $eccCount; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = $exp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                if ($poly[$j] !== 0) {
                    $prod = $exp[$log[$poly[$j]] + $log[$factor]];
                    $next[$j + 1] ^= $prod;
                }
            }
            $poly = $next;
        }

        // Polynomial synthetic division
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
