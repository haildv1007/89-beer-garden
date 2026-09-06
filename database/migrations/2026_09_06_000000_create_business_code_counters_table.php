<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_code_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('code_type', 4);
            $table->date('business_date');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['code_type', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_code_counters');
    }
};
