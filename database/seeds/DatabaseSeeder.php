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
        factory(App\User::class)->create([
            'name' => 'Admin',
            'email' => config('pullkins.UI_email'),
            'password' => bcrypt(config('pullkins.UI_password')), // secret
        ]);
    }
}
