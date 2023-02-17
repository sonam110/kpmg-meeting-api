<html>
<head>
<title></title>
</head>
<body>
<p>Hi {{ @$content['name'] }},</p>
<p><strong>{{ @$content['meeting_title'] }}</strong></p>
<p><strong>Date:</strong>{{ @$content['metting_date'] }}</p>
<p><strong>Time:</strong>{{ date('H:i A' strtotime(@$content['metting_time'])) }}</p>
<p><strong>Meeting Participants:</strong>{{ @$content['attendees'] }}</p>
<p><strong>Agenda</strong> </p>
<p>{{ @$content['agenda_of_meeting'] }}</p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p>We highly recommend whitelisting offer notification email so that you never miss out on any paid offer.</p>

<p><strong>Thanks &amp; Regards,</strong></p>

<p><strong>Team KPMG</strong><br />
&nbsp;</p>
</body>
</html>
