<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ProgramsTableSeeder::class);
        $this->call(WorkspacesTableSeeder::class);
        $this->call(DesignDiagramsTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);
        $this->call(RoleUserTableSeeder::class);
        $this->call(PermissionRoleTableSeeder::class);
    }
}
