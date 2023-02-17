
<html>
<head>
    <title></title>
</head>
<body>
<p>Hi {{ ucfirst($content['name']) }} ,</p>

<p>Welcome to KPMG.&nbsp;</p>
<p><strong>Email:</strong>{{ @$content['email'] }}</p>
<p><strong>Password:</strong>{{ @$content['password'] }}</p>
<p>Click on link below to get your new password.</p>
<p><center>
  <a href=" {!! $content['passowrd_link'] !!}" style="color: #000;font-size: 18px;text-decoration: underline;font-family: "Roboto Condensed", sans-serif;" target="_blank">Reset your password </a>
</center></p>

<p>If you have any questions, please feel free to reach out right away.</p>

<p>We highly recommend whitelisting offer notification email so that you never miss out of any information.</p>

<p><br />
Best,<br />
<strong>Team Reachomation</strong><br />
&nbsp;</p>
</body>
</html>
