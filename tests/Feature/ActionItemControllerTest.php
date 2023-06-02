<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ActionItem;
use App\Models\Attendee;
use App\Models\Meeting;
use App\Models\User;
use Str;
use Session;

class ActionItemControllerTest extends TestCase
{
    public function test_action_items()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $payload = [
            'per_page_record' => 10
        ];

        $response = $this->json('POST', route('action-items'), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_create_actionItem()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $name = Str::random(10);
        $description = 'test desc';

        //create Meeting
        $rand = strtoupper(Str::random(2)).rand(10000000,99999999);
        $meeting = new Meeting;
        $meeting->meetRandomId = generateRandomString(14);
        $meeting->meeting_title = $name;
        $meeting->meeting_ref_no = $rand;
        $meeting->agenda_of_meeting  = $name;
        $meeting->meeting_date = date('Y-m-d');
        $meeting->meeting_time_start = date('Y-m-d H:i');
        $meeting->meeting_time_end = date('Y-m-d H:i');
        $meeting->meeting_link = '';
        $meeting->organised_by = auth()->id();
        $meeting->is_repeat = 0;
        $meeting->status = 1;
        $meeting->save();
        /*------------Attendees---------------------*/
        /*---------Add User---------------------*/
        $userInfo = addUser($name.'@meeting.com');
        $user_id = $userInfo->id;
        $name = $userInfo->name;

        $attende = new Attendee;
        $attende->meeting_id = $meeting->id;
        $attende->user_id = $user_id;
        $attende->save();
        


        $payload = [
            "meeting_id" => $meeting->id,
            "note_id" => "",
            "owner_id" => auth()->id(),
            "date_opened" => date('Y-m-d'),
            "task" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum",
            "priority"=>"Low",
            "due_date"=>"2023-02-15",
            "complete_percentage"=>"0",
            "image"=>"http://localhost:8000/uploads/uploads/1676106286-94986.docx",
            "comment" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum",
            "documents"=> [
                [
                    "file"=> "http://localhost:8000/uploads/uploads/1676106286-94986.docx",
                    "file_extension"=>"",
                    "file_name"=>"",
                    "uploading_file_name"=>""
                ]
            ]
        ];

        $response = $this->json('POST', route('action-item.store'), $payload, $headers);

        $response->assertStatus(201);
    }

    public function test_show_actionItem()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastActionItem = \DB::table('action_items')
        ->select('id')
        ->orderBy('id', 'DESC')
        ->first();

        $response = $this->json('GET', route('action-item.show', [$lastActionItem->id]), $headers);

        $response->assertStatus(200);
    }

    public function test_update_actionItem()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastActionItem = ActionItem::orderBy('id', 'DESC')
        ->first();

        $payload = [
            "meeting_id" => $lastActionItem->meeting_id,
            "note_id" => "",
            "owner_id" => auth()->id(),
            "date_opened" => date('Y-m-d'),
            "task" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum",
            "priority"=>"Low",
            "due_date"=>"2023-02-15",
            "complete_percentage"=>"0",
            "image"=>"http://localhost:8000/uploads/uploads/1676106286-94986.docx",
            "comment" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum",
            "documents"=> [
                [
                    "file"=> "http://localhost:8000/uploads/uploads/1676106286-94986.docx",
                    "file_extension"=>"",
                    "file_name"=>"",
                    "uploading_file_name"=>""
                ]
            ]
        ];

        $response = $this->json('PUT', route('action-item.update', [$lastActionItem->id]), $payload, $headers);

        $response->assertStatus(200);
    }
}
