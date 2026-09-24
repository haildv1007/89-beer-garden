<?php

namespace Database\Seeders;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalOperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LocalOperationsDemoSeeder may only run locally or in tests.');
        }

        DB::transaction(function (): void {
            $employee = Employee::query()->updateOrCreate(
                ['employee_code' => 'DEMO-OPERATIONS'],
                ['name' => 'Nguyễn Minh Quân', 'phone' => '0908900101', 'position' => 'Quản lý ca', 'status' => EmployeeStatus::Active],
            );
            $products = Product::query()->where('status', Product::STATUS_ACTIVE)->where('price', '>', 0)->limit(12)->get();

            if ($products->count() < 4) {
                throw new RuntimeException('Cần ít nhất 4 sản phẩm đang hoạt động. Hãy chạy Menu89Seeder trước.');
            }

            $customers = collect([
                ['Nguyễn Hoàng Lan', '0908900201', 'lan.demo@89.local'],
                ['Trần Gia Huy', '0908900202', 'huy.demo@89.local'],
                ['Lê Thanh Mai', '0908900203', 'mai.demo@89.local'],
                ['Phạm Minh Tuấn', '0908900204', 'tuan.demo@89.local'],
                ['Võ Thảo Vy', '0908900205', 'vy.demo@89.local'],
                ['Đặng Quốc Khánh', '0908900206', 'khanh.demo@89.local'],
            ])->map(fn (array $item) => Customer::withTrashed()->updateOrCreate(
                ['email' => $item[2]],
                ['name' => $item[0], 'phone' => $item[1], 'note' => 'Dữ liệu minh họa giao diện local.'],
            ));

            $tables = collect(range(1, 8))->map(function (int $number): RestaurantTable {
                return RestaurantTable::withTrashed()->updateOrCreate(
                    ['code' => sprintf('DEMO-OPS-T%02d', $number)],
                    [
                        'name' => sprintf('Bàn demo %02d', $number),
                        'capacity' => [4, 6, 8, 4, 10, 6, 4, 8][$number - 1],
                        'location' => $number <= 4 ? 'Ngoài trời' : 'Trong nhà',
                        'runtime_status' => RestaurantTableStatus::Available,
                        'is_active' => true,
                    ],
                );
            });

            $reservations = $this->seedReservations($customers, $tables, $employee);
            $this->seedDiningSessions($customers, $tables, $reservations, $employee, $products);
            $this->seedFulfillmentOrders($customers, $employee, $products);
        });
    }

    private function seedReservations($customers, $tables, Employee $employee): array
    {
        $definitions = [
            ['DEMO-RSV-PENDING-1', 0, null, 1, '19:00', 4, ReservationStatus::Pending],
            ['DEMO-RSV-PENDING-2', 1, null, 2, '20:00', 6, ReservationStatus::Pending],
            ['DEMO-RSV-CONFIRMED-1', 2, 2, 1, '18:30', 5, ReservationStatus::Confirmed],
            ['DEMO-RSV-CONFIRMED-2', 3, 3, 2, '19:30', 4, ReservationStatus::Confirmed],
            ['DEMO-RSV-CHECKEDIN-1', 4, 0, 0, '18:00', 4, ReservationStatus::CheckedIn],
            ['DEMO-RSV-COMPLETED-1', 5, 4, -1, '19:00', 7, ReservationStatus::Completed],
            ['DEMO-RSV-NOSHOW-1', 1, 5, -2, '18:30', 3, ReservationStatus::NoShow],
        ];
        $result = [];

        foreach ($definitions as [$code, $customerIndex, $tableIndex, $dayOffset, $time, $partySize, $status]) {
            $scheduled = now()->addDays($dayOffset)->setTimeFromTimeString($time);
            $confirmed = in_array($status, [ReservationStatus::Confirmed, ReservationStatus::CheckedIn, ReservationStatus::Completed], true);
            $checkedIn = in_array($status, [ReservationStatus::CheckedIn, ReservationStatus::Completed], true);
            $result[$code] = Reservation::query()->updateOrCreate(['reservation_code' => $code], [
                'customer_id' => $customers[$customerIndex]->id,
                'table_id' => $tableIndex === null ? null : $tables[$tableIndex]->id,
                'reservation_date' => $scheduled->toDateString(),
                'reservation_time' => $scheduled->format('H:i:s'),
                'party_size' => $partySize,
                'status' => $status,
                'note' => $status === ReservationStatus::Pending ? 'Ưu tiên khu vực thoáng, gọi xác nhận trước.' : 'Khách demo chụp giao diện.',
                'confirmed_by_employee_id' => $confirmed ? $employee->id : null,
                'confirmed_at' => $confirmed ? $scheduled->copy()->subHours(4) : null,
                'checked_in_at' => $checkedIn ? $scheduled : null,
                'completed_at' => $status === ReservationStatus::Completed ? $scheduled->copy()->addHours(2) : null,
                'no_show_at' => $status === ReservationStatus::NoShow ? $scheduled->copy()->addMinutes(30) : null,
                'cancelled_at' => null,
            ]);
        }

        return $result;
    }

    private function seedDiningSessions($customers, $tables, array $reservations, Employee $employee, $products): void
    {
        $definitions = [
            ['DEMO-SESSION-ACTIVE-1', 0, 0, 'DEMO-RSV-CHECKEDIN-1', DiningSessionStatus::Active, 4, 0, 2],
            ['DEMO-SESSION-ACTIVE-2', 1, 1, null, DiningSessionStatus::Active, 6, 0, 3],
            ['DEMO-SESSION-DONE-1', 2, 2, null, DiningSessionStatus::Completed, 4, -1, 3],
            ['DEMO-SESSION-DONE-2', 3, 3, null, DiningSessionStatus::Completed, 5, -2, 4],
            ['DEMO-SESSION-DONE-3', 4, 4, 'DEMO-RSV-COMPLETED-1', DiningSessionStatus::Completed, 7, -4, 4],
            ['DEMO-SESSION-DONE-4', 5, 5, null, DiningSessionStatus::Completed, 3, -7, 2],
        ];

        foreach ($definitions as $sessionIndex => [$code, $customerIndex, $tableIndex, $reservationCode, $status, $guests, $dayOffset, $itemCount]) {
            $startedAt = now()->addDays($dayOffset)->setTime(18 + ($sessionIndex % 2), 15);
            $session = DiningSession::query()->updateOrCreate(['session_code' => $code], [
                'table_id' => $tables[$tableIndex]->id,
                'customer_id' => $customers[$customerIndex]->id,
                'reservation_id' => $reservationCode ? $reservations[$reservationCode]->id : null,
                'opened_by_employee_id' => $employee->id,
                'completed_by_employee_id' => $status === DiningSessionStatus::Completed ? $employee->id : null,
                'status' => $status,
                'started_at' => $startedAt,
                'ended_at' => $status === DiningSessionStatus::Completed ? $startedAt->copy()->addHours(2)->addMinutes(20) : null,
                'guest_count' => $guests,
                'note' => $status === DiningSessionStatus::Active ? 'Đang phục vụ – bàn demo.' : 'Phiên đã hoàn tất – dữ liệu demo.',
            ]);
            $tables[$tableIndex]->forceFill(['runtime_status' => $status === DiningSessionStatus::Active ? RestaurantTableStatus::Occupied : RestaurantTableStatus::Available])->save();

            $oldOrderIds = Order::query()->where('dining_session_id', $session->id)->pluck('id');
            OrderItem::query()->whereIn('order_id', $oldOrderIds)->delete();
            Order::query()->whereIn('id', $oldOrderIds)->delete();
            $order = Order::query()->forceCreate([
                'order_code' => 'DEMO-ORD-'.str_pad((string) ($sessionIndex + 1), 2, '0', STR_PAD_LEFT),
                'dining_session_id' => $session->id,
                'created_by_employee_id' => $employee->id,
                'source' => 'staff',
                'note' => 'Lượt gọi món minh họa.',
                'ordered_at' => $startedAt->copy()->addMinutes(15),
            ]);
            $subtotal = 0;
            foreach ($products->take($itemCount)->values() as $index => $product) {
                $quantity = ($index % 2) + 1;
                $subtotal += $product->price * $quantity;
                OrderItem::query()->forceCreate([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $quantity,
                    'status' => $status === DiningSessionStatus::Completed ? OrderItemStatus::Served : [OrderItemStatus::Waiting, OrderItemStatus::Preparing, OrderItemStatus::Ready][$index % 3],
                    'note' => $index === 0 ? 'Ít cay, không hành.' : null,
                ]);
            }

            if ($status === DiningSessionStatus::Completed) {
                $paidAt = $session->ended_at;
                $bill = Bill::query()->updateOrCreate(['dining_session_id' => $session->id], [
                    'bill_code' => 'DEMO-BILL-'.str_pad((string) ($sessionIndex + 1), 2, '0', STR_PAD_LEFT),
                    'subtotal' => $subtotal,
                    'discount_amount' => $sessionIndex % 2 === 0 ? 20000 : 0,
                    'total_amount' => $subtotal - ($sessionIndex % 2 === 0 ? 20000 : 0),
                    'status' => BillStatus::Paid,
                    'issued_at' => $paidAt,
                ]);
                Payment::query()->where('bill_id', $bill->id)->delete();
                Payment::query()->forceCreate([
                    'payment_code' => 'DEMO-PAY-'.str_pad((string) ($sessionIndex + 1), 2, '0', STR_PAD_LEFT),
                    'bill_id' => $bill->id,
                    'processed_by_employee_id' => $employee->id,
                    'method' => $sessionIndex % 2 === 0 ? Payment::METHOD_BANK_TRANSFER : Payment::METHOD_CASH,
                    'amount' => $bill->total_amount,
                    'received_amount' => $bill->total_amount,
                    'change_amount' => 0,
                    'status' => PaymentStatus::Success,
                    'transaction_reference' => $sessionIndex % 2 === 0 ? 'DEMO-BANK-'.($sessionIndex + 1) : null,
                    'paid_at' => $paidAt,
                ]);
            }
        }
    }

    private function seedFulfillmentOrders($customers, Employee $employee, $products): void
    {
        $definitions = [
            ['DEMO-NQ-001', 0, 'pickup', FulfillmentOrder::STATUS_PENDING, FulfillmentOrder::PAYMENT_UNPAID, -0, 2],
            ['DEMO-NQ-002', 1, 'delivery', FulfillmentOrder::STATUS_PENDING, FulfillmentOrder::PAYMENT_PAID, -0, 3],
            ['DEMO-NQ-003', 2, 'pickup', FulfillmentOrder::STATUS_CONFIRMED, FulfillmentOrder::PAYMENT_PAID, -1, 2],
            ['DEMO-NQ-004', 3, 'delivery', FulfillmentOrder::STATUS_CONFIRMED, FulfillmentOrder::PAYMENT_PAID, -2, 4],
            ['DEMO-NQ-005', 4, 'pickup', FulfillmentOrder::STATUS_CONFIRMED, FulfillmentOrder::PAYMENT_UNPAID, -3, 2],
            ['DEMO-NQ-006', 5, 'delivery', FulfillmentOrder::STATUS_REJECTED, FulfillmentOrder::PAYMENT_UNPAID, -4, 3],
            ['DEMO-NQ-007', 0, 'pickup', FulfillmentOrder::STATUS_CONFIRMED, FulfillmentOrder::PAYMENT_PAID, -6, 3],
            ['DEMO-NQ-008', 1, 'delivery', FulfillmentOrder::STATUS_CONFIRMED, FulfillmentOrder::PAYMENT_PAID, -10, 4],
        ];

        foreach ($definitions as $index => [$code, $customerIndex, $type, $status, $paymentStatus, $dayOffset, $itemCount]) {
            $placedAt = now()->addDays($dayOffset)->setTime(10 + ($index % 8), 20);
            $selected = $products->slice($index % max(1, $products->count() - $itemCount), $itemCount)->values();
            $subtotal = $selected->values()->map(
                fn (Product $product, int $itemIndex) => $product->price * (($itemIndex % 2) + 1),
            )->sum();
            $shipping = $type === FulfillmentOrder::TYPE_DELIVERY ? 25000 : 0;
            $order = FulfillmentOrder::query()->updateOrCreate(['order_code' => $code], [
                'customer_id' => $customers[$customerIndex]->id,
                'fulfillment_type' => $type,
                'payment_option' => $index % 2 === 0 ? FulfillmentOrder::PAYMENT_ON_RECEIPT : FulfillmentOrder::PAYMENT_BANK_TRANSFER,
                'status' => $status,
                'confirmed_by_employee_id' => $status === FulfillmentOrder::STATUS_CONFIRMED ? $employee->id : null,
                'confirmed_at' => $status === FulfillmentOrder::STATUS_CONFIRMED ? $placedAt->copy()->addMinutes(10) : null,
                'rejected_at' => $status === FulfillmentOrder::STATUS_REJECTED ? $placedAt->copy()->addMinutes(15) : null,
                'rejection_reason' => $status === FulfillmentOrder::STATUS_REJECTED ? 'Khách đổi lịch nhận món.' : null,
                'customer_name' => $customers[$customerIndex]->name,
                'phone' => $customers[$customerIndex]->phone,
                'email' => $customers[$customerIndex]->email,
                'delivery_address' => $type === FulfillmentOrder::TYPE_DELIVERY ? '89 Đường Lý Thái Tổ, TP. Bắc Ninh' : null,
                'requested_for' => $placedAt->copy()->addHours(2),
                'note' => $index % 2 === 0 ? 'Gọi trước 10 phút khi món sẵn sàng.' : 'Giao giờ hành chính.',
                'subtotal' => $subtotal,
                'discount_amount' => $index % 3 === 0 ? 15000 : 0,
                'shipping_fee' => $shipping,
                'total_amount' => $subtotal - ($index % 3 === 0 ? 15000 : 0) + $shipping,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentStatus === FulfillmentOrder::PAYMENT_PAID ? ($index % 2 === 0 ? 'cash' : 'bank_transfer') : null,
                'received_amount' => $paymentStatus === FulfillmentOrder::PAYMENT_PAID ? $subtotal - ($index % 3 === 0 ? 15000 : 0) + $shipping : null,
                'change_amount' => $paymentStatus === FulfillmentOrder::PAYMENT_PAID ? 0 : null,
                'paid_at' => $paymentStatus === FulfillmentOrder::PAYMENT_PAID ? $placedAt->copy()->addMinutes(20) : null,
                'paid_by_employee_id' => $paymentStatus === FulfillmentOrder::PAYMENT_PAID ? $employee->id : null,
                'placed_at' => $placedAt,
            ]);
            FulfillmentOrderItem::query()->where('fulfillment_order_id', $order->id)->delete();
            foreach ($selected as $itemIndex => $product) {
                $quantity = ($itemIndex % 2) + 1;
                FulfillmentOrderItem::query()->forceCreate([
                    'fulfillment_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $quantity,
                    'note' => $itemIndex === 0 ? 'Đóng gói riêng nước chấm.' : null,
                    'status' => $status === FulfillmentOrder::STATUS_CONFIRMED ? OrderItemStatus::Ready : OrderItemStatus::Waiting,
                ]);
            }
        }
    }
}
