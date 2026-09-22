import { createRouter, createWebHistory } from 'vue-router';
import { appConfig } from '../config';

/**
 * Pages are lazy-loaded so each one becomes its own chunk.
 * Add a new page by adding a route here and a link in components/layout/navigation.js.
 */
const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('../pages/DashboardPage.vue'),
        meta: { title: 'Dashboard' },
    },
    {
        path: '/goals',
        name: 'goals.index',
        component: () => import('../pages/goals/GoalListPage.vue'),
        meta: { title: 'Savings Goals' },
    },
    {
        path: '/goals/new',
        name: 'goals.create',
        component: () => import('../pages/goals/GoalFormPage.vue'),
        meta: { title: 'New Goal' },
    },
    {
        path: '/goals/:id(\\d+)',
        name: 'goals.show',
        component: () => import('../pages/goals/GoalDetailPage.vue'),
        props: (route) => ({ id: Number(route.params.id) }),
        meta: { title: 'Goal Details' },
    },
    {
        path: '/goals/:id(\\d+)/edit',
        name: 'goals.edit',
        component: () => import('../pages/goals/GoalFormPage.vue'),
        props: (route) => ({ id: Number(route.params.id) }),
        meta: { title: 'Edit Goal' },
    },
    {
        path: '/reports',
        name: 'reports',
        component: () => import('../pages/ReportsPage.vue'),
        meta: { title: 'Reports' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../pages/NotFoundPage.vue'),
        meta: { title: 'Page Not Found' },
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} · ${appConfig.name}` : appConfig.name;
});
