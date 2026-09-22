import { http } from './client';

export const dashboardApi = {
    overview: () => http.get('/dashboard').then((response) => response.data),
};
