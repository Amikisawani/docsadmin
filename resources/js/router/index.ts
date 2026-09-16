import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/access-denied',
        name: 'AccessDenied',
        component: () => import('../pages/errors/AccessDenied.vue'),
        meta: { requiresAuth: true, title: 'Accès refusé' },
    },
    {
        path: '/',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: () => import('../pages/dashboard/DashboardPage.vue'),
                meta: { title: 'Tableau de bord', icon: 'pi-home' },
            },
            {
                path: 'documents',
                name: 'Documents',
                component: () => import('../pages/documents/DocumentList.vue'),
                meta: { title: 'Documents', icon: 'pi-file' },
            },
            {
                path: 'documents/create',
                name: 'DocumentCreate',
                component: () => import('../pages/documents/DocumentForm.vue'),
                meta: { title: 'Nouveau document', icon: 'pi-plus' },
            },
            {
                path: 'documents/:id',
                name: 'DocumentShow',
                component: () => import('../pages/documents/DocumentShow.vue'),
                meta: { title: 'Détail du document' },
            },
            {
                path: 'documents/:id/edit',
                name: 'DocumentEdit',
                component: () => import('../pages/documents/DocumentForm.vue'),
                meta: { title: 'Modifier le document' },
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('../pages/users/UserList.vue'),
                meta: { title: 'Utilisateurs', icon: 'pi-users', roles: ['admin'] },
            },
            {
                path: 'users/create',
                name: 'UserCreate',
                component: () => import('../pages/users/UserForm.vue'),
                meta: { title: 'Nouvel utilisateur' },
            },
            {
                path: 'users/:id/edit',
                name: 'UserEdit',
                component: () => import('../pages/users/UserForm.vue'),
                meta: { title: 'Modifier l\'utilisateur' },
            },
            {
                path: 'departments',
                name: 'Departments',
                component: () => import('../pages/departments/DepartmentTree.vue'),
                meta: { title: 'Organigramme', icon: 'pi-sitemap' },
            },
            {
                path: 'mail-merge',
                name: 'MailMerge',
                component: () => import('../pages/mailmerge/MailMergeList.vue'),
                meta: { title: 'Publipostage', icon: 'pi-send' },
            },
            {
                path: 'workflows',
                name: 'Workflows',
                component: () => import('../pages/workflows/WorkflowList.vue'),
                meta: { title: 'Workflows', icon: 'pi-sync' },
            },
            {
                path: 'signatures',
                name: 'Signatures',
                component: () => import('../pages/signatures/SignatureList.vue'),
                meta: { title: 'Signatures', icon: 'pi-pencil' },
            },
            {
                path: 'archives',
                name: 'Archives',
                component: () => import('../pages/archives/ArchiveList.vue'),
                meta: { title: 'Archives', icon: 'pi-box' },
            },
            {
                path: 'audit',
                name: 'Audit',
                component: () => import('../pages/audit/AuditLog.vue'),
                meta: { title: 'Audit', icon: 'pi-history', roles: ['admin', 'auditeur'] },
            },
{
                path: 'tasks',
                name: 'MyTasks',
                component: () => import('../pages/tasks/MyTasks.vue'),
                meta: { title: 'Mes tâches', icon: 'pi-clipboard' },
            },
            // ---- Espace Directeur de Cabinet (boîte de réception de validation) ----
            {
                path: 'director',
                name: 'DirectorDashboard',
                component: () => import('../pages/director/DirectorDashboard.vue'),
                meta: { title: 'Validation des documents', icon: 'pi-inbox', roles: ['directeur_cabinet'] },
            },
{
                path: 'director/documents/:id',
                name: 'DirectorDocumentReview',
                component: () => import('../pages/director/DirectorDocumentReview.vue'),
                meta: { title: 'Examen du document', roles: ['directeur_cabinet'] },
            },
            {
                path: 'director/campaigns/:id',
                name: 'DirectorCampaignReview',
                component: () => import('../pages/director/DirectorDocumentReview.vue'),
                props: { mode: 'campaign' },
                meta: { title: 'Signer la campagne', roles: ['directeur_cabinet'] },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

function isDirectorAllowedPath(path: string): boolean {
    const allowed = ['/director', '/signatures', '/archives', '/tasks'];
    if (allowed.some((prefix) => path === prefix || path.startsWith(`${prefix}/`))) {
        return true;
    }

    // Fiche document ouverte depuis Archives / Mes tâches (pas la liste ni la création).
    return /^\/documents\/[^/]+$/.test(path);
}

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next('/login');
    }

    if (to.name === 'Login' && authStore.isAuthenticated) {
        return next(authStore.hasRole('directeur_cabinet') ? '/director' : '/');
    }

    // Le Directeur de Cabinet reste sur son espace, plus signatures / archives / tâches.
    if (
        authStore.isAuthenticated &&
        authStore.hasRole('directeur_cabinet') &&
        !isDirectorAllowedPath(to.path) &&
        to.name !== 'Login' &&
        to.name !== 'AccessDenied'
    ) {
        return next('/director');
    }

    // Vérifier les rôles requis
    const requiredRoles = to.meta.roles as string[] | undefined;
    if (requiredRoles && requiredRoles.length > 0) {
        const hasRequiredRole = requiredRoles.some(role => authStore.hasRole(role));
        if (!hasRequiredRole) {
            return next('/');
        }
    }

    next();
});

export default router;