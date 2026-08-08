<?php
namespace App\Enums\AiChat;
enum MedicalCategory:string { case GENERAL='general';case SYMPTOM='symptom';case DISEASE='disease';case Product='product';case NUTRITION='nutrition';case PSYCHOLOGY='psychology';case EMERGENCY='emergency';public function label():string{return match($this){self::GENERAL=>'عمومی',self::SYMPTOM=>'علائم',self::DISEASE=>'بیماری',self::Product=>'دارو',self::NUTRITION=>'تغذیه',self::PSYCHOLOGY=>'روان‌شناسی',self::EMERGENCY=>'اورژانس'};}public function getPromptSlug():string{return $this->value;} }
