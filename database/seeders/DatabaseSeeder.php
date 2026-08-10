<?php

namespace Database\Seeders;

use App\Domains\Departments\Models\Department;
use App\Domains\Users\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // Create default departments (idempotent)
        $presidence = Department::firstOrCreate(
            ['code' => 'PRES'],
            [
                'name' => 'Présidence',
                'type' => 'ministere',
                'description' => 'Présidence de la République',
            ]
        );

        $primature = Department::firstOrCreate(
            ['code' => 'PRIM'],
            [
                'name' => 'Primature',
                'type' => 'ministere',
                'description' => 'Primature',
            ]
        );

        $finances = Department::firstOrCreate(
            ['code' => 'MINFIN'],
            [
                'name' => 'Ministère des Finances',
                'type' => 'ministere',
                'description' => 'Ministère des Finances',
            ]
        );

        $interieur = Department::firstOrCreate(
            ['code' => 'MININT'],
            [
                'name' => 'Ministère de l\'Intérieur',
                'type' => 'ministere',
                'description' => 'Ministère de l\'Intérieur',
            ]
        );

        // Create admin user
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@adminflow.ci',
            'password' => bcrypt('Admin@123456'),
            'department_id' => $presidence->id,
            'job_title' => 'Administrateur Système',
            'phone' => '+225 01 02 03 04 05',
            'is_active' => true,
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // Create secretary
        $secretary = User::create([
            'name' => 'Secrétaire Général',
            'email' => 'secretaire@adminflow.ci',
            'password' => bcrypt('Secret@123456'),
            'department_id' => $primature->id,
            'job_title' => 'Secrétaire Général',
            'phone' => '+225 01 02 03 04 06',
            'is_active' => true,
            'status' => 'active',
        ]);
        $secretary->assignRole('secretaire');

        // Create director
        $director = User::create([
            'name' => 'Directeur Général',
            'email' => 'directeur@adminflow.ci',
            'password' => bcrypt('Direct@123456'),
            'department_id' => $finances->id,
            'job_title' => 'Directeur Général',
            'phone' => '+225 01 02 03 04 07',
            'is_active' => true,
            'status' => 'active',
        ]);
        $director->assignRole('directeur');

        // Create directeur de cabinet (seul détenteur du pouvoir de signature)
        $cabinet = User::firstOrCreate(
            ['email' => 'cabinet@presidence.ci'],
            [
                'name' => 'Directeur de Cabinet',
                'password' => bcrypt('Cabinet@123456'),
                'department_id' => $presidence->id,
                'job_title' => 'Directeur de Cabinet',
                'phone' => '+225 01 02 03 04 10',
                'is_active' => true,
                'status' => 'active',
            ]
        );
        if (! $cabinet->hasRole('directeur_cabinet')) {
            $cabinet->assignRole('directeur_cabinet');
        }

        // Create agent
        $agent = User::create([
            'name' => 'Agent Administratif',
            'email' => 'agent@adminflow.ci',
            'password' => bcrypt('Agent@123456'),
            'department_id' => $interieur->id,
            'job_title' => 'Agent Administratif',
            'phone' => '+225 01 02 03 04 08',
            'is_active' => true,
            'status' => 'active',
        ]);
        $agent->assignRole('agent');

        // ==================== UTILISATEUR DEMANDÉ ====================
        // hami – admin
        $hami = User::firstOrCreate(
            ['email' => 'hami@gmail.com'],
            [
                'name' => 'Hami',
                'password' => bcrypt('hamilton'),
                'department_id' => $presidence->id,
                'job_title' => 'Administrateur',
                'phone' => '+225 01 02 03 04 09',
                'is_active' => true,
                'status' => 'active',
            ]
        );
        if (! $hami->hasRole('admin')) {
            $hami->assignRole('admin');
        }
    }
}
