<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard, login, register } from '@/routes';
import type { PublicStats, RegionOption } from '@/types';

const props = defineProps<{
    regions: RegionOption[];
    selectedRegion: string | null;
    stats: PublicStats | null;
}>();

const region = computed({
    get: () => props.selectedRegion ?? '',
    set: (value: string) => {
        router.get(
            '/',
            { region: value },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    },
});

const dateFormat = new Intl.DateTimeFormat('sk-SK', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function formatDate(value: string): string {
    return dateFormat.format(new Date(value));
}

function formatRange(start: string, end: string, singleDay: boolean): string {
    return singleDay ? formatDate(start) : `${formatDate(start)} – ${formatDate(end)}`;
}

function countdownLabel(days: number): string {
    if (days === 1) return '1 deň';
    if (days >= 2 && days <= 4) return `${days} dni`;
    return `${days} dní`;
}
</script>

<template>
    <Head title="Do konca školského roka" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-6">
            <span class="text-sm font-semibold tracking-tight">Vyjebaná Škola</span>
            <nav class="flex items-center gap-2 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-md border px-4 py-1.5 hover:bg-muted"
                >
                    Prehľad
                </Link>
                <template v-else>
                    <Link :href="login()" class="rounded-md px-4 py-1.5 hover:bg-muted">
                        Prihlásiť sa
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-md border px-4 py-1.5 hover:bg-muted"
                    >
                        Registrácia
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-4xl px-6 pb-24">
            <div class="mt-8 max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Koľko školy ešte zostáva?
                </h1>
                <p class="mt-3 text-muted-foreground">
                    Vyber si kraj a zisti, koľko dní ostáva do konca školského roka,
                    kedy sú najbližšie prázdniny a koľko „čistých“ vyučovacích dní ťa
                    ešte čaká.
                </p>
            </div>

            <div class="mt-6 max-w-sm">
                <label class="mb-1.5 block text-sm font-medium">Kraj</label>
                <Select v-model="region">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Vyber kraj…" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in regions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="stats" class="mt-10 space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardDescription>Do konca školského roka</CardDescription>
                            <CardTitle class="text-4xl font-bold tabular-nums">
                                {{ countdownLabel(stats.days_to_school_year_end) }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Vyučovanie končí {{ formatDate(stats.school_year_end) }}.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription>Čisté vyučovacie dni</CardDescription>
                            <CardTitle class="text-4xl font-bold tabular-nums">
                                {{ stats.raw_school_days_left }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Pracovné dni bez víkendov, štátnych sviatkov a prázdnin pre
                            {{ stats.region_label }}.
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Najbližšie voľno</CardTitle>
                        <CardDescription>
                            Štátne sviatky a školské prázdniny v {{ stats.region_label }}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y">
                            <li
                                v-for="holiday in stats.nearest_holidays"
                                :key="holiday.name + holiday.start"
                                class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p class="font-medium">{{ holiday.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatRange(holiday.start, holiday.end, holiday.single_day) }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        holiday.type === 'statny_sviatok'
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    "
                                >
                                    {{ holiday.ongoing ? 'práve teraz' : `o ${countdownLabel(holiday.days_until)}` }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <p v-else class="mt-10 text-sm text-muted-foreground">
                Vyber kraj a zobrazia sa čísla.
            </p>
        </main>
    </div>
</template>
