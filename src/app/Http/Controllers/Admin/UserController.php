<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * لیست کاربران
     */
    public function index(Request $request)
    {
        if ($request->has('search')) {
            $users = $this->userService->searchUsers($request->search);
        } else {
            $users = $this->userService->getUsers();
        }

        return view('admin.users.index', compact('users'));
    }

    /**
     * نمایش فرم ایجاد کاربر
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * ذخیره کاربر جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = $this->userService->createUser($request->all());

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * نمایش اطلاعات کاربر
     */
    public function show(int $id)
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            abort(404, 'کاربر یافت نشد.');
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * نمایش فرم ویرایش کاربر
     */
    public function edit(int $id)
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            abort(404, 'کاربر یافت نشد.');
        }

        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * بروزرسانی کاربر
     */
    public function update(Request $request, int $id)
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            abort(404, 'کاربر یافت نشد.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|unique:users,phone,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->userService->updateUser($user, $request->all());

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف کاربر
     */
    public function destroy(int $id)
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            abort(404, 'کاربر یافت نشد.');
        }

        // جلوگیری از حذف کاربر جاری
        if ($user->id === auth()->id()) {
            return back()->with('error', 'نمی‌توانید خودتان را حذف کنید.');
        }

        $this->userService->deleteUser($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(int $id)
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            abort(404, 'کاربر یافت نشد.');
        }

        $this->userService->toggleStatus($user);

        return back()->with('success', 'وضعیت کاربر با موفقیت تغییر کرد.');
    }
}
