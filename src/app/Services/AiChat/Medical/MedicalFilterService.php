<?php

namespace App\Services\AiChat\Medical;

use App\Enums\AiChat\MedicalCategory;
use App\Enums\AiChat\SeverityLevel;
use App\Models\AiChat\MedicalQuery;
use App\Models\AiChat\ChatSession;
use App\Models\User;
use App\Services\AiChat\System\ConfigManager;
use Illuminate\Support\Facades\Log;

class MedicalFilterService
{
    private array $medicalKeywords;
    private array $emergencyKeywords;
    private array $symptomPatterns;
    private array $diseasePatterns;
    private array $drugPatterns;

    public function __construct(
        private ConfigManager $configManager
    ) {
        $this->medicalKeywords = $this->loadMedicalKeywords();
        $this->emergencyKeywords = $this->loadEmergencyKeywords();
        $this->symptomPatterns = $this->loadSymptomPatterns();
        $this->diseasePatterns = $this->loadDiseasePatterns();
        $this->drugPatterns = $this->loadDrugPatterns();
    }

    /**
     * فیلتر و تحلیل کامل سوال کاربر
     */
    public function filter(string $question, ?array $context = null): FilterResult
    {
        $question = trim($question);
        $lowerQuestion = mb_strtolower($question);
        
        // ۱. تشخیص اورژانسی بودن
        $isEmergency = $this->isEmergency($question);
        
        if ($isEmergency) {
            $symptoms = $this->extractSymptoms($question);
            $actions = $this->getEmergencyActions($question);
            
            return new FilterResult(
                isMedical: true,
                isEmergency: true,
                category: MedicalCategory::EMERGENCY,
                severity: SeverityLevel::EMERGENCY,
                detectedSymptoms: $symptoms,
                suggestedActions: $actions,
                confidence: 0.95,
                message: $question
            );
        }

        // ۲. تشخیص پزشکی بودن سوال
        $isMedical = $this->isMedical($question);
        
        if (!$isMedical) {
            return new FilterResult(
                isMedical: false,
                isEmergency: false,
                category: MedicalCategory::GENERAL,
                severity: SeverityLevel::NORMAL,
                detectedSymptoms: [],
                suggestedActions: [],
                confidence: 0.5,
                message: 'سوال شما پزشکی تشخیص داده نشد.'
            );
        }

        // ۳. دسته‌بندی سوال
        $category = $this->classify($question);
        
        // ۴. استخراج علائم
        $symptoms = $this->extractSymptoms($question);
        
        // ۵. تشخیص شدت
        $severity = $this->detectSeverity($question, $symptoms);
        
        // ۶. پیشنهاد اقدامات
        $actions = $this->suggestActions($category, $symptoms, $severity);

        // ۷. محاسبه امتیاز اطمینان
        $confidence = $this->calculateConfidence($question, $category, $symptoms);

        return new FilterResult(
            isMedical: true,
            isEmergency: false,
            category: $category,
            severity: $severity,
            detectedSymptoms: $symptoms,
            suggestedActions: $actions,
            confidence: $confidence,
            message: $question
        );
    }

