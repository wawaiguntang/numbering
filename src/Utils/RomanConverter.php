<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Utils;

/**
 * Convert integers to Roman numerals (1-3999).
 */
class RomanConverter
{
    private const MAP = [
        1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
        100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
        10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV',
        1 => 'I'
    ];

    /**
     * Convert integer to Roman numeral.
     *
     * @param int $number Number to convert (1-3999)
     * @param bool $uppercase Return uppercase (default) or lowercase
     * @return string Roman numeral
     * @throws \InvalidArgumentException If number is out of range
     */
    public static function toRoman(int $number, bool $uppercase = true): string
    {
        if ($number < 1 || $number > 3999) {
            throw new \InvalidArgumentException('Number must be between 1 and 3999');
        }

        $result = '';
        foreach (self::MAP as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }

        return $uppercase ? $result : strtolower($result);
    }

    /**
     * Convert Roman numeral to integer.
     *
     * @param string $roman Roman numeral to convert
     * @return int Integer value
     * @throws \InvalidArgumentException If invalid Roman numeral
     */
    public static function fromRoman(string $roman): int
    {
        $roman = strtoupper(trim($roman));
        if (empty($roman)) {
            throw new \InvalidArgumentException('Empty Roman numeral');
        }

        $result = 0;
        $map = array_flip(self::MAP);
        
        for ($i = 0; $i < strlen($roman); $i++) {
            $current = $map[$roman[$i]] ?? null;
            if ($current === null) {
                throw new \InvalidArgumentException("Invalid Roman numeral character: {$roman[$i]}");
            }

            $next = isset($roman[$i + 1]) ? ($map[$roman[$i + 1]] ?? null) : null;

            if ($next !== null && $next > $current) {
                $result += $next - $current;
                $i++;
            } else {
                $result += $current;
            }
        }

        return $result;
    }

    /**
     * Get month name in Roman numerals (I-XII).
     *
     * @param int $month Month number (1-12)
     * @param bool $uppercase Return uppercase (default) or lowercase
     * @return string Roman month
     */
    public static function month(int $month, bool $uppercase = true): string
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Month must be between 1 and 12');
        }

        return self::toRoman($month, $uppercase);
    }

    /**
     * Get year in Roman numerals.
     *
     * @param int $year Year (e.g., 2025)
     * @param bool $short Use 2-digit year (e.g., 25)
     * @param bool $uppercase Return uppercase (default) or lowercase
     * @return string Roman year
     */
    public static function year(int $year, bool $short = false, bool $uppercase = true): string
    {
        if ($short) {
            $year = $year % 100;
        }

        return self::toRoman($year, $uppercase);
    }
}
