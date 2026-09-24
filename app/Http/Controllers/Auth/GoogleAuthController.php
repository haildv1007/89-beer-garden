<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Customer\GoogleCustomerLoginService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request, TypedSystemSettingResolver $settings): RedirectResponse
    {
        $oauth = $settings->googleOAuth();
        abort_if($oauth === null, 404);
        $state = Str::random(64);
        $request->session()->put('google_oauth_state', $state);
        $query = http_build_query([
            'client_id' => $oauth['client_id'],
            'redirect_uri' => route('auth.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(
        Request $request,
        TypedSystemSettingResolver $settings,
        GoogleCustomerLoginService $service,
    ): RedirectResponse {
        $expected = $request->session()->pull('google_oauth_state');
        if (! is_string($expected) || ! is_string($request->state) || ! hash_equals($expected, $request->state)) {
            throw ValidationException::withMessages([
                'google' => 'Phiên đăng nhập Google không hợp lệ. Vui lòng thử lại.',
            ]);
        }
        if ($request->filled('error') || ! $request->filled('code') || ($oauth = $settings->googleOAuth()) === null) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Không thể hoàn tất đăng nhập Google.']);
        }

        try {
            $tokenResponse = $this->googleHttp()
                ->asForm()
                ->timeout(8)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $oauth['client_id'],
                    'client_secret' => $oauth['client_secret'],
                    'code' => $request->string('code')->toString(),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => route('auth.google.callback'),
                ]);
            if ($tokenResponse->failed()) {
                $error = (string) $tokenResponse->json('error', 'token_exchange_failed');
                Log::warning('Google OAuth token exchange failed.', [
                    'status' => $tokenResponse->status(),
                    'error' => $error,
                    'description' => $tokenResponse->json('error_description'),
                    'redirect_uri' => route('auth.google.callback'),
                ]);

                return redirect()
                    ->route('login')
                    ->withErrors(['google' => $this->tokenErrorMessage($error)]);
            }

            $accessToken = $tokenResponse->json('access_token');
            if (! is_string($accessToken) || $accessToken === '') {
                return redirect()
                    ->route('login')
                    ->withErrors(['google' => 'Google không trả về access token hợp lệ.']);
            }
            $profileResponse = $this->googleHttp()
                ->withToken($accessToken)
                ->timeout(8)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
            if ($profileResponse->failed()) {
                Log::warning('Google OAuth userinfo failed.', ['status' => $profileResponse->status()]);

                return redirect()
                    ->route('login')
                    ->withErrors(['google' => 'Đã đăng nhập Google nhưng không lấy được thông tin tài khoản.']);
            }
            $user = $service->login($profileResponse->json());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Google OAuth connection failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Máy chủ chưa kết nối được tới Google. Vui lòng kiểm tra Internet hoặc chứng chỉ SSL của PHP.',
                ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('customer.home');
    }

    private function googleHttp(): PendingRequest
    {
        $caBundle = config('services.google.ca_bundle');

        return is_string($caBundle) && is_file($caBundle)
            ? Http::withOptions(['verify' => $caBundle])
            : Http::withOptions([]);
    }

    private function tokenErrorMessage(string $error): string
    {
        return match ($error) {
            'invalid_client' => 'Google từ chối Client ID hoặc Client Secret. Hãy sao chép và lưu lại đúng cặp thông tin trong Google Cloud.',
            'invalid_grant' => 'Mã đăng nhập đã hết hạn hoặc Redirect URI chưa khớp tuyệt đối. Hãy thử lại và kiểm tra URI trong Google Cloud.',
            'redirect_uri_mismatch' => 'Redirect URI không khớp cấu hình Google Cloud.',
            'access_denied' => 'Bạn đã hủy hoặc chưa cấp quyền đăng nhập Google.',
            default => 'Google từ chối yêu cầu đăng nhập ('.$error.'). Vui lòng kiểm tra cấu hình OAuth.',
        };
    }
}
