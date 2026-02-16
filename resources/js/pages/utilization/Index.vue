<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import {
    AlertTriangle,
    BarChart3,
    ChevronLeft,
    ChevronRight,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { utilization } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface UtilizationBucket {
    label: string;
    start: string;
    end: string;
    capacity: number;
    allocated: number;
    absent: number;
    available: number;
    utilization_percentage: number;
}

interface ResourceUtilization {
    id: number;
    name: string;
    resource_type: string | null;
    capacity_per_day: number;
    capacity_unit: string | null;
    summary: {
        total_days: number;
        total_capacity: number;
        total_allocated: number;
        total_absent: number;
        available_capacity: number;
        utilization_percentage: number;
    };
    buckets: UtilizationBucket[];
}

interface Props {
    resources: ResourceUtilization[];
    period: {
        start: string;
        end: string;
        granularity: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auslastung', href: utilization().url },
];

const granularity = ref(props.period.granularity);
const rangeStart = computed(() => dayjs(props.period.start));
const rangeEnd = computed(() => dayjs(props.period.end));

const granularityOptions = [
    { value: 'day', label: 'Täglich' },
    { value: 'week', label: 'Wöchentlich' },
    { value: 'month', label: 'Monatlich' },
];

const defaultRangeDays: Record<string, number> = {
    day: 14,
    week: 28,
    month: 90,
};

const perDayUnitLabels: Record<string, string> = {
    hours_per_day: 'Std./Tag',
    slots: 'Slots/Tag',
};

const totalUnitLabels: Record<string, string> = {
    hours_per_day: 'Std.',
    slots: 'Slot-Tage',
};

const rangeLabel = computed(() => {
    const start = rangeStart.value;
    const end = rangeEnd.value;

    return `${start.format('DD.MM.YYYY')} – ${end.format('DD.MM.YYYY')}`;
});

function utilizationUrl(params: {
    start: string;
    end: string;
    granularity: string;
}): string {
    return utilization({ query: params }).url;
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
        utilizationUrl({
            start: newStart.format('YYYY-MM-DD'),
            end: newEnd.format('YYYY-MM-DD'),
            granularity: granularity.value,
        }),
        {},
        { preserveState: true },
    );
}

function goToToday(): void {
    const days = defaultRangeDays[granularity.value] ?? 28;
    const today = dayjs().startOf('day');

    router.get(
        utilizationUrl({
            start: today.format('YYYY-MM-DD'),
            end: today.add(days, 'day').format('YYYY-MM-DD'),
            granularity: granularity.value,
        }),
        {},
        { preserveState: true },
    );
}

watch(granularity, (newGranularity) => {
    const days = defaultRangeDays[newGranularity] ?? 28;
    const start = rangeStart.value;

    router.get(
        utilizationUrl({
            start: start.format('YYYY-MM-DD'),
            end: start.add(days, 'day').format('YYYY-MM-DD'),
            granularity: newGranularity,
        }),
        {},
        { preserveState: true },
    );
});

const totalResources = computed(() => props.resources.length);

const overloadedResources = computed(
    () =>
        props.resources.filter((r) => r.summary.utilization_percentage > 100)
            .length,
);

const avgUtilization = computed(() => {
    if (props.resources.length === 0) {
        return 0;
    }

    const sum = props.resources.reduce(
        (acc, r) => acc + r.summary.utilization_percentage,
        0,
    );

    return Math.round(sum / props.resources.length);
});

const sortedResources = computed(() =>
    [...props.resources].sort(
        (a, b) =>
            b.summary.utilization_percentage - a.summary.utilization_percentage,
    ),
);

function utilizationColor(percentage: number): string {
    if (percentage > 100) {
        return 'bg-red-500 dark:bg-red-600';
    }

    if (percentage >= 75) {
        return 'bg-amber-500 dark:bg-amber-500';
    }

    if (percentage > 0) {
        return 'bg-emerald-500 dark:bg-emerald-600';
    }

    return 'bg-muted';
}

function utilizationTextColor(percentage: number): string {
    if (percentage > 100) {
        return 'text-red-600 dark:text-red-400';
    }

    if (percentage >= 75) {
        return 'text-amber-600 dark:text-amber-400';
    }

    return 'text-foreground';
}

function formatTotalUnit(resource: ResourceUtilization): string {
    return totalUnitLabels[resource.capacity_unit ?? ''] ?? '';
}

function formatPerDayUnit(resource: ResourceUtilization): string {
    return perDayUnitLabels[resource.capacity_unit ?? ''] ?? '';
}

function formatNumber(value: number): string {
    return value % 1 === 0 ? value.toString() : value.toFixed(1);
}
</script>

<template>
    <Head title="Auslastung" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Auslastung"
                description="Auslastung der Ressourcen im Zeitverlauf. Engpässe frühzeitig erkennen."
            />

            <!-- Period controls -->
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
                    <span class="text-sm text-muted-foreground"
                        >Granularität:</span
                    >
                    <Select v-model="granularity">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in granularityOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Ressourcen
                        </CardTitle>
                        <Users class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ totalResources }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Durchschnittl. Auslastung
                        </CardTitle>
                        <BarChart3 class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold"
                            :class="utilizationTextColor(avgUtilization)"
                        >
                            {{ avgUtilization }}%
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Überlastet
                        </CardTitle>
                        <AlertTriangle class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold"
                            :class="
                                overloadedResources > 0
                                    ? 'text-red-600 dark:text-red-400'
                                    : ''
                            "
                        >
                            {{ overloadedResources }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Utilization bars -->
            <Card v-if="totalResources > 0">
                <CardHeader>
                    <CardTitle>Auslastung pro Ressource</CardTitle>
                    <CardDescription>
                        Gesamtauslastung im gewählten Zeitraum ({{
                            rangeLabel
                        }})
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <TooltipProvider :delay-duration="100">
                            <div
                                v-for="resource in sortedResources"
                                :key="resource.id"
                                class="space-y-1.5"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-2"
                                    >
                                        <span
                                            class="truncate text-sm font-medium"
                                        >
                                            {{ resource.name }}
                                        </span>
                                        <Badge
                                            v-if="resource.resource_type"
                                            variant="outline"
                                            class="shrink-0 text-xs"
                                        >
                                            {{ resource.resource_type }}
                                        </Badge>
                                    </div>
                                    <span
                                        class="shrink-0 text-sm font-semibold tabular-nums"
                                        :class="
                                            utilizationTextColor(
                                                resource.summary
                                                    .utilization_percentage,
                                            )
                                        "
                                    >
                                        {{
                                            formatNumber(
                                                resource.summary
                                                    .utilization_percentage,
                                            )
                                        }}%
                                    </span>
                                </div>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div
                                            class="h-3 w-full overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    utilizationColor(
                                                        resource.summary
                                                            .utilization_percentage,
                                                    )
                                                "
                                                :style="{
                                                    width: `${Math.min(resource.summary.utilization_percentage, 100)}%`,
                                                }"
                                            />
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        side="bottom"
                                        class="text-xs"
                                    >
                                        <div class="space-y-1">
                                            <div class="font-medium">
                                                {{ resource.name }}
                                            </div>
                                            <div>
                                                Kapazität/Tag:
                                                {{
                                                    formatNumber(
                                                        resource.capacity_per_day,
                                                    )
                                                }}
                                                {{ formatPerDayUnit(resource) }}
                                            </div>
                                            <div>
                                                Kapazität im Zeitraum:
                                                {{
                                                    formatNumber(
                                                        resource.summary
                                                            .total_capacity,
                                                    )
                                                }}
                                                {{ formatTotalUnit(resource) }}
                                            </div>
                                            <div>
                                                Zugewiesen im Zeitraum:
                                                {{
                                                    formatNumber(
                                                        resource.summary
                                                            .total_allocated,
                                                    )
                                                }}
                                                {{ formatTotalUnit(resource) }}
                                            </div>
                                            <div
                                                v-if="
                                                    resource.summary
                                                        .total_absent > 0
                                                "
                                            >
                                                Abwesend im Zeitraum:
                                                {{
                                                    formatNumber(
                                                        resource.summary
                                                            .total_absent,
                                                    )
                                                }}
                                                {{ formatTotalUnit(resource) }}
                                            </div>
                                            <div>
                                                Verfügbar im Zeitraum:
                                                {{
                                                    formatNumber(
                                                        resource.summary
                                                            .available_capacity,
                                                    )
                                                }}
                                                {{ formatTotalUnit(resource) }}
                                            </div>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </div>
                </CardContent>
            </Card>

            <!-- Per-bucket breakdown -->
            <Card
                v-if="
                    sortedResources.length > 0 &&
                    sortedResources[0].buckets.length > 1
                "
            >
                <CardHeader>
                    <CardTitle>Zeitverlauf</CardTitle>
                    <CardDescription>
                        Auslastung pro Zeitabschnitt
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div
                            v-for="resource in sortedResources"
                            :key="resource.id"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                {{ resource.name }}
                                <Badge
                                    v-if="resource.resource_type"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ resource.resource_type }}
                                </Badge>
                            </div>

                            <TooltipProvider :delay-duration="100">
                                <div class="flex items-end gap-1">
                                    <Tooltip
                                        v-for="bucket in resource.buckets"
                                        :key="bucket.start"
                                    >
                                        <TooltipTrigger as-child>
                                            <div
                                                class="flex min-w-0 flex-1 flex-col items-center gap-1"
                                            >
                                                <div
                                                    class="relative flex h-24 w-full items-end justify-center overflow-hidden rounded-t bg-muted"
                                                >
                                                    <div
                                                        class="w-full rounded-t transition-all duration-500"
                                                        :class="
                                                            utilizationColor(
                                                                bucket.utilization_percentage,
                                                            )
                                                        "
                                                        :style="{
                                                            height: `${Math.min(bucket.utilization_percentage, 100)}%`,
                                                        }"
                                                    />
                                                </div>
                                                <span
                                                    class="w-full truncate text-center text-[10px] text-muted-foreground"
                                                >
                                                    {{ bucket.label }}
                                                </span>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            side="top"
                                            class="text-xs"
                                        >
                                            <div class="space-y-1">
                                                <div class="font-medium">
                                                    {{ bucket.label }}
                                                </div>
                                                <div>
                                                    Auslastung:
                                                    {{
                                                        formatNumber(
                                                            bucket.utilization_percentage,
                                                        )
                                                    }}%
                                                </div>
                                                <div>
                                                    Zugewiesen:
                                                    {{
                                                        formatNumber(
                                                            bucket.allocated,
                                                        )
                                                    }}
                                                    / Verfügbar:
                                                    {{
                                                        formatNumber(
                                                            bucket.available,
                                                        )
                                                    }}
                                                    {{
                                                        formatTotalUnit(
                                                            resource,
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </TooltipProvider>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty state -->
            <div
                v-if="totalResources === 0"
                class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">
                    Keine Ressourcen vorhanden. Erstellen Sie zunächst
                    Ressourcen, um die Auslastung zu sehen.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
