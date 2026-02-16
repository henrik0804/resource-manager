<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    extendDayjs,
    GGanttChart,
    GGanttRow,
} from '@infectoone/vue-ganttastic';
import dayjs from 'dayjs';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { schedule } from '@/routes';
import type { BreadcrumbItem } from '@/types';

extendDayjs();

interface GanttBar {
    start: string;
    end: string;
    ganttBarConfig: {
        id: string;
        label: string;
        style: Record<string, string>;
    };
}

interface ScheduleRow {
    id: number;
    label: string;
    resourceType: string | null;
    bars: GanttBar[];
}

interface Props {
    rows: ScheduleRow[];
    rangeStart: string;
    rangeEnd: string;
    precision: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Zeitplan', href: schedule().url },
];

const precision = ref(props.precision);
const rangeStart = computed(() => dayjs(props.rangeStart));
const rangeEnd = computed(() => dayjs(props.rangeEnd));

const precisionOptions = [
    { value: 'day', label: 'Tag' },
    { value: 'week', label: 'Woche' },
    { value: 'month', label: 'Monat' },
];

const defaultRangeDays: Record<string, number> = {
    day: 2,
    week: 60,
    month: 180,
};

const ganttPrecisionMap: Record<string, string> = {
    day: 'hour',
    week: 'day',
    month: 'month',
};

const ganttPrecision = computed(
    () => ganttPrecisionMap[precision.value] ?? 'day',
);

const rangeLabel = computed(() => {
    const start = rangeStart.value;
    const end = rangeEnd.value;

    return `${start.format('DD.MM.YYYY')} – ${end.format('DD.MM.YYYY')}`;
});

function scheduleUrl(params: {
    start: string;
    end: string;
    precision: string;
}): string {
    return schedule({ query: params }).url;
}

function navigate(direction: 'prev' | 'next'): void {
    const current = rangeStart.value;
    const duration = rangeEnd.value.diff(current, 'day');

    const newStart =
        direction === 'prev'
            ? current.subtract(duration, 'day')
            : current.add(duration, 'day');

    const newEnd = newStart.add(duration, 'day');

    router.get(
        scheduleUrl({
            start: newStart.format('YYYY-MM-DD'),
            end: newEnd.format('YYYY-MM-DD'),
            precision: precision.value,
        }),
        {},
        { preserveState: true },
    );
}

function goToToday(): void {
    const days = defaultRangeDays[precision.value] ?? 21;
    const today = dayjs();

    const newStart =
        precision.value === 'day'
            ? today.startOf('day')
            : today.subtract(Math.floor(days / 4), 'day');
    const newEnd = newStart.add(days, 'day');

    router.get(
        scheduleUrl({
            start: newStart.format('YYYY-MM-DD'),
            end: newEnd.format('YYYY-MM-DD'),
            precision: precision.value,
        }),
        {},
        { preserveState: true },
    );
}

watch(precision, (newPrecision) => {
    const days = defaultRangeDays[newPrecision] ?? 21;
    const start =
        newPrecision === 'day' ? dayjs().startOf('day') : rangeStart.value;

    router.get(
        scheduleUrl({
            start: start.format('YYYY-MM-DD'),
            end: start.add(days, 'day').format('YYYY-MM-DD'),
            precision: newPrecision,
        }),
        {},
        { preserveState: true },
    );
});

const chartStart = computed(() => rangeStart.value.format('YYYY-MM-DD HH:mm'));
const chartEnd = computed(() => rangeEnd.value.format('YYYY-MM-DD HH:mm'));

const hasData = computed(() => props.rows.length > 0);
const hasAnyBars = computed(() =>
    props.rows.some((row) => row.bars.length > 0),
);
</script>

<template>
    <Head title="Zeitplan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Zeitplan"
                description="Visuelle Übersicht aller Ressourcen und deren Aufgaben im Zeitverlauf."
            />

            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
            >
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        @click="navigate('prev')"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button variant="outline" size="sm" @click="goToToday">
                        <CalendarDays class="mr-2 size-4" />
                        Heute
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        @click="navigate('next')"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                    <span class="ml-2 text-sm text-muted-foreground">
                        {{ rangeLabel }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Ansicht:</span>
                    <Select v-model="precision">
                        <SelectTrigger class="w-[120px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in precisionOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div
                v-if="!hasData"
                class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">
                    Keine Ressourcen vorhanden. Erstellen Sie zunächst
                    Ressourcen, um den Zeitplan zu sehen.
                </p>
            </div>

            <div
                v-else
                class="flex-1 overflow-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <g-gantt-chart
                    :chart-start="chartStart"
                    :chart-end="chartEnd"
                    :precision="ganttPrecision"
                    bar-start="start"
                    bar-end="end"
                    date-format="YYYY-MM-DD HH:mm"
                    color-scheme="dark"
                    grid
                    :row-height="40"
                    label-column-title="Ressource"
                    label-column-width="200px"
                    width="100%"
                    font="inherit"
                >
                    <template #bar-tooltip="{ bar }">
                        <div
                            class="rounded-md bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg"
                        >
                            {{ bar.ganttBarConfig.label }}
                        </div>
                    </template>

                    <g-gantt-row
                        v-for="row in rows"
                        :key="row.id"
                        :label="row.label"
                        :bars="row.bars"
                        highlight-on-hover
                    />
                </g-gantt-chart>

                <div
                    v-if="!hasAnyBars"
                    class="border-t border-sidebar-border/70 p-4 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    Keine Zuweisungen oder Abwesenheiten im gewählten Zeitraum.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
