<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $developer = User::create([
            'name' => 'Christopher Guzman', 
            'email' => 'dev@gmail.com',
            'password' => Hash::make('12345678')
        ]);
        $developer->assignRole('Developer');

        $sysAdmin = User::create([
            'name' => 'System Admin', 
            'email' => 'sysadmin@gmail.com',
            'password' => Hash::make('12345678')
        ]);
        $sysAdmin->assignRole('System Administrator');

        // Creating Admin User
        $custodian = User::create([
            'name' => 'Property Costudian', 
            'email' => 'custodian@gmail.com',
            'password' => Hash::make('12345678')
        ]);
        $custodian->assignRole('Custodian');
    }
}
