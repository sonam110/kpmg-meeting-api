<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Meeting;
use Str;
use Session;

class MeetingControllerTest extends TestCase
{
    public function test_meetings()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $payload = [
            'per_page_record' => 10
        ];

        $response = $this->json('POST', route('meetings'), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_create_meeting()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $name = Str::random(10);
        $payload = [
            'meeting_title' =>'Team meeting Project discussion',
            'meeting_date' =>'2023-04-13',
            'meeting_time_start' =>'15:10',
            'meeting_time_end' =>'17:10',
            'meeting_ref_no' =>'zyrsts28965ui',
            'meeting_link' =>'http::/fdfdgfd.fgfd',
            'agenda_of_meeting' =>'Team meeting Project',
            'is_repeat' =>'0',
            'attendees'=> [
                [
                    'email'=>'abc@gmail.com'
                ]
            ],
            'documents'=> [
                [
                    'file'=> 'http://localhost:8000/uploads/uploads/1676106286-94986.docx',
                    'file_extension'=>'',
                    'file_name'=>'',
                    'uploading_file_name'=>''
                ]
            ]
        ];

        $response = $this->json('POST', route('meeting.store'), $payload, $headers);

        $response->assertStatus(201);
    }

    public function test_show_meeting()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastMeeting = \DB::table('meetings')
            ->select('id')
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->first();

        $response = $this->json('GET', route('meeting.show', [$lastMeeting->id]), $headers);

        $response->assertStatus(200);
    }

    public function test_update_meeting()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastMeeting = Meeting::orderBy('id', 'DESC')
            ->first();

        $payload = [
            'meeting_title' =>$lastMeeting->meeting_title.'-update',
            'meeting_date' =>'2023-04-13',
            'meeting_time_start' =>'15:10',
            'meeting_time_end' =>'17:10',
            'meeting_ref_no' =>'zyrsts28965ui',
            'meeting_link' =>'http::/fdfdgfd.fgfd',
            'agenda_of_meeting' =>'Team meeting Project',
            'is_repeat' =>'0',
            'attendees'=> [
                [
                    'email'=>'abc@gmail.com'
                ]
            ],
            'documents'=> [
                [
                    'file'=> 'http://localhost:8000/uploads/uploads/1676106286-94986.docx',
                    'file_extension'=>'',
                    'file_name'=>'',
                    'uploading_file_name'=>''
                ]
            ]
        ];

        $response = $this->json('PUT', route('meeting.update', [$lastMeeting->id]), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_delete_meeting()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastMeeting = \DB::table('meetings')
            ->select('id')
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->first();

        $response = $this->json('DELETE', route('meeting.destroy', [$lastMeeting->id]), $headers);

        $response->assertStatus(200);
    }
}
