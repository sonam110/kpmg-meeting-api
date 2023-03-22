<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Mail;
use App\Mail\UpdatePasswordMail;
use Hash;

class UpdatePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:password';

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
        $daysBefore91days = date("Y-m-d",strtotime('-91 days'));
        $daysBefore46days = date("Y-m-d",strtotime('-46 days'));
        $users = User::where('status',1)->get(['id','name','email','role_id','password_last_updated']);
        foreach ($users as $key => $user) {
            if((($user->role_id == 1) && ($user->password_last_updated == $daysBefore46days)) || (($user->role_id != 1) && ($user->password_last_updated == $daysBefore91days)))
            {
                User::where('id',$user->id)->update(['password'=>Hash::make(rand(1000000000,99999999999)),'password_last_updated'=>date('Y-m-d')]);
                $content = [
                    "name" => $user->name,
                    "body" => 'Your password has been changed.Reset your passwod using forgot password.',
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {
                   
                    $recevier = Mail::to(@$user->email)->send(new UpdatePasswordMail($content));
                }
            }
        }
        return;
    }
}
