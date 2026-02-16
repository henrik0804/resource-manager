<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export interface ConflictEntry {
    label: string;
    description: string;
    entries: {
        related_ids: number[];
        metrics?: Record<string, number | string | null>;
    }[];
}

export interface ConflictCheckResponse {
    has_conflicts: boolean;
    conflicts: Record<string, ConflictEntry>;
}

interface Props {
    conflicts: Record<string, ConflictEntry>;
}

const props = defineProps<Props>();

const unitLabels: Record<string, string> = {
    hours_per_day: 'Std./Tag',
    slots: 'Slots',
};

function toNumber(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined) {
        return null;
    }

    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function formatQuantity(value: number | string | null | undefined): string {
    const numeric = toNumber(value);

    if (numeric === null) {
        return '—';
    }

    return numeric % 1 === 0 ? numeric.toString() : numeric.toFixed(2);
}

function formatUnit(value: string | number | null | undefined): string {
    if (typeof value !== 'string') {
        return '';
    }

    return unitLabels[value] ?? value;
}

function formatAllocationSummary(conflict: ConflictEntry): string | null {
    const metrics = conflict.entries[0]?.metrics;

    if (!metrics) {
        return null;
    }

    const allocation = formatQuantity(metrics.allocation);
    const capacity = formatQuantity(metrics.capacity);
    const unit = formatUnit(metrics.capacity_unit);

    if (allocation === '—' || capacity === '—') {
        return null;
    }

    return `${allocation} / ${capacity}${unit ? ` ${unit}` : ''}`;
}
</script>

<template>
    <Alert variant="warning">
        <TriangleAlert class="size-4" />
        <AlertTitle>Konflikterkennung</AlertTitle>
        <AlertDescription>
            <ul class="mt-1 space-y-1.5">
                <li
                    v-for="(conflict, type) in props.conflicts"
                    :key="type"
                    class="text-sm"
                >
                    <span class="font-medium">{{ conflict.label }}:</span>
                    {{ conflict.description }}
                    <span
                        v-if="
                            (type === 'overloaded' ||
                                type === 'double_booked') &&
                            formatAllocationSummary(conflict)
                        "
                        class="text-amber-700 dark:text-amber-400"
                    >
                        ({{ formatAllocationSummary(conflict) }})
                    </span>
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
