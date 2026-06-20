<?php

namespace App\Services\Sms;

use App\Models\SmsGatewaySetting;
use App\Services\Sms\Contracts\SmsInterface;
use Illuminate\Support\Facades\Log;

class SmsGatewayManager
{
    protected array $gateways = [];
    protected array $priorities = [];
    protected ?SmsInterface $defaultGateway = null;
    protected bool $loaded = false;

    public function __construct()
    {
        $this->loadGatewaysFromDatabase();
    }

    protected function loadGatewaysFromDatabase(): void
    {
        if ($this->loaded) {
            return;
        }

        $settings = SmsGatewaySetting::where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($settings as $setting) {
            $gateway = $this->createGatewayInstance($setting);
            if ($gateway) {
                $this->gateways[$setting->gateway] = $gateway;
                $this->priorities[$setting->gateway] = $setting->priority;

                if ($setting->is_default) {
                    $this->defaultGateway = $gateway;
                }
            }
        }

        $this->loaded = true;
    }

    /**
     * دریافت نام درگاه فعال فعلی
     */
    public function getGatewayName(): string
    {
        $gateways = $this->getGatewaysSortedByPriority();

        if (empty($gateways)) {
            return 'fake';
        }

        $firstGateway = reset($gateways);
        return $firstGateway->getGatewayName();
    }

    /**
     * دریافت نام درگاه پیش‌فرض
     */
    public function getDefaultGatewayName(): string
    {
        $this->loadGatewaysFromDatabase();

        if ($this->defaultGateway) {
            return $this->defaultGateway->getGatewayName();
        }

        $first = reset($this->gateways);
        return $first ? $first->getGatewayName() : 'fake';
    }

    /**
     * بررسی اینکه آیا درگاه خاصی فعال است
     */
    public function isGatewayActive(string $gatewayName): bool
    {
        $this->loadGatewaysFromDatabase();
        return isset($this->gateways[$gatewayName]);
    }

    /**
     * دریافت لیست درگاه‌های فعال
     */
    public function getAvailableGateways(): array
    {
        $this->loadGatewaysFromDatabase();
        return array_keys($this->gateways);
    }

    /**
     * دریافت درگاه‌های مرتب شده بر اساس اولویت
     */
    public function getGatewaysSortedByPriority(): array
    {
        $this->loadGatewaysFromDatabase();

        $sorted = $this->gateways;

        uasort($sorted, function ($a, $b) {
            $nameA = $a->getGatewayName();
            $nameB = $b->getGatewayName();
            $priorityA = $this->priorities[$nameA] ?? 999;
            $priorityB = $this->priorities[$nameB] ?? 999;
            return $priorityA <=> $priorityB;
        });

        return $sorted;
    }

    /**
     * دریافت یک درگاه خاص
     */
    public function getGateway(string $gatewayName): ?SmsInterface
    {
        $this->loadGatewaysFromDatabase();
        return $this->gateways[$gatewayName] ?? null;
    }

    protected function createGatewayInstance(SmsGatewaySetting $setting): ?SmsInterface
    {
        $config = config("sms.gateways.{$setting->gateway}", []);

        if (empty($config)) {
            Log::warning("SMS gateway config not found", ['gateway' => $setting->gateway]);
            return null;
        }

        $class = $config['class'] ?? null;

        if (!$class || !class_exists($class)) {
            Log::warning("SMS gateway class not found", [
                'gateway' => $setting->gateway,
                'class' => $class
            ]);
            return null;
        }

        // ادغام تنظیمات دیتابیس با کانفیگ
        $dbConfig = $setting->config ?? [];
        $mergedConfig = array_merge($config, $dbConfig);

        return new $class($mergedConfig);
    }

