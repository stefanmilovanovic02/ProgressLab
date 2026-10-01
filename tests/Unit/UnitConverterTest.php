<?php

namespace Tests\Unit;

use App\Support\UnitConverter;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    public function test_it_converts_weight_and_length_without_changing_metric_values(): void
    {
        $this->assertSame(80.0, UnitConverter::weightFromKg(80, 'metric'));
        $this->assertSame(176.4, UnitConverter::weightFromKg(80, 'imperial'));
        $this->assertEqualsWithDelta(80, UnitConverter::weightToKg(176.37, 'imperial'), 0.01);

        $this->assertSame(180.0, UnitConverter::lengthFromCm(180, 'metric'));
        $this->assertSame(70.9, UnitConverter::lengthFromCm(180, 'imperial'));
        $this->assertEqualsWithDelta(180, UnitConverter::lengthToCm(70.87, 'imperial'), 0.02);
    }

    public function test_unknown_or_missing_preferences_default_to_metric(): void
    {
        $this->assertSame(UnitConverter::METRIC, UnitConverter::normalize(null));
        $this->assertSame(UnitConverter::METRIC, UnitConverter::normalize('other'));
        $this->assertSame('kg', UnitConverter::weightUnit(null));
        $this->assertSame('cm', UnitConverter::lengthUnit(null));
    }
}
