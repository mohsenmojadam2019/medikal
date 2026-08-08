<?php
namespace App\Enums\AiChat;
enum SeverityLevel:string { case NORMAL='normal'; case URGENT='urgent'; case EMERGENCY='emergency'; public function label():string{return match($this){self::NORMAL=>'عادی',self::URGENT=>'فوری',self::EMERGENCY=>'اورژانسی'};} }
