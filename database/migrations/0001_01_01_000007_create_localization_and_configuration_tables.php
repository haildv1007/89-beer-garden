<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('field');
            $table->string('locale', 5);
            $table->text('source_text');
            $table->text('translated_text');
            $table->string('source_hash', 64);
            $table->string('source');
            $table->foreignId('updated_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->timestamps();
            $table->unique(
                ['translatable_type', 'translatable_id', 'field', 'locale'],
                'translations_entity_field_locale_unique',
            );
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type');
            $table->foreignId('updated_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->timestamps();
        });

        $this->mysqlCheck('translations', 'chk_translations_locale', "locale IN ('en','zh')");
        $this->mysqlCheck('translations', 'chk_translations_source', "source IN ('provider','manual')");
        $this->mysqlCheck('system_settings', 'chk_system_settings_type', "type IN ('integer','boolean','string')");
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('translations');
    }

    private function mysqlCheck(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }
};
