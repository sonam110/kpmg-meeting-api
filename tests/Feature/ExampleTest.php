<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $this->setupAdmin();
        $response = $this->call('POST', 'api/users');

        $response->assertStatus(200);
    }

    // public function test_change_password()
    // {
    //     $this->setupAdmin();
    //     $response = $this->call('POST', 'api/change-password', [
    //         "old_password" => "Khushboo#12345678",
    //         "password" => "Khushboo#87654321"
    //     ]);
    //     $this->assertEquals(200, $response->getStatusCode());
    // }

    // public function test_logout()
    // {
    //     $this->setupAdmin();
    //     $response = $this->call('POST', 'api/logout', [
    //         'email' => 'admin@gmail.com'
    //     ]);
    //     $this->assertEquals(200, $response->getStatusCode());
    // }


}
