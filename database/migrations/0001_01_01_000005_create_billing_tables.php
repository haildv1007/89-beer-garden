<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('discount_type');
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('min_order_amount');
            $table->unsignedBigInteger('max_discount_amount')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code')->unique();
            $table->foreignId('dining_session_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount');
            $table->unsignedBigInteger('total_amount');
            $table->string('status');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->foreignId('processed_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->string('transaction_reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['bill_id', 'status']);
        });

        $this->mysqlCheck('vouchers', 'chk_vouchers_discount_type', "discount_type IN ('fixed','percentage')");
        $this->mysqlCheck(
            'vouchers',
            'chk_vouchers_amounts_non_negative',
            'discount_value >= 0 AND min_order_amount >= 0 AND (max_discount_amount IS NULL OR max_discount_amount >= 0)',
        );
        $this->mysqlCheck('vouchers', 'chk_vouchers_usage_non_negative', 'usage_limit IS NULL OR usage_limit >= 0');
        $this->mysqlCheck(
            'bills',
            'chk_bills_money_non_negative',
            'subtotal >= 0 AND discount_amount >= 0 AND total_amount >= 0',
        );
        $this->mysqlCheck('payments', 'chk_payments_amount_positive', 'amount > 0');
        $this->mysqlCheck('payments', 'chk_payments_method', "method IN ('cash','bank_transfer','other')");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('vouchers');
    }

    private function mysqlCheck(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }
};
