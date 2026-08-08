<?php

namespace App\Services\PublicInquiry;

use App\Models\PublicInquiry;

class PublicInquiryService
{
    public function create(array $data): PublicInquiry
    {
        $data['status'] = 'new';
        return PublicInquiry::create($data);
    }
}
