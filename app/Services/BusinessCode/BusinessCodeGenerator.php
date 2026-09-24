<?php

namespace App\Services\BusinessCode;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class BusinessCodeGenerator
{
    public const RESERVATION = 'DB';

    public const DINING_SESSION = 'PV';

    public const DINING_ORDER = 'GM';

    public const FULFILLMENT_ORDER = 'NQ';

    public const BILL = 'HD';

    public const PAYMENT = 'TT';

    public const KITCHEN_TICKET = 'BEP';

    public function next(string $prefix, ?CarbonInterface $occurredAt = null): string
    {
        if (! preg_match('/^[A-Z]{2,4}$/', $prefix)) {
            throw new InvalidArgumentException('Business code prefixes must contain two to four uppercase letters.');
        }

        $timestamp = ($occurredAt === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($occurredAt)
        )->setTimezone(config('app.timezone'));
        $businessDate = $timestamp->toDateString();

        $sequence = DB::transaction(function () use ($prefix, $businessDate): int {
            $now = now();

            DB::table('business_code_counters')->insertOrIgnore([
                'code_type' => $prefix,
                'business_date' => $businessDate,
                'last_sequence' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $counter = DB::table('business_code_counters')
                ->where('code_type', $prefix)
                ->where('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                throw new \LogicException('The business code counter could not be initialized.');
            }

            $nextSequence = (int) $counter->last_sequence + 1;

            DB::table('business_code_counters')
                ->where('id', $counter->id)
                ->update([
                    'last_sequence' => $nextSequence,
                    'updated_at' => $now,
                ]);

            return $nextSequence;
        }, 5);

        return sprintf('%s-%s-%04d', $prefix, $timestamp->format('ymd'), $sequence);
    }
}
