<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_inquiry_can_be_created(): void
    {
        $response = $this->postJson('/api/public-inquiries', [
            'type' => 'medical_tourism', 'locale' => 'ar', 'name' => 'Test User',
            'phone' => '+989120000000', 'email' => 'test@example.com',
            'subject' => 'Treatment request', 'message' => 'Please contact me about treatment.',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('public_inquiries', ['type' => 'medical_tourism', 'locale' => 'ar']);
    }

    public function test_public_inquiry_validates_supported_locale(): void
    {
        $this->postJson('/api/public-inquiries', ['locale' => 'de'])->assertUnprocessable();
    }
}
