<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ==================== PERMISSIONS ====================
        $permissions = [
            // Documents
            'documents.create', 'documents.read', 'documents.update', 'documents.delete',
            'documents.sign', 'documents.archive', 'documents.export',

            // Users
            'users.create', 'users.read', 'users.update', 'users.delete',

            // Departments
            'departments.create', 'departments.read', 'departments.update', 'departments.delete',

            // Templates
            'templates.create', 'templates.read', 'templates.update', 'templates.delete',

            // Workflows
            'workflows.create', 'workflows.read', 'workflows.update', 'workflows.delete',
            'workflows.approve', 'workflows.reject',

            // Signatures
            'signatures.create', 'signatures.read', 'signatures.update', 'signatures.delete',
            'signatures.verify',

            // Archives
            'archives.create', 'archives.read', 'archives.update', 'archives.delete',
            'archives.destroy',

            // Reports
            'reports.read', 'reports.export',

            // Audit
            'audit.read', 'audit.export',

            // Administration
            'admin.access', 'admin.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ==================== ROLES ====================
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        $secretary = Role::firstOrCreate(['name' => 'secretaire', 'guard_name' => 'web']);
        $secretary->givePermissionTo([
            'documents.create', 'documents.read', 'documents.update',
            'users.read',
            'departments.read',
            'templates.read',
            'workflows.read',
            'signatures.read',
            'archives.read',
        ]);

        $director = Role::firstOrCreate(['name' => 'directeur', 'guard_name' => 'web']);
        $director->givePermissionTo([
            'documents.create', 'documents.read', 'documents.update',
            'users.read',
            'departments.read',
            'templates.read',
            'workflows.read', 'workflows.approve', 'workflows.reject',
            'signatures.create', 'signatures.read',
            'archives.read',
            'reports.read',
        ]);

        $chief = Role::firstOrCreate(['name' => 'chef_service', 'guard_name' => 'web']);
        $chief->givePermissionTo([
            'documents.create', 'documents.read', 'documents.update',
            'users.read',
            'departments.read',
            'templates.read',
            'workflows.read', 'workflows.approve', 'workflows.reject',
            'signatures.read',
            'archives.read',
        ]);

        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        $agent->givePermissionTo([
            'documents.create', 'documents.read',
            'users.read',
            'departments.read',
            'templates.read',
            'workflows.read',
            'signatures.read',
            'archives.read',
        ]);

        $auditor = Role::firstOrCreate(['name' => 'auditeur', 'guard_name' => 'web']);
        $auditor->givePermissionTo([
            'documents.read',
            'users.read',
            'departments.read',
            'workflows.read',
            'signatures.read',
            'archives.read',
            'audit.read', 'audit.export',
            'reports.read',
        ]);

        $archivist = Role::firstOrCreate(['name' => 'archiviste', 'guard_name' => 'web']);
        $archivist->givePermissionTo([
            'documents.read',
            'archives.create', 'archives.read', 'archives.update', 'archives.delete', 'archives.destroy',
            'reports.read',
        ]);

        // ==================== ROLES HIÉRARCHIQUES ====================
        // Pyramide administrative de la Présidence.
        // Version Présidence : le Directeur de Cabinet est le SEUL détenteur du pouvoir de signature.
        $hierarchicalRoles = [
            'directeur_cabinet' => 'Directeur de Cabinet',
            'secretaire_general' => 'Secrétaire Général',
            'secretaire_general_adjoint' => 'Secrétaire Général Adjoint',
            'directeur' => 'Directeur',
            'directeur_chef_service' => 'Directeur Chef de Service',
            'chef_division' => 'Chef de Division',
            'chef_bureau' => 'Chef de Bureau',
            'agent_administration' => 'Agent d\'Administration',
            'huissier' => 'Huissier',
        ];

        // Rôle autorisé à signer un document : UNIQUEMENT le Directeur de Cabinet.
        $signingRole = 'directeur_cabinet';

        foreach ($hierarchicalRoles as $roleName => $label) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo([
                'documents.create', 'documents.read', 'documents.update',
                'users.read',
                'departments.read',
                'templates.read',
                'workflows.read',
                'signatures.create', 'signatures.read',
                'archives.read',
                'reports.read',
            ]);
        }

        // Le Directeur de Cabinet reçoit explicitement la permission de signer.
        $directorRole = Role::findByName('directeur_cabinet', 'web');
        $directorRole->givePermissionTo(['documents.sign', 'workflows.approve', 'workflows.reject', 'signatures.create', 'signatures.read']);

        // Retirer la permission de signer des anciens rôles (ils ne signent plus).
        foreach (['admin', 'secretaire_general', 'directeur', 'chef_division', 'chef_bureau'] as $legacyRole) {
            $role = Role::findByName($legacyRole, 'web');
            $role->revokePermissionTo('documents.sign');
        }
    }
}
