<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { Projection, RegionOption } from '@/types';

const props = defineProps<{
    projection: Projection;
    regions: RegionOption[];
    region: string | null;
    regionLabel: string | null;
    weekParityAnchor: string | null;
    hasLessons: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Prehľad', href: dashboard() }],
    },
});

const prefs = useForm({
    region: props.region ?? '',
    week_parity_anchor: props.weekParityAnchor ?? '',
});

function savePrefs() {
    prefs.transform((data) => ({
        region: data.region || null,
        week_parity_anchor: data.week_parity_anchor || null,
    })).patch('/settings/schedule-prefs', { preserveScroll: true });
}

const teacherFilter = ref<string>('all');
const flairFilter = ref<string>('all');

const filteredTeachers = computed(() =>
    props.projection.per_teacher.filter((row) => {
        if (teacherFilter.value !== 'all' && String(row.teacher_id) !== teacherFilter.value) {
            return false;
        }
        if (flairFilter.value !== 'all') {
            const id = row.flair?.id ? String(row.flair.id) : 'none';
            if (id !== flairFilter.value) return false;
        }
        return true;
    }),
);

const dateFormat = new Intl.DateTimeFormat('sk-SK', { day: 'numeric', month: 'long' });
function formatDate(value: string | null): string {
    return value ? dateFormat.format(new Date(value)) : '—';
}

function hoursLabel(n: number): string {
    if (n === 1) return '1 hodina';
    if (n >= 2 && n <= 4) return `${n} hodiny`;
    return `${n} hodín`;
}
</script>

<template>
    <Head title="Prehľad" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!-- Preferences -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Nastavenia rozvrhu</CardTitle>
                <CardDescription>
                    Kraj určuje jarné prázdniny. Kotviaci dátum je ľubovoľný deň, ktorý
                    padne do <em>nepárneho</em> týždňa — podľa neho sa počítajú párne/nepárne
                    týždne.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-56">
                        <Label class="mb-1.5">Kraj</Label>
                        <Select v-model="prefs.region">
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
                    <div class="w-48">
                        <Label class="mb-1.5" for="anchor">Nepárny týždeň (dátum)</Label>
                        <Input id="anchor" v-model="prefs.week_parity_anchor" type="date" />
                    </div>
                    <Button :disabled="prefs.processing" @click="savePrefs">Uložiť</Button>
                </div>
            </CardContent>
        </Card>

        <!-- Empty states -->
        <Card v-if="!projection.has_region">
            <CardContent class="py-8 text-center text-sm text-muted-foreground">
                Vyber si kraj vyššie, aby sme vedeli spočítať tvoje vyučovacie dni.
            </CardContent>
        </Card>

        <Card v-else-if="!hasLessons">
            <CardContent class="py-8 text-center text-sm text-muted-foreground">
                Zatiaľ nemáš žiadne hodiny.
                <Link href="/schedule" class="text-primary underline">Vyplň si rozvrh</Link>
                a pridaj
                <Link href="/teachers" class="text-primary underline">učiteľov</Link>.
            </CardContent>
        </Card>

        <template v-else>
            <!-- Totals -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Vyučovacích dní zostáva</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ projection.school_days_remaining }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        do {{ formatDate(projection.range_end) }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Hodín zostáva spolu</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ projection.total_remaining }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card
                    v-for="flair in projection.per_flair"
                    :key="flair.flair_id ?? 'none'"
                >
                    <CardHeader>
                        <CardDescription class="flex items-center gap-2">
                            <span
                                class="inline-block size-2.5 rounded-full"
                                :style="{ backgroundColor: flair.color }"
                            />
                            {{ flair.label }}
                        </CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ flair.remaining }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ hoursLabel(flair.remaining) }} do konca roka
                    </CardContent>
                </Card>
            </div>

            <!-- Per-teacher table -->
            <Card>
                <CardHeader class="flex-row flex-wrap items-center justify-between gap-4">
                    <div>
                        <CardTitle class="text-base">Koľko hodín ešte s kým</CardTitle>
                        <CardDescription>
                            Od dnes do konca školského roka pre {{ regionLabel }}.
                        </CardDescription>
                    </div>
                    <div class="flex gap-2">
                        <Select v-model="teacherFilter">
                            <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Všetci učitelia</SelectItem>
                                <SelectItem
                                    v-for="row in projection.per_teacher.filter((r) => r.teacher_id !== null)"
                                    :key="row.teacher_id ?? 0"
                                    :value="String(row.teacher_id)"
                                >
                                    {{ row.short_code }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="flairFilter">
                            <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Každé hodnotenie</SelectItem>
                                <SelectItem
                                    v-for="flair in projection.per_flair"
                                    :key="flair.flair_id ?? 'none'"
                                    :value="flair.flair_id ? String(flair.flair_id) : 'none'"
                                >
                                    {{ flair.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="py-2 font-medium">Učiteľ</th>
                                <th class="py-2 font-medium">Hodnotenie</th>
                                <th class="py-2 text-right font-medium">Zostáva</th>
                                <th class="py-2 text-right font-medium">Najbližšie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in filteredTeachers"
                                :key="row.teacher_id ?? 'none'"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">
                                    <span class="font-medium">{{ row.short_code }}</span>
                                    <span v-if="row.full_name" class="text-muted-foreground">
                                        — {{ row.full_name }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <span
                                        v-if="row.flair"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs"
                                        :style="{
                                            backgroundColor: row.flair.color + '22',
                                            color: row.flair.color,
                                        }"
                                    >
                                        {{ row.flair.label }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                                <td class="py-2 text-right tabular-nums">{{ row.remaining }}</td>
                                <td class="py-2 text-right text-muted-foreground">
                                    {{ formatDate(row.next_date) }}
                                </td>
                            </tr>
                            <tr v-if="filteredTeachers.length === 0">
                                <td colspan="4" class="py-6 text-center text-muted-foreground">
                                    Nič nezodpovedá filtru.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
