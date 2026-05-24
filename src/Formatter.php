<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering;

use Wawaiguntang\Numbering\Utils\RomanConverter;

/**
 * Parse and format numbering patterns with placeholders.
 */
class Formatter
{
    private array $params = [];
    private ?int $sequence = null;
    private ?int $sequenceLength = null;
    private bool $romanSequence = false;
    private string $padChar = '0';

    /**
     * Set custom parameter value.
     */
    public function setParam(string $name, string $value): self
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Set sequence number and length.
     */
    public function setSequence(int $number, ?int $length = null): self
    {
        $this->sequence = $number;
        $this->sequenceLength = $length;
        return $this;
    }

    /**
     * Use Roman numerals for sequence.
     */
    public function useRomanSequence(bool $use = true): self
    {
        $this->romanSequence = $use;
        return $this;
    }

    /**
     * Set padding character for sequence.
     * Default is '0' (e.g., 001, 002).
     */
    public function setPadChar(string $char): self
    {
        $this->padChar = $char;
        return $this;
    }

    /**
     * Format a pattern string with all placeholders.
     */
    public function format(string $pattern, ?\DateTimeInterface $date = null): string
    {
        $date = $date ?? new \DateTimeImmutable();

        // Replace placeholders
        $result = $pattern;

        // {prefix} or {prefix:XXX}
        $result = $this->replacePrefix($result);

        // {suffix} or {suffix:XXX}
        $result = $this->replaceSuffix($result);

        // {param:NAME}
        $result = $this->replaceParams($result);

        // {date:FORMAT} or {date}
        $result = $this->replaceDate($result, $date);

        // {year}, {month}, {day}
        $result = $this->replaceDateParts($result, $date);

        // {romanMonth}
        $result = $this->replaceRomanMonth($result, $date);

        // {romanYear} or {romanYearShort}
        $result = $this->replaceRomanYear($result, $date);

        // {romanDate} or {romanDateShort}
        $result = $this->replaceRomanDate($result, $date);

        // {sequence} or {sequence:N} or {roman} or {roman:N}
        $result = $this->replaceSequence($result);

        // {random:N}
        $result = $this->replaceRandom($result);

        return $result;
    }

    private function replacePrefix(string $pattern): string
    {
        return preg_replace_callback('/\{prefix(?::([^}]+))?\}/', function ($matches) {
            return $matches[1] ?? ($this->params['prefix'] ?? '');
        }, $pattern);
    }

    private function replaceSuffix(string $pattern): string
    {
        return preg_replace_callback('/\{suffix(?::([^}]+))?\}/', function ($matches) {
            return $matches[1] ?? ($this->params['suffix'] ?? '');
        }, $pattern);
    }

    private function replaceParams(string $pattern): string
    {
        return preg_replace_callback('/\{param:([^}]+)\}/', function ($matches) {
            $name = $matches[1];
            return $this->params[$name] ?? '';
        }, $pattern);
    }

    private function replaceDate(string $pattern, \DateTimeInterface $date): string
    {
        return preg_replace_callback('/\{date(?::([^}]+))?\}/', function ($matches) use ($date) {
            $format = $matches[1] ?? 'Ymd';
            return $date->format($format);
        }, $pattern);
    }

    private function replaceDateParts(string $pattern, \DateTimeInterface $date): string
    {
        $replacements = [
            '{year}' => $date->format('Y'),
            '{month}' => $date->format('m'),
            '{day}' => $date->format('d'),
        ];

        return strtr($pattern, $replacements);
    }

    private function replaceRomanMonth(string $pattern, \DateTimeInterface $date): string
    {
        return preg_replace_callback('/\{romanMonth\}/', function () use ($date) {
            $month = (int) $date->format('n');
            return RomanConverter::month($month);
        }, $pattern);
    }

    private function replaceRomanYear(string $pattern, \DateTimeInterface $date): string
    {
        $year = (int) $date->format('Y');

        $pattern = preg_replace_callback('/\{romanYearShort\}/', function () use ($year) {
            return RomanConverter::year($year, true);
        }, $pattern);

        return preg_replace_callback('/\{romanYear\}/', function () use ($year) {
            return RomanConverter::year($year, false);
        }, $pattern);
    }

    private function replaceRomanDate(string $pattern, \DateTimeInterface $date): string
    {
        $day = (int) $date->format('j');
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');
        $shortYear = $year % 100;

        // {romanDate} -> XXIII/V/MMXXV
        $pattern = preg_replace_callback('/\{romanDate\}/', function () use ($day, $month, $year) {
            return RomanConverter::toRoman($day) . '/' . RomanConverter::month($month) . '/' . RomanConverter::year($year);
        }, $pattern);

        // {romanDateShort} -> 23/V/25
        return preg_replace_callback('/\{romanDateShort\}/', function () use ($day, $month, $shortYear) {
            return $day . '/' . RomanConverter::month($month) . '/' . RomanConverter::year($shortYear);
        }, $pattern);
    }

    private function replaceSequence(string $pattern): string
    {
        if ($this->sequence === null) {
            return $pattern;
        }

        // {roman} or {roman:N} - always Roman
        $pattern = preg_replace_callback('/\{roman(?::(\d+))?\}/', function ($matches) {
            $number = $this->sequence ?? 1;
            $roman = RomanConverter::toRoman($number);
            $length = isset($matches[1]) ? (int) $matches[1] : null;
            
            if ($length !== null && strlen($roman) < $length) {
                return str_repeat('I', $length - strlen($roman)) . $roman;
            }
            
            return $roman;
        }, $pattern);

        // {sequence} or {sequence:N}
        return preg_replace_callback('/\{sequence(?::(\d+))?\}/', function ($matches) {
            $number = $this->sequence ?? 1;
            
            // Check if should use Roman (romanSequence flag)
            if ($this->romanSequence) {
                return RomanConverter::toRoman($number);
            }
            
            $length = isset($matches[1]) ? (int) $matches[1] : $this->sequenceLength;
            
            if ($length !== null) {
                return str_pad((string) $number, $length, $this->padChar, STR_PAD_LEFT);
            }
            
            return (string) $number;
        }, $pattern);
    }

    private function replaceRandom(string $pattern): string
    {
        return preg_replace_callback('/\{random(?::(\d+))?\}/', function ($matches) {
            $length = isset($matches[1]) ? (int) $matches[1] : 4;
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $result = '';
            
            for ($i = 0; $i < $length; $i++) {
                $result .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            return $result;
        }, $pattern);
    }
}
