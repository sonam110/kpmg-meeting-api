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
use File;

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
            File::ensureDirectoryExists('public/ics');
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

                $checkMsgIExist = Meeting::where('message_id',$overview->msgno)->first();
                if(empty($checkMsgIExist)){
                
                    $check = imap_mailboxmsginfo($mbox);
                    $random = \Str::random(10);
                    $fileName = $random.'-invite.ics';
                   
                    $message = imap_body($mbox, $overview->msgno);
                    $body =  (imap_fetchbody($mbox,$overview->msgno,1.2)); 
                    $toEmailList = '';
                    $toNameList = '';
                    $emails ='';
                    $pMessgae =  $this->processMessage($mbox, $overview->msgno);

                    $from = trim(substr($overview->from, 0, 16));
                   
                    
                    $attendees = (!empty(@$pMessgae['toEmails'])) ? explode(';',@$pMessgae['toEmails']) :NULL;
                    preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $message, $match);
                    $meeting_links  = @$match[0];
                    
                   /*-------Invite through  google event --------------------------*/
                    if($from =='Google Calendar'){

                        $body =  (imap_fetchbody($mbox,$overview->msgno,1.1));
                        $decoded = base64_decode($body);
                        file_put_contents('public/ics/'.$fileName,$decoded);

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

                        if (null !== ($dom->findOne("meta[itemprop='description']", 0))) {
                            foreach(($dom->find("meta[itemprop='description']")) as $key =>$desc) {

                               $description[]= $desc->content;
                            }
                        }
                        $agenda_of_meeting = (!empty(@$description[1])) ? @$description[1]: @$description[0];
                        $meeting_link = (!empty(@$meeting_links[7])) ? @$meeting_links[7]: NULL;
                        $file_name = 'ics/'.$fileName;
                        
                    }else{
                        /*-------Invite through  Meet Link --------------------------*/
                        $title = $overview->subject;
                        $startDate = date('Y-m-d H:i:s');
                        $endDate = date('Y-m-d H:i:s',strtotime('+60 minutes'));
                        $agenda_of_meeting = @$pMessgae['body'];
                        $meeting_link = (!empty(@$meeting_links[0])) ? @$meeting_links[0]: NULL;
                        $file_name  = '';
                            

                    }
                   
        
                    //-Create New meeting in an application---
                    $meeting = new Meeting;
                    $meeting->message_id = @$overview->msgno;
                    $meeting->meetRandomId = $random;
                    $meeting->meeting_title = $title;
                    $meeting->meeting_link = $meeting_link;
                    $meeting->meeting_date = date('Y-m-d',strtotime($startDate));
                    $meeting->meeting_time_start = date('H:i:',strtotime($startDate));
                    $meeting->meeting_time_end = date('H:i:',strtotime($endDate));
                    $meeting->agenda_of_meeting = $agenda_of_meeting;
                    $meeting->invite_file = $file_name ;
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

    function extractEmail($content) {
        $regexp = '/([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+/i';
        preg_match_all($regexp, $content, $m);
        return isset($m[0]) ? $m[0] : array ();
    }

    function getAddressText(&$emailList, &$nameList, $addressObject) { 
        $emailList = '';
        $nameList = '';
        foreach ($addressObject as $object) {
            $emailList .= ';';
            if (isset($object->personal)) { 
                 $emailList .= $object->personal;
            } 
            $nameList .= ';';
            if (isset($object->mailbox) && isset($object->host)) { 
                $nameList .= $object->mailbox . "@" . $object->host;
            }    
        }    
        $emailList = ltrim($emailList, ';');
        $nameList = ltrim($nameList, ';');
    } 


    function processMessage($mbox, $messageNumber) { 
        $resultArr =[];
        // get imap_fetch header and put single lines into array
        $header = imap_rfc822_parse_headers(imap_fetchheader($mbox, $messageNumber));
        $fromEmailList = '';
        $fromNameList = '';
        if (isset($header->from)) { 
            $this->getAddressText($fromEmailList, $fromNameList, $header->from); 
        }
        $toEmailList = '';
        $toNameList = '';
        if (isset($header->to)) {
            $this->getAddressText($toEmailList, $toNameList, $header->to); 
        }    
        $body = imap_fetchbody($mbox, $messageNumber, 1);
        $bodyEmailList = implode(';', $this->extractEmail($body));
        $resultArr =[
            "body"=> $body,
            "toEmails"=> $toNameList,
        ];
        return $resultArr;
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
