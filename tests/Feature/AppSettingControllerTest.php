<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Str;
use Session;

class AppSettingControllerTest extends TestCase
{
    public function test_appSettings()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $payload = [
            
        ];

        $response = $this->json('GET', route('app-setting'), $payload, $headers);

        $response->assertStatus(200);
    }

    
    

    public function test_update_appSetting()
    {
        $this->setupAdmin();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastUser = User::orderBy('id', 'DESC')
            ->first();

        $payload = [
            "app_name" => "Meeting App",
            "description" => "Meeting App",
            "app_logo" => "1",
            "email" => "admin@gmail.com",
            "mobile_no" => "8103844000",
            "address" => "Test",
            "log_expiry_days" => "30"
        ];

        $response = $this->json('POST', route('update-setting'), $payload, $headers);

        $response->assertStatus(200);
    }
}
