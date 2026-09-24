<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        foreach (['food' => 'Ẩm thực', 'event' => 'Sự kiện', 'promotion' => 'Ưu đãi', 'story' => 'Câu chuyện'] as $slug => $name) {
            DB::table('post_categories')->insert(['slug' => $slug, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->string('content_format', 10)->default('text');
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('content_format'));
        Schema::dropIfExists('post_categories');
    }
};
