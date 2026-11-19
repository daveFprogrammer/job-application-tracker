<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import { logout, register } from '@/routes';

// Leave the shared account first, otherwise the guest-only sign-up page
// would bounce straight back to the dashboard.
function createOwnAccount(): void {
    router.flushAll();
    router.post(logout(), {}, { onSuccess: () => router.visit(register()) });
}
</script>

<template>
    <div
        v-if="$page.props.auth.user?.is_demo"
        class="flex items-start gap-2 border-b bg-muted/60 px-4 py-2 text-sm md:px-6"
        role="status"
        data-test="demo-banner"
    >
        <Sparkles class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
        <p class="text-muted-foreground">
            You are exploring the shared
            <span class="font-medium text-foreground">demo account</span>: edit
            anything you like, the board is reset every
            {{ $page.props.demo.resetAfterHours }} hours and no emails are sent.
            <button
                type="button"
                class="font-medium whitespace-nowrap text-foreground underline underline-offset-4"
                @click="createOwnAccount"
            >
                Create your own account
            </button>
        </p>
    </div>
</template>
