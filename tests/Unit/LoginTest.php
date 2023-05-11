<?php

namespace Tests\Unit;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\MasterUser;

class LoginTest extends TestCase
{
	// use RefreshDatabase;

	public function test_login_functions()
	{

		// $masterUser = new MasterUser;
		// $masterUser->name = 'test';
		// $masterUser->email = 'test@gmail.com';
		// $masterUser->password = bcrypt('12345678');
		// $masterUser->save();

		// $user = new User;
		// $user->name = 'test';
		// $user->email = 'test@gmail.com';
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

	public function test_verify_otp()
	{
			$data = $this->call('POST', 'api/verify-otp', [
		        // 'email' => $user->email,
		        'email' => 'admin@gmail.com',
		        'otp' => '736878'
		    ]);
		    $this->assertEquals(200, $data->getStatusCode());
		    $this->assertContains('admin@gmail.com', $data['data']);
	}
}
