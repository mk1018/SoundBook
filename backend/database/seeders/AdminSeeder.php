<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->add('admin1', 'admin1@example.com', 1, 'password');
    }

    private function add($name, $email, $role, $password)
    {
        (new Admin([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => bcrypt($password),
        ]))->save();
    }
}
