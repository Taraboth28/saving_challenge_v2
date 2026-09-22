import { http } from './client';

export const transactionsApi = {
    /** Filters: from, to (YYYY-MM-DD), goal_id, type. */
    list: (filters = {}) => http.get('/transactions', filters).then((response) => response.data),
    create: (attributes) => http.post('/transactions', attributes).then((response) => response.data),
    update: (id, attributes) => http.put(`/transactions/${id}`, attributes).then((response) => response.data),
    remove: (id) => http.delete(`/transactions/${id}`),
};
