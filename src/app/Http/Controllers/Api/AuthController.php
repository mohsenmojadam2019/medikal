<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function loginWithMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $result = $this->authService->loginWithMobile($request->mobile);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $result = $this->authService->verifyOtp($request->mobile, $request->code);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function loginWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $result = $this->authService->loginWithEmail($request->email, $request->password);
            return $this->success($result, $result['message']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return $this->success(null, 'خروج با موفقیت انجام شد');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        // فقط اطلاعات کاربر رو برگردون (بدون role و permission)
        return $this->success([
            'user' => $user,
        ]);
    }
}
