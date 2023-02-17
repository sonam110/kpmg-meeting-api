<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<title></title>
<style type="text/css">
  @font-face {
      font-family: 'Roboto Condensed', sans-serif;
      src: url(https://fonts.googleapis.com/css?family=Roboto+Condensed&display=swap);
  }
  body{
    font-family: 'Roboto Condensed', sans-serif;
    color: #000;
  }
  .table table {
    border-collapse: collapse;
    width: 100%;
    color: #000;
  }

  .table th, .table td {
    text-align: left;
    padding: 8px;
    border-top: 1px solid #428b9f;
    color: #000;
  }

  .table tr:nth-child(even) {
    background-color: #2d4046;
  }
  .ii a[href], a {
      color: #000!important;
  }
</style>
</head>
<body>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#4dbce7" style="padding-bottom: 30px;padding: 1px; font-family: 'Roboto Condensed', sans-serif;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff" style="padding: 15px 15px 0px 15px; font-family: 'Roboto Condensed', sans-serif;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#4dbce7" style="padding: 15px; font-family: 'Roboto Condensed', sans-serif;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline; font-family: 'Roboto Condensed', sans-serif;">Forgot Password</center>
                            </div>
                          </td>
                        </tr>
                      </table>

                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px; font-family: 'Roboto Condensed', sans-serif;">
                        <tr>
                          <td width="50%">
                            <div style="border: 3px solid #fff; width: 225px; padding: 10px; background: #4dbce7; float: right; font-family: 'Roboto Condensed', sans-serif;">
                              <center style="color: #fff; font-size: 16px; font-family: 'Roboto Condensed', sans-serif;"><?php echo date('d/m/Y, H:i A') ?></center>
                            </div>
                          </td>
                          <td>
                          
                            <div style="float: right; background: #fff; padding: 10px;">
                              <img src="{{ $appSetting->app_logo }}">
                            </div>
                          
                          </td>
                        </tr>

                        <tr>
                          <td colspan="2" style="color: #000; font-family: 'Roboto Condensed', sans-serif;">
                          This email is to confirm a recent password reset request for your account. To confirm this request and reset your password Please click below link
                            <br><br>
                            <center>
                              <a href=" {!! $content['passowrd_link'] !!}" style="color: #000;font-size: 18px;text-decoration: underline;font-family: "Roboto Condensed", sans-serif;" target="_blank">Reset your password </a>
                            </center>
                            <br><br>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 5px; color: #030303; background: #fff; text-align: center;border: 1px solid #4dbce7; font-family: 'Roboto Condensed', sans-serif;">
                        <tr>
                          <td style="font-family: 'Roboto Condensed', sans-serif;">
                            {{ $appSetting->app_name }}
                          </td>
                        </tr>
                      </table>
                      <br>
                    </td>
                  </tr>
                </table>
            </td>
          </tr>
        </table>
    </td>
  </tr>
</table>

</body>
</html>