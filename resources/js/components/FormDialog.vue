<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

interface Props {
    open: boolean;
    title: string;
    description?: string;
    submitLabel?: string;
    cancelLabel?: string;
    processing?: boolean;
}

withDefaults(defineProps<Props>(), {
    description: undefined,
    submitLabel: 'Speichern',
    cancelLabel: 'Abbrechen',
    processing: false,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'submit'): void;
}>();

function handleOpenChange(value: boolean) {
    emit('update:open', value);
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="emit('submit')">
                <slot />

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="processing"
                        >
                            {{ cancelLabel }}
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" class="mr-2 size-4" />
                        {{ submitLabel }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
