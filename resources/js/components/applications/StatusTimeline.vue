<script setup lang="ts">
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { statusAccent } from '@/lib/status';
import type { StatusChange } from '@/types';

const props = defineProps<{
    changes: StatusChange[];
}>();

const DAY = 24 * 60 * 60 * 1000;

// Newest first: the most relevant step is the current one.
const steps = computed(() => [...props.changes].reverse());

const current = computed(() => steps.value[0]);

const daysInCurrentStatus = computed(() =>
    current.value
        ? Math.max(
              0,
              Math.floor(
                  (Date.now() - new Date(current.value.changed_at).getTime()) /
                      DAY,
              ),
          )
        : 0,
);

function duration(days: number): string {
    if (days === 0) {
        return 'since today';
    }

    return days === 1 ? 'for 1 day' : `for ${days} days`;
}

function describe(change: StatusChange): string {
    return change.from_status === null
        ? `Added to ${change.to_label}`
        : `Moved to ${change.to_label}`;
}
</script>

<template>
    <Card data-test="status-timeline">
        <CardHeader>
            <CardTitle>History</CardTitle>
            <CardDescription v-if="current">
                In {{ current.to_label }} {{ duration(daysInCurrentStatus) }}.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <ol class="relative grid gap-4">
                <li
                    v-for="(change, index) in steps"
                    :key="change.id"
                    class="relative flex gap-3 text-sm"
                >
                    <span
                        v-if="index < steps.length - 1"
                        class="absolute top-4 bottom-[-1rem] left-[0.3125rem] w-px bg-border"
                        aria-hidden="true"
                    />
                    <span
                        class="relative mt-1 size-2.5 shrink-0 rounded-full ring-4 ring-card"
                        :class="statusAccent[change.to_status]"
                        aria-hidden="true"
                    />
                    <div>
                        <p :class="{ 'font-medium': index === 0 }">
                            {{ describe(change) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(change.changed_at) }}
                        </p>
                    </div>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
