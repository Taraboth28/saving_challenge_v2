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

/** "1 goal", "3 goals". */
export function pluralize(count, singular, plural = `${singular}s`) {
    return `${count} ${count === 1 ? singular : plural}`;
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

/** Local calendar date as YYYY-MM-DD (no UTC shift). */
export function toIsoDate(date) {
    return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
}

export function todayIso() {
    return toIsoDate(new Date());
}

export function describeDaysLeft(goal) {
    if (goal.status === 'completed') {
        return goal.completed_at ? `Completed ${formatDate(goal.completed_at)}` : 'Completed';
    }

    if (goal.days_left === null) {
        return 'No target date';
    }

    if (goal.days_left < 0) {
        return `${pluralize(Math.abs(goal.days_left), 'day')} overdue`;
    }

    return goal.days_left === 0 ? 'Due today' : `${pluralize(goal.days_left, 'day')} left`;
}
