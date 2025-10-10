<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        DB::table('users')->insert([
            [
                'name' => 'user1',
                'email' => 'user1@example.com',
                'email_verified_at' => $now,
                'password' => Hash::make('11111111'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'user2',
                'email' => 'user2@example.com',
                'email_verified_at' => $now,
                'password' => Hash::make('11111111'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'user3',
                'email' => 'user3@example.com',
                'email_verified_at' => $now,
                'password' => Hash::make('11111111'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
