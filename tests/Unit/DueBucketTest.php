<?php

namespace Tests\Unit;

use App\Enums\DueBucket;
use PHPUnit\Framework\TestCase;

class DueBucketTest extends TestCase
{
    public function test_each_case_has_a_human_label(): void
    {
        $this->assertSame('Today', DueBucket::Today->label());
        $this->assertSame('Tomorrow', DueBucket::Tomorrow->label());
        $this->assertSame('This week', DueBucket::ThisWeek->label());
        $this->assertSame('Next week', DueBucket::NextWeek->label());
        $this->assertSame('Whenever', DueBucket::Whenever->label());
    }

    public function test_sort_order_runs_soonest_first_with_whenever_last(): void
    {
        $sorted = collect(DueBucket::cases())
            ->sortBy(fn (DueBucket $bucket) => $bucket->sortOrder())
            ->values()
            ->all();

        $this->assertSame([
            DueBucket::Today,
            DueBucket::Tomorrow,
            DueBucket::ThisWeek,
            DueBucket::NextWeek,
            DueBucket::Whenever,
        ], $sorted);
    }

    public function test_whenever_has_the_highest_sort_order(): void
    {
        $max = collect(DueBucket::cases())->max(fn (DueBucket $bucket) => $bucket->sortOrder());

        $this->assertSame($max, DueBucket::Whenever->sortOrder());
    }
}