    /**
     * ارسال پیام با اولویت‌بندی (Fallback)
     */
    public function sendWithFallback(string $phone, string $message, ?int $campaignId = null): array
    {
        $gatewaysByPriority = $this->getGatewaysSortedByPriority();

        if (empty($gatewaysByPriority)) {
            return [
                'success' => false,
                'error' => 'هیچ درگاه پیامکی فعالی وجود ندارد'
            ];
        }

        $lastError = null;

        foreach ($gatewaysByPriority as $gateway) {
            try {
                $result = $gateway->send($phone, $message);

                // ذخیره لاگ
                $this->saveLog($phone, $message, $result, $gateway->getGatewayName(), $campaignId);

                if ($result['success']) {
                    return array_merge($result, [
                        'gateway' => $gateway->getGatewayName(),
                        'fallback_used' => false
                    ]);
                }

                $lastError = $result['error'] ?? 'Unknown error';

                // ذخیره لاگ خطا
                $this->saveLog($phone, $message, $result, $gateway->getGatewayName(), $campaignId, false);

                Log::warning("SMS gateway failed, trying next", [
                    'gateway' => $gateway->getGatewayName(),
                    'error' => $lastError,
                    'phone' => $phone
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $this->saveLog($phone, $message, ['success' => false, 'error' => $lastError], $gateway->getGatewayName(), $campaignId, false);
            }
        }

        return [
            'success' => false,
            'error' => $lastError,
            'message' => 'همه درگاه‌ها ناموفق بودند'
        ];
    }

    /**
     * ارسال با درگاه خاص
     */
    public function sendVia(string $gatewayName, string $phone, string $message, ?int $campaignId = null): array
    {
        $this->loadGatewaysFromDatabase();

        $gateway = $this->gateways[$gatewayName] ?? null;

        if (!$gateway) {
            return ['success' => false, 'error' => "Gateway '{$gatewayName}' not found"];
        }

        $result = $gateway->send($phone, $message);
        $this->saveLog($phone, $message, $result, $gatewayName, $campaignId, $result['success']);

        return $result;
    }

    /**
     * ارسال با درگاه پیش‌فرض
     */
    public function send(string $phone, string $message, ?int $campaignId = null): array
    {
        return $this->sendVia($this->getDefaultGatewayName(), $phone, $message, $campaignId);
    }

    /**
     * ارسال پیامک با الگو (pattern)
     */
    public function sendPatternWithFallback(string $phone, string $patternCode, array $params, ?int $campaignId = null): array
    {
        $gatewaysByPriority = $this->getGatewaysSortedByPriority();

        $lastError = null;

        foreach ($gatewaysByPriority as $gateway) {
            try {
                $result = $gateway->sendPattern($phone, $patternCode, $params);

                $this->saveLog($phone, json_encode($params), $result, $gateway->getGatewayName(), $campaignId, $result['success']);

                if ($result['success']) {
                    return $result;
                }

                $lastError = $result['error'] ?? 'Unknown error';

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        return ['success' => false, 'error' => $lastError];
    }

    /**
     * ارسال الگو با درگاه خاص
     */
    public function sendPatternVia(string $gatewayName, string $phone, string $patternCode, array $params, ?int $campaignId = null): array
    {
        $this->loadGatewaysFromDatabase();

        $gateway = $this->gateways[$gatewayName] ?? null;

        if (!$gateway) {
            return ['success' => false, 'error' => "Gateway '{$gatewayName}' not found"];
        }

        $result = $gateway->sendPattern($phone, $patternCode, $params);
        $this->saveLog($phone, json_encode($params), $result, $gatewayName, $campaignId, $result['success']);

        return $result;
    }

    /**
     * دریافت اعتبار درگاه پیش‌فرض
     */
    public function getBalance(): float
    {
        $gateway = $this->getGateway($this->getDefaultGatewayName());
        return $gateway ? $gateway->getBalance() : 0;
    }

    /**
     * دریافت اعتبار یک درگاه خاص
     */
    public function getBalanceVia(string $gatewayName): float
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway ? $gateway->getBalance() : 0;
    }

    /**
     * ذخیره لاگ ارسال
     */
    protected function saveLog(string $phone, string $message, array $result, string $gateway, ?int $campaignId, bool $isSuccess = true): void
    {
        try {
            \App\Models\SmsLog::create([
                'campaign_id' => $campaignId,
                'phone' => $phone,
                'message' => $message,
                'gateway_used' => $gateway,
                'status' => $isSuccess ? 'sent' : 'failed',
                'error_message' => $isSuccess ? null : ($result['error'] ?? null),
                'response_data' => $result,
                'sent_at' => $isSuccess ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save SMS log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * بازنشانی و بارگذاری مجدد درگاه‌ها
     */
    public function refresh(): void
    {
        $this->loaded = false;
        $this->gateways = [];
        $this->priorities = [];
        $this->defaultGateway = null;
        $this->loadGatewaysFromDatabase();
    }

    /**
     * دریافت آمار درگاه‌ها
     */
    public function getGatewaysStats(): array
    {
        $this->loadGatewaysFromDatabase();

        $stats = [];
        foreach ($this->gateways as $name => $gateway) {
            $stats[$name] = [
                'name' => $name,
                'priority' => $this->priorities[$name] ?? 999,
                'is_default' => $this->defaultGateway && $this->defaultGateway->getGatewayName() === $name,
                'balance' => $gateway->getBalance(),
            ];
        }

        return $stats;
    }
}
