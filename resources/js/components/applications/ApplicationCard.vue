<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarClock,
    GripVertical,
    MapPin,
    MessageSquareText,
} from '@lucide/vue';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/applications';
import type { JobApplication } from '@/types';

defineProps<{
    application: JobApplication;
}>();
</script>

<template>
    <article
        :data-id="application.id"
        class="group rounded-lg border bg-card p-3 text-card-foreground shadow-xs transition hover:border-foreground/20 hover:shadow-sm"
    >
        <div class="flex items-start gap-2">
            <GripVertical
                class="drag-handle mt-0.5 size-4 shrink-0 cursor-grab text-muted-foreground/50 active:cursor-grabbing"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <Link
                    :href="show(application.id)"
                    class="block truncate font-medium hover:underline"
                >
                    {{ application.position }}
                </Link>
                <p class="truncate text-sm text-muted-foreground">
                    {{ application.company }}
                </p>
            </div>
        </div>

        <div
            class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 pl-6 text-xs text-muted-foreground"
        >
            <span
                v-if="application.location"
                class="inline-flex items-center gap-1"
            >
                <MapPin class="size-3" />
                {{ application.location }}
            </span>
            <span
                v-if="application.notes_count"
                class="inline-flex items-center gap-1"
            >
                <MessageSquareText class="size-3" />
                {{ application.notes_count }}
            </span>
            <span
                v-if="application.next_interview_at"
                class="inline-flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400"
            >
                <CalendarClock class="size-3" />
                {{ formatDateTime(application.next_interview_at) }}
            </span>
        </div>
    </article>
</template>
