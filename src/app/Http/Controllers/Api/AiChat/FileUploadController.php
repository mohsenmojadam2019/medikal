<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Services\AiChat\Chat\FileUploadService;
use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * آپلود فایل
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:5120',
            'session_token' => 'nullable|string|exists:chat_sessions,session_token',
            'message_id' => 'nullable|integer|exists:chat_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // بررسی نوع فایل
        $allowedTypes = $this->fileUploadService->getAllowedTypes();
        $extension = $request->file('file')->getClientOriginalExtension();

        if (!in_array(strtolower($extension), $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'نوع فایل مجاز نیست. انواع مجاز: ' . implode(', ', $allowedTypes),
            ], 400);
        }

        // بررسی حجم فایل
        $maxSize = $this->fileUploadService->getMaxSize();
        if ($request->file('file')->getSize() > $maxSize * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'حجم فایل بیش از حد مجاز است. حداکثر: ' . $maxSize . 'KB',
            ], 400);
        }

        try {
            $file = $this->fileUploadService->upload(
                $request->file('file'),
                auth()->user(),
                $request->session_token,
                $request->message_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => [
                        'id' => $file->id,
                        'original_name' => $file->original_name,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                        'file_size_human' => $file->file_size_human,
                        'mime_type' => $file->mime_type,
                        'file_type' => $file->file_type,
                        'url' => $file->getUrl(),
                        'thumb_url' => $file->getThumbUrl(),
                        'medium_url' => $file->getMediumUrl(),
                        'expires_at' => $file->expires_at?->toDateTimeString(),
                    ],
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
     * دانلود فایل
     */
    public function download($id)
    {
        $file = ChatFile::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'فایل یافت نشد',
            ], 404);
        }

        // بررسی دسترسی
        if ($file->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این فایل ندارید',
            ], 403);
        }

        return $this->fileUploadService->download($file);
    }

    /**
     * حذف فایل
     */
    public function delete($id)
    {
        $file = ChatFile::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'فایل یافت نشد',
            ], 404);
        }

        if ($file->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این فایل ندارید',
            ], 403);
        }

        $this->fileUploadService->delete($file);

        return response()->json([
            'success' => true,
            'message' => 'فایل با موفقیت حذف شد',
        ]);
    }

    /**
     * لیست فایل‌های جلسه
     */
    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:chat_sessions,session_token',
            'file_type' => 'nullable|string|in:image,pdf,document,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)
            ->where('user_id', auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه‌ای با این توکن یافت نشد',
            ], 404);
        }

        $query = ChatFile::where('session_id', $session->id)
            ->where('user_id', auth()->id());

        if ($request->file_type) {
            $query->where('file_type', $request->file_type);
        }

        $files = $query->orderBy('created_at', 'desc')->get()->map(function ($file) {
            return [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'file_size_human' => $file->file_size_human,
                'mime_type' => $file->mime_type,
                'file_type' => $file->file_type,
                'url' => $file->getUrl(),
                'thumb_url' => $file->getThumbUrl(),
                'created_at' => $file->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'files' => $files,
                'total' => $files->count(),
            ],
        ]);
    }
}
