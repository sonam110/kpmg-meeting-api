<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\User;
use App\Models\ActionItem;
use Mail;
use App\Mail\TaskReminderMail;
use Illuminate\Support\Carbon;
class ActionItemReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:task-reminder';

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
        $dayBeforeFiveDays = date("Y-m-d",strtotime('-5 days'));

        $allactionItems = ActionItem::whereDate('created_at','<=',$dayBeforeFiveDays)
            ->whereNotIn('status',['completed','verified'])
            ->get();

        foreach ($allactionItems as $value) 
        {
            $user = User::find($value->owner_id);
            if($user)
            {
                $content = [
                    "name" => $user->name,
                    "body" => 'Your Assigned Task for meeting  '.$value->meeting->meeting_title.' has not completed yet.',
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {
                   
                    $recevier = Mail::to($user->email)->send(new TaskReminderMail($content));
                }
            }
        }
        return;
    }
}
