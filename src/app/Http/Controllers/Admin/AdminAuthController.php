<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\AdminAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    use ApiResponse;

    protected AdminAuthService $adminAuthService;

    public function __construct(AdminAuthService $adminAuthService)
    {
        $this->adminAuthService = $adminAuthService;
    }

    /**
     * ورود ادمین با ایمیل و رمز عبور
     */
    public function loginWithEmail(Request $request)
    {
//        // تنظیم دستی هدرها برای پاسخ
//        $response = response()->json();
//
//        // تنظیم CORS headers
//        $response->header('Access-Control-Allow-Origin', 'http://localhost:3001');
//        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
//        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
//        $response->header('Access-Control-Allow-Credentials', 'true');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $result = $this->adminAuthService->loginWithEmail(
                $request->email,
                $request->password
            );

            return $this->success($result, 'ورود با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروج ادمین
     */
    public function logout(Request $request)
    {
        $this->adminAuthService->logout($request->user());
        return $this->success(null, 'خروج با موفقیت انجام شد');
    }

    /**
     * اطلاعات کاربر جاری (ادمین)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // بررسی دسترسی ادمین
        if (!$this->adminAuthService->checkAdminAccess($user)) {
            return $this->error('شما دسترسی به این بخش ندارید', 403);
        }

        $result = $this->adminAuthService->getCurrentUser($user);
        return $this->success($result);
    }
}
