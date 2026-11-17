<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { parseDate, today, getLocalTimeZone } from '@internationalized/date';
import { CalendarIcon, Clock } from '@lucide/vue';
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
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { locale, toDateTimeLocal } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * Calendar + time picker. Submits a local `YYYY-MM-DDTHH:mm` value through a
 * hidden input, the same format as a native datetime-local field.
 */
const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        /** ISO 8601 string, e.g. from the API. */
        defaultValue?: string | null;
        defaultTime?: string;
        placeholder?: string;
        /** Disable days before today. */
        futureOnly?: boolean;
    }>(),
    {
        defaultValue: null,
        defaultTime: '09:00',
        placeholder: 'Pick a date',
        futureOnly: false,
    },
);

function initial(): { date: DateValue | undefined; time: string } {
    const local = toDateTimeLocal(props.defaultValue);

    return local
        ? { date: parseDate(local.slice(0, 10)), time: local.slice(11, 16) }
        : { date: undefined, time: props.defaultTime };
}

const date = shallowRef<DateValue | undefined>(initial().date);
const time = ref<string>(initial().time);
const open = ref(false);
const input = useTemplateRef<HTMLInputElement>('input');

const minValue = computed(() =>
    props.futureOnly ? today(getLocalTimeZone()) : undefined,
);

const value = computed(() =>
    date.value ? `${date.value.toString()}T${time.value || '00:00'}` : '',
);

const label = computed(() =>
    date.value
        ? new Intl.DateTimeFormat(locale, {
              weekday: 'short',
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(date.value.toDate('UTC'))
        : props.placeholder,
);

function select(selected: DateValue | undefined): void {
    date.value = selected;
    open.value = false;
}

const onReset = () => {
    const { date: d, time: t } = initial();
    date.value = d;
    time.value = t;
};
onMounted(() => input.value?.form?.addEventListener('reset', onReset));
onBeforeUnmount(() => input.value?.form?.removeEventListener('reset', onReset));
</script>

<template>
    <div class="flex gap-2">
        <input ref="input" type="hidden" :name="name" :value="value" />
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    :class="
                        cn(
                            'min-w-0 flex-1 justify-start font-normal',
                            !date && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon />
                    <span class="truncate">{{ label }}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    :model-value="date"
                    :min-value="minValue"
                    :locale="locale"
                    layout="month-and-year"
                    initial-focus
                    @update:model-value="select"
                />
            </PopoverContent>
        </Popover>
        <div class="relative w-28 shrink-0">
            <Clock
                class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="time"
                type="time"
                aria-label="Time"
                class="appearance-none pl-8 [&::-webkit-calendar-picker-indicator]:hidden"
            />
        </div>
    </div>
</template>
