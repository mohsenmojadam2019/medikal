<?php
namespace App\Enums\AiChat;
enum ChatSessionStatus:string { case ACTIVE='active'; case EXPIRED='expired'; case CLOSED='closed'; case DELETED='deleted'; }
