export type ApplicationStatus =
    | 'wishlist'
    | 'applied'
    | 'interviewing'
    | 'offer'
    | 'rejected';

export type Option<T extends string = string> = {
    value: T;
    label: string;
};

export type ApplicationSummary = {
    id: number;
    company: string;
    position: string;
};

export type Interview = {
    id: number;
    scheduled_at: string;
    type: string;
    type_label: string;
    location: string | null;
    notes: string | null;
    has_reminder?: boolean;
    application?: ApplicationSummary;
};

export type Note = {
    id: number;
    body: string;
    created_at: string;
};

export type Reminder = {
    id: number;
    remind_at: string;
    message: string;
    interview_id: number | null;
    sent_at: string | null;
    application?: ApplicationSummary;
};

export type JobApplication = {
    id: number;
    company: string;
    position: string;
    job_url: string | null;
    location: string | null;
    work_mode: string | null;
    salary_range: string | null;
    status: ApplicationStatus;
    applied_at: string | null;
    description: string | null;
    next_interview_at?: string | null;
    interviews_count?: number;
    notes_count?: number;
    interviews?: Interview[];
    notes?: Note[];
    reminders?: Reminder[];
    created_at: string;
    updated_at: string;
};

export type FormOptions = {
    statuses: Option<ApplicationStatus>[];
    workModes: Option[];
    interviewTypes: Option[];
};
