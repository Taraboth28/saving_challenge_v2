/**
 * Main navigation. `match` is the route-name prefix that marks the link active.
 */
export const navigation = [
    { label: 'Dashboard', to: { name: 'dashboard' }, icon: 'dashboard', match: 'dashboard' },
    { label: 'Goals', to: { name: 'goals.index' }, icon: 'target', match: 'goals.' },
    { label: 'Reports', to: { name: 'reports' }, icon: 'report', match: 'reports' },
];
