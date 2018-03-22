<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        {
            $now = \Carbon\Carbon::now();
            \Illuminate\Support\Facades\DB::table('roles')->insert([
                [
                    'name' => 'Super Admin',
                    'slug' => 'superadmin',
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                [
                    'name' => 'Moderator',
                    'slug' => 'moderator',
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                [
                    'name' => 'Seller',
                    'slug' => 'seller',
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                [
                    'name' => 'Customer',
                    'slug' => 'customer',
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            ]);
        }
    }
}
