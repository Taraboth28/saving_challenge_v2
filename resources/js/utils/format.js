import { appConfig } from '../config';

const currencyFormatter = new Intl.NumberFormat(appConfig.locale, {
    style: 'currency',
    currency: appConfig.currency,
});

const compactCurrencyFormatter = new Intl.NumberFormat(appConfig.locale, {
    style: 'currency',
    currency: appConfig.currency,
    notation: 'compact',
    maximumFractionDigits: 1,
});

export function formatCurrency(amount, { compact = false } = {}) {
    return (compact ? compactCurrencyFormatter : currencyFormatter).format(Number(amount) || 0);
}

export function formatPercent(value) {
    return `${Number(value || 0).toLocaleString(appConfig.locale, { maximumFractionDigits: 1 })}%`;
}

/** Parse YYYY-MM-DD as a local date (avoids the UTC shift of new Date('YYYY-MM-DD')). */
export function parseDate(value) {
    const [year, month, day = 1] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function formatDate(value) {
    if (!value) {
        return '—';
    }

    return parseDate(value.slice(0, 10)).toLocaleDateString(appConfig.locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/** "2026-05" → "May 2026" (or "May" when short). */
export function formatMonth(value, { short = false } = {}) {
    return parseDate(value).toLocaleDateString(appConfig.locale, short ? { month: 'short' } : { month: 'long', year: 'numeric' });
}

export function todayIso() {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

export function describeDaysLeft(goal) {
    if (goal.status === 'completed') {
        return goal.completed_at ? `Completed ${formatDate(goal.completed_at)}` : 'Completed';
    }

    if (goal.days_left === null) {
        return 'No target date';
    }

    if (goal.days_left < 0) {
        return `${Math.abs(goal.days_left)} days overdue`;
    }

    return goal.days_left === 0 ? 'Due today' : `${goal.days_left} days left`;
}
