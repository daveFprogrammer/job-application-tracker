<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import DemoController from '@/actions/App/Http/Controllers/DemoController';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
        label?: string;
    }>(),
    {
        variant: 'outline',
        size: 'default',
        label: 'Try the demo',
    },
);
</script>

<template>
    <Form
        v-if="$page.props.demo.enabled && !$page.props.auth.user"
        v-bind="DemoController.store.form()"
        v-slot="{ processing }"
        class="inline-flex"
    >
        <Button
            type="submit"
            :variant="variant"
            :size="size"
            :disabled="processing"
            data-test="demo-login-button"
        >
            <Spinner v-if="processing" />
            <Sparkles v-else />
            {{ label }}
        </Button>
    </Form>
</template>
