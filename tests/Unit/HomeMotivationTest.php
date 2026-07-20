<?php

namespace Tests\Unit;

use App\Http\Controllers\HomeController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HomeMotivationTest extends TestCase
{
    #[DataProvider('cycleDays')]
    public function test_motivation_repeats_in_one_hundred_day_cycles(
        int $streak,
        int $expectedProgress,
        string $expectedSubtext
    ): void {
        $controller = new HomeController();
        $method = new ReflectionMethod($controller, 'motivationProgress');

        $motivation = $method->invoke($controller, $streak);

        $this->assertSame($expectedProgress, $motivation['progress']);
        $this->assertSame($expectedSubtext, $motivation['subtext']);
    }

    public static function cycleDays(): array
    {
        return [
            'not started' => [0, 0, 'Day 0 of 100 · Start your first cycle today'],
            'first day' => [1, 1, 'Cycle 1 · Day 1 of 100'],
            'first cycle complete' => [100, 100, 'Cycle 1 complete · 100 of 100 days'],
            'second cycle starts' => [101, 1, 'Cycle 2 · Day 1 of 100'],
            'second cycle complete' => [200, 100, 'Cycle 2 complete · 100 of 100 days'],
            'third cycle starts' => [201, 1, 'Cycle 3 · Day 1 of 100'],
        ];
    }
}
