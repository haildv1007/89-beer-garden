<?php

namespace App\Services\DiningSession;

use App\Models\DiningSession;
use App\Models\KitchenTicket;
use Illuminate\Support\Collection;

class BuildDiningSessionTimelineService
{
    /** @return Collection<int, array{at:mixed, category:string, title:string, description:?string, actor:?string, code:?string}> */
    public function build(DiningSession $session): Collection
    {
        $session->loadMissing([
            'openedBy:id,name', 'completedBy:id,name', 'cancelledBy:id,name',
            'reservation.confirmedBy:id,name', 'reservation.cancelledBy:id,name',
            'orders.createdByEmployee:id,name', 'orders.createdByCustomer:id,name',
            'orders.items.cancelledBy:id,name', 'orders.kitchenTickets.createdByEmployee:id,name',
            'tableTransfers.fromTable:id,name', 'tableTransfers.toTable:id,name', 'tableTransfers.transferredBy:id,name',
            'bill.voucher:id,code', 'bill.payments.processedBy:id,name',
        ]);

        $events = collect();
        $add = function ($at, string $category, string $title, ?string $description = null, ?string $actor = null, ?string $code = null) use ($events): void {
            if ($at) $events->push(compact('at', 'category', 'title', 'description', 'actor', 'code'));
        };

        if ($reservation = $session->reservation) {
            $arrival = $reservation->reservation_date?->format('d/m/Y').' '.substr((string) $reservation->reservation_time, 0, 5);
            $checkInTable = $session->tableTransfers->sortBy('transferred_at')->first()?->fromTable?->name ?? $session->table->name;
            $add($reservation->created_at, 'reservation', 'Khách gửi yêu cầu đặt bàn', "Dự kiến đến {$arrival} · {$reservation->party_size} khách", null, $reservation->reservation_code);
            $add($reservation->confirmed_at, 'reservation', 'Yêu cầu đặt bàn được xác nhận', null, $reservation->confirmedBy?->name, $reservation->reservation_code);
            $add($reservation->checked_in_at, 'session', 'Khách đã check-in', 'Bố trí tại '.$checkInTable, $session->openedBy?->name, $reservation->reservation_code);
            if (! $session->cancelled_at) $add($reservation->cancelled_at, 'reservation', 'Yêu cầu đặt bàn đã bị hủy', $reservation->cancellation_reason, $reservation->cancelledBy?->name, $reservation->reservation_code);
        }

        $add($session->started_at, 'session', 'Mở phiên phục vụ', $session->guest_count.' khách · '.$session->table->name, $session->openedBy?->name, $session->session_code);

        foreach ($session->orders as $order) {
            $items = $order->items->map(fn ($item) => $item->quantity.'× '.$item->product_name)->join(', ');
            $actor = $order->createdByEmployee?->name ?? $order->createdByCustomer?->name ?? 'Hệ thống';
            $add($order->ordered_at, 'order', 'Tạo lượt gọi món', $items.($order->note ? ' · Ghi chú: '.$order->note : ''), $actor, $order->order_code);

            foreach ($order->kitchenTickets->where('type', '!=', KitchenTicket::TYPE_ORDER) as $ticket) {
                $changes = collect($ticket->payload['items'] ?? [])->pluck('change')->filter()->join('; ');
                $title = $ticket->type === KitchenTicket::TYPE_CANCELLATION ? 'Gửi hủy món xuống bếp' : 'Gửi điều chỉnh món xuống bếp';
                $add($ticket->created_at, 'kitchen', $title, $changes ?: null, $ticket->createdByEmployee?->name, $ticket->ticket_code);
            }
            foreach ($order->items->whereNotNull('cancelled_at') as $item) {
                $add($item->cancelled_at, 'order', 'Hủy món '.$item->product_name, $item->cancellation_reason, $item->cancelledBy?->name, $order->order_code);
            }
        }

        foreach ($session->tableTransfers as $transfer) {
            $add($transfer->transferred_at, 'session', 'Đổi bàn '.$transfer->fromTable->name.' → '.$transfer->toTable->name, $transfer->reason, $transfer->transferredBy?->name, $session->session_code);
        }

        if ($bill = $session->bill) {
            $add($bill->issued_at ?? $bill->created_at, 'payment', 'Mở hóa đơn', number_format($bill->total_amount).' ₫', null, $bill->bill_code);
            if ($bill->voucher) $add($bill->updated_at, 'payment', 'Áp dụng voucher '.$bill->voucher->code, 'Giảm '.number_format($bill->discount_amount).' ₫', null, $bill->bill_code);
            foreach ($bill->payments as $payment) {
                $method = match ($payment->method) { 'cash' => 'tiền mặt', 'bank_transfer' => 'chuyển khoản', default => 'phương thức khác' };
                $add($payment->created_at, 'payment', 'Tạo giao dịch thanh toán', number_format($payment->amount).' ₫ · '.$method, $payment->processedBy?->name, $payment->payment_code);
                $add($payment->paid_at, 'payment', 'Thanh toán thành công', number_format($payment->amount).' ₫'.($payment->transaction_reference ? ' · Mã đối soát '.$payment->transaction_reference : ''), $payment->processedBy?->name ?? 'Hệ thống', $payment->payment_code);
                $add($payment->failed_at, 'payment', 'Thanh toán thất bại', $payment->failure_reason, $payment->processedBy?->name ?? 'Hệ thống', $payment->payment_code);
            }
        }

        if ($session->cancelled_at) {
            $add($session->cancelled_at, 'session', 'Hủy phiên phục vụ', $session->cancellation_reason, $session->cancelledBy?->name, $session->session_code);
        } else {
            $add($session->ended_at, 'session', 'Kết thúc phiên phục vụ', null, $session->completedBy?->name, $session->session_code);
        }

        return $events->sortBy('at')->values();
    }
}
