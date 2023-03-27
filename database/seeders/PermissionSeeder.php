<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

      
      app()['cache']->forget('spatie.permission.cache');
       // create roles and assign existing permissions
      Permission::create(['name' => 'user-browse', 'guard_name' => 'api','group_name'=>'user','se_name'=>'user-browse','belongs_to'=>'1']);

      Permission::create(['name' => 'user-read', 'guard_name' => 'api','group_name'=>'user','se_name'=>'user-read','belongs_to'=>'1']);

      Permission::create(['name' => 'user-add', 'guard_name' => 'api','group_name'=>'user','se_name'=>'user-create','belongs_to'=>'1']);

      Permission::create(['name' => 'user-edit', 'guard_name' => 'api','group_name'=>'user','se_name'=>'user-edit','belongs_to'=>'1']);

      Permission::create(['name' => 'user-delete', 'guard_name' => 'api','group_name'=>'user','se_name'=>'user-delete','belongs_to'=>'1']);

      Permission::create(['name' => 'role-browse', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-browse','belongs_to'=>'1']);

      Permission::create(['name' => 'role-read', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-read','belongs_to'=>'1']);

      Permission::create(['name' => 'role-add', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-add','belongs_to'=>'3']);

      Permission::create(['name' => 'role-edit', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-edit','belongs_to'=>'1']);

      Permission::create(['name' => 'role-delete', 'guard_name' => 'api','group_name'=>'role','se_name'=>'role-delete','belongs_to'=>'1']);


      Permission::create(['name' => 'dashboard-browse', 'guard_name' => 'api','group_name'=>'dashboard','se_name'=>'dashboard-browse','belongs_to'=>'3']);


      Permission::create(['name' => 'notifications-browse', 'guard_name' => 'api','group_name'=>'notifications','se_name'=>'notifications-browse','belongs_to'=>'3']);

      Permission::create(['name' => 'notifications-add', 'guard_name' => 'api','group_name'=>'notifications','se_name'=>'notifications-add','belongs_to'=>'3']);

      Permission::create(['name' => 'notifications-edit', 'guard_name' => 'api','group_name'=>'notifications','se_name'=>'notifications-edit','belongs_to'=>'3']);

      Permission::create(['name' => 'notifications-delete', 'guard_name' => 'api','group_name'=>'notifications','se_name'=>'notifications-delete','belongs_to'=>'3']);


      Permission::create(['name' => 'all-meeting-browse', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'all-meeting-browse','belongs_to'=>'3']);
      
      Permission::create(['name' => 'meeting-browse', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'meeting-browse','belongs_to'=>'3']);

      Permission::create(['name' => 'meeting-add', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'meeting-add','belongs_to'=>'3']);
      Permission::create(['name' => 'meeting-read', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'meeting-read','belongs_to'=>'3']);

      Permission::create(['name' => 'meeting-edit', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'meeting-edit','belongs_to'=>'3']);
        
      Permission::create(['name' => 'meeting-delete', 'guard_name' => 'api','group_name'=>'meeting','se_name'=>'meeting-delete','belongs_to'=>'3']);


      Permission::create(['name' => 'notes-browse', 'guard_name' => 'api','group_name'=>'notes','se_name'=>'notes-browse','belongs_to'=>'3']);

      Permission::create(['name' => 'notes-add', 'guard_name' => 'api','group_name'=>'notes','se_name'=>'notes-add','belongs_to'=>'3']);

      Permission::create(['name' => 'notes-read', 'guard_name' => 'api','group_name'=>'notes','se_name'=>'notes-read','belongs_to'=>'3']);

      Permission::create(['name' => 'notes-edit', 'guard_name' => 'api','group_name'=>'notes','se_name'=>'notes-edit','belongs_to'=>'3']);
        
      Permission::create(['name' => 'notes-delete', 'guard_name' => 'api','group_name'=>'notes','se_name'=>'notes-delete','belongs_to'=>'3']);

      

      Permission::create(['name' => 'action-items-browse', 'guard_name' => 'api','group_name'=>'action-items','se_name'=>'action-items-browse','belongs_to'=>'3']);

      Permission::create(['name' => 'action-items-add', 'guard_name' => 'api','group_name'=>'action-items','se_name'=>'action-items-add','belongs_to'=>'3']);
      Permission::create(['name' => 'action-items-read', 'guard_name' => 'api','group_name'=>'action-items','se_name'=>'action-items-read','belongs_to'=>'3']);

      Permission::create(['name' => 'action-items-edit', 'guard_name' => 'api','group_name'=>'action-items','se_name'=>'action-items-edit','belongs_to'=>'3']);
        
      Permission::create(['name' => 'action-items-delete', 'guard_name' => 'api','group_name'=>'action-items','se_name'=>'categories-delete','belongs_to'=>'3']);

      Permission::create(['name' => 'logs-browse', 'guard_name' => 'api','group_name'=>'logs','se_name'=>'logs-browse','belongs_to'=>'1']);

       

    }
}
