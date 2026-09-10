<?php

namespace App\Enums;

/**
 * The eight self-governing regions (kraje) of Slovakia.
 *
 * The backing value is a stable ASCII slug shared with the frontend; the
 * human label is Slovak. Spring break (jarné prázdniny) rotates across three
 * groups of regions, which is the only school break that depends on the region.
 */
enum Region: string
{
    case Bratislavsky = 'bratislavsky';
    case Trnavsky = 'trnavsky';
    case Trenciansky = 'trenciansky';
    case Nitriansky = 'nitriansky';
    case Zilinsky = 'zilinsky';
    case Banskobystricky = 'banskobystricky';
    case Presovsky = 'presovsky';
    case Kosicky = 'kosicky';

    public function label(): string
    {
        return match ($this) {
            self::Bratislavsky => 'Bratislavský kraj',
            self::Trnavsky => 'Trnavský kraj',
            self::Trenciansky => 'Trenčiansky kraj',
            self::Nitriansky => 'Nitriansky kraj',
            self::Zilinsky => 'Žilinský kraj',
            self::Banskobystricky => 'Banskobystrický kraj',
            self::Presovsky => 'Prešovský kraj',
            self::Kosicky => 'Košický kraj',
        };
    }

    /**
     * Which of the three jarné prázdniny rotation groups this region belongs to.
     */
    public function springBreakGroup(): int
    {
        return match ($this) {
            self::Bratislavsky, self::Nitriansky, self::Trnavsky => 1,
            self::Banskobystricky, self::Zilinsky, self::Trenciansky => 2,
            self::Kosicky, self::Presovsky => 3,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $r) => ['value' => $r->value, 'label' => $r->label()],
            self::cases(),
        );
    }
}
