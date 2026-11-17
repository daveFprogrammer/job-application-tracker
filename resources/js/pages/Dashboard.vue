<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, CalendarClock, KanbanSquare } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { statusAccent } from '@/lib/status';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/applications';
import type { ApplicationStatus, Interview, Option, Reminder } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

defineProps<{
    stats: {
        total: number;
        byStatus: (Option<ApplicationStatus> & { count: number })[];
        responseRate: number | null;
    };
    upcomingInterviews: Interview[];
    pendingReminders: Reminder[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold tracking-tight">Dashboard</h1>
            <Button as-child>
                <Link :href="index()">
                    <KanbanSquare />
                    Open board
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Applications tracked</CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{ stats.total }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Response rate</CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{
                            stats.responseRate === null
                                ? '—'
                                : `${stats.responseRate}%`
                        }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Sent applications that reached an interview or an offer.
                </CardContent>
            </Card>
            <Card class="sm:col-span-2">
                <CardHeader>
                    <CardDescription>Pipeline</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <div
                        v-for="status in stats.byStatus"
                        :key="status.value"
                        class="flex items-center gap-3 text-sm"
                    >
                        <span class="w-24 shrink-0 text-muted-foreground">
                            {{ status.label }}
                        </span>
                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="statusAccent[status.value]"
                                :style="{
                                    width: stats.total
                                        ? `${(status.count / stats.total) * 100}%`
                                        : '0%',
                                }"
                            />
                        </div>
                        <span class="w-6 text-right tabular-nums">
                            {{ status.count }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarClock class="size-4" />
                        Upcoming interviews
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="upcomingInterviews.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No interviews scheduled yet.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="interview in upcomingInterviews"
                            :key="interview.id"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <Link
                                v-if="interview.application"
                                :href="show(interview.application.id)"
                                class="min-w-0 hover:underline"
                            >
                                <span class="block truncate font-medium">
                                    {{ interview.application.company }}
                                </span>
                                <span
                                    class="block truncate text-muted-foreground"
                                >
                                    {{ interview.type_label }} ·
                                    {{ interview.application.position }}
                                </span>
                            </Link>
                            <span class="shrink-0 tabular-nums">
                                {{ formatDateTime(interview.scheduled_at) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Bell class="size-4" />
                        Upcoming reminders
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="pendingReminders.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing to remember. Enjoy!
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="reminder in pendingReminders"
                            :key="reminder.id"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <Link
                                v-if="reminder.application"
                                :href="show(reminder.application.id)"
                                class="min-w-0 hover:underline"
                            >
                                <span class="block truncate font-medium">
                                    {{ reminder.message }}
                                </span>
                                <span
                                    class="block truncate text-muted-foreground"
                                >
                                    {{ reminder.application.company }}
                                </span>
                            </Link>
                            <span class="shrink-0 tabular-nums">
                                {{ formatDateTime(reminder.remind_at) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
