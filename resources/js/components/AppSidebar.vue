<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Boxes,
    CalendarOff,
    CheckSquare,
    ClipboardList,
    Folder,
    GraduationCap,
    KeyRound,
    LayoutGrid,
    LinkIcon,
    Medal,
    Package,
    Shield,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type AccessSection } from '@/lib/access-sections';
import { dashboard } from '@/routes';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as qualificationsIndex } from '@/routes/qualifications';
import { index as resourceAbsencesIndex } from '@/routes/resource-absences';
import { index as resourceQualificationsIndex } from '@/routes/resource-qualifications';
import { index as resourceTypesIndex } from '@/routes/resource-types';
import { index as resourcesIndex } from '@/routes/resources';
import { index as rolesIndex } from '@/routes/roles';
import { index as taskAssignmentsIndex } from '@/routes/task-assignments';
import { index as taskRequirementsIndex } from '@/routes/task-requirements';
import { index as tasksIndex } from '@/routes/tasks';
import { index as usersIndex } from '@/routes/users';
import type { AppPageProps, NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage<AppPageProps>();

const permissions = computed(() => page.props.auth?.permissions ?? {});

const canAccess = (sections: AccessSection[]): boolean =>
    sections.some((section) => {
        const permission = permissions.value?.[section];

        return (
            permission?.can_read ||
            permission?.can_write ||
            permission?.can_write_owned
        );
    });

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const resourceNavItems = computed<NavItem[]>(() => {
    if (!canAccess([AccessSections.ResourceManagement])) {
        return [];
    }

    return [
        {
            title: 'Ressourcen',
            href: resourcesIndex(),
            icon: Package,
        },
        {
            title: 'Ressourcentypen',
            href: resourceTypesIndex(),
            icon: Boxes,
        },
        {
            title: 'Abwesenheiten',
            href: resourceAbsencesIndex(),
            icon: CalendarOff,
        },
        {
            title: 'Qualifikationen',
            href: qualificationsIndex(),
            icon: GraduationCap,
        },
        {
            title: 'Ressourcenqualifikationen',
            href: resourceQualificationsIndex(),
            icon: Medal,
        },
    ];
});

const taskNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (canAccess([AccessSections.TaskCreation])) {
        items.push(
            {
                title: 'Aufgaben',
                href: tasksIndex(),
                icon: CheckSquare,
            },
            {
                title: 'Anforderungen',
                href: taskRequirementsIndex(),
                icon: ClipboardList,
            },
        );
    }

    if (
        canAccess([
            AccessSections.ManualAssignment,
            AccessSections.EmployeeFeedback,
        ])
    ) {
        items.push({
            title: 'Zuweisungen',
            href: taskAssignmentsIndex(),
            icon: LinkIcon,
        });
    }

    return items;
});

const adminNavItems = computed<NavItem[]>(() => {
    if (!canAccess([AccessSections.RoleManagement])) {
        return [];
    }

    return [
        {
            title: 'Benutzer',
            href: usersIndex(),
            icon: Users,
        },
        {
            title: 'Rollen',
            href: rolesIndex(),
            icon: Shield,
        },
        {
            title: 'Berechtigungen',
            href: permissionsIndex(),
            icon: KeyRound,
        },
    ];
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="resourceNavItems.length"
                :items="resourceNavItems"
                label="Ressourcen"
            />
            <NavMain
                v-if="taskNavItems.length"
                :items="taskNavItems"
                label="Aufgaben"
            />
            <NavMain
                v-if="adminNavItems.length"
                :items="adminNavItems"
                label="Verwaltung"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
