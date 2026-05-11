<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
import CurrentTeamController from '@/wayfinder/App/Http/Controllers/Team/CurrentTeamController';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();

const user = computed(() => (page.props.auth?.user ?? null) as App.Http.Resources.User.UserResource | null);
const userTeams = computed<App.Http.Resources.Team.TeamResource[]>(() => user.value?.teams ?? []);
const currentTeam = computed(() => userTeams.value.find((team) => team.id === user.value?.current_team_id) ?? null);
const showTeamSwitcher = computed(() => currentTeam.value !== null && userTeams.value.length > 1);

const form = useForm({
    team_id: null as number | null,
});

function switchTeam(value: unknown): void {
    form.team_id = Number(value);
    form.put(CurrentTeamController.update.url());
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div v-if="showTeamSwitcher" class="ml-auto">
            <Select :model-value="String(currentTeam?.id)" @update:model-value="switchTeam">
                <SelectTrigger class="w-48">
                    <SelectValue :placeholder="currentTeam?.name" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="team in userTeams" :key="team.id" :value="String(team.id)">
                        {{ team.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </header>
</template>
