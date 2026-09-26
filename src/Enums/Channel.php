<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Enums;

enum Channel: string
{
    case AiAssistant = 'ai_assistant';
    case Paid = 'paid';
    case Organic = 'organic';
    case Social = 'social';
    case Email = 'email';
    case Referral = 'referral';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::AiAssistant => 'AI assistant',
            self::Paid => 'Paid',
            self::Organic => 'Organic search',
            self::Social => 'Social',
            self::Email => 'Email',
            self::Referral => 'Referral',
            self::Direct => 'Direct',
        };
    }
}
