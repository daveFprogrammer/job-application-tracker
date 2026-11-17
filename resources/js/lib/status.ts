import type { ApplicationStatus } from '@/types';

/**
 * Accent colour of each kanban column.
 */
export const statusAccent: Record<ApplicationStatus, string> = {
    wishlist: 'bg-slate-400',
    applied: 'bg-sky-500',
    interviewing: 'bg-amber-500',
    offer: 'bg-emerald-500',
    rejected: 'bg-rose-500',
};