    /**
     * تشخیص اورژانسی بودن سوال
     */
    public function isEmergency(string $question): bool
    {
        $lowerQuestion = mb_strtolower($question);
        
        foreach ($this->emergencyKeywords as $keyword) {
            if (mb_strpos($lowerQuestion, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        // تشخیص الگوهای اورژانسی
        $emergencyPatterns = [
            '/\b(سکته|قلب|خونریزی|تشنج|کما|سوختگی|تصادف|مسمومیت|غرق|قطع عضو)\b/u',
            '/\b(فوری|اورژانس|خطرناک|شدید|بحرانی)\s+(کمک|نیاز|وضعیت|حال)\b/u',
            '/\b(ایست|توقف|ناگهانی)\s+(قلب|تنفس|نفس)\b/u',
        ];

        foreach ($emergencyPatterns as $pattern) {
            if (preg_match($pattern, $lowerQuestion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * دسته‌بندی سوال پزشکی
     */
    public function classify(string $question): MedicalCategory
    {
        $lowerQuestion = mb_strtolower($question);

        // تشخیص دسته‌بندی بر اساس الگوها
        $score = [
            MedicalCategory::SYMPTOM->value => 0,
            MedicalCategory::DISEASE->value => 0,
            MedicalCategory::DRUG->value => 0,
            MedicalCategory::NUTRITION->value => 0,
            MedicalCategory::PSYCHOLOGY->value => 0,
            MedicalCategory::GENERAL->value => 0,
        ];

        // امتیازدهی به هر دسته‌بندی
        foreach ($this->symptomPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/u', $lowerQuestion)) {
                $score[MedicalCategory::SYMPTOM->value] += 2;
            }
        }

        foreach ($this->diseasePatterns as $pattern) {
            if (preg_match('/' . $pattern . '/u', $lowerQuestion)) {
                $score[MedicalCategory::DISEASE->value] += 2;
            }
        }

        foreach ($this->drugPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/u', $lowerQuestion)) {
                $score[MedicalCategory::DRUG->value] += 2;
            }
        }

        // تشخیص تغذیه
        if (preg_match('/\b(غذا|رژیم|تغذیه|ویتامین|میوه|سبزی|پروتئین|کربوهیدرات|چربی)\b/u', $lowerQuestion)) {
            $score[MedicalCategory::NUTRITION->value] += 1.5;
        }

        // تشخیص روانشناسی
        if (preg_match('/\b(روان|اعصاب|استرس|اضطراب|افسردگی|خواب|کابوس|توهم|وسواس)\b/u', $lowerQuestion)) {
            $score[MedicalCategory::PSYCHOLOGY->value] += 1.5;
        }

        // انتخاب دسته‌بندی با بیشترین امتیاز
        arsort($score);
        $topCategory = array_key_first($score);
        
        // اگر همه امتیازها صفر بودند، GENERAL را برمی‌گردانیم
        if ($score[$topCategory] === 0) {
            return MedicalCategory::GENERAL;
        }

        return MedicalCategory::from($topCategory);
    }

    /**
     * استخراج علائم از سوال
     */
    public function extractSymptoms(string $question): array
    {
        $symptoms = [];
        $lowerQuestion = mb_strtolower($question);

        // لیست علائم رایج
        $commonSymptoms = [
            'درد', 'تب', 'سرفه', 'تنگی نفس', 'خستگی', 'سرگیجه',
            'تهوع', 'استفراغ', 'اسهال', 'یبوست', 'سردرد', 'میگرن',
            'درد قفسه سینه', 'تپش قلب', 'تعریق', 'لرز', 'گرگرفتگی',
            'سوزش ادرار', 'تکرر ادرار', 'خون در ادرار', 'خون در مدفوع',
            'کاهش وزن', 'افزایش وزن', 'بی‌اشتهایی', 'پرخوری',
            'خشکی دهان', 'تشنگی زیاد', 'تاری دید', 'دو بینی',
            'بی‌حسی', 'گزگز', 'ضعف عضلانی', 'تشنج', 'غش',
            'التهاب', 'قرمزی', 'خارش', 'بثورات پوستی', 'زخم',
            'ترشح گوش', 'وزوز گوش', 'کاهش شنوایی', 'گرفتگی بینی',
            'خونریزی بینی', 'گلو درد', 'گرفتگی صدا', 'سوزش سر دل',
            'نفخ', 'گاز معده', 'درد شکم', 'درد پشت', 'درد مفاصل',
        ];

        foreach ($commonSymptoms as $symptom) {
            if (mb_strpos($lowerQuestion, mb_strtolower($symptom)) !== false) {
                $symptoms[] = $symptom;
            }
        }

        // تشخیص علائم ترکیبی
        $complexSymptomPatterns = [
            '/درد\s+(شکم|سینه|پشت|گردن|شانه|پا|دست|سر|گوش|دندان)/u',
            '/سوزش\s+(معده|ادرار|پوست|چشم)/u',
            '/خون\s+(در\s+)?(ادرار|مدفوع|خلط|استفراغ)/u',
            '/تنگ\s+نفس/u',
            '/تپش\s+قلب/u',
            '/بی‌حسی\s+(دست|پا|صورت)/u',
        ];

        foreach ($complexSymptomPatterns as $pattern) {
            if (preg_match($pattern, $lowerQuestion, $matches)) {
                $symptoms[] = $matches[0];
            }
        }

        return array_unique($symptoms);
    }

    /**
     * تشخیص شدت وضعیت
     */
    private function detectSeverity(string $question, array $symptoms): SeverityLevel
    {
        $lowerQuestion = mb_strtolower($question);
        
        // کلمات شدید
        $urgentWords = ['شدید', 'غیرقابل تحمل', 'ناتوان کننده', 'مداوم', 'پیشرونده'];
        $emergencyWords = ['ناگهانی', 'وحشتناک', 'بحرانی', 'تهدیدکننده', 'مرگبار'];

        foreach ($emergencyWords as $word) {
            if (mb_strpos($lowerQuestion, $word) !== false) {
                return SeverityLevel::EMERGENCY;
            }
        }

        foreach ($urgentWords as $word) {
            if (mb_strpos($lowerQuestion, $word) !== false) {
                return SeverityLevel::URGENT;
            }
        }

        // تشخیص بر اساس تعداد علائم
        $symptomCount = count($symptoms);
        if ($symptomCount >= 5) {
            return SeverityLevel::URGENT;
        } elseif ($symptomCount >= 3) {
            return SeverityLevel::URGENT;
        }

        return SeverityLevel::NORMAL;
    }

    /**
     * پیشنهاد اقدامات
     */
    private function suggestActions(MedicalCategory $category, array $symptoms, SeverityLevel $severity): array
    {
        $actions = [];

        if ($severity === SeverityLevel::EMERGENCY) {
            $actions[] = 'فوراً با اورژانس (115) تماس بگیرید';
            $actions[] = 'به نزدیک‌ترین بیمارستان مراجعه کنید';
            $actions[] = 'تا رسیدن کمک، حرکات اضافی انجام ندهید';
            return $actions;
        }

        if ($severity === SeverityLevel::URGENT) {
            $actions[] = 'در اسرع وقت به پزشک مراجعه کنید';
            $actions[] = 'از خوددرمانی خودداری کنید';
        }

        // پیشنهادات بر اساس دسته‌بندی
        switch ($category) {
            case MedicalCategory::SYMPTOM:
                $actions[] = 'علائم خود را دقیقاً یادداشت کنید';
                $actions[] = 'تغییرات علائم را پیگیری کنید';
                break;
            case MedicalCategory::DRUG:
                $actions[] = 'داروها را فقط با نسخه پزشک مصرف کنید';
                $actions[] = 'عوارض جانبی را جدی بگیرید';
                break;
            case MedicalCategory::NUTRITION:
                $actions[] = 'رژیم غذایی متعادل را رعایت کنید';
                $actions[] = 'مصرف آب کافی را فراموش نکنید';
                break;
            case MedicalCategory::PSYCHOLOGY:
                $actions[] = 'تکنیک‌های تنفس عمیق را تمرین کنید';
                $actions[] = 'در صورت نیاز به مشاور مراجعه کنید';
                break;
            default:
                $actions[] = 'برای تشخیص دقیق به پزشک مراجعه کنید';
        }

        return $actions;
    }

    /**
     * بررسی پزشکی بودن سوال
     */
    public function isMedical(string $question): bool
    {
        $lowerQuestion = mb_strtolower($question);
        
        // بررسی کلمات کلیدی پزشکی
        foreach ($this->medicalKeywords as $keyword) {
            if (mb_strpos($lowerQuestion, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        // بررسی الگوهای پزشکی
        $medicalPatterns = [
            '/\b(درد|تب|سرفه|تنگی|نفس|فشار|دیابت|قلب|معده|سرطان|آلرژی|مسمومیت|بارداری|اعصاب|روان|پوست|مو|دندان|چشم|گوش|حلق|بینی|ریه|کبد|کلیه|تیروئید)\b/u',
            '/\b(دارو|درمان|بیماری|سلامتی|تغذیه|ویتامین|آزمایش|جراحی|واکسن|عفونت|ویروس|باکتری|قارچ|آسم|میگرن|صرع|ام‌اس|پارکینسون|آلزایمر)\b/u',
            '/\b(چاقی|لاغری|رژیم|ورزش|سلامت|پزشک|بیمارستان|کلینیک|داروخانه|نسخه|دکتر)\b/u',
        ];

        foreach ($medicalPatterns as $pattern) {
            if (preg_match($pattern, $lowerQuestion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * دریافت کلمات کلیدی پیشنهادی
     */
    public function getSuggestions(string $question): array
    {
        $suggestions = [];
        $lowerQuestion = mb_strtolower($question);

        // اگر سوال خیلی کوتاه است
        if (mb_strlen($question) < 10) {
            return [
                'لطفاً سوال خود را با جزئیات بیشتر مطرح کنید.',
                'مثال: "درد شکم همراه با تب و تهوع دارم"',
            ];
        }

        // اگر سوال شامل علائم است
        $symptoms = $this->extractSymptoms($question);
        if (!empty($symptoms)) {
            $suggestions[] = 'علائمی که ذکر کردید: ' . implode('، ', $symptoms);
            $suggestions[] = 'آیا تغییرات دیگری هم در وضعیت خود احساس می‌کنید؟';
        }

        // اگر سوال در مورد دارو است
        if (preg_match('/\b(دارو|قرص|شربت|پماد|آمپول)\b/u', $lowerQuestion)) {
            $suggestions[] = 'آیا این دارو توسط پزشک تجویز شده است؟';
            $suggestions[] = 'دوز مصرفی را دقیقاً رعایت کنید.';
        }

        return $suggestions;
    }

    /**
     * بارگذاری کلمات کلیدی پزشکی
     */
    private function loadMedicalKeywords(): array
    {
        return $this->configManager->get('filter.keywords', [
            'درد', 'تب', 'سرفه', 'تنگی نفس', 'فشار خون', 'دیابت',
            'قلب', 'معده', 'سرطان', 'آلرژی', 'مسمومیت', 'بارداری',
            'اعصاب', 'روان', 'پوست', 'مو', 'دندان', 'چشم', 'گوش',
            'حلق', 'بینی', 'ریه', 'کبد', 'کلیه', 'تیروئید',
            'دارو', 'درمان', 'بیماری', 'سلامتی', 'تغذیه', 'ویتامین',
        ]);
    }

    /**
     * بارگذاری کلمات کلیدی اورژانسی
     */
    private function loadEmergencyKeywords(): array
    {
        return $this->configManager->get('filter.emergency_keywords', [
            'اورژانس', 'سکته', 'مرگ', 'خونریزی شدید', 'ایست قلبی',
            'تصادف', 'غرق شدگی', 'مسمومیت شدید', 'تشنج', 'کما',
            'شوک', 'آنافیلاکسی', 'سوختگی شدید', 'قطع عضو',
        ]);
    }

    /**
     * بارگذاری الگوهای علائم
     */
    private function loadSymptomPatterns(): array
    {
        return [
            '\b(درد|سوزش|خارش|التهاب|تورم|قرمزی|بی‌حسی|گزگز|سفتی|تپش|تنگی)\b',
            '\b(سرفه|عطسه|تب|لرز|تعریق|گرگرفتگی|تهوع|استفراغ|اسهال|یبوست)\b',
            '\b(ضعف|خستگی|بی‌حالی|سرگیجه|غش|تشنج|لرزش|کرامپ|اسپاسم)\b',
        ];
    }

    /**
     * بارگذاری الگوهای بیماری
     */
    private function loadDiseasePatterns(): array
    {
        return [
            '\b(سرطان|دیابت|فشار\s+خون|قلب|عروق|کبد|کلیه|ریه|معده|روده|پانکراس|تیروئید)\b',
            '\b(آسم|آلرژی|میگرن|صرع|ام‌اس|پارکینسون|آلزایمر|سکته|تشنج)\b',
            '\b(عفونت|ویروس|باکتری|قارچ|پارازیت|التهاب|خودایمنی|ژنتیک)\b',
        ];
    }

    /**
     * بارگذاری الگوهای دارو
     */
    private function loadDrugPatterns(): array
    {
        return [
            '\b(دارو|قرص|شربت|پماد|آمپول|کپسول|قطره|شیاف|اسپری|چسب)\b',
            '\b(مسکن|آنتی‌بیوتیک|ضدعفونی|ضدالتهاب|ضدحساسیت|ضددرد|ضدتشنج)\b',
            '\b(ویتامین|مکمل|معدنی|گیاهی|طب\s+سنتی|داروی\s+گیاهی)\b',
        ];
    }

    /**
     * دریافت اقدامات اورژانسی
     */
    private function getEmergencyActions(string $question): array
    {
        $actions = ['تماس با اورژانس 115'];
        
        $lowerQuestion = mb_strtolower($question);
        
        // تشخیص نوع اورژانس برای اقدامات خاص
        if (preg_match('/\b(قلب|سینه|تنفس|نفس)\b/u', $lowerQuestion)) {
            $actions[] = 'دراز بکشید و حرکات اضافی انجام ندهید';
            $actions[] = 'اگر داروی قلبی دارید، مصرف کنید (با صلاحدید پزشک)';
        }
        
        if (preg_match('/\b(خونریزی|زخم|بریدگی|ضربه)\b/u', $lowerQuestion)) {
            $actions[] = 'با پارچه تمیز روی زخم فشار دهید';
            $actions[] = 'زخم را بالاتر از سطح قلب قرار دهید';
        }
        
        if (preg_match('/\b(سوختگی|آتش|گرما)\b/u', $lowerQuestion)) {
            $actions[] = 'محل سوختگی را با آب سرد خنک کنید (نه یخ)';
            $actions[] = 'روی سوختگی پماد یا کرم نزنید';
        }
        
        if (preg_match('/\b(مسمومیت|سم|شیمیایی)\b/u', $lowerQuestion)) {
            $actions[] = 'در صورت امکان ماده مسموم‌کننده را شناسایی کنید';
            $actions[] = 'استفراغ را تحریک نکنید مگر اینکه پزشک بگوید';
        }
        
        return $actions;
    }

    /**
     * محاسبه امتیاز اطمینان
     */
    private function calculateConfidence(string $question, MedicalCategory $category, array $symptoms): float
    {
        $confidence = 0.5;
        
        // افزایش اطمینان بر اساس دسته‌بندی دقیق
        if ($category !== MedicalCategory::GENERAL) {
            $confidence += 0.2;
        }
        
        // افزایش اطمینان در صورت تشخیص علائم
        $symptomCount = count($symptoms);
        if ($symptomCount > 0) {
            $confidence += min($symptomCount * 0.05, 0.25);
        }
        
        // افزایش اطمینان در صورت طولانی بودن سوال
        $questionLength = mb_strlen($question);
        if ($questionLength > 50) {
            $confidence += 0.05;
        }
        
        return min(max($confidence, 0), 1);
    }

    /**
     * ثبت سوال پزشکی در دیتابیس
     */
    public function logMedicalQuery(
        ChatSession $session,
        User $user,
        array $data
    ): MedicalQuery {
        return MedicalQuery::create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'question' => $data['question'],
            'response' => $data['response'] ?? null,
            'category' => $data['category'] ?? null,
            'severity' => $data['severity'] ?? SeverityLevel::NORMAL->value,
            'detected_symptoms' => $data['detected_symptoms'] ?? null,
            'suggested_actions' => $data['suggested_actions'] ?? null,
            'is_handled' => $data['is_handled'] ?? false,
            'ai_confidence' => $data['ai_confidence'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * دریافت آمار سوالات پزشکی
     */
    public function getStatistics(): array
    {
        return [
            'total' => MedicalQuery::count(),
            'emergencies' => MedicalQuery::where('severity', SeverityLevel::EMERGENCY->value)->count(),
            'urgent' => MedicalQuery::where('severity', SeverityLevel::URGENT->value)->count(),
            'handled' => MedicalQuery::where('is_handled', true)->count(),
            'unhandled' => MedicalQuery::where('is_handled', false)->count(),
            'by_category' => MedicalQuery::select('category')
                ->selectRaw('count(*) as total')
                ->groupBy('category')
                ->get()
                ->pluck('total', 'category')
                ->toArray(),
        ];
    }
}
