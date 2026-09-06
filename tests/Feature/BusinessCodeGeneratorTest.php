<?php

namespace Tests\Feature;

use App\Services\BusinessCode\BusinessCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BusinessCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_readable_daily_sequences_for_each_business_code_type(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-06 10:30:00', config('app.timezone')));
        $generator = $this->app->make(BusinessCodeGenerator::class);

        $this->assertSame('DB-260906-0001', $generator->next(BusinessCodeGenerator::RESERVATION));
        $this->assertSame('DB-260906-0002', $generator->next(BusinessCodeGenerator::RESERVATION));
        $this->assertSame('NQ-260906-0001', $generator->next(BusinessCodeGenerator::FULFILLMENT_ORDER));

        $this->travelTo(CarbonImmutable::parse('2026-09-07 00:01:00', config('app.timezone')));

        $this->assertSame('DB-260907-0001', $generator->next(BusinessCodeGenerator::RESERVATION));
    }

    public function test_it_rejects_invalid_prefixes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(BusinessCodeGenerator::class)->next('reservation');
    }
}
