<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('icon_color', 20)->default('#0b6b4f')->after('icon_class');
            $table->string('background_color', 20)->default('#e8f5ef')->after('icon_color');
        });

        foreach ([
            'hot' => ['#a33a11', '#fff0e6'],
            '10p' => ['#155b91', '#eaf6ff'],
            '15-20p' => ['#8b5a00', '#fff7df'],
            '30-40p' => ['#65489a', '#f5efff'],
            'co-ngay' => ['#17633f', '#eaf7ef'],
        ] as $slug => [$iconColor, $backgroundColor]) {
            DB::table('tags')->where('slug', $slug)->update([
                'icon_color' => $iconColor,
                'background_color' => $backgroundColor,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tags', fn (Blueprint $table) => $table->dropColumn(['icon_color', 'background_color']));
    }
};
