import { http } from './client';

export const goalsApi = {
    list: (filters = {}) => http.get('/goals', filters).then((response) => response.data),

    /** Resolves to { goal, transactions }. */
    find: (id) =>
        http.get(`/goals/${id}`).then((response) => ({
            goal: response.data,
            transactions: response.transactions,
        })),

    create: (attributes) => http.post('/goals', attributes).then((response) => response.data),
    update: (id, attributes) => http.put(`/goals/${id}`, attributes).then((response) => response.data),
    remove: (id) => http.delete(`/goals/${id}`),
};
