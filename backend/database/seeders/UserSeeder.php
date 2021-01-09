<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->add('name1', 'name1@example.com', 'password');
        $this->add('name2', 'name2@example.com', 'password');
        $this->add('name3', 'name3@example.com', 'password');
    }

    private function add($name, $email, $password)
    {
        (new User([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]))->save();
    }
}
