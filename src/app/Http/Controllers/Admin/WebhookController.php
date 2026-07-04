<?php
// app/Http/Controllers/Admin/WebhookController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    use ApiResponse;

    /**
     * دریافت وضعیت وب‌هوک
     */
    public function status()
    {
        try {
            $status = $this->getWebhookStatus();

            return $this->success([
                'enabled' => $status['enabled'] ?? false,
                'has_secret' => !empty($status['secret']),
                'provider' => $status['provider'] ?? 'isp',
                'last_run' => $status['last_run'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook status error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * تغییر وضعیت وب‌هوک
     */
    public function toggle()
    {
        try {
            $status = $this->getWebhookStatus();
            $newStatus = !($status['enabled'] ?? false);

            $this->saveWebhookStatus([
                'enabled' => $newStatus,
                'updated_at' => now(),
            ]);

            return $this->success(
                ['enabled' => $newStatus],
                $newStatus ? 'وب‌هوک با موفقیت فعال شد' : 'وب‌هوک با موفقیت غیرفعال شد'
            );
        } catch (\Exception $e) {
            Log::error('Webhook toggle error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت لاگ‌های وب‌هوک
     */
    public function logs(Request $request)
    {
        try {
            $query = DB::table('webhook_logs')
                ->orderBy('created_at', 'desc');

            if ($request->has('provider')) {
                $query->where('provider', $request->provider);
            }

            if ($request->has('event_type')) {
                $query->where('event_type', $request->event_type);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('provider', 'like', "%{$search}%")
                        ->orWhere('event_type', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%");
                });
            }

            $logs = $query->paginate($request->get('per_page', 20));

            return $this->success($logs);
        } catch (\Exception $e) {
            Log::error('Webhook logs error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * دریافت تنظیمات وب‌هوک - ✅ متد اضافه شد
     */
    public function settings()
    {
        try {
            $settings = $this->getWebhookSettings();

            return $this->success([
                'url' => $settings['url'] ?? null,
                'secret' => $settings['secret'] ?? null,
                'provider' => $settings['provider'] ?? 'isp',
                'events' => $settings['events'] ?? ['appointment_created', 'appointment_cancelled'],
                'retry_count' => $settings['retry_count'] ?? 3,
                'timeout' => $settings['timeout'] ?? 30,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook settings error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ذخیره تنظیمات وب‌هوک
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'nullable|string|max:255',
            'secret' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:50',
            'events' => 'nullable|array',
            'events.*' => 'string',
            'retry_count' => 'nullable|integer|min:1|max:10',
            'timeout' => 'nullable|integer|min:5|max:120',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $this->saveWebhookSettings($request->all());

            Log::info('Webhook settings updated', [
                'user_id' => auth()->id(),
            ]);

            return $this->success(
                $request->all(),
                'تنظیمات وب‌هوک با موفقیت ذخیره شد'
            );
        } catch (\Exception $e) {
            Log::error('Webhook settings update error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال تست وب‌هوک
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:255',
            'payload' => 'nullable|array',
            'provider' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $payload = $request->payload ?? ['test' => true, 'message' => 'Webhook test'];
            $provider = $request->provider ?? 'isp';

            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
            ]);

            $response = $client->post($request->url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Test' => 'true',
                    'X-Provider' => $provider,
                ],
            ]);

            DB::table('webhook_logs')->insert([
                'provider' => $provider,
                'event_type' => 'test',
                'payload' => json_encode($payload),
                'response' => json_encode(json_decode($response->getBody(), true)),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->success([
                'status_code' => $response->getStatusCode(),
                'response' => json_decode($response->getBody(), true),
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            ], 'تست وب‌هوک با موفقیت انجام شد');
        } catch (\Exception $e) {
            DB::table('webhook_logs')->insert([
                'provider' => $request->provider ?? 'isp',
                'event_type' => 'test',
                'payload' => json_encode($request->payload ?? ['test' => true]),
                'error_message' => $e->getMessage(),
                'status_code' => 500,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }

    // ===== متدهای کمکی =====

    private function getWebhookStatus()
    {
        $path = storage_path('app/webhook_status.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'enabled' => false,
            'provider' => 'isp',
            'secret' => null,
            'last_run' => null,
        ];
    }

    private function saveWebhookStatus(array $data)
    {
        $path = storage_path('app/webhook_status.json');
        $current = $this->getWebhookStatus();
        $merged = array_merge($current, $data);
        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT));
    }

    private function getWebhookSettings()
    {
        $path = storage_path('app/webhook_settings.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'url' => null,
            'secret' => null,
            'provider' => 'isp',
            'events' => ['appointment_created', 'appointment_cancelled'],
            'retry_count' => 3,
            'timeout' => 30,
        ];
    }

    private function saveWebhookSettings(array $data)
    {
        $path = storage_path('app/webhook_settings.json');
        $current = $this->getWebhookSettings();
        $merged = array_merge($current, $data);
        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT));
    }
}
