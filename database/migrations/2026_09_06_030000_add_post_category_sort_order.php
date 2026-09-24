<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_categories', fn (Blueprint $table) => $table->unsignedInteger('sort_order')->default(0)->index());
        foreach (DB::table('post_categories')->orderBy('id')->get() as $index => $category) {
            DB::table('post_categories')->where('id', $category->id)->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    public function down(): void
    {
        Schema::table('post_categories', fn (Blueprint $table) => $table->dropColumn('sort_order'));
    }
};
