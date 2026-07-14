<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    use ApiResponse;

    public function upload(Request $request)
    {
        return $this->success([
            'id' => 'file_' . time(),
            'filename' => $request->file('file')->getClientOriginalName() ?? 'file.pdf',
            'size' => $request->file('file')->getSize() ?? 1024
        ], 'فایل با موفقیت آپلود شد');
    }

    public function list(Request $request)
    {
        return $this->success([
            'files' => [],
            'total' => 0
        ]);
    }

    public function download($id)
    {
        return $this->success([
            'file_url' => '/downloads/' . $id . '.pdf'
        ]);
    }

    public function delete($id)
    {
        return $this->success(null, 'فایل با موفقیت حذف شد');
    }
}
