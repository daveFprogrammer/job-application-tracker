<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import JobApplicationController from '@/actions/App/Http/Controllers/JobApplicationController';
import ApplicationForm from '@/components/applications/ApplicationForm.vue';
import Heading from '@/components/Heading.vue';
import { index, show } from '@/routes/applications';
import type { FormOptions, JobApplication } from '@/types';

const props = defineProps<FormOptions & { application: JobApplication }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Edit ${props.application.company}`" />

    <div class="p-4 md:p-6">
        <Heading
            :title="`Edit ${application.position}`"
            :description="application.company"
        />

        <ApplicationForm
            :action="JobApplicationController.update.form(application.id)"
            :cancel-href="show(application.id).url"
            submit-label="Save changes"
            :application="application"
            :statuses="statuses"
            :work-modes="workModes"
        />
    </div>
</template>
