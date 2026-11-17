<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import type { SortableEvent } from 'sortablejs';
import { VueDraggable } from 'vue-draggable-plus';
import { ref, watch } from 'vue';
import { move } from '@/actions/App/Http/Controllers/JobApplicationController';
import ApplicationCard from '@/components/applications/ApplicationCard.vue';
import { Button } from '@/components/ui/button';
import { statusAccent } from '@/lib/status';
import { create, index } from '@/routes/applications';
import type { ApplicationStatus, JobApplication, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: index() }],
    },
});

const props = defineProps<{
    applications: JobApplication[];
    statuses: Option<ApplicationStatus>[];
}>();

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
                    Drag the cards to move them through your hiring pipeline.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New application
                </Link>
            </Button>
        </div>

        <div class="-mx-4 flex flex-1 gap-4 overflow-x-auto px-4 pb-2">
            <section
                v-for="status in statuses"
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
                        Drop a card here
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
