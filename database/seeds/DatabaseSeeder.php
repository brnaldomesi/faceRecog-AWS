<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
          'name' => 'org1',
          'contactName' => '',
          'contactEmail' => '',
          'contactPhone' => ''
      ]);
      DB::table('organizations')->insert([
          'name' => 'org2',
          'contactName' => '',
          'contactEmail' => '',
          'contactPhone' => ''
      ]);
      DB::table('stats')->insert([
          'organizationId' => 1,
          'searches' => 0
      ]);
      DB::table('stats')->insert([
          'organizationId' => 2,
          'searches' => 0
      ]);
        // $this->call(UsersTableSeeder::class);
    }
}
