<?php
$config = $this->getServerConfig();
$destination = $config['DESTINATION'];
$mallid = $config['MALLID_BCAKLIKPAY'];
$sharedkey = $config['SHAREDKEY_BCAKLIKPAY'];
$chainmerchant = $config['CHAIN_BCAKLIKPAY'];
$words = sha1($_POST['AMOUNT'].$mallid.$sharedkey.$_POST['TRANSIDMERCHANT']);
if ($destination=="DEVELOPMENT"){
$URL_REDIRECT='http://staging.doku.com/Suite/ReceiveMIP';
}else{
$URL_REDIRECT='https://pay.doku.com/Suite/ReceiveMIP';
}
foreach($_POST as $name=>$value)
{
		switch ($name)
	{
		case "MALLID":
		$value = $mallid;
		$MALLID = $value;		
		break;
		
		case "CHAINMERCHANT":
		$value = $chainmerchant;
		$CHAINMERCHANT = $value;		
		break;

		case "WORDS":
		$value = $words;
		$WORDS = $value;		
		break;
	}
  $pariabel.=$name.'='.$value.'&';  
}
$POSTVARIABLES = $pariabel;


define('POSTURL' , $URL_REDIRECT);
define('POSTVARS', $POSTVARIABLES);


  $ch = curl_init(POSTURL);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, POSTVARS);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 18);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $GETDATARESULT = curl_exec($ch);
  curl_close($ch);

    $xml = simplexml_load_string($GETDATARESULT);
  $URL_REDIRECT = $xml->REDIRECTURL;
  $REDIRECTPARAMETER = $xml->REDIRECTPARAMETER;

			$data_array = explode( ";;", $REDIRECTPARAMETER);
			$data_raw   = array_map('urldecode', $data_array);
						$n = 0;
			$bca_table = "";
			foreach ( $data_raw as $data_row )
			{						
						$data = explode( "||", $data_row );
						
						if ( $n == 2 ) $URL = $data[1];
						
						$bca_table .= "<input class=\"dokuinput\" name=\"".$data[0]."\" id=\"".$data[0]."\" type=\"hidden\" value=\"".$data[1]."\" readonly=\"readonly\">\r\n";						
						
						$n++;
			}


?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DOKU Payment Page - Redirect</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>  
<script type="text/javascript" src="https://pay.doku.com/merchant_data/ocov2/js/doku.analytics.js"></script>
<link rel="stylesheet" type="text/css" href="https://pay.doku.com/merchant_data/ocov2/css/default.min.css"/>
<link rel="stylesheet" type="text/css" href="https://pay.doku.com/merchant_data/ocov2/css/style.min.css"/>
<script type="text/javascript">
		var DYN = (new Date%9e6).toString(36);
		
		window['COUNTER' + DYN] = 10;
		setTimeout(countDown,1000);
		
		function countDown(){
				window['COUNTER' + DYN]--;
				if(window['COUNTER' + DYN] > 0){
					setTimeout(countDown,1000);
				}
				else
				{
					$('#TEXT-CONTINUE').show();
					$('#BTN-CONTINUE').show();
				}
		}
			
		$(document).ready(function(){
			$('#TEXT-CONTINUE').hide();
			$('#BTN-CONTINUE').hide();
		});    						
</script>

</head>

<body class="tempdefault tempcolor tempone" onload="document.formRedirect.submit()">

	<section class="default-width">
		
		<div class="head padd-default">
			<div class="left-head fleft">
			</div>
			
			<div class="clear"></div>
		</div>
		
		<br />
		
		<div class="">
		    
		<div class="loading">
			<div class="spinner">
				<div class="double-bounce1"></div>
				<div class="double-bounce2"></div>
			</div>
			<div class="color-one">
				Please wait.<br />
				Your request is being processed...<br />
				<br />
				<span id="TEXT-CONTINUE">Click button below if the page is not change</span>
			</div>
		</div>
		    
<?php
echo $bca_table;
  ?>
  			<input type="submit" class="default-btn-page font-reg" id="BTN-CONTINUE" value="Lanjutkan">
				
		</div>
		
	</section>
	
	<div class="footer">
		<div id="copyright" class="">Copyright DOKU 2019</div>
	</div>
    
</body>
</html>
