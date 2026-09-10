<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Lesson, WeekParityOption } from '@/types';

const props = defineProps<{
    lessons: Lesson[];
    teachers: { id: number; short_code: string; full_name: string | null }[];
    weekParities: WeekParityOption[];
    hasParityAnchor: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rozvrh', href: '/schedule' }],
    },
});

const days = [
    { value: 1, label: 'Pondelok', short: 'Po' },
    { value: 2, label: 'Utorok', short: 'Ut' },
    { value: 3, label: 'Streda', short: 'St' },
    { value: 4, label: 'Štvrtok', short: 'Št' },
    { value: 5, label: 'Piatok', short: 'Pi' },
];
const periods = [0, 1, 2, 3, 4, 5, 6, 7, 8];

function lessonsAt(day: number, period: number): Lesson[] {
    return props.lessons.filter((l) => l.day_of_week === day && l.period === period);
}

const parityBadge: Record<string, string> = { odd: 'N', even: 'P', every: '' };

const open = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    day_of_week: 1,
    period: 0,
    subject_code: '',
    subject_name: '',
    room: '',
    group_label: '',
    teacher_id: 'none' as string,
    week_parity: 'every' as string,
});

const dialogTitle = computed(() => (editingId.value ? 'Upraviť hodinu' : 'Pridať hodinu'));

function openCreate(day: number, period: number) {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.day_of_week = day;
    form.period = period;
    open.value = true;
}

function openEdit(lesson: Lesson) {
    editingId.value = lesson.id;
    form.clearErrors();
    form.day_of_week = lesson.day_of_week;
    form.period = lesson.period;
    form.subject_code = lesson.subject_code;
    form.subject_name = lesson.subject_name ?? '';
    form.room = lesson.room ?? '';
    form.group_label = lesson.group_label ?? '';
    form.teacher_id = lesson.teacher_id ? String(lesson.teacher_id) : 'none';
    form.week_parity = lesson.week_parity;
    open.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    form
        .transform((data) => ({
            ...data,
            teacher_id: data.teacher_id === 'none' ? null : Number(data.teacher_id),
            subject_name: data.subject_name || null,
            room: data.room || null,
            group_label: data.group_label || null,
        }))
        [editingId.value ? 'put' : 'post'](
            editingId.value ? `/schedule/${editingId.value}` : '/schedule',
            options,
        );
}

function destroy() {
    if (!editingId.value) return;
    router.delete(`/schedule/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Head title="Rozvrh" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Týždenný rozvrh</h1>
                <p class="text-sm text-muted-foreground">
                    Klikni do bunky a pridaj hodinu. N = nepárny týždeň, P = párny.
                </p>
            </div>
            <Button size="sm" @click="openCreate(1, 0)">Pridať hodinu</Button>
        </div>

        <p
            v-if="!hasParityAnchor"
            class="rounded-md bg-amber-500/10 px-3 py-2 text-sm text-amber-700 dark:text-amber-400"
        >
            Nemáš nastavený kotviaci dátum pre párny/nepárny týždeň — hodiny označené len
            pre jeden typ týždňa sa zatiaľ počítajú ako nepárne. Nastav ho v Prehľade.
        </p>

        <div class="overflow-x-auto">
            <div class="grid min-w-[720px] grid-cols-[3rem_repeat(5,1fr)] gap-1">
                <div></div>
                <div
                    v-for="day in days"
                    :key="day.value"
                    class="px-2 py-1.5 text-center text-sm font-medium"
                >
                    {{ day.label }}
                </div>

                <template v-for="period in periods" :key="period">
                    <div
                        class="flex items-center justify-center text-xs font-medium text-muted-foreground"
                    >
                        {{ period }}
                    </div>
                    <div
                        v-for="day in days"
                        :key="day.value + '-' + period"
                        class="min-h-16 rounded-md border border-dashed p-1"
                    >
                        <button
                            v-for="lesson in lessonsAt(day.value, period)"
                            :key="lesson.id"
                            type="button"
                            class="mb-1 block w-full rounded bg-primary/10 px-1.5 py-1 text-left text-xs hover:bg-primary/20"
                            @click="openEdit(lesson)"
                        >
                            <span class="font-semibold">{{ lesson.subject_code }}</span>
                            <span v-if="parityBadge[lesson.week_parity]" class="ml-1 text-primary">
                                {{ parityBadge[lesson.week_parity] }}
                            </span>
                            <span class="block text-muted-foreground">
                                {{ [lesson.room, teachers.find((t) => t.id === lesson.teacher_id)?.short_code]
                                    .filter(Boolean)
                                    .join(' · ') }}
                            </span>
                        </button>
                        <button
                            type="button"
                            class="block w-full rounded py-1 text-center text-xs text-muted-foreground/50 hover:text-muted-foreground"
                            @click="openCreate(day.value, period)"
                        >
                            +
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <Dialog v-model:open="open">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="mb-1.5">Deň</Label>
                            <Select v-model.number="form.day_of_week">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="day in days"
                                        :key="day.value"
                                        :value="day.value"
                                    >
                                        {{ day.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label class="mb-1.5">Hodina</Label>
                            <Select v-model.number="form.period">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="period in periods"
                                        :key="period"
                                        :value="period"
                                    >
                                        {{ period }}. hodina
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="mb-1.5" for="subject_code">Skratka predmetu</Label>
                            <Input id="subject_code" v-model="form.subject_code" required placeholder="MAT" />
                            <p v-if="form.errors.subject_code" class="mt-1 text-xs text-destructive">
                                {{ form.errors.subject_code }}
                            </p>
                        </div>
                        <div>
                            <Label class="mb-1.5" for="subject_name">Názov (nepovinné)</Label>
                            <Input id="subject_name" v-model="form.subject_name" placeholder="Matematika" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="mb-1.5" for="room">Miestnosť</Label>
                            <Input id="room" v-model="form.room" placeholder="B-317" />
                        </div>
                        <div>
                            <Label class="mb-1.5" for="group_label">Skupina</Label>
                            <Input id="group_label" v-model="form.group_label" placeholder="S1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="mb-1.5">Učiteľ</Label>
                            <Select v-model="form.teacher_id">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">— žiadny —</SelectItem>
                                    <SelectItem
                                        v-for="teacher in teachers"
                                        :key="teacher.id"
                                        :value="String(teacher.id)"
                                    >
                                        {{ teacher.short_code }}
                                        <span v-if="teacher.full_name">— {{ teacher.full_name }}</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="teachers.length === 0" class="mt-1 text-xs text-muted-foreground">
                                Učiteľov pridáš v sekcii Učitelia.
                            </p>
                        </div>
                        <div>
                            <Label class="mb-1.5">Týždeň</Label>
                            <Select v-model="form.week_parity">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="parity in weekParities"
                                        :key="parity.value"
                                        :value="parity.value"
                                    >
                                        {{ parity.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:justify-between">
                        <Button
                            v-if="editingId"
                            type="button"
                            variant="destructive"
                            @click="destroy"
                        >
                            Zmazať
                        </Button>
                        <Button type="submit" :disabled="form.processing">Uložiť</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
