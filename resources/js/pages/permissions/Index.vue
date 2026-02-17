<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Shield, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { sync } from '@/actions/App/Http/Controllers/PermissionController';
import AlertError from '@/components/AlertError.vue';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/permissions';
import type { BreadcrumbItem } from '@/types';

interface SectionOption {
    value: string;
    label: string;
}

interface PermissionFlags {
    can_read: boolean;
    can_write: boolean;
    can_write_owned: boolean;
}

interface RoleWithPermissions {
    id: number;
    name: string;
    description: string | null;
    users_count: number;
    permissions: Record<string, PermissionFlags>;
}

interface Props {
    roles: RoleWithPermissions[];
    sections: SectionOption[];
    selectedRoleId: number | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Berechtigungen', href: index().url },
];

const selectedRoleId = ref<number | null>(
    props.selectedRoleId ?? props.roles[0]?.id ?? null,
);

const selectedRole = computed(() =>
    props.roles.find((role) => role.id === selectedRoleId.value),
);

type PermissionsMap = Record<
    string,
    { can_read: boolean; can_write: boolean; can_write_owned: boolean }
>;

function buildPermissionsData(
    role: RoleWithPermissions | undefined,
): PermissionsMap {
    const result: PermissionsMap = {};

    for (const section of props.sections) {
        const existing = role?.permissions?.[section.value];
        result[section.value] = {
            can_read: existing?.can_read ?? false,
            can_write: existing?.can_write ?? false,
            can_write_owned: existing?.can_write_owned ?? false,
        };
    }

    return result;
}

const form = useForm({
    permissions: buildPermissionsData(selectedRole.value),
});

watch(selectedRoleId, () => {
    form.permissions = buildPermissionsData(selectedRole.value);
    form.clearErrors();
});

watch(
    () => props.roles,
    () => {
        form.permissions = buildPermissionsData(selectedRole.value);
    },
);

const isDirty = computed(() => {
    if (!selectedRole.value) {
        return false;
    }

    for (const section of props.sections) {
        const current = form.permissions[section.value];
        const original = selectedRole.value.permissions?.[section.value];
        const originalRead = original?.can_read ?? false;
        const originalWrite = original?.can_write ?? false;
        const originalWriteOwned = original?.can_write_owned ?? false;

        if (
            current.can_read !== originalRead ||
            current.can_write !== originalWrite ||
            current.can_write_owned !== originalWriteOwned
        ) {
            return true;
        }
    }

    return false;
});

function selectRole(roleId: number) {
    selectedRoleId.value = roleId;
}

function submit() {
    if (!selectedRole.value) {
        return;
    }

    form.put(sync(selectedRole.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.clearErrors();
        },
    });
}

function sectionHasAnyPermission(sectionValue: string): boolean {
    const flags = selectedRole.value?.permissions?.[sectionValue];

    if (!flags) {
        return false;
    }

    return flags.can_read || flags.can_write || flags.can_write_owned;
}

const permissionCount = computed(() => {
    if (!selectedRole.value) {
        return 0;
    }

    return Object.values(selectedRole.value.permissions).filter(
        (p) => p.can_read || p.can_write || p.can_write_owned,
    ).length;
});
</script>

<template>
    <Head title="Berechtigungen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Berechtigungen"
                description="Verwalten Sie die Zugriffsrechte je Rolle."
            />

            <div
                v-if="roles.length === 0"
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed p-12"
            >
                <p class="text-muted-foreground">
                    Keine Rollen vorhanden. Erstellen Sie zuerst eine Rolle.
                </p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">
                        Rolle auswählen
                    </p>
                    <div class="space-y-1">
                        <button
                            v-for="role in roles"
                            :key="role.id"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition-colors"
                            :class="
                                selectedRoleId === role.id
                                    ? 'border-primary bg-primary/5'
                                    : 'border-transparent hover:bg-muted/50'
                            "
                            @click="selectRole(role.id)"
                        >
                            <Shield
                                class="size-4 shrink-0"
                                :class="
                                    selectedRoleId === role.id
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium"
                                    :class="
                                        selectedRoleId === role.id
                                            ? 'text-primary'
                                            : ''
                                    "
                                >
                                    {{ role.name }}
                                </p>
                                <p
                                    v-if="role.description"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ role.description }}
                                </p>
                            </div>
                            <Badge variant="secondary" class="shrink-0">
                                <Users class="mr-1 size-3" />
                                {{ role.users_count }}
                            </Badge>
                        </button>
                    </div>
                </div>

                <div v-if="selectedRole">
                    <Card>
                        <CardHeader>
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <CardTitle>
                                        {{ selectedRole.name }}
                                    </CardTitle>
                                    <CardDescription>
                                        {{ permissionCount }} von
                                        {{ sections.length }} Bereichen
                                        aktiviert
                                    </CardDescription>
                                </div>
                                <Button
                                    :disabled="form.processing || !isDirty"
                                    @click="submit"
                                >
                                    <Spinner
                                        v-if="form.processing"
                                        class="mr-2 size-4"
                                    />
                                    <Check v-else class="mr-2 size-4" />
                                    Speichern
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <AlertError
                                v-if="form.hasErrors"
                                :message="Object.values(form.errors).join(', ')"
                            />

                            <div class="divide-y">
                                <div
                                    v-for="section in sections"
                                    :key="section.value"
                                    class="grid grid-cols-[1fr_auto] items-start gap-4 py-4 first:pt-0 last:pb-0"
                                >
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ section.label }}
                                        </p>
                                        <div
                                            v-if="
                                                sectionHasAnyPermission(
                                                    section.value,
                                                )
                                            "
                                            class="mt-0.5"
                                        >
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                Aktiv
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-6">
                                        <div class="flex items-center gap-2">
                                            <Switch
                                                :id="`${section.value}-read`"
                                                :checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_read
                                                "
                                                :disabled="form.processing"
                                                @update:checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_read = $event
                                                "
                                            />
                                            <Label
                                                :for="`${section.value}-read`"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Lesen
                                            </Label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <Switch
                                                :id="`${section.value}-write`"
                                                :checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_write
                                                "
                                                :disabled="form.processing"
                                                @update:checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_write = $event
                                                "
                                            />
                                            <Label
                                                :for="`${section.value}-write`"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Schreiben
                                            </Label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <Switch
                                                :id="`${section.value}-write-owned`"
                                                :checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_write_owned
                                                "
                                                :disabled="form.processing"
                                                @update:checked="
                                                    form.permissions[
                                                        section.value
                                                    ].can_write_owned = $event
                                                "
                                            />
                                            <Label
                                                :for="`${section.value}-write-owned`"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Eigene
                                            </Label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
