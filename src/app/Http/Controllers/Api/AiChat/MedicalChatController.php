<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Services\AiChat\Chat\ChatService;
use App\Services\AiChat\Medical\MedicalFilterService;
use App\Services\AiChat\System\ConfigManager;
use App\Models\AiChat\MedicalQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalChatController extends Controller
{
    public function __construct(
        private ChatService $chatService,
        private MedicalFilterService $medicalFilter,
        private ConfigManager $configManager
    ) {}

    /**
     * پرسش پزشکی مستقیم
     */
    public function ask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:5000',
            'session_token' => 'nullable|string|exists:chat_sessions,session_token',
            'age' => 'nullable|integer|min:1|max:150',
            'gender' => 'nullable|in:male,female',
            'symptoms' => 'nullable|array|max:20',
            'symptoms.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // ساختاردهی سوال با اطلاعات اضافی
        $question = $request->question;
        $context = [];

        if ($request->age) {
            $context[] = "سن: {$request->age} سال";
        }
        if ($request->gender) {
            $context[] = "جنسیت: " . ($request->gender === 'male' ? 'مرد' : 'زن');
        }
        if ($request->symptoms) {
            $context[] = "علائم: " . implode('، ', $request->symptoms);
        }

        if (!empty($context)) {
            $question = "اطلاعات بیمار:\n" . implode("\n", $context) . "\n\nسوال: " . $request->question;
        }

        try {
            $response = $this->chatService->sendMessage(
                auth()->user(),
                $question,
                $request->session_token,
                ['category' => 'medical']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'answer' => $response['message']['content'],
                    'analysis' => $response['analysis'] ?? null,
                    'suggestions' => $response['suggestions'] ?? [],
                    'session' => $response['session'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بررسی علائم (Symptom Checker)
     */
    public function symptomCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symptoms' => 'required|array|min:1|max:20',
            'symptoms.*' => 'string|max:100',
            'duration' => 'nullable|string|max:50',
            'severity' => 'nullable|in:mild,moderate,severe',
            'age' => 'nullable|integer|min:1|max:150',
            'gender' => 'nullable|in:male,female',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // ساخت سوال بر اساس علائم
        $question = "علائم زیر را دارم:\n- " . implode("\n- ", $request->symptoms);

        if ($request->duration) {
            $question .= "\nمدت زمان: {$request->duration}";
        }
        if ($request->severity) {
            $question .= "\nشدت: " . match($request->severity) {
                'mild' => 'خفیف',
                'moderate' => 'متوسط',
                'severe' => 'شدید',
                default => $request->severity,
            };
        }
        if ($request->age) {
            $question .= "\nسن: {$request->age} سال";
        }
        if ($request->gender) {
            $question .= "\nجنسیت: " . ($request->gender === 'male' ? 'مرد' : 'زن');
        }

        $question .= "\n\nلطفاً راهنمایی کنید که چه کاری باید انجام دهم.";

        try {
            $response = $this->chatService->sendMessage(
                auth()->user(),
                $question,
                $request->session_token ?? null,
                ['category' => 'symptom']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'analysis' => $response['analysis'] ?? null,
                    'suggestions' => $response['suggestions'] ?? [],
                    'answer' => $response['message']['content'],
                    'disclaimer' => '⚠️ این تحلیل فقط جنبه اطلاع‌رسانی دارد و تشخیص نهایی با پزشک است.',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تاریخچه سوالات پزشکی کاربر
     */
    public function history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'category' => 'nullable|string',
            'severity' => 'nullable|in:normal,urgent,emergency',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = MedicalQuery::where('user_id', auth()->id());

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $queries = $query->orderBy('created_at', 'desc')
            ->skip($request->offset ?? 0)
            ->take($request->limit ?? 20)
            ->get()
            ->map(function ($query) {
                return [
                    'id' => $query->id,
                    'question' => $query->question,
                    'response' => $query->response,
                    'category' => $query->category,
                    'category_label' => $query->category ? \App\Enums\AiChat\MedicalCategory::tryFrom($query->category)?->label() : null,
                    'severity' => $query->severity,
                    'severity_label' => $query->severity ? \App\Enums\AiChat\SeverityLevel::tryFrom($query->severity)?->label() : null,
                    'is_handled' => $query->is_handled,
                    'ai_confidence' => $query->ai_confidence,
                    'created_at' => $query->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'queries' => $queries,
                'total' => MedicalQuery::where('user_id', auth()->id())->count(),
                'stats' => $this->medicalFilter->getStatistics(),
            ],
        ]);
    }

    /**
     * دریافت دسته‌بندی‌های پزشکی
     */
    public function categories()
    {
        $categories = [];
        foreach (\App\Enums\AiChat\MedicalCategory::cases() as $category) {
            $categories[] = [
                'value' => $category->value,
                'label' => $category->label(),
                'icon' => $category->icon(),
                'description' => $this->getCategoryDescription($category),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * دریافت توضیحات دسته‌بندی
     */
    private function getCategoryDescription($category): string
    {
        return match ($category->value) {
            'symptom' => 'سوالات مربوط به علائم بیماری‌ها',
            'disease' => 'سوالات درباره بیماری‌ها و تشخیص',
            'drug' => 'سوالات درباره داروها و عوارض',
            'emergency' => 'وضعیت‌های اورژانسی و فوری',
            'nutrition' => 'سوالات تغذیه و رژیم غذایی',
            'psychology' => 'سوالات روانشناسی و سلامت روان',
            default => 'سوالات عمومی پزشکی',
        };
    }

    /**
     * دریافت آمار پزشکی کاربر
     */
    public function stats()
    {
        $stats = $this->medicalFilter->getStatistics();

        // فیلتر کردن آمار برای کاربر فعلی
        $userStats = [
            'total' => MedicalQuery::where('user_id', auth()->id())->count(),
            'emergencies' => MedicalQuery::where('user_id', auth()->id())
                ->where('severity', 'emergency')
                ->count(),
            'urgent' => MedicalQuery::where('user_id', auth()->id())
                ->where('severity', 'urgent')
                ->count(),
            'by_category' => MedicalQuery::where('user_id', auth()->id())
                ->select('category')
                ->selectRaw('count(*) as total')
                ->groupBy('category')
                ->get()
                ->pluck('total', 'category')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $userStats,
        ]);
    }
}
