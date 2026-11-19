<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import type { SortableEvent } from 'sortablejs';
import { VueDraggable } from 'vue-draggable-plus';
import { computed, reactive, ref, watch } from 'vue';
import { move } from '@/actions/App/Http/Controllers/JobApplicationController';
import ApplicationCard from '@/components/applications/ApplicationCard.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { statusAccent } from '@/lib/status';
import { create, index } from '@/routes/applications';
import type {
    ApplicationStatus,
    BoardFilters,
    JobApplication,
    Option,
    WorkMode,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: index() }],
    },
});

const props = defineProps<{
    applications: JobApplication[];
    statuses: Option<ApplicationStatus>[];
    workModes: Option<WorkMode>[];
    filters: BoardFilters;
    total: number;
}>();

const filters = reactive<BoardFilters>({ ...props.filters });

// Reordering a filtered column would compute positions against a partial
// list, so drag and drop is paused while search or work mode are active.
const isNarrowed = computed(
    () => props.filters.search !== '' || props.filters.work_mode !== null,
);

const isFiltered = computed(
    () => isNarrowed.value || props.filters.hide_rejected,
);

const visibleStatuses = computed(() =>
    props.statuses.filter(
        (status) =>
            !(props.filters.hide_rejected && status.value === 'rejected'),
    ),
);

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: filters.search.trim() || undefined,
            work_mode: filters.work_mode ?? undefined,
            hide_rejected: filters.hide_rejected ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['applications', 'filters'],
        },
    );
}

const applySearch = useDebounceFn(applyFilters, 300);

function clearFilters(): void {
    Object.assign(filters, {
        search: '',
        work_mode: null,
        hide_rejected: false,
    });
    applyFilters();
}

type Columns = Record<ApplicationStatus, JobApplication[]>;

function groupByStatus(applications: JobApplication[]): Columns {
    const columns = Object.fromEntries(
        props.statuses.map((status) => [status.value, []]),
    ) as unknown as Columns;

    for (const application of applications) {
        columns[application.status].push(application);
    }

    return columns;
}

// Local copy of the board: the drag and drop library mutates it optimistically,
// the server response (a fresh `applications` prop) re-syncs it.
const columns = ref<Columns>(groupByStatus(props.applications));

watch(
    () => props.applications,
    (applications) => {
        columns.value = groupByStatus(applications);
    },
);

function persistMove(status: ApplicationStatus, event: SortableEvent): void {
    const id = Number(event.item.dataset.id);
    const position = event.newIndex ?? 0;
    const card = columns.value[status].find((item) => item.id === id);

    if (card) {
        card.status = status;
    }

    router.patch(
        move.url(id),
        { status, position },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['applications'],
            onError: () => {
                columns.value = groupByStatus(props.applications);
            },
        },
    );
}
</script>

<template>
    <Head title="Applications" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">
                    Applications
                </h1>
                <p class="text-sm text-muted-foreground">
                    <template v-if="isNarrowed">
                        Showing {{ applications.length }} of {{ total }}
                        applications. Clear the search and work mode to reorder
                        cards.
                    </template>
                    <template v-else>
                        Drag the cards to move them through your hiring
                        pipeline.
                    </template>
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New application
                </Link>
            </Button>
        </div>

        <div
            class="flex flex-wrap items-center gap-3"
            role="search"
            data-test="board-filters"
        >
            <div class="relative w-full sm:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    class="pl-8"
                    placeholder="Search company, role or location"
                    aria-label="Search applications"
                    data-test="board-search"
                    @update:model-value="applySearch"
                />
            </div>
            <NativeSelect
                class="w-full sm:w-44"
                :options="workModes"
                placeholder="Any work mode"
                aria-label="Work mode"
                data-test="board-work-mode"
                :value="filters.work_mode ?? ''"
                @change="
                    filters.work_mode =
                        (($event.target as HTMLSelectElement)
                            .value as WorkMode) || null;
                    applyFilters();
                "
            />
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="filters.hide_rejected"
                    type="checkbox"
                    class="size-4 rounded border-input accent-primary"
                    data-test="board-hide-rejected"
                    @change="applyFilters"
                />
                Hide rejected
            </label>
            <Button
                v-if="isFiltered"
                variant="ghost"
                size="sm"
                data-test="board-clear-filters"
                @click="clearFilters"
            >
                <X />
                Clear filters
            </Button>
        </div>

        <div class="-mx-4 flex flex-1 gap-4 overflow-x-auto px-4 pb-2">
            <section
                v-for="status in visibleStatuses"
                :key="status.value"
                class="flex w-72 shrink-0 flex-col rounded-xl bg-muted/50 dark:bg-muted/30"
                :data-test="`column-${status.value}`"
            >
                <header class="flex items-center justify-between px-3 pt-3">
                    <h2 class="flex items-center gap-2 text-sm font-medium">
                        <span
                            class="size-2 rounded-full"
                            :class="statusAccent[status.value]"
                        />
                        {{ status.label }}
                        <span class="text-muted-foreground">
                            {{ columns[status.value].length }}
                        </span>
                    </h2>
                    <Button variant="ghost" size="icon" class="size-7" as-child>
                        <Link
                            :href="create({ query: { status: status.value } })"
                            :aria-label="`Add to ${status.label}`"
                        >
                            <Plus class="size-4" />
                        </Link>
                    </Button>
                </header>

                <div class="relative flex-1">
                    <VueDraggable
                        v-model="columns[status.value]"
                        group="applications"
                        handle=".drag-handle"
                        :disabled="isNarrowed"
                        :animation="150"
                        ghost-class="opacity-40"
                        class="flex min-h-32 flex-col gap-2 p-3"
                        @add="persistMove(status.value, $event)"
                        @update="persistMove(status.value, $event)"
                    >
                        <ApplicationCard
                            v-for="application in columns[status.value]"
                            :key="application.id"
                            :application="application"
                        />
                    </VueDraggable>
                    <p
                        v-if="columns[status.value].length === 0"
                        class="pointer-events-none absolute inset-x-3 top-3 rounded-lg border border-dashed p-6 text-center text-xs text-muted-foreground"
                    >
                        {{ isNarrowed ? 'No matches' : 'Drop a card here' }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
