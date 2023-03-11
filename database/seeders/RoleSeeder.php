<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use App\Models\UserType;
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*------------Default Role-----------------------------------*/
        \DB::table('roles')->delete();
        $role1 = Role::create([
            'id' => '1',
            'name' => 'Admin',
            'se_name' => 'Admin', 
            'guard_name' => 'api',
            'is_default'=>'0', 
            
        ]);
        $role2 = Role::create([
            'id' => '2',
            'name' => 'User',
            'se_name' => 'User', 
            'guard_name' => 'api',
            'is_default'=>'0', 
            
        ]);
        
        
    }
}
