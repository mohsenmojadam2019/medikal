<?php

namespace App\Enums;

enum FormFieldTypeEnum: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case FILE = 'file';
    case SIGNATURE = 'signature';
    case SECTION = 'section';
    case HTML = 'html';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'متن کوتاه',
            self::TEXTAREA => 'متن بلند',
            self::NUMBER => 'عدد',
            self::EMAIL => 'ایمیل',
            self::PHONE => 'تلفن',
            self::DATE => 'تاریخ',
            self::DATETIME => 'تاریخ و زمان',
            self::SELECT => 'انتخاب از لیست',
            self::MULTI_SELECT => 'انتخاب چندگانه',
            self::CHECKBOX => 'چک‌باکس',
            self::RADIO => 'دکمه رادیویی',
            self::FILE => 'آپلود فایل',
            self::SIGNATURE => 'امضای دیجیتال',
            self::SECTION => 'بخش جداگانه',
            self::HTML => 'متن HTML',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEXT => '📝',
            self::TEXTAREA => '📄',
            self::NUMBER => '🔢',
            self::EMAIL => '📧',
            self::PHONE => '📱',
            self::DATE => '📅',
            self::DATETIME => '🕐',
            self::SELECT => '📋',
            self::MULTI_SELECT => '✅',
            self::CHECKBOX => '☑️',
            self::RADIO => '⭕',
            self::FILE => '📎',
            self::SIGNATURE => '✍️',
            self::SECTION => '📌',
            self::HTML => '🌐',
        };
    }

    public function isRequired(): bool
    {
        return in_array($this, [
            self::TEXT,
            self::NUMBER,
            self::EMAIL,
            self::PHONE,
            self::DATE,
            self::SELECT,
        ]);
    }

    public function hasOptions(): bool
    {
        return in_array($this, [
            self::SELECT,
            self::MULTI_SELECT,
            self::CHECKBOX,
            self::RADIO,
        ]);
    }
}
