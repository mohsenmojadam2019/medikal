<?php

namespace App\Contracts\AiChat;

use App\Models\AiChat\AIProvider;

interface AIConnectorInterface
{
    public function generate(
        AIProvider $provider,
        array $messages,
        array $options = []
    ): array;

    public function test(
        AIProvider $provider,
        string $prompt
    ): array;

    public function models(
        AIProvider $provider
    ): array;
}
