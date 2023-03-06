<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\Attendee;
use Mail;
use App\Mail\scheduleMeetingMail;
use Illuminate\Support\Carbon;
class ScheduleMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:meeting-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       $currentNow = Carbon::now()->format("Y-m-d");
       $getAllmeetingAttendees = Attendee::join('meetings','meetings.id','attendees.meeting_id')->whereDate('meetings.meeting_date',$currentNow)->orderby('meetings.id','ASC')->with('user:id,name,email')->get();

        echo Carbon::now()->format('Y-m-d H:i:s') . '------Reminder  Start------';
        foreach ($getAllmeetingAttendees as $key => $meeting) {
            $meeting_time = Carbon::createFromFormat('H:i:s',$meeting->meeting_time);
            $current_time =  Carbon::createFromFormat('H:i:s',date('H:i:s'));
            $diff_in_minutes = $meeting_time->diffInMinutes($current_time);
            if($diff_in_minutes=='15'){
                $content = [
                    "name" => @$meeting->user->name,
                    "meeting_title" => @$meeting->meeting_title,
                    "meeting_date" => @$meeting->meeting_date,
                    "meeting_time" => @$meeting->meeting_time,
                    "agenda_of_meeting" => @$meeting->agenda_of_meeting,
               
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {
                   
                    $recevier = Mail::to(@$meeting->user->email)->send(new scheduleMeetingMail($content));
                }

            }

           
        }
         echo Carbon::now()->format('Y-m-d H:i:s') . '------Reminder  end------';
    }
}
