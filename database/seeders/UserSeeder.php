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
        $masterUser = MasterUser::first();
        if(\DB::table(env('KPMG_MASTER_DB_DATABASE').'.modules')->count()<1)
        {
            \DB::table(env('KPMG_MASTER_DB_DATABASE').'.modules')->delete();
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
        }

        $adminUser = new User();
        $adminUser->id                      = $masterUser->id;
        $adminUser->role_id                 = '1';
        $adminUser->name                    = 'admin';
        $adminUser->email                   = 'admin@gmail.com';
        $adminUser->password                = \Hash::make(12345678);
        $adminUser->save();
        $admin = $adminUser;

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



        $adminPermissions = [
            'action-items-add',
            'action-items-browse',
            'action-items-delete',
            'action-items-edit',
            'action-items-read',
            'all-meeting-browse',
            'dashboard-browse',
            'logs-browse',
            'meeting-add',
            'meeting-browse',
            'meeting-delete',
            'meeting-edit',
            'meeting-read',
            'notes-add',
            'notes-browse',
            'notes-delete',
            'notes-edit',
            'notes-read',
            'notifications-add',
            'notifications-browse',
            'notifications-delete',
            'notifications-edit',
            'notifications-read',
            'role-add',
            'role-browse',
            'role-delete',
            'role-edit',
            'role-read',
            'user-add',
            'user-browse',
            'user-delete',
            'user-edit',
            'user-read',
        ];
        foreach ($adminPermissions as $key => $permission) {
            $adminRole->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }


        $userPermissions = [
            'user-browse',
            'meeting-browse',
            'meeting-add',
            'meeting-read',
            'meeting-edit',
            'notes-browse',
            'notes-add',
            'notes-read',
            'action-items-browse',
            'action-items-add',
            'action-items-read',
            'action-items-edit'
        ];
        foreach ($userPermissions as $key => $permission) {
            $userRole->givePermissionTo($permission);
        }


    }
}
