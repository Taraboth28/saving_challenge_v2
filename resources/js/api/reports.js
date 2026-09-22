import { http } from './client';

/** Every report accepts the same filters: from, to (YYYY-MM-DD), goal_id. */
export const reportsApi = {
    history: (filters) => http.get('/reports/history', filters).then((response) => response.data),
    goals: (filters) => http.get('/reports/goals', filters).then((response) => response.data),
    monthly: (filters) => http.get('/reports/monthly', filters).then((response) => response.data),
    status: (filters) => http.get('/reports/status', filters).then((response) => response.data),
};
