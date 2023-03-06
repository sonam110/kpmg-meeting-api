<?php

namespace App\Console\Commands;
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\Attendee;
use App\Models\User;
use App\Models\MasterUser;
use App\Models\Module;
use App\Models\AssigneModule;
use App\Models\MeetingLog;
use voku\helper\HtmlDomParser;
use Mail;
use App\Mail\MeetingMail;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use DB;
class MailSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:sync';

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
        try {
            $incoming_mail_server = '{outlook.office365.com:993/imap/ssl/novalidate-cert}INBOX'; 
            //This is an example incoming mail server for Gmail which you can configure to your outlook, check out the manual on Supported IMAP client list below.
              
            $your_email = env('CONNECTED_MAIL'); //'erashok23@outlook.com'; // your outlook email ID
            $yourpassword = env('MAIL_PASSWARD');//'Ashok_64554@'; // your outlook email password
              
            $mbox   = imap_open($incoming_mail_server, $your_email , $yourpassword)   or  die("can't connect: " . imap_last_error());
            $num = imap_num_msg($mbox); // read total messages in email
            $MC = imap_check($mbox);
            $msg = array();
            // Fetch an overview for all messages in INBOX
            $result = imap_fetch_overview($mbox,"$num:{$MC->Nmsgs}",0);
            foreach ($result as $overview) 
            {
                //print_r($overview);
               // echo 'Message no: '.$overview->msgno. '<br/>';
                     "{$overview->subject}<br/>";
                $check = imap_mailboxmsginfo($mbox);
               // echo $check->Unread. '<br/>';
               // echo $overview->subject. '<br/>';
                //echo $overview->body. '<br/>';
                //echo '<hr>';
                $random = \Str::random(10);
                $fileName = $random.'-invite.ics';
                $body =  (imap_fetchbody($mbox,$overview->msgno,1.1));
                $decoded = base64_decode($body);
                file_put_contents($fileName,$decoded);

                $message = imap_body($mbox, $overview->msgno);

                $subject = $overview->subject;
                $str = substr($subject, strpos($subject, 'invitation: ') + 12);
                $exploade = explode('@', $str);
                $title = @$exploade[0];
                
                $message = str_replace('3D','', $message);
                $message = preg_replace("/\s/",' ',$message);
               
                $message = str_replace("\u{c2a0}", "", $message);
                $message = str_replace("&nbsp;",' ', $message);
                $message = str_replace(".=  com;",'.com', $message);
                $message = str_replace("=  ",'', $message);
                $message = str_replace("=-",'-', $message);
                $message = str_replace("&n=  bsp;",'&nbsp;', $message);
                $message = str_replace("&n=  bsp;",'&nbsp;', $message);

                $message = str_replace("’",'’',$message);

                $description=[];
                $dom = HtmlDomParser::str_get_html($message);

                $startDate ='';
                if (null !== ($dom->findOne("time[itemprop='startDate']", 0))) {
                    $startDate = $dom->findOne("time[itemprop='startDate']", 0)->datetime;
                }
                $endDate ='';
                if (null !== ($dom->findOne("time[itemprop='endDate']", 0))) {
                    $endDate = $dom->findOne("time[itemprop='endDate']", 0)->datetime;
                }

                $attendees = [];
                if (null !== ($dom->findOne("meta[itemprop='email']", 0))) {
                    foreach(($dom->find("meta[itemprop='email']")) as $key =>$email) {

                       $attendees[] = $email->content;
                    }
                }

                

                if (null !== ($dom->findOne("meta[itemprop='description']", 0))) {
                    foreach(($dom->find("meta[itemprop='description']")) as $key =>$desc) {

                       $description[]= $desc->content;
                    }
                }

                $meet_link = '';

                /*if (null !== ($dom->findOne("a[class='primary-button-text']"))) {
                    $meet_link = $dom->findOne("a[class='primary-button-text]")->href;
                }*/
                

                

                //print_r($attendees);
                //die;
                //print_r(date('Y-m-d',strtotime($startDate)));
                //print_r(date('H:i:s',strtotime($endDate)));
               // die;
                $checkMsgIExist = Meeting::where('message_id',$overview->msgno)->first();
                if(empty($checkMsgIExist)){
                    //-Create New meeting in an application---
                    $meeting = new Meeting;
                    $meeting->message_id = @$overview->msgno;
                    $meeting->meetRandomId = $random;
                    $meeting->meeting_title = $title;
                    $meeting->meeting_ref_no = $meet_link;
                    $meeting->meeting_date = date('Y-m-d',strtotime($startDate));
                    $meeting->meeting_time_start = date('H:i:',strtotime($startDate));
                    $meeting->meeting_time_end = date('H:i:',strtotime($endDate));
                    $meeting->agenda_of_meeting = (!empty(@$description[1])) ? @$description[1]: @$description[0];
                    $meeting->invite_file =$fileName;
                    $meeting->save();

                        foreach ($attendees as $key => $attendee) {
                           
                            $checkUser = User::where('email',$attendee)->first();
                            /*---------Add User---------------------*/
                            if(empty($checkUser)){
                                $userInfo = $this->addUser($attendee);
                                $user_id = $userInfo->id;
                                $name = $userInfo->name;
                            } else{
                                $user_id = $checkUser->id;
                                $name = $checkUser->name;
                            }
                            
                            $attende = new Attendee;
                            $attende->meeting_id = $meeting->id;
                            $attende->user_id = $user_id;
                            $attende->save();
                            

                            if (env('IS_MAIL_ENABLE', false) == true) {
                                $content = [
                                    "name" =>$name,
                                    "meeting_title" => $meeting->meeting_title,
                                    "meeting_date" => $meeting->meeting_date,
                                    "meeting_time" => $meeting->meeting_time_start,
                                    "agenda_of_meeting" => $meeting->agenda_of_meeting,
                           
                                ];
                               
                                $recevier = Mail::to($attendee)->send(new MeetingMail($content));
                            }

                        }
                    


                }

            }
        } catch (\Exception $exception) {
          return $exception->getMessage();
        }

        
    }

     public function addUser($email)
    {
        $randomNo = generateRandomNumber(10);
        $password = Hash::make($randomNo);
        $masterUser = new MasterUser;
        $masterUser->name = $email;
        $masterUser->email  = $email;
        $masterUser->password = $password;
        $masterUser->save();

        $user = new User;
        $user->id = $masterUser->id;
        $user->role_id = '2';
        $user->name = $email;
        $user->email  = $email;
        $user->password = $password;
        $user->created_by = auth()->user()->id;
        $user->save();

        //Delete if entry exists
        DB::table('password_resets')->where('email', $email)->delete();

        $token = \Str::random(64);
        DB::table('password_resets')->insert([
          'email' => $email, 
          'token' => $token, 
          'created_at' => Carbon::now()
        ]);

        $baseRedirURL = env('APP_URL');
        $content = [
            "name" => $user->name,
            "email" => $user->email,
            "password" => $randomNo,
            "passowrd_link" => $baseRedirURL.'/authentication/reset-password/'.$token
        ];

        if (env('IS_MAIL_ENABLE', false) == true) {
           
            $recevier = Mail::to($email)->send(new WelcomeMail($content));
        }

        /*-------Assigne Meeting module for this user*/
        $assigneModule = new AssigneModule;
        $assigneModule->module_id  = '1';
        $assigneModule->user_id  = $masterUser->id;
        $assigneModule->save();

    
        //Role and permission sync
        $role = Role::where('id','2')->first();
        $permissions = $role->permissions->pluck('name');
        
        $user->assignRole($role->name);
        foreach ($permissions as $key => $permission) {
            $user->givePermissionTo($permission);
        }
        return $user;

    }
    /*function mail_parse_headers($headers)
    {

        $headers=preg_replace('/\r\n\s+/m', '',$headers);

        preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);

        foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key];

        return($result);

    }
    function mail_get_parts($imap,$mid,$part,$prefix)

    {    

        $attachments=array();

        $attachments[$prefix]=mail_decode_part($imap,$mid,$part,$prefix);

        if (isset($part->parts)) // multipart

        {

            $prefix = ($prefix == "0")?"":"$prefix.";

            foreach ($part->parts as $number=>$subpart) 

                $attachments=array_merge($attachments, mail_get_parts($imap,$mid,$subpart,$prefix.($number+1)));

        }

        return $attachments;

    }*/
}
