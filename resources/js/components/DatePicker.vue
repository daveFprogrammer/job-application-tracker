<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { parseDate } from '@internationalized/date';
import { CalendarIcon, X } from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    useTemplateRef,
} from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { locale } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * Calendar date picker that submits a `YYYY-MM-DD` value through a hidden
 * input, so it works inside Inertia's <Form> like a native field.
 */
const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        defaultValue?: string | null;
        placeholder?: string;
        clearable?: boolean;
    }>(),
    {
        defaultValue: null,
        placeholder: 'Pick a date',
        clearable: true,
    },
);

function parse(value: string | null): DateValue | undefined {
    return value ? parseDate(value.slice(0, 10)) : undefined;
}

const date = shallowRef<DateValue | undefined>(parse(props.defaultValue));
const open = ref(false);
const input = useTemplateRef<HTMLInputElement>('input');

const label = computed(() =>
    date.value
        ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(
              date.value.toDate('UTC'),
          )
        : props.placeholder,
);

function select(value: DateValue | undefined): void {
    date.value = value;
    open.value = false;
}

// Follow the parent form when Inertia resets it after a successful submit.
const onReset = () => (date.value = parse(props.defaultValue));
onMounted(() => input.value?.form?.addEventListener('reset', onReset));
onBeforeUnmount(() => input.value?.form?.removeEventListener('reset', onReset));
</script>

<template>
    <div class="flex gap-1">
        <input
            ref="input"
            type="hidden"
            :name="name"
            :value="date?.toString() ?? ''"
        />
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    :class="
                        cn(
                            'flex-1 justify-start font-normal',
                            !date && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon />
                    {{ label }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    :model-value="date"
                    :locale="locale"
                    layout="month-and-year"
                    initial-focus
                    @update:model-value="select"
                />
            </PopoverContent>
        </Popover>
        <Button
            v-if="clearable && date"
            type="button"
            variant="ghost"
            size="icon"
            aria-label="Clear date"
            @click="date = undefined"
        >
            <X />
        </Button>
    </div>
</template>
