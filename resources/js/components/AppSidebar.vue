<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Boxes,
    CalendarOff,
    CheckSquare,
    ClipboardList,
    Folder,
    GraduationCap,
    LayoutGrid,
    LinkIcon,
    Medal,
    Package,
    Shield,
    Users,
} from 'lucide-vue-next';

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
import { dashboard } from '@/routes';
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
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const resourceNavItems: NavItem[] = [
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

const taskNavItems: NavItem[] = [
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
    {
        title: 'Zuweisungen',
        href: taskAssignmentsIndex(),
        icon: LinkIcon,
    },
];

const adminNavItems: NavItem[] = [
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
];

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
            <NavMain :items="resourceNavItems" label="Ressourcen" />
            <NavMain :items="taskNavItems" label="Aufgaben" />
            <NavMain :items="adminNavItems" label="Verwaltung" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
