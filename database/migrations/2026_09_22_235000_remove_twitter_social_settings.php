<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_image_path',
        ])->delete();
    }

    public function down(): void
    {
        // Removed settings have no safe value to restore.
    }
};
