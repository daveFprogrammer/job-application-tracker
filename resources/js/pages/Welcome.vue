<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, CalendarClock, KanbanSquare } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: KanbanSquare,
        title: 'Kanban pipeline',
        text: 'Drag every application from wishlist to offer and see where you stand at a glance.',
    },
    {
        icon: CalendarClock,
        title: 'Interviews & notes',
        text: 'Keep dates, links, recaps and the questions to ask next time in one place.',
    },
    {
        icon: Bell,
        title: 'Email reminders',
        text: 'Get an email before each interview and when it is time to follow up.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between p-6"
        >
            <div class="flex items-center gap-2 font-semibold">
                <div
                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </div>
                {{ $page.props.name }}
            </div>
            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Dashboard</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Sign up</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-12 px-6 py-12"
        >
            <section class="max-w-2xl space-y-4">
                <h1
                    class="text-4xl font-semibold tracking-tight text-balance md:text-5xl"
                >
                    Your job search, organised.
                </h1>
                <p class="text-lg text-muted-foreground">
                    Track every application, interview and follow-up on a simple
                    board, and let reminders land in your inbox so nothing slips
                    through the cracks.
                </p>
                <Button size="lg" as-child>
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                    >
                        Start tracking
                    </Link>
                </Button>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-xl border p-5"
                >
                    <component
                        :is="feature.icon"
                        class="mb-3 size-5 text-muted-foreground"
                    />
                    <h2 class="font-medium">{{ feature.title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ feature.text }}
                    </p>
                </article>
            </section>
        </main>
    </div>
</template>
