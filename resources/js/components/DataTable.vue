<script setup lang="ts" generic="T extends Record<string, unknown>">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import ActionCell from '@/components/ActionCell.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AppPageProps } from '@/types';
import type { Paginated } from '@/types/models';

export interface Column<R> {
    key: string;
    label: string;
    render?: (row: R) => string;
    class?: string;
}

interface Props {
    data: Paginated<T>;
    columns: Column<T>[];
    searchPlaceholder?: string;
    search?: string;
    routePrefix: string;
    deleteAction: (id: number) => { url: string; method: string };
    showActions?: boolean;
    showEdit?: boolean;
    showDelete?: boolean;
    emptyMessage?: string;
    deleteTitle?: string;
    deleteDescription?: string;
    dependencyDeleteDescription?: string;
}

const props = withDefaults(defineProps<Props>(), {
    searchPlaceholder: 'Suchen...',
    search: '',
    showActions: true,
    showEdit: true,
    showDelete: true,
    emptyMessage: 'Keine Einträge gefunden.',
    deleteTitle: 'Eintrag löschen',
    deleteDescription:
        'Möchten Sie diesen Eintrag wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.',
    dependencyDeleteDescription:
        'Dieser Eintrag hat abhängige Daten, die ebenfalls unwiderruflich gelöscht werden. Möchten Sie wirklich fortfahren?',
});

const emit = defineEmits<{
    (e: 'edit', row: T): void;
}>();

const page = usePage<AppPageProps>();

const searchValue = ref(props.search);
const deleteDialogOpen = ref(false);
const dependencyDialogOpen = ref(false);
const deletingRow = ref<T | null>(null);
const isDeleting = ref(false);
const dependents = ref<Record<string, number> | null>(null);

watch(
    () => props.search,
    (value) => {
        searchValue.value = value;
    },
);

const dependencyDetailText = computed(() => {
    if (!dependents.value) {
        return '';
    }

    const labels: Record<string, string> = {
        resources: 'Ressourcen',
        qualifications: 'Qualifikationen',
        users: 'Benutzer',
        resource_qualifications: 'Ressourcenqualifikationen',
        task_assignments: 'Aufgabenzuweisungen',
        resource_absences: 'Abwesenheiten',
        task_requirements: 'Aufgabenanforderungen',
        resource: 'Ressource',
    };

    const parts = Object.entries(dependents.value).map(
        ([key, count]) => `${count} ${labels[key] ?? key}`,
    );

    return parts.join(', ');
});

function onSearch(value: string) {
    router.get(
        props.routePrefix,
        { search: value || undefined },
        { preserveState: true, replace: true },
    );
}

function confirmDelete(row: T) {
    deletingRow.value = row;
    deleteDialogOpen.value = true;
}

function executeDelete(confirmDependencyDeletion = false) {
    if (!deletingRow.value) {
        return;
    }

    isDeleting.value = true;

    const action = props.deleteAction(deletingRow.value.id as number);

    router.delete(action.url, {
        preserveScroll: true,
        data: confirmDependencyDeletion
            ? { confirm_dependency_deletion: true }
            : {},
        onSuccess: () => {
            const flash = page.props.flash;

            if (flash?.status === 'has_dependents' && flash.dependents) {
                dependents.value = flash.dependents;
                deleteDialogOpen.value = false;
                dependencyDialogOpen.value = true;
                isDeleting.value = false;
                return;
            }

            isDeleting.value = false;
            deleteDialogOpen.value = false;
            dependencyDialogOpen.value = false;
            deletingRow.value = null;
            dependents.value = null;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

function executeDependencyDelete() {
    executeDelete(true);
}

function closeDependencyDialog() {
    dependencyDialogOpen.value = false;
    deletingRow.value = null;
    dependents.value = null;
}

function getCellValue(row: T, column: Column<T>): string {
    if (column.render) {
        return column.render(row);
    }

    const value = row[column.key];

    if (value === null || value === undefined) {
        return '—';
    }

    return String(value);
}

const showPagination = computed(() => props.data.last_page > 1);
const actionColumnCount = computed(() => (props.showActions ? 1 : 0));

const paginationInfo = computed(() => {
    const { from, to, total } = props.data;

    if (!from || !to) {
        return '';
    }

    return `${from}–${to} von ${total}`;
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <SearchInput
                :model-value="searchValue"
                :placeholder="searchPlaceholder"
                @update:model-value="onSearch"
            />
            <slot name="toolbar" />
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="column in columns"
                            :key="column.key"
                            :class="column.class"
                        >
                            {{ column.label }}
                        </TableHead>
                        <TableHead v-if="showActions" class="w-12">
                            <span class="sr-only">Aktionen</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="data.data.length > 0">
                        <TableRow
                            v-for="row in data.data"
                            :key="row.id as number"
                        >
                            <TableCell
                                v-for="column in columns"
                                :key="column.key"
                                :class="column.class"
                            >
                                {{ getCellValue(row, column) }}
                            </TableCell>
                            <TableCell v-if="showActions" class="w-12">
                                <ActionCell
                                    :show-edit="props.showEdit"
                                    :show-delete="props.showDelete"
                                    @edit="emit('edit', row)"
                                    @delete="confirmDelete(row)"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="columns.length + actionColumnCount"
                    >
                        <p class="text-muted-foreground">
                            {{ emptyMessage }}
                        </p>
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <div v-if="showPagination" class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                {{ paginationInfo }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    v-if="data.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="data.prev_page_url" preserve-state>
                        <ChevronLeft class="size-4" />
                        Zurück
                    </Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>
                    <ChevronLeft class="size-4" />
                    Zurück
                </Button>

                <span class="text-sm text-muted-foreground">
                    Seite {{ data.current_page }} von
                    {{ data.last_page }}
                </span>

                <Button
                    v-if="data.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="data.next_page_url" preserve-state>
                        Weiter
                        <ChevronRight class="size-4" />
                    </Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>
                    Weiter
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>

    <ConfirmDialog
        :open="deleteDialogOpen"
        :title="deleteTitle"
        :description="deleteDescription"
        confirm-label="Löschen"
        :processing="isDeleting"
        @update:open="deleteDialogOpen = $event"
        @confirm="executeDelete(false)"
    />

    <ConfirmDialog
        :open="dependencyDialogOpen"
        :title="deleteTitle"
        :description="`${dependencyDeleteDescription} (${dependencyDetailText})`"
        confirm-label="Endgültig löschen"
        :processing="isDeleting"
        @update:open="
            (val) => {
                if (!val) closeDependencyDialog();
            }
        "
        @confirm="executeDependencyDelete"
    />
</template>
