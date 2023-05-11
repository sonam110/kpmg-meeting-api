<?php

namespace Tests;
use App\Models\User;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Str;

trait UserLoginTrait 
{
    public $token;

    public function setupAdmin()
    {
        /*
        $this->user = User::factory()->create([
            'username' => Str::random(15),
            'password' => bcrypt('password')
        ]);

        $this->user->assignRole('admin');
        $this->user->givePermissionTo(Permission::all());*/

        $this->admin = User::first();
        Passport::actingAs($this->admin);

        //See Below
        $token = $this->admin->createToken('authToken')->accessToken;

    }

    public function setupUser()
    {
        $this->user = User::where('role_id',2)->first();
        Passport::actingAs($this->user);

        //See Below
        $token = $this->user->createToken('authToken')->accessToken;

    }
}
