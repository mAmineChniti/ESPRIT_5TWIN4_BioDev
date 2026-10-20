<?php

namespace App\Enums;

/**
 * The languages NutriTrace speaks, written once here.
 *
 * The URL prefix is the primary source of truth ({en,fr,ar} is part of every
 * route); the visitor's locale cookie is secondary; English is the default.
 * Validation, the language switcher and the `<html lang dir>` attributes all
 * read this list, so adding a language cannot leave a stale copy behind.
 */
enum Locale: string
{
    case English = 'en';
    case French = 'fr';
    case Arabic = 'ar';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::French => 'Français',
            self::Arabic => 'العربية',
        };
    }

    public function dir(): string
    {
        return match ($this) {
            self::Arabic => 'rtl',
            default => 'ltr',
        };
    }

    /**
     * The language name to give an AI model, which does not read locale codes.
     */
    public function promptLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::French => 'French',
            self::Arabic => 'Arabic',
        };
    }

    public static function resolve(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::English;
    }
}
