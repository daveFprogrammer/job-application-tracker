<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import type { RouteFormDefinition } from '@/wayfinder';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Textarea from '@/components/Textarea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { FormOptions, JobApplication } from '@/types';

defineProps<
    {
        action: RouteFormDefinition<'post'>;
        cancelHref: string;
        submitLabel: string;
        application?: JobApplication;
        defaultStatus?: string;
    } & Pick<FormOptions, 'statuses' | 'workModes'>
>();
</script>

<template>
    <Form
        v-bind="action"
        class="grid max-w-3xl gap-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="company">Company</Label>
                <Input
                    id="company"
                    name="company"
                    :default-value="application?.company"
                    required
                    autofocus
                    placeholder="Acme S.p.A."
                />
                <InputError :message="errors.company" />
            </div>

            <div class="grid gap-2">
                <Label for="position">Position</Label>
                <Input
                    id="position"
                    name="position"
                    :default-value="application?.position"
                    required
                    placeholder="Backend Developer"
                />
                <InputError :message="errors.position" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <NativeSelect
                    id="status"
                    name="status"
                    :options="statuses"
                    :value="application?.status ?? defaultStatus"
                />
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="applied_at">Applied on</Label>
                <DatePicker
                    id="applied_at"
                    name="applied_at"
                    :default-value="application?.applied_at"
                    placeholder="Not sent yet"
                />
                <InputError :message="errors.applied_at" />
            </div>

            <div class="grid gap-2">
                <Label for="location">Location</Label>
                <Input
                    id="location"
                    name="location"
                    :default-value="application?.location ?? ''"
                    placeholder="Milano"
                />
                <InputError :message="errors.location" />
            </div>

            <div class="grid gap-2">
                <Label for="work_mode">Work mode</Label>
                <NativeSelect
                    id="work_mode"
                    name="work_mode"
                    :options="workModes"
                    placeholder="—"
                    :value="application?.work_mode ?? ''"
                />
                <InputError :message="errors.work_mode" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_range">Salary range</Label>
                <Input
                    id="salary_range"
                    name="salary_range"
                    :default-value="application?.salary_range ?? ''"
                    placeholder="35-42k"
                />
                <InputError :message="errors.salary_range" />
            </div>

            <div class="grid gap-2">
                <Label for="job_url">Job posting URL</Label>
                <Input
                    id="job_url"
                    name="job_url"
                    type="url"
                    :default-value="application?.job_url ?? ''"
                    placeholder="Link to the job ad"
                />
                <InputError :message="errors.job_url" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <Textarea
                id="description"
                name="description"
                rows="5"
                :value="application?.description ?? ''"
                placeholder="Stack, team, requirements, why you are interested…"
            />
            <InputError :message="errors.description" />
        </div>

        <div class="flex items-center gap-3">
            <Button :disabled="processing" data-test="save-application-button">
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
