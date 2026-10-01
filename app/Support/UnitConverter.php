<?php

namespace App\Support;

final class UnitConverter
{
    public const METRIC = 'metric';
    public const IMPERIAL = 'imperial';

    private const POUNDS_PER_KILOGRAM = 2.2046226218;
    private const INCHES_PER_CENTIMETRE = 0.3937007874;

    public static function normalize(?string $system): string
    {
        return $system === self::IMPERIAL ? self::IMPERIAL : self::METRIC;
    }

    public static function weightFromKg(float|int|null $kilograms, ?string $system, int $precision = 1): ?float
    {
        if ($kilograms === null) return null;

        $value = self::normalize($system) === self::IMPERIAL
            ? (float) $kilograms * self::POUNDS_PER_KILOGRAM
            : (float) $kilograms;

        return round($value, $precision);
    }

    public static function weightToKg(float|int|null $value, ?string $system, int $precision = 2): ?float
    {
        if ($value === null) return null;

        $kilograms = self::normalize($system) === self::IMPERIAL
            ? (float) $value / self::POUNDS_PER_KILOGRAM
            : (float) $value;

        return round($kilograms, $precision);
    }

    public static function lengthFromCm(float|int|null $centimetres, ?string $system, int $precision = 1): ?float
    {
        if ($centimetres === null) return null;

        $value = self::normalize($system) === self::IMPERIAL
            ? (float) $centimetres * self::INCHES_PER_CENTIMETRE
            : (float) $centimetres;

        return round($value, $precision);
    }

    public static function lengthToCm(float|int|null $value, ?string $system, int $precision = 2): ?float
    {
        if ($value === null) return null;

        $centimetres = self::normalize($system) === self::IMPERIAL
            ? (float) $value / self::INCHES_PER_CENTIMETRE
            : (float) $value;

        return round($centimetres, $precision);
    }

    public static function weightUnit(?string $system): string
    {
        return self::normalize($system) === self::IMPERIAL ? 'lb' : 'kg';
    }

    public static function lengthUnit(?string $system): string
    {
        return self::normalize($system) === self::IMPERIAL ? 'in' : 'cm';
    }
}
