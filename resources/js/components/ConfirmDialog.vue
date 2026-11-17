<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RouteDefinition } from '@/wayfinder';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        action: RouteDefinition<'delete'>;
        confirmLabel?: string;
    }>(),
    {
        confirmLabel: 'Delete',
    },
);

const open = ref(false);
const processing = ref(false);

function confirm(): void {
    router.visit(props.action, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onSuccess: () => (open.value = false),
        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    data-test="confirm-delete-button"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
