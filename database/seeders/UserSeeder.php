<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\AppSetting;
use App\Models\MasterUser;
use App\Models\Module;
use App\Models\AssigneModule;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        

        Module::truncate();
        $module1 = Module::create([
            'id' => '1',
            'name' => 'MEETING APP',
            
        ]);
        $module2 = Module::create([
            'id' => '2',
            'name' => 'DPR MANAGEMENT',
            
        ]);
        $module3 = Module::create([
            'id' => '3',
            'name' => 'CHECKLIST APP',
            
        ]);

        $masterUser = new MasterUser;
        $masterUser->name = 'admin';
        $masterUser->email  = 'admin@gmail.com';
        $masterUser->password = \Hash::make(12345678);
        $masterUser->save();

        $adminUser = new User();
        $adminUser->id                      = $masterUser->id;
        $adminUser->role_id                 = '1';
        $adminUser->name                    = 'admin';
        $adminUser->email                   = 'admin@gmail.com';
        $adminUser->password                = \Hash::make(12345678);
        $adminUser->save();

        /*-------Assigne Meeting module for this user*/
        $assigneModule = new AssigneModule;
        $assigneModule->module_id  = '1';
        $assigneModule->user_id  = $masterUser->id;
        $assigneModule->save();


        $appSetting = new AppSetting();
        $appSetting->id                      = '1';
        $appSetting->app_name                = 'Meeting App';
        $appSetting->description             = 'Meeting App';
        $appSetting->email                   = 'admin@gmail.com';
        $appSetting->mobile_no               = '45465767';
        $appSetting->save();

        $adminRole = Role::where('id','1')->first();
        $userRole = Role::where('id','2')->first();
        $adminUser->assignRole($adminRole);



        $adminPermissions = Permission::select('id','name')->whereIn('belongs_to',['1','3'])->get();
        foreach ($adminPermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $adminRole->givePermissionTo($addedPermission);
        }


        $userPermissions = Permission::select('id','name')->whereIn('belongs_to',['2','3'])->get();
        foreach ($userPermissions as $key => $permission) {
            $addedPermission = $permission->name;
            $userRole->givePermissionTo($addedPermission);
        }


    }
}
