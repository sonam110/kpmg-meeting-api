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
use Str;
use ICal\ICal;
class IcalMeetSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "ical-mail:sync";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command description";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info("i m trigger");
        try {
            File::ensureDirectoryExists("public/ics");
            $incoming_mail_server =
                "{mail.gofactz.com:993/imap/ssl/novalidate-cert}INBOX";
            //This is an example incoming mail server for Gmail which you can configure to your outlook, check out the manual on Supported IMAP client list below.

            $your_email = env("CONNECTED_MAIL"); //'erashok23@outlook.com'; // your outlook email ID
            $yourpassword = env("MAIL_PASSWARD"); //'Ashok_64554@'; // your outlook email password

            ($mbox = imap_open(
                $incoming_mail_server,
                $your_email,
                $yourpassword
            )) or die("can't connect: " . imap_last_error());
            $num = imap_num_msg($mbox); // read total messages in email
            $MC = imap_check($mbox);
            $msg = [];
            // Fetch an overview for all messages in INBOX
            $search = 'SINCE "' . date("j F Y", strtotime("0 days")) . '"';
            $emails = imap_search($mbox, $search); 

           // $emails = array_reverse($emails);

            if(!empty($emails)){
                foreach($emails as $email){
                    $result = imap_fetch_overview($mbox, $email, 0);
                    $check = imap_mailboxmsginfo($mbox);

                    foreach ($result as $overview) {
                        $creation_date = date('Y-m-d',strtotime($overview->date));
                        $getResults = $this->getmsg($mbox, $overview->msgno);
                        $randomNo = generateRandomNumber(10);
                        $path = public_path(@$getResults["filePath"]);
                        preg_match_all("#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#", $message,$match);
                        $meeting_links = @$match[0];
                        $from = trim(substr($overview->from, 0, 16));
                         \Log::info($path);
                        if (@$getResults["filePath"]) {
                            $ical = new ICal($path, [
                                "defaultSpan" => 2, // Default value
                                "defaultTimeZone" => "UTC",
                                "defaultWeekStart" => "MO", // Default value
                                "disableCharacterReplacement" => false, // Default value
                                "filterDaysAfter" => null, // Default value
                                "filterDaysBefore" => null, // Default value
                                "httpUserAgent" => null, // Default value
                                "skipRecurrence" => false, // Default value
                            ]);

                            $events = $ical->sortEventsWithOrder($ical->events());
                           
                            if (!empty(@$events[0])) {
                                $event = @$events[0];
                               
                                if($event->location=='Microsoft Teams Meeting'){
                                        $meeting_link = @$event->x_microsoft_skypeteamsmeetingurl_array[1];
                                }elseif(($from =='Google Calendar') && !empty(@$event->x_google_conference)) {
                                    $meeting_link = @$event->x_google_conference;
                                } else{
                                    $meeting_link = @$event->location;
            
                                }
                                   
                                $attendees = (!empty(@$event->attendee)) ? explode(",", @$event->attendee) : [];
                                $organizer = explode(":", @$event->organizer);
                                $checkMsgIExist = Meeting::where("meeting_uid",@$event->uid)->first();
                                if(!empty($checkMsgIExist)){
                                    if($event->status=='CANCELLED'){
                                            $checkMsgIExist->status ='3';
                                            $checkMsgIExist->save();
                                    }
                                    if($event->status=='DELETED'){
                                        $checkMsgIExist->delete();
                                    }
                                    if($event->status=='CONFIRMED' && @$checkMsgIExist->message_id != $overview->msgno){
                                        $checkMsgIExist->message_id = @$overview->msgno;
                                        $checkMsgIExist->meeting_title = @$event->summary;
                                        $checkMsgIExist->meeting_date = date("Y-m-d",strtotime(@$event->dtstart));
                                        $checkMsgIExist->meeting_time_start = date("H:i:s",strtotime(@$event->dtstart));
                                        $checkMsgIExist->meeting_time_end = date("H:i:s",strtotime(@$event->dtend) );
                                        $checkMsgIExist->agenda_of_meeting = @$event->description;
                                        $checkMsgIExist->invite_file = @$getResults["filePath"];
                                        $checkMsgIExist->save();
                                        \Log::info('meeting updated:'.@$checkMsgIExist->id);
                                        $deleteOldAtt = Attendee::where('meeting_id',$checkMsgIExist->id)->delete();
                                        $this->addAttendees($attendees,$checkMsgIExist);
                                    }

                                    \Log::info('id already-'.@$checkMsgIExist->id);
                                }
                                
                                if (empty($checkMsgIExist)) {
                                    if(!empty(@$organizer[1])){
                                        $organizerExist = User::where("email",@$organizer[1])->first();
                                        if (empty($organizerExist)) {
                                            $userInfo = $this->addUser(@$organizer[1]);
                                            $user_id = $userInfo->id;
                                        } else {
                                            $user_id = $organizerExist->id;
                                        }

                                    } else{
                                        $user_id = '1';
                                    }

                                    //-Create New meeting in an application---
                                    $meeting_ref_no = strtoupper(Str::random(2)).rand(10000000,99999999);

                                    $meeting = new Meeting();
                                    $meeting->message_id = @$overview->msgno;
                                    $meeting->meetRandomId =  generateRandomNumber(14);
                                    $meeting->meeting_ref_no =  $meeting_ref_no;
                                    $meeting->organised_by = $user_id;
                                    $meeting->meeting_title = @$event->summary;
                                    $meeting->meeting_link = $meeting_link;
                                    $meeting->meeting_uid = @$event->uid;
                                    $meeting->meeting_date = date("Y-m-d",strtotime(@$event->dtstart));
                                    $meeting->meeting_time_start = date("H:i:s",strtotime(@$event->dtstart));
                                    $meeting->meeting_time_end = date("H:i:s",strtotime(@$event->dtend) );
                                    $meeting->agenda_of_meeting = @$event->description;
                                    $meeting->invite_file = @$getResults["filePath"];
                                    $meeting->save();
                                    \Log::info('meeting created:'.@$meeting->id);
                                    $this->addAttendees($attendees,$meeting);
                                   
                                }
                            }
                        }
                        
                    }
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    function addAttendees($attendees,$meeting)
    {
        foreach ($attendees as $key => $attn) {
            $attend = explode(":", @$attn);
            $attendee = @$attend[1];
            $checkUser = User::where("email",$attendee)->first();
            /*---------Add User---------------------*/
            if (empty($checkUser)) {
                $userInfo = $this->addUser($attendee);
                $user_id = $userInfo->id;
                $name = $userInfo->name;
            } else {
                $user_id = $checkUser->id;
                $name = $checkUser->name;
            }
           
            $attende = new Attendee();
            $attende->meeting_id = $meeting->id;
            $attende->user_id = $user_id;
            $attende->save();
            if (env("IS_MAIL_ENABLE", false) == true) {
                $content = [
                    "name" => $name,
                    "meeting_title" =>$meeting->meeting_title,
                    "meeting_date" => $meeting->meeting_date,
                    "meeting_time" =>$meeting->meeting_time_start,
                    "agenda_of_meeting" =>$meeting->agenda_of_meeting,
                ];

                $recevier = Mail::to($attendee)->send(
                    new MeetingMail($content)
                );
            }
        }

    }
    function getmsg($mbox, $mid)
    {
        // input $mbox = IMAP stream, $mid = message id
        // output all the following:

        $result = "";
        $htmlmsg = $plainmsg = $charset = "";
        $attachments = [];

        // HEADER
        $h = imap_headerinfo($mbox, $mid);
        // add code here to get date, from, to, cc, subject...
        //print_r($h);

        // BODY
        $s = imap_fetchstructure($mbox, $mid);
        //print_r('---------------------------s----------------------------');
        // print_r($s);
        if (!$s->parts) {
            // simple
            $result = $this->getpart($mbox, $mid, $s, 0);
        }
        // pass 0 as part-number
        else {
            // multipart: cycle through each part
            foreach ($s->parts as $partno0 => $p) {
                $result = $this->getpart($mbox, $mid, $p, $partno0 + 1);
            }
        }

        return $result;
    }

    function getpart($mbox, $mid, $p, $partno)
    {
        // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
        File::ensureDirectoryExists("public/ics");
        $htmlmsg = $plainmsg = $charset = "";
        $attachments = [];

        // DECODE DATA
        $random = \Str::random(10);
        $fileName = $random . "-invite.ics";
        $data = $partno
            ? imap_fetchbody($mbox, $mid, $partno) // multipart
            : imap_body($mbox, $mid); // simple
        // Any part may be encoded, even plain text messages, so check everything.

        if ($p->encoding == 4) {
            $data = quoted_printable_decode($data);
        } elseif ($p->encoding == 3) {
            $data = base64_decode($data);
            file_put_contents(public_path("ics/" . $fileName), $data);

           // file_put_contents("public/ics/" . $fileName, $data);
        }

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.

        // print_r('---------------------------data----------------------------');
        // print_r($data);
        $params = [];
        if ($p->parameters) {
            foreach ($p->parameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;
            };
        }
        if (@$p->dparameters) {
            foreach (@$p->dparameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;
            };
        }

        // ATTACHMENT
        // Any part with a filename is an attachment,
        // so an attached text file (type 0) is not mistaken as the message.
        if (@$params["filename"] || @$params["name"]) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = $params["filename"]
                ? $params["filename"]
                : $params["name"];
            // filename may be encoded, so see imap_mime_header_decode()
            $attachments[$filename] = $data; // this is a problem if two files have same name
        }

        // TEXT

        if ($p->type == 0 && $data) {
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.

            if (strtolower($p->subtype) == "plain") {
                $plainmsg .= trim($data) . '\n\n';
            } else {
                $htmlmsg .= $data . "<br><br>";
            }
            $charset = $params["charset"]; // assume all parts are same charset
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type == 2 && $data) {
            $plainmsg .= $data . '\n\n';
        }

        // SUBPART RECURSION
        if (@$p->parts) {
            foreach (@$p->parts as $partno0 => $p2) {
                $this->getpart(
                    $mbox,
                    $mid,
                    $p2,
                    $partno . "." . ($partno0 + 1)
                );
            } // 1.2, 1.2.1, etc.
        }

        return $dataResult = [
            "htmlmsg" => $htmlmsg,
            "plainmsg" => $plainmsg,
            "charset" => $charset,
            "attachments" => $attachments,
            "data" => $data,
            "fileName" => $fileName,
            "filePath" => "ics/" . $fileName,
        ];
    }

    public function addUser($email)
    {
        $randomNo = generateRandomNumber(10);
        $password = Hash::make($randomNo);
        $masterUser = new MasterUser();
        $masterUser->name = $email;
        $masterUser->email = $email;
        $masterUser->password = $password;
        $masterUser->save();

        $user = new User();
        $user->id = $masterUser->id;
        $user->role_id = "2";
        $user->name = $email;
        $user->email = $email;
        $user->password = $password;
        $user->created_by = auth()->user()->id;
        $user->save();

        //Delete if entry exists
        DB::table("password_resets")
            ->where("email", $email)
            ->delete();

        $token = \Str::random(64);
        DB::table("password_resets")->insert([
            "email" => $email,
            "token" => $token,
            "created_at" => Carbon::now(),
        ]);

        $baseRedirURL = env("APP_URL");
        $content = [
            "name" => $user->name,
            "email" => $user->email,
            "password" => $randomNo,
            "passowrd_link" =>
                $baseRedirURL . "/authentication/reset-password/" . $token,
        ];

        if (env("IS_MAIL_ENABLE", false) == true) {
            $recevier = Mail::to($email)->send(new WelcomeMail($content));
        }

        /*-------Assigne Meeting module for this user*/
        $assigneModule = new AssigneModule();
        $assigneModule->module_id = "1";
        $assigneModule->user_id = $masterUser->id;
        $assigneModule->save();

        //Role and permission sync
        $role = Role::where("id", "2")->first();
        $permissions = $role->permissions->pluck("name");

        $user->assignRole($role->name);
        foreach ($permissions as $key => $permission) {
            $user->givePermissionTo($permission);
        }
        return $user;
    }
}
