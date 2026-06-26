<?php

use Illuminate\Support\Facades\Broadcast;

// کانال چت برای هر کاربر
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// کانال حضور برای چت گروهی
Broadcast::channel('chat.presence.{roomId}', function ($user, $roomId) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
