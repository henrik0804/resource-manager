<script setup lang="ts">
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Props {
    editLabel?: string;
    deleteLabel?: string;
    showEdit?: boolean;
    showDelete?: boolean;
}

withDefaults(defineProps<Props>(), {
    editLabel: 'Bearbeiten',
    deleteLabel: 'Löschen',
    showEdit: true,
    showDelete: true,
});

defineEmits<{
    (e: 'edit'): void;
    (e: 'delete'): void;
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon-sm">
                <MoreHorizontal class="size-4" />
                <span class="sr-only">Aktionen öffnen</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem v-if="showEdit" @click="$emit('edit')">
                <Pencil class="mr-2 size-4" />
                {{ editLabel }}
            </DropdownMenuItem>
            <DropdownMenuItem
                v-if="showDelete"
                class="text-destructive focus:text-destructive"
                @click="$emit('delete')"
            >
                <Trash2 class="mr-2 size-4" />
                {{ deleteLabel }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
