<?php

namespace Tests\Unit;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\MasterUser;

class AuthControllerTest extends TestCase
{
    // use RefreshDatabase;

    public function test_login()
    {

        // $masterUser = new MasterUser;
        // $masterUser->name = 'testftgjg';
        // $masterUser->email = 'testftgjg@gmail.com';
        // $masterUser->password = bcrypt('12345678');
        // $masterUser->save();

        // $user = new User;
        // $user->name = 'testftgjg';
        // $user->email = 'testftgjg@gmail.com';
        // $user->password = bcrypt('12345678');
        // $user->id = $masterUser->id;
        // $user->role_id = '2';
        // $user->save();
        // $this->assertNotEmpty($user);
        $response = $this->call('POST', 'api/login', [
            // 'email' => $user->email,
            'email' => 'admin@gmail.com',
            'password' => 'Khushboo#12345678', 
            'logout_from_all_devices'=>'yes'
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_otp_verify()
    {
        $data = $this->call('POST', 'api/verify-otp', [
            // 'email' => $user->email,
            'email' => 'admin@gmail.com',
            'otp' => '736878'
        ]);
        $this->assertEquals(200, $data->getStatusCode());
        $this->assertContains('admin@gmail.com', $data['data']);
    }

    public function test_forgot_password()
    {
        $data = $this->call('POST', 'api/forgot-password', [
            'email' => 'admin@gmail.com',
        ]);
        $this->assertEquals(200, $data->getStatusCode());
    }

    
}
