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
		  'contactName' => 'Contact Person',
          'contactEmail' => 'test@email.com',
          'contactPhone' => '555-555-5555',
		  'aws_collection_male_id' => '',
		  'aws_collection_female_id' => '',
		  'aws_collection_cases_id' => ''
        ]);

      DB::table('permissions')->insert([[
          'can_edit_all_users' => 1,
          'can_manage_organization_agreements' => 1,
          'can_view_logs' => 0,
          'can_create_case' => 1,
          'can_edit_case' => 1,
          'can_view_case' => 1,
		  'can_manage_organization' => 0
      ], [
        'can_edit_all_users' => 0,
        'can_manage_organization_agreements' => 0,
        'can_view_logs' => 0,
        'can_create_case' => 1,
        'can_edit_case' => 1,
        'can_view_case' => 1,
		'can_manage_organization' => 0
      ], [
        'can_edit_all_users' => 0,
        'can_manage_organization_agreements' => 0,
        'can_view_logs' => 0,
        'can_create_case' => 0,
        'can_edit_case' => 0,
        'can_view_case' => 0,
        'can_manage_organization' => 1
      ]]);

      DB::table('user_groups')->insert([[
          'name' => 'Admin',
          'permissionId' => 1,
        ], [
          'name' => 'Default',
          'permissionId' => 2,
        ],[
          'name' => 'Super Admin',
          'permissionId' => 3,
      ]]);

      DB::table('stats')->insert([[
          'organizationId' => 1,
          'searches' => 0
      ]]);

      DB::table('users')->insert([
          'name' => 'Super Admin',
          'email' => 'superadmin@afrengine.com',
          'organizationId' => 0,
          'userGroupId' => 3, // Super Admin
          'password' => Hash::make('master'),
          'loginCount' => 0
      ]);
	  
      DB::table('users')->insert([
          'name' => 'Org1 Admin',
          'email' => 'org1@afrengine.com',
          'organizationId' => 1,
          'userGroupId' => 1, // Super Admin
          'password' => Hash::make('master'),
          'loginCount' => 0
      ]);	  
        // $this->call(UsersTableSeeder::class);
    }
}
