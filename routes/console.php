<?php

use App\Services\Reservation\RefreshLateReservationsService;
use App\Services\AIChat\AIChatProviderInput;
use App\Services\AIChat\GeminiAIChatProvider;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:refresh-late', function (RefreshLateReservationsService $service) {
    $this->info($service->refresh().' đặt bàn được chuyển sang trạng thái trễ check-in.');
})->purpose('Cập nhật các đặt bàn đã quá giờ check-in');

Schedule::command('reservations:refresh-late')->everyMinute()->withoutOverlapping();

Artisan::command('ai-chat:diagnose', function (
    TypedSystemSettingResolver $settings,
    GeminiAIChatProvider $provider,
) {
    $gemini = $settings->gemini();
    $this->line('Enabled: '.($gemini['enabled'] ? 'yes' : 'no'));
    $this->line('API key: '.(is_string($gemini['api_key']) && $gemini['api_key'] !== '' ? 'configured' : 'missing'));
    $this->line('Model: '.$gemini['model']);

    if (! $gemini['enabled'] || ! is_string($gemini['api_key']) || $gemini['api_key'] === '') {
        $this->error('Gemini is not fully configured.');
        return 1;
    }

    $result = $provider->classify(new AIChatProviderInput('vi', [
        ['role' => 'user', 'content' => 'Xin chào'],
    ]));
    if (! $result->available) {
        $this->error('Connection failed: '.$result->failureCategory);
        $this->line('HTTP status: '.($result->httpStatus ?? 'none'));
        $this->line('Duration: '.$result->durationMs.' ms');
        return 1;
    }

    $this->info('Gemini connection successful.');
    $this->line('Duration: '.$result->durationMs.' ms');
    return 0;
})->purpose('Kiểm tra cấu hình và kết nối Gemini mà không hiển thị API key');
