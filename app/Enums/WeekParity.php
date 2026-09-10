<?php

namespace App\Enums;

/**
 * When a recurring lesson takes place in the A/B week rotation
 * (párny / nepárny týždeň) used by Slovak schools.
 */
enum WeekParity: string
{
    case Every = 'every';
    case Odd = 'odd';   // nepárny týždeň
    case Even = 'even';  // párny týždeň

    public function label(): string
    {
        return match ($this) {
            self::Every => 'Každý týždeň',
            self::Odd => 'Nepárny týždeň',
            self::Even => 'Párny týždeň',
        };
    }

    /**
     * Does a lesson with this parity occur on a date whose week parity is $on?
     */
    public function matches(self $on): bool
    {
        return $this === self::Every || $this === $on;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $p) => ['value' => $p->value, 'label' => $p->label()],
            self::cases(),
        );
    }
}
