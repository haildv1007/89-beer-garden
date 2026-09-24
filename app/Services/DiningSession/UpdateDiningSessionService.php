<?php

namespace App\Services\DiningSession;

use App\Enums\DiningSessionStatus;
use App\Models\DiningSession;
use App\Models\Reservation;
use App\Services\Customer\CustomerIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDiningSessionService
{
    public function __construct(private readonly CustomerIdentityService $identity) {}

    /** @param array<string, mixed> $data */
    public function update(DiningSession $session, array $data): DiningSession
    {
        return DB::transaction(function () use ($session, $data): DiningSession {
            $locked = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== DiningSessionStatus::Active) {
                throw ValidationException::withMessages([
                    'dining_session' => 'Chỉ phiên đang phục vụ mới được chỉnh sửa.',
                ]);
            }

            $customerId = $locked->customer_id;
            if (filled($data['customer_name'] ?? null) && filled($data['phone'] ?? null)) {
                $customerId = $this->identity->resolve($data['customer_name'], $data['phone'])->id;
                if ($locked->reservation_id !== null) {
                    Reservation::query()
                        ->whereKey($locked->reservation_id)
                        ->lockForUpdate()
                        ->update(['customer_id' => $customerId]);
                }
            }

            $locked
                ->forceFill([
                    'customer_id' => $customerId,
                    'guest_count' => (int) $data['guest_count'],
                    'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
                ])
                ->save();

            return $locked->fresh();
        });
    }
}
