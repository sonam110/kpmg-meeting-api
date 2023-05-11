<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Str;
use Session;

class UserControllerTest extends TestCase
{
    public function test_users()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $payload = [
            'per_page_record' => 10
        ];

        $response = $this->json('POST', route('users'), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_create_user()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $name = Str::random(10);
        $payload = [
            'role_id' => "2",
            'name' => strtolower($name),
            'email' => $name.'@nrt.co.in',
            'username' => $name,
            'password' => bcrypt('password')
        ];

        $response = $this->json('POST', route('user.store'), $payload, $headers);

        $response->assertStatus(201);
    }

    public function test_show_user()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastUser = \DB::table('users')
            ->select('id')
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->first();

        $response = $this->json('GET', route('user.show', [$lastUser->id]), $headers);

        $response->assertStatus(200);
    }

    public function test_update_user()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastUser = User::orderBy('id', 'DESC')
            ->first();

        $payload = [
            'role_id' => "2",
            'name' => $lastUser->name.'-update',
            'email' => $lastUser->email,
            'mobile' => rand(9000000000, 9999999999),
            'address' => 'bhopal'
        ];

        $response = $this->json('PUT', route('user.update', [$lastUser->id]), $payload, $headers);

        $response->assertStatus(200);
    }

    // public function test_delete_user()
    // {
    //     $this->setupAdmin();

    //     $headers = [ 
    //         'Accept' => 'application/json',
    //         'Authorization' => 'Bearer $this->token'
    //     ];

    //     $lastUser = \DB::table('users')
    //         ->select('id')
    //         ->whereNull('deleted_at')
    //         ->orderBy('id', 'DESC')
    //         ->first();

    //     $response = $this->json('DELETE', route('user.destroy', [$lastUser->id]), $headers);

    //     $response->assertStatus(200);
    // }
}
