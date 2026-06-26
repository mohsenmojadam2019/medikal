<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\System\SystemService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    use ApiResponse;

    protected SystemService $systemService;

    public function __construct(SystemService $systemService)
    {
        $this->systemService = $systemService;
    }

    /**
     * اطلاعات سیستم
     */
    public function info()
    {
        $info = $this->systemService->getSystemInfo();
        return $this->success($info);
    }

    /**
     * لیست فایل‌های لاگ
     */
    public function logs()
    {
        $logs = $this->systemService->getLogFiles();
        return $this->success($logs);
    }

    /**
     * مشاهده محتوای لاگ
     */
    public function logContent(Request $request, $filename)
    {
        $lines = $request->get('lines', 100);
        try {
            $content = $this->systemService->getLogContent($filename, $lines);
            return $this->success([
                'filename' => $filename,
                'content' => $content,
                'lines' => $lines,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * حذف یک فایل لاگ
     */
    public function deleteLog($filename)
    {
        try {
            $this->systemService->deleteLogFile($filename);
            return $this->success(null, 'فایل لاگ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * پاک کردن تمام لاگ‌ها
     */
    public function clearLogs()
    {
        try {
            $count = $this->systemService->clearAllLogs();
            return $this->success(['count' => $count], "{$count} فایل لاگ با موفقیت پاک شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * پاک کردن تمام کش‌ها
     */
    public function clearCache()
    {
        $result = $this->systemService->clearAllCache();
        return $this->success($result, 'تمام کش‌های سیستم با موفقیت پاک شد');
    }
}
