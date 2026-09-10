<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import type { Flair, Teacher } from '@/types';

const props = defineProps<{
    teachers: Teacher[];
    flairs: Flair[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Učitelia', href: '/teachers' }],
    },
});

const sentimentLabels: Record<number, string> = {
    [-1]: 'nepríjemný',
    [0]: 'neutrálny',
    [1]: 'obľúbený',
};

/* ---- teachers ---- */
const teacherOpen = ref(false);
const editingTeacherId = ref<number | null>(null);
const teacherForm = useForm({
    short_code: '',
    full_name: '',
    note: '',
    flair_id: 'none' as string,
});

function newTeacher() {
    editingTeacherId.value = null;
    teacherForm.reset();
    teacherForm.clearErrors();
    teacherForm.flair_id = 'none';
    teacherOpen.value = true;
}

function editTeacher(teacher: Teacher) {
    editingTeacherId.value = teacher.id;
    teacherForm.clearErrors();
    teacherForm.short_code = teacher.short_code;
    teacherForm.full_name = teacher.full_name ?? '';
    teacherForm.note = teacher.note ?? '';
    teacherForm.flair_id = teacher.flair_id ? String(teacher.flair_id) : 'none';
    teacherOpen.value = true;
}

function submitTeacher() {
    const payload = teacherForm.transform((data) => ({
        ...data,
        full_name: data.full_name || null,
        note: data.note || null,
        flair_id: data.flair_id === 'none' ? null : Number(data.flair_id),
    }));
    const done = { preserveScroll: true, onSuccess: () => (teacherOpen.value = false) };
    editingTeacherId.value
        ? payload.put(`/teachers/${editingTeacherId.value}`, done)
        : payload.post('/teachers', done);
}

function deleteTeacher(id: number) {
    router.delete(`/teachers/${id}`, { preserveScroll: true });
}

function setTeacherFlair(teacher: Teacher, flairId: string) {
    router.put(
        `/teachers/${teacher.id}`,
        {
            short_code: teacher.short_code,
            full_name: teacher.full_name,
            note: teacher.note,
            flair_id: flairId === 'none' ? null : Number(flairId),
        },
        { preserveScroll: true },
    );
}

/* ---- flairs ---- */
const flairOpen = ref(false);
const editingFlairId = ref<number | null>(null);
const flairForm = useForm({
    label: '',
    color: '#64748b',
    sentiment: 0,
});

function newFlair() {
    editingFlairId.value = null;
    flairForm.reset();
    flairForm.clearErrors();
    flairOpen.value = true;
}

function editFlair(flair: Flair) {
    editingFlairId.value = flair.id;
    flairForm.clearErrors();
    flairForm.label = flair.label;
    flairForm.color = flair.color;
    flairForm.sentiment = flair.sentiment;
    flairOpen.value = true;
}

function submitFlair() {
    const done = { preserveScroll: true, onSuccess: () => (flairOpen.value = false) };
    editingFlairId.value
        ? flairForm.put(`/flairs/${editingFlairId.value}`, done)
        : flairForm.post('/flairs', done);
}

