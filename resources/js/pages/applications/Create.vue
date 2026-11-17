<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import JobApplicationController from '@/actions/App/Http/Controllers/JobApplicationController';
import ApplicationForm from '@/components/applications/ApplicationForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/applications';
import type { FormOptions } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: index() },
            { title: 'New application', href: create() },
        ],
    },
});

defineProps<FormOptions & { defaultStatus: string }>();
</script>

<template>
    <Head title="New application" />

    <div class="p-4 md:p-6">
        <Heading
            title="New application"
            description="Track a position you are interested in or already applied to"
        />

        <ApplicationForm
            :action="JobApplicationController.store.form()"
            :cancel-href="index().url"
            submit-label="Add application"
            :statuses="statuses"
            :work-modes="workModes"
            :default-status="defaultStatus"
        />
    </div>
</template>
