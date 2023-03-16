<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\MeetingMailLog;
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
        $check_time = strtotime(date('H:i:00.000000',strtotime('+20 minutes')));
        $meetings = Meeting::whereDate('meeting_date',date('Y-m-d'))
        ->get();
        foreach ($meetings as $key => $meeting) {
            $meeting_time_start = strtotime($meeting->meeting_time_start);
            $current_time = strtotime(date('H:i:00.000000'));
            if(($meeting_time_start <= $check_time) && $meeting_time_start >= $current_time)
            {
                
                
                $attendees = $meeting->attendees;
                foreach ($attendees as $key => $attendee) {
                    $content = [
                        "name" => @$attendee->user->name,
                        "body" => 'Meeting '.@$meeting->meeting_title.' has been scheduled on '.@$meeting->meeting_date.' '.@$meeting->meeting_time_start.' for '.@$meeting->agenda_of_meeting.'.',
                    ];
                    if (env('IS_MAIL_ENABLE', false) == true) {
                        $mailLogData = MeetingMailLog::where('meeting_id',$meeting->id)->where('user_id',$attendee->user_id)->first();
                        if(empty($mailLogData))
                        {
                          if(!empty($attendee->user))
                          {
                            $recevier = Mail::to($attendee->user->email)->send(new scheduleMeetingMail($content));
                            if($recevier == true)
                            {
                                $meetingMailLog = new MeetingMailLog;
                                $meetingMailLog->meeting_id = $meeting->id;
                                $meetingMailLog->user_id = $attendee->user_id;
                                $meetingMailLog->save();
                            }
                          }
                            
                        }
                    }
                }
            }
        }
        return 0;


       // $currentNow = Carbon::now()->format("Y-m-d");
       // $getAllmeetingAttendees = Attendee::join('meetings','meetings.id','attendees.meeting_id')->whereDate('meetings.meeting_date',$currentNow)->orderby('meetings.id','ASC')->with('user:id,name,email')->get();

       //  echo Carbon::now()->format('Y-m-d H:i:s') . '------Reminder  Start------';
       //  foreach ($getAllmeetingAttendees as $key => $meeting) {
       //      $meeting_time = Carbon::createFromFormat('H:i:s',$meeting->meeting_time);
       //      $current_time =  Carbon::createFromFormat('H:i:s',date('H:i:s'));
       //      $diff_in_minutes = $meeting_time->diffInMinutes($current_time);
       //      if($diff_in_minutes=='15'){
       //          $content = [
       //          "name" => @$meeting->user->name,
       //          "meeting_title" => @$meeting->meeting_title,
       //          "meeting_date" => @$meeting->meeting_date,
       //          "meeting_time" => @$meeting->meeting_time,
       //          "agenda_of_meeting" => @$meeting->agenda_of_meeting,
               
       //          ];

       //          if (env('IS_MAIL_ENABLE', false) == true) {
                   
       //              $recevier = Mail::to(@$meeting->user->email)->send(new scheduleMeetingMail($content));
       //          }

       //      }

           
       //  }
       //   echo Carbon::now()->format('Y-m-d H:i:s') . '------Reminder  end------';
    }
}