function deleteFlair(id: number) {
    router.delete(`/flairs/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Učitelia" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Card>
            <CardHeader class="flex-row items-center justify-between">
                <div>
                    <CardTitle class="text-base">Učitelia</CardTitle>
                    <CardDescription>
                        Prirad každému učiteľovi hodnotenie — podľa neho sa v Prehľade
                        sčítavajú zostávajúce hodiny.
                    </CardDescription>
                </div>
                <Button size="sm" @click="newTeacher">Pridať učiteľa</Button>
            </CardHeader>
            <CardContent>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="py-2 font-medium">Skratka</th>
                            <th class="py-2 font-medium">Meno</th>
                            <th class="py-2 font-medium">Hodnotenie</th>
                            <th class="py-2 text-right font-medium">Hodín</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="teacher in teachers"
                            :key="teacher.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-2 font-medium">{{ teacher.short_code }}</td>
                            <td class="py-2 text-muted-foreground">
                                {{ teacher.full_name ?? '—' }}
                            </td>
                            <td class="py-2">
                                <Select
                                    :model-value="teacher.flair_id ? String(teacher.flair_id) : 'none'"
                                    @update:model-value="(v) => setTeacherFlair(teacher, String(v))"
                                >
                                    <SelectTrigger class="w-44"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">— bez hodnotenia —</SelectItem>
                                        <SelectItem
                                            v-for="flair in flairs"
                                            :key="flair.id"
                                            :value="String(flair.id)"
                                        >
                                            {{ flair.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="py-2 text-right tabular-nums text-muted-foreground">
                                {{ teacher.lessons_count ?? 0 }}
                            </td>
                            <td class="py-2 text-right">
                                <Button size="sm" variant="ghost" @click="editTeacher(teacher)">
                                    Upraviť
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive"
                                    @click="deleteTeacher(teacher.id)"
                                >
                                    Zmazať
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="teachers.length === 0">
                            <td colspan="5" class="py-6 text-center text-muted-foreground">
                                Zatiaľ žiadni učitelia.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex-row items-center justify-between">
                <div>
                    <CardTitle class="text-base">Hodnotenia</CardTitle>
                    <CardDescription>Vlastné štítky pre učiteľov.</CardDescription>
                </div>
                <Button size="sm" variant="outline" @click="newFlair">Pridať hodnotenie</Button>
            </CardHeader>
            <CardContent>
                <ul class="divide-y">
                    <li
                        v-for="flair in flairs"
                        :key="flair.id"
                        class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-block size-4 rounded-full"
                                :style="{ backgroundColor: flair.color }"
                            />
                            <span class="font-medium">{{ flair.label }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ sentimentLabels[flair.sentiment] }}
                            </span>
                        </div>
                        <div>
                            <Button size="sm" variant="ghost" @click="editFlair(flair)">Upraviť</Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive"
                                @click="deleteFlair(flair.id)"
                            >
                                Zmazať
                            </Button>
                        </div>
                    </li>
                    <li v-if="flairs.length === 0" class="py-4 text-center text-muted-foreground">
                        Žiadne hodnotenia.
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Teacher dialog -->
        <Dialog v-model:open="teacherOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingTeacherId ? 'Upraviť učiteľa' : 'Nový učiteľ' }}
                    </DialogTitle>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submitTeacher">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="mb-1.5" for="short_code">Skratka</Label>
                            <Input id="short_code" v-model="teacherForm.short_code" required placeholder="SIE" />
                            <p v-if="teacherForm.errors.short_code" class="mt-1 text-xs text-destructive">
                                {{ teacherForm.errors.short_code }}
                            </p>
                        </div>
                        <div>
                            <Label class="mb-1.5" for="full_name">Meno</Label>
                            <Input id="full_name" v-model="teacherForm.full_name" placeholder="Mgr. Siheľská" />
                        </div>
                    </div>
                    <div>
                        <Label class="mb-1.5">Hodnotenie</Label>
                        <Select v-model="teacherForm.flair_id">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">— bez hodnotenia —</SelectItem>
                                <SelectItem
                                    v-for="flair in flairs"
                                    :key="flair.id"
                                    :value="String(flair.id)"
                                >
                                    {{ flair.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="mb-1.5" for="note">Poznámka</Label>
                        <Input id="note" v-model="teacherForm.note" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="teacherForm.processing">Uložiť</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Flair dialog -->
        <Dialog v-model:open="flairOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingFlairId ? 'Upraviť hodnotenie' : 'Nové hodnotenie' }}
                    </DialogTitle>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submitFlair">
                    <div>
                        <Label class="mb-1.5" for="label">Názov</Label>
                        <Input id="label" v-model="flairForm.label" required placeholder="dobrá" />
                        <p v-if="flairForm.errors.label" class="mt-1 text-xs text-destructive">
                            {{ flairForm.errors.label }}
                        </p>
                    </div>
                    <div class="flex items-end gap-3">
                        <div>
                            <Label class="mb-1.5" for="color">Farba</Label>
                            <input
                                id="color"
                                v-model="flairForm.color"
                                type="color"
                                class="h-9 w-16 rounded-md border bg-transparent"
                            />
                        </div>
                        <div class="flex-1">
                            <Label class="mb-1.5">Ladenie</Label>
                            <Select v-model.number="flairForm.sentiment">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="1">obľúbený</SelectItem>
                                    <SelectItem :value="0">neutrálny</SelectItem>
                                    <SelectItem :value="-1">nepríjemný</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="flairForm.processing">Uložiť</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
