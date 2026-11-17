<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Textarea from '@/components/Textarea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Interview, Option } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

const props = defineProps<{
    action: RouteFormDefinition<'post'>;
    interviewTypes: Option[];
    interview?: Interview;
    submitLabel: string;
}>();

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const idPrefix = `interview-${props.interview?.id ?? 'new'}`;
</script>

<template>
    <Form
        v-bind="action"
        class="grid gap-3"
        :options="{ preserveScroll: true }"
        :reset-on-success="!interview"
        @success="emit('saved')"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-scheduled_at`">Date and time</Label>
            <DateTimePicker
                :id="`${idPrefix}-scheduled_at`"
                name="scheduled_at"
                :default-value="interview?.scheduled_at"
                default-time="10:00"
            />
            <InputError :message="errors.scheduled_at" />
        </div>
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-type`">Type</Label>
            <NativeSelect
                :id="`${idPrefix}-type`"
                name="type"
                :options="interviewTypes"
                :value="interview?.type ?? 'video'"
            />
            <InputError :message="errors.type" />
        </div>
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-location`">Where</Label>
            <Input
                :id="`${idPrefix}-location`"
                name="location"
                :default-value="interview?.location ?? ''"
                placeholder="Google Meet link, office address…"
            />
            <InputError :message="errors.location" />
        </div>
        <div class="grid gap-1.5">
            <Label :for="`${idPrefix}-notes`">Notes</Label>
            <Textarea
                :id="`${idPrefix}-notes`"
                name="notes"
                rows="2"
                :value="interview?.notes ?? ''"
                placeholder="Who you will meet, what to prepare…"
            />
            <InputError :message="errors.notes" />
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                name="remind"
                value="1"
                class="size-4 rounded border-input accent-primary"
                :checked="interview ? interview.has_reminder : true"
            />
            Email me a reminder the day before
        </label>
        <div class="flex gap-2">
            <Button size="sm" :disabled="processing">{{ submitLabel }}</Button>
            <Button
                v-if="interview"
                type="button"
                size="sm"
                variant="ghost"
                @click="emit('cancel')"
            >
                Cancel
            </Button>
        </div>
    </Form>
</template>
