<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    BellOff,
    Briefcase,
    CalendarDays,
    ExternalLink,
    MapPin,
    Pencil,
    Trash2,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InterviewController from '@/actions/App/Http/Controllers/Applications/InterviewController';
import NoteController from '@/actions/App/Http/Controllers/Applications/NoteController';
import ReminderController from '@/actions/App/Http/Controllers/Applications/ReminderController';
import JobApplicationController from '@/actions/App/Http/Controllers/JobApplicationController';
import InterviewForm from '@/components/applications/InterviewForm.vue';
import StatusTimeline from '@/components/applications/StatusTimeline.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import Textarea from '@/components/Textarea.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatDateTime } from '@/lib/format';
import { statusAccent } from '@/lib/status';
import { edit, index } from '@/routes/applications';
import type { FormOptions, JobApplication } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: index() }],
    },
});

const props = defineProps<FormOptions & { application: JobApplication }>();

const editingInterviewId = ref<number | null>(null);

const statusLabel = computed(
    () =>
        props.statuses.find((s) => s.value === props.application.status)
            ?.label ?? props.application.status,
);

const workModeLabel = computed(
    () =>
        props.workModes.find((m) => m.value === props.application.work_mode)
            ?.label,
);
</script>

<template>
    <Head :title="`${application.position} at ${application.company}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span
                        class="size-2 rounded-full"
                        :class="statusAccent[application.status]"
                    />
                    <Badge variant="secondary">{{ statusLabel }}</Badge>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ application.position }}
                </h1>
                <p class="text-muted-foreground">{{ application.company }}</p>
            </div>
            <div class="flex gap-2">
                <Button v-if="application.job_url" variant="outline" as-child>
                    <a
                        :href="application.job_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink />
                        Job posting
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="edit(application.id)">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <ConfirmDialog
                    title="Are you sure you want to delete this application?"
                    :description="`${application.position} at ${application.company} will be permanently deleted, together with its interviews, notes and reminders. This cannot be undone.`"
                    confirm-label="Delete application"
                    :action="JobApplicationController.destroy(application.id)"
                >
                    <Button
                        variant="destructive"
                        data-test="delete-application-button"
                    >
                        <Trash2 />
                        Delete
                    </Button>
                </ConfirmDialog>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <dl class="grid gap-4 text-sm sm:grid-cols-2">
                            <div class="flex items-center gap-2">
                                <MapPin class="size-4 text-muted-foreground" />
                                <dt class="sr-only">Location</dt>
                                <dd>
                                    {{ application.location ?? '—' }}
                                    <span
                                        v-if="workModeLabel"
                                        class="text-muted-foreground"
                                    >
                                        · {{ workModeLabel }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <Wallet class="size-4 text-muted-foreground" />
                                <dt class="sr-only">Salary range</dt>
                                <dd>{{ application.salary_range ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <CalendarDays
                                    class="size-4 text-muted-foreground"
                                />
                                <dt class="text-muted-foreground">
                                    Applied on
                                </dt>
                                <dd>
                                    {{ formatDate(application.applied_at) }}
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <Briefcase
                                    class="size-4 text-muted-foreground"
                                />
                                <dt class="text-muted-foreground">Added</dt>
                                <dd>
                                    {{ formatDate(application.created_at) }}
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-if="application.description"
                            class="text-sm whitespace-pre-line"
                        >
                            {{ application.description }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                        <CardDescription>
                            Call recaps, feedback, questions to ask.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <Form
                            v-bind="NoteController.store.form(application.id)"
                            :options="{ preserveScroll: true }"
                            reset-on-success
                            class="grid gap-2"
                            v-slot="{ errors, processing }"
                        >
                            <Label for="note-body" class="sr-only">Note</Label>
                            <Textarea
                                id="note-body"
                                name="body"
                                rows="3"
                                placeholder="Write a note…"
                                required
                            />
                            <InputError :message="errors.body" />
                            <div>
                                <Button size="sm" :disabled="processing">
                                    Add note
                                </Button>
                            </div>
                        </Form>

                        <ul class="grid gap-3">
                            <li
                                v-for="note in application.notes"
                                :key="note.id"
                                class="group rounded-lg border p-3"
                            >
                                <div
                                    class="flex items-center justify-between text-xs text-muted-foreground"
                                >
                                    {{ formatDateTime(note.created_at) }}
                                    <ConfirmDialog
                                        title="Delete this note?"
                                        description="The note will be permanently deleted."
                                        :action="
                                            NoteController.destroy(note.id)
                                        "
                                    >
                                        <button
                                            type="button"
                                            class="opacity-0 transition group-hover:opacity-100 hover:text-destructive focus:opacity-100"
                                            aria-label="Delete note"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </ConfirmDialog>
                                </div>
                                <p class="mt-1 text-sm whitespace-pre-line">
                                    {{ note.body }}
                                </p>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-col gap-6">
                <StatusTimeline
                    v-if="application.status_changes?.length"
                    :changes="application.status_changes"
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Interviews</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <ul
                            v-if="application.interviews?.length"
                            class="grid gap-3"
                        >
                            <li
                                v-for="interview in application.interviews"
                                :key="interview.id"
                                class="rounded-lg border p-3 text-sm"
                            >
                                <InterviewForm
                                    v-if="editingInterviewId === interview.id"
                                    :action="
                                        InterviewController.update.form(
                                            interview.id,
                                        )
                                    "
                                    :interview="interview"
                                    :interview-types="interviewTypes"
                                    submit-label="Save"
                                    @saved="editingInterviewId = null"
                                    @cancel="editingInterviewId = null"
                                />
                                <template v-else>
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div>
                                            <p class="font-medium">
                                                {{
                                                    formatDateTime(
                                                        interview.scheduled_at,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-muted-foreground">
                                                {{ interview.type_label }}
                                                <template
                                                    v-if="interview.location"
                                                >
                                                    · {{ interview.location }}
                                                </template>
                                            </p>
                                        </div>
                                        <div
                                            class="flex items-center gap-1 text-muted-foreground"
                                        >
                                            <Bell
                                                v-if="interview.has_reminder"
                                                class="size-3.5"
                                                aria-label="Reminder set"
                                            />
                                            <button
                                                type="button"
                                                class="rounded p-1 hover:text-foreground"
                                                aria-label="Edit interview"
                                                @click="
                                                    editingInterviewId =
                                                        interview.id
                                                "
                                            >
                                                <Pencil class="size-3.5" />
                                            </button>
                                            <ConfirmDialog
                                                title="Delete this interview?"
                                                description="The interview and its reminder will be permanently deleted."
                                                :action="
                                                    InterviewController.destroy(
                                                        interview.id,
                                                    )
                                                "
                                            >
                                                <button
                                                    type="button"
                                                    class="rounded p-1 hover:text-destructive"
                                                    aria-label="Delete interview"
                                                >
                                                    <Trash2 class="size-3.5" />
                                                </button>
                                            </ConfirmDialog>
                                        </div>
                                    </div>
                                    <p
                                        v-if="interview.notes"
                                        class="mt-2 whitespace-pre-line"
                                    >
                                        {{ interview.notes }}
                                    </p>
                                </template>
                            </li>
                        </ul>

                        <details class="group rounded-lg border p-3">
                            <summary
                                class="cursor-pointer text-sm font-medium select-none"
                            >
                                Schedule an interview
                            </summary>
                            <div class="mt-3">
                                <InterviewForm
                                    :action="
                                        InterviewController.store.form(
                                            application.id,
                                        )
                                    "
                                    :interview-types="interviewTypes"
                                    submit-label="Add interview"
                                />
                            </div>
                        </details>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Reminders</CardTitle>
                        <CardDescription>
                            Sent to your inbox at the chosen time.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <ul
                            v-if="application.reminders?.length"
                            class="grid gap-2"
                        >
                            <li
                                v-for="reminder in application.reminders"
                                :key="reminder.id"
                                class="flex items-start justify-between gap-2 text-sm"
                                :class="{
                                    'text-muted-foreground line-through':
                                        reminder.sent_at,
                                }"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ formatDateTime(reminder.remind_at) }}
                                    </p>
                                    <p>{{ reminder.message }}</p>
                                </div>
                                <ConfirmDialog
                                    title="Delete this reminder?"
                                    description="You will not receive this email."
                                    :action="
                                        ReminderController.destroy(reminder.id)
                                    "
                                >
                                    <button
                                        type="button"
                                        class="rounded p-1 text-muted-foreground hover:text-destructive"
                                        aria-label="Delete reminder"
                                    >
                                        <BellOff class="size-3.5" />
                                    </button>
                                </ConfirmDialog>
                            </li>
                        </ul>

                        <Form
                            v-bind="
                                ReminderController.store.form(application.id)
                            "
                            :options="{ preserveScroll: true }"
                            reset-on-success
                            class="grid gap-2"
                            v-slot="{ errors, processing }"
                        >
                            <Label for="remind_at">Remind me on</Label>
                            <DateTimePicker
                                id="remind_at"
                                name="remind_at"
                                future-only
                            />
                            <InputError :message="errors.remind_at" />
                            <Input
                                name="message"
                                placeholder="Follow up with the recruiter"
                                required
                            />
                            <InputError :message="errors.message" />
                            <div>
                                <Button size="sm" :disabled="processing">
                                    Add reminder
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
