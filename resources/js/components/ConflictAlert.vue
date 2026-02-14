<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export interface ConflictEntry {
    label: string;
    description: string;
    entries: {
        related_ids: number[];
        metrics?: Record<string, number | null>;
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

function formatPercentage(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }
    return `${Math.round(value * 100)}%`;
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
                            type === 'overloaded' &&
                            conflict.entries[0]?.metrics
                        "
                        class="text-amber-700 dark:text-amber-400"
                    >
                        ({{
                            formatPercentage(
                                conflict.entries[0].metrics.allocation_ratio,
                            )
                        }}
                        von
                        {{
                            formatPercentage(
                                conflict.entries[0].metrics.capacity_ratio,
                            )
                        }}
                        Kapazität)
                    </span>
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
