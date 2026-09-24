<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kitchen\KitchenQueueRequest;
use App\Models\KitchenTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitchenOrderController extends Controller
{
    public function index(KitchenQueueRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $tickets = KitchenTicket::query()
            ->with('createdByEmployee:id,name')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->where('ticket_code', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%"),
                ),
            )
            ->latest()
            ->paginate(30)
            ->withQueryString();
        $summary = [
            'pending' => KitchenTicket::query()
                ->whereIn('status', [KitchenTicket::STATUS_PENDING, KitchenTicket::STATUS_PRINTING])
                ->count(),
            'printed' => KitchenTicket::query()->where('status', KitchenTicket::STATUS_PRINTED)->count(),
            'failed' => KitchenTicket::query()->where('status', KitchenTicket::STATUS_FAILED)->count(),
            'today' => KitchenTicket::query()->whereDate('created_at', today())->count(),
        ];

        return view('kitchen.queue', compact('tickets', 'summary', 'filters', 'search'));
    }

    public function print(Request $request, KitchenTicket $kitchenTicket): RedirectResponse
    {
        $kitchenTicket
            ->forceFill([
                'status' => KitchenTicket::STATUS_PRINTED,
                'printed_at' => now(),
                'print_attempts' => $kitchenTicket->print_attempts + 1,
                'last_error' => null,
            ])
            ->save();

        return redirect()->route('kitchen.tickets.printable', ['kitchenTicket' => $kitchenTicket, 'autoprint' => 1]);
    }

    public function printable(KitchenTicket $kitchenTicket): View
    {
        $kitchenTicket->load('createdByEmployee:id,name');

        return view('kitchen.ticket', compact('kitchenTicket'));
    }

    public function autoprint(): View
    {
        return view('kitchen.autoprint');
    }

    public function nextTicket(): JsonResponse
    {
        $ticket = DB::transaction(function (): ?KitchenTicket {
            KitchenTicket::query()
                ->where('status', KitchenTicket::STATUS_PRINTING)
                ->where('updated_at', '<', now()->subMinute())
                ->update(['status' => KitchenTicket::STATUS_PENDING]);
            $ticket = KitchenTicket::query()
                ->where('status', KitchenTicket::STATUS_PENDING)
                ->oldest()
                ->lockForUpdate()
                ->first();
            $ticket?->forceFill(['status' => KitchenTicket::STATUS_PRINTING])->save();

            return $ticket;
        });

        return response()->json(
            $ticket
                ? [
                    'ticket_id' => $ticket->id,
                    'ticket_code' => $ticket->ticket_code,
                    'print_url' => route('kitchen.tickets.printable', ['kitchenTicket' => $ticket, 'autoprint' => 1]),
                ]
                : null,
        );
    }

    public function completeAutomaticPrint(KitchenTicket $kitchenTicket): JsonResponse
    {
        if (in_array($kitchenTicket->status, [KitchenTicket::STATUS_PENDING, KitchenTicket::STATUS_PRINTING], true)) {
            $kitchenTicket
                ->forceFill([
                    'status' => KitchenTicket::STATUS_PRINTED,
                    'printed_at' => now(),
                    'print_attempts' => $kitchenTicket->print_attempts + 1,
                    'last_error' => null,
                ])
                ->save();
        }

        return response()->json(['message' => 'Đã ghi nhận lệnh in.']);
    }
}
