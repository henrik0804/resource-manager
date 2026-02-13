<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Props {
    modelValue?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Suchen...',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const localValue = ref(props.modelValue);

watch(
    () => props.modelValue,
    (value) => {
        localValue.value = value;
    },
);

let debounceTimer: ReturnType<typeof setTimeout>;

function onInput(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    localValue.value = value;

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        emit('update:modelValue', value);
    }, 300);
}

function clear() {
    localValue.value = '';
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="relative w-full max-w-sm">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            :model-value="localValue"
            :placeholder="placeholder"
            class="pr-9 pl-9"
            @input="onInput"
        />
        <Button
            v-if="localValue"
            variant="ghost"
            size="icon-sm"
            class="absolute top-1/2 right-1 -translate-y-1/2"
            @click="clear"
        >
            <X class="size-3.5" />
            <span class="sr-only">Suche leeren</span>
        </Button>
    </div>
</template>
