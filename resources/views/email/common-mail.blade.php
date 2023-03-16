<html>
<head>
  <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
  <style type="text/css">
    @import  url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Open+Sans+Condensed:wght@700&display=swap');

    body {
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 700;
      color: #000;
    }

    h2 {
      font-family: 'Open Sans Condensed';
      font-style: normal;
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
  </style>
</head>
<body>
  <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff">
    <tr>
      <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff" style="padding-bottom: 30px;padding: 1px;">
          <tr>
            <th>
              <div style="text-align: center;">
                <img src="{{asset('kpmg-logo.png')}}" width="225px">
              </div>
            </th>
          </tr>
          <tr>
            <th>
              <h2 style="text-align: center; color: #00338D;">KPMG PIVOT - MAI</h2>
            </th>
          </tr>
          <tr>
            <td>
              <!-- NAME SECTION START -->
              Hello {{$content['name']}},<br>
              <!-- NAME SECTION START -->

              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

              <!-- CONTENT SECTION START -->
               {!! $content['body'] !!}
              <!-- CONTENT SECTION END -->

            </td>
          </tr>
          <tr>
            <td>
                <br>
                <div style="font-size: 12px">Note: This is a system generated mail. Please do not reply on this,</div><br><br>
                Regards,<br>
                KPMG PIVOT Team<br>
                <a href="mailto:in-fmpivotsupport@kpmg.com">in-fmpivotsupport@kpmg.com</a><br><br>
                
                <div style="padding: 20px 0; margin: 15px 0; border-top: 1px solid gray; border-bottom: 1px solid gray; color: gray;">
                  {{date('Y')}} KPMG International Cooperative
                </div>

                KPMG (in India) allows reasonable personal use of the e-mail system. Views and opinions expressed in these communications do not necessarily represent those of KPMG (in India).<br><br>

                <div style="padding: 20px 0; margin: 15px 0; border-top: 2px dashed gray; border-bottom: 2px dashed gray; font-size: 14px">
                DISCLAIMER<br>
                The information in this e-mail is confidential and may be legally privileged. It is intended solely for the addressee. Access to this e-mail by anyone else is unauthorized. If you have received this communication in error, please address with the subject heading "Received in error," send to postmaster1@kpmg.com, then delete the e-mail and destroy any copies of it. If you are not the intended recipient, any disclosure, copying, distribution or any action taken or omitted to be taken in reliance on it, is prohibited and may be unlawful. Any opinions or advice contained in this e-mail are subject to the terms and conditions expressed in the governing KPMG client engagement letter. Opinions, conclusions and other information in this e-mail and any attachments that do not relate to the official business of the firm are neither given nor endorsed by it.<br><br>

                KPMG cannot guarantee that e-mail communications are secure or error-free, as information could be intercepted, corrupted, amended, lost, destroyed, arrive late or incomplete, or contain viruses.<br><br>

                KPMG, an Indian partnership and a member firm of KPMG International Cooperative ("KPMG International"), an English entity that serves as a coordinating entity for a network of independent firms operating under the KPMG name. KPMG International Cooperative (“KPMG International”) provides no services to clients. Each member firm of KPMG International Cooperative (“KPMG International”) is a legally distinct and separate entity and each describes itself as such.<br><br>

                “Notwithstanding anything inconsistent contained in the meeting invite to which this acceptance pertains, this acceptance is restricted solely to confirming my availability for the proposed call and should not be construed in any manner as acceptance of any other terms or conditions. Specifically, nothing contained herein may be construed as an acceptance (or deemed acceptance) of any request or notification for recording of the call, which can be done only if it is based on my explicit and written consent and subject to the terms and conditions on which such consent has been granted”<br>
                <div>

            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

</body>
</html>