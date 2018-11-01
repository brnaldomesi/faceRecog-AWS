<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
    	DB::table('organizations')->insert([
          'name' => 'Organization 1',
          'account' => 'org1',
		      'contactName' => '',
          'contactEmail' => '',
          'contactPhone' => ''
        ]);

      DB::table('permissions')->insert([[
          'can_edit_all_users' => 1,
          'can_manage_organization_agreements' => 1,
          'can_view_logs' => 0,
          'can_create_case' => 1,
          'can_edit_case' => 1,
          'can_view_case' => 1
      ], [
        'can_edit_all_users' => 0,
        'can_manage_organization_agreements' => 0,
        'can_view_logs' => 0,
        'can_create_case' => 1,
        'can_edit_case' => 1,
        'can_view_case' => 1
      ]]);

      DB::table('user_groups')->insert([[
          'name' => 'Admin',
          'permissionId' => 1,
        ], [
          'name' => 'User',
          'permissionId' => 2,
      ]]);

      DB::table('stats')->insert([[
          'organizationId' => 1,
          'searches' => 0
      ]]);

      DB::table('users')->insert([
          'name' => 'master',
          'email' => 'master@gmail.com',
          'organizationId' => 1,
          'userGroupId' => 1,
          'password' => Hash::make('master'),
          'loginCount' => 0
      ]);
        // $this->call(UsersTableSeeder::class);
    }
}
