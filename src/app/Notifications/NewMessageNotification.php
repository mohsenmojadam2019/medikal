<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'message' => $this->message->message,
            'type' => $this->message->type,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'message_id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender->name,
                'message' => $this->message->message,
            ],
        ];
    }
}
