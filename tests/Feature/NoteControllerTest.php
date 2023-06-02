<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\MeetingNote;
use Str;
use App\Models\Attendee;
use App\Models\Meeting;
use App\Models\User;
use Session;

class NoteControllerTest extends TestCase
{
    public function test_notes()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $payload = [
            'per_page_record' => 10
        ];

        $response = $this->json('POST', route('notes'), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_create_note()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $name = Str::random(10);
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
            'notes' => '<p>note with doc</p>\n',
            'duration' => 12,
            'documents' => [
                [
                    'file'=> 'https://meeting-api.gofactz.com/public/uploads/1678511389-28923.png',
                    'file_name'=> 'https://meeting-api.gofactz.com/public/uploads/1678511389-28923.png',
                    'file_extension'=> 'png',
                    'uploading_file_name'=> 'Day-Switgfch-Dkjat9rk-Mode-Button-Night-Switch-Light-Mode-63796365.png'
                ]
            ],
            'decision' => '<p>desc</p>\n',
            'meeting_id' => $meeting->id
        ];

        $response = $this->json('POST', route('note.store'), $payload, $headers);

        $response->assertStatus(201);
    }

    public function test_show_note()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastNote = \DB::table('meeting_notes')
            ->select('id')
            ->orderBy('id', 'DESC')
            ->first();

        $response = $this->json('GET', route('note.show', [$lastNote->id]), $headers);

        $response->assertStatus(200);
    }

    public function test_update_note()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastNote = MeetingNote::orderBy('id', 'DESC')
            ->first();

        $payload = [
            'notes' => $lastNote->notes.'-update',
            'duration' => 12,
            'documents' => [
                [
                    'file'=> 'https://meeting-api.gofactz.com/public/uploads/1678511389-28923.png',
                    'file_name'=> 'https://meeting-api.gofactz.com/public/uploads/1678511389-28923.png',
                    'file_extension'=> 'png',
                    'uploading_file_name'=> 'Day-Switgfch-Dkjat9rk-Mode-Button-Night-Switch-Light-Mode-63796365.png'
                ]
            ],
            'decision' => '<p>desc</p>\n',
            'meeting_id' => $lastNote->meeting_id
        ];

        $response = $this->json('PUT', route('note.update', [$lastNote->id]), $payload, $headers);

        $response->assertStatus(200);
    }

    public function test_delete_note()
    {
        $this->setupUser();

        $headers = [ 
            'Accept' => 'application/json',
            'Authorization' => 'Bearer $this->token'
        ];

        $lastNote = \DB::table('meeting_notes')
            ->select('id')
            ->orderBy('id', 'DESC')
            ->first();

        $response = $this->json('DELETE', route('note.destroy', [$lastNote->id]), $headers);

        $response->assertStatus(200);
    }
}
