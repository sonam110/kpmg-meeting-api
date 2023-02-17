
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Invoice</title>    
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
    <style type="text/css">


.clearfix:after {
  content: "";
  display: table;
  clear: both;
}
a {
  color: #0087C3;
  text-decoration: none;
}

body {
  position: relative;
  width: 21cm;  
  margin: 0 auto; 
  color: #555555;
  background: #FFFFFF; 
  font-family: Arial, sans-serif; 
  font-size: 14px; 
  font-family: Source Sans Pro;
}

header {
  padding: 10px 0;
  margin-bottom: 20px;
  border-bottom: 1px solid #AAAAAA;
}

#logo {
  float: left;
  margin-top: 8px;
}

#logo img {
  height: 70px;
}

#company {
  float: right;
  text-align: right;
}


#details {
  margin-bottom: 50px;
}

#client {
  padding-left: 6px;
  border-left: 6px solid #0087C3;
  float: left;
}

#client .to {
  color: #777777;
}

h2.name {
  font-size: 1.4em;
  font-weight: normal;
  margin: 0;
}

#invoice {
  float: right;
  text-align: right;
}

#invoice h1 {
  color: #0087C3;
  font-size: 2.4em;
  line-height: 1em;
  font-weight: normal;
  margin: 0  0 10px 0;
}

#invoice .date {
  font-size: 1.1em;
  color: #777777;
}

table {
  width: 95%;
  border-spacing: 0;
  margin-bottom: 20px;
}

table th,
table td {
  padding: 5px !important;
  background: #EEEEEE;
  text-align: center;
  border-bottom: 1px solid #FFFFFF;
}

table th {
}

table td {
  text-align: center;
}

table td h3{
  color: #ffad33;
  font-size: 1.2em;
  font-weight: normal;
  margin: 0 0 0.2em 0;
}

table .no {
  color: #FFFFFF;
  font-size: 11px;
  background: #ffad33;
}

table .desc {
  text-align: left;
}

table .unit {
  background: #DDDDDD;
}

table .qty {
}

table .total {
  background: #ffad33;
  color: #FFFFFF;
}

table td.unit,
table td.qty,
table td.total {
  font-size: 11px;
}

table tbody tr:last-child td {
  border: none;
}

table tfoot td {
  padding: 10px 20px;
  background: #FFFFFF;
  border-bottom: none;
  font-size: 1.2em;
  border-top: 1px solid #AAAAAA; 
}

table tfoot tr:first-child td {
  border-top: none; 
}

table tfoot tr:last-child td {
  color: #ffad33;
  font-size: 11px;
  border-top: 1px solid #ffad33; 

}

table tfoot tr td:first-child {
  border: none;
}

#thanks{
  font-size: 2em;
  margin-bottom: 50px;
}

#notices{
  padding-left: 6px;
  border-left: 6px solid #0087C3;  
}

#notices .notice {
  font-size: 1.2em;
}

footer {
  color: #777777;
  width: 100%;
  height: 30px;
  position: absolute;
  bottom: 0;
  border-top: 1px solid #AAAAAA;
  padding: 8px 0;
  text-align: center;
}


    </style>
  </head>
  <body>
    <header class="clearfix">
     
      <div id="company">
        <h2 class="name">Test</h2>
      </div>
    </header>
    <main>
      @foreach($dprList as $key => $dpr)
      <?php 
        $alldpr = App\Models\DprImport::where('work_item','!=','')->where('work_item',$dpr->work_item)->where('data_date',$dpr->data_date)->get();

      ?>
      <table border="0" width="100%" style="padding: 10px 20px;">
        <thead>
          <tr>
            <th class="no" colspan="8">{{ $dpr->work_item }}</th>
            <th class="no" colspan="4">{{ date('Y-m-d', strtotime($dpr->data_date)) }}</th>
          </tr>
         <tr>
              <th class="no"></th>
              <th class="desc"><div>PROJECT</div></th>
              <th class="desc"><div>WORK ITEM</div></th>
              <th class="desc"><div>SCOPE</div></th>
              <th class="qty"><div>ACTUAL FTM</div></th>
              <th class="qty"><div>ACTUAL TILL DATE</div></th>
              <th class="qty"><div>PLAN FTM</div></th>
              <th class="qty"><div>DATA DATE</div></th>
              <th class="qty"><div>DWG AVAIL</div></th>
              <th class="qty"><div>MANPOWER</div></th>
              <th class="qty"><div>TODAY</div></th>
            </tr>
        </thead>
        <tbody>
    
        @foreach($alldpr as $key => $dpr)

        <?php

         $url = url('user/import/'.@$dpr->dprManage->original_import_file.'');
        ?>
         <tr>
          <td class="no"></td>
          <td class="desc">{{ $dpr->work_item }}  <a href="{{ $url }}" download> {{ $dpr->work_item }} EMD-{{ $key+1 }}</a></td>
        
            <td class="desc">{{ $dpr->work_item }}  </td>
            <td class="desc">{{ $dpr->total_scope }}  </td>
            <td class="desc">{{ $dpr->actual_ftm }}  </td>
            <td class="desc">{{ $dpr->actual_till_date }}  </td>
            <td class="desc">{{ $dpr->plan_ftm }}  </td>
            <td class="desc">{{ $dpr->data_date }}  </td>
            <td class="desc">{{ $dpr->dwg_avail }}  </td>
            <td class="desc">{{ $dpr->manpower }}  </td>
            <td class="desc">{{ $dpr->today }}  </td>
          </tr>
         @endforeach
        </tbody>
         
      </table>
      @endforeach
      <div id="thanks">Thank you!</div>
      <div id="notices"></div>
        
    </main>
   
  </body>
</html>