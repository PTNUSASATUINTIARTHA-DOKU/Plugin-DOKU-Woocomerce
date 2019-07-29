<?php
						$myserverpath = explode ( "/", $_SERVER['PHP_SELF'] );
						if ( $myserverpath[1] <> 'admin' && $myserverpath[1] <> 'wp-admin' ) 
						{
								$serverpath = '/' . $myserverpath[1];    
						}
						else
						{
								$serverpath = '';
						}
						
						if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
						{
								$myserverprotocol = "https";
						}
						else
						{
								$myserverprotocol = "http";    
						}
						
						$myservername = $_SERVER['SERVER_NAME'] . $serverpath;			
										
						$mainurl =  $myserverprotocol.'://'.$myservername;

$config = $this->getServerConfig();
$destination = $config['DESTINATION'];
$mallid = $config['MALLID_MHVA'];
$sharedkey = $config['SHAREDKEY_MHVA'];
$chainmerchant = $config['CHAIN_MHVA'];
$exp = $configarray['expired'];
$prefix = $configarray[$payment.'VA'];
$wordsmh = sha1($_POST['AMOUNT'].$mallid.$sharedkey.$_POST['TRANSIDMERCHANT'].$_POST['CURRENCY']);
$AMOUNT=$_POST['AMOUNT'];
$TRANSIDMERCHANT=$_POST['TRANSIDMERCHANT'];
if ($destination=="DEVELOPMENT"){
$URL_REDIRECT='https://staging.doku.com/api/payment/DoGeneratePaycodeVA';
}else{
$URL_REDIRECT='https://pay.doku.com/api/payment/DoGeneratePaycodeVA';
}

$data= array('req_address'=>$_POST['ADDRESS'],
		'req_amount'=>$_POST['AMOUNT'],
		'req_basket'=>$_POST['BASKET'],
		'req_chain_merchant'=>$chainmerchant,
		'req_email'=>$_POST['EMAIL'],
		'req_expiry_time'=>$exp,
		'req_mall_id'=>$mallid,
		'req_mobile_phone'=>$_POST['MOBILEPHONE'],
		'req_name'=>$_POST['ADDRESS'],
		'req_purchase_amount'=>$_POST['AMOUNT'],
		'req_request_date_time'=>$_POST['REQUESTDATETIME'],
		'req_session_id'=>$_POST['SESSIONID'],
		'req_trans_id_merchant'=>$_POST['TRANSIDMERCHANT'],
		'req_words'=>$wordsmh,
		'req_currency'=>$_POST['CURRENCY'],
		'req_purchase_currency'=>$_POST['PURCHASECURRENCY']
	);
$POSTVARIABLES = $pariabel;

// echo json_encode($data);
define('POSTURL' , $URL_REDIRECT);

  $ch = curl_init(POSTURL);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='. json_encode($data));
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 18);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $GETDATARESULT = curl_exec($ch);
  curl_close($ch);
// echo $GETDATARESULT;
  $GETDATARESULT = json_decode($GETDATARESULT);
  $wordspayment = sha1($_POST['AMOUNT'].$sharedkey.$_POST['TRANSIDMERCHANT'].$GETDATARESULT->res_payment_code);

if($GETDATARESULT->res_response_code == '0000'){
	$paymentcode=$prefix.$GETDATARESULT->res_pay_code;
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
		    
		<form action="<?php echo $mainurl;?>/?wc-api=wc_dokuonecheckout_gateway&task=redirect" method="POST" id="formRedirect" name="formRedirect">
			<input type="hidden" name="WORDS" value="<?php echo $wordspayment;?>">
			<input type="hidden" name="AMOUNT" value="<?php echo $_POST['AMOUNT'];?>">
			<input type="hidden" name="TRANSIDMERCHANT" value="<?php echo $_POST['TRANSIDMERCHANT'];?>">
			<input type="hidden" name="STATUSCODE" value="<?php echo $GETDATARESULT->res_payment_code;?>">
			<input type="hidden" name="PAYMENTCODE" value="<?php echo $prefix.$GETDATARESULT->res_pay_code;?>">
			<input type="hidden" name="PAYMENTCHANNEL" value="<?php echo $_POST['PAYMENTCHANNEL'];?>">
			<input type="hidden" name="SESSIONID" value="<?php echo $_POST['SESSIONID'];?>">
			<input type="submit" class="default-btn-page font-reg" id="BTN-CONTINUE" value="Lanjutkan">
		</form>				
				
		</div>
		
	</section>
	
	<div class="footer">
		<div id="copyright" class="">Copyright DOKU 2018</div>
	</div>
    
</body>
</html>
<?php
}
else{
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
		    
		<form action="<?php echo $mainurl;?>/?wc-api=wc_dokuonecheckout_gateway&task=redirect" method="POST" id="formRedirect" name="formRedirect">
			<input type="hidden" name="WORDS" value="<?php echo $wordspayment;?>">
			<input type="hidden" name="AMOUNT" value="<?php echo $_POST['AMOUNT'];?>">
			<input type="hidden" name="TRANSIDMERCHANT" value="<?php echo $_POST['TRANSIDMERCHANT'];?>">
			<input type="hidden" name="STATUSCODE" value="9999">
			<input type="hidden" name="PAYMENTCODE" value="<?php echo $GETDATARESULT->res_pay_code;?>">
			<input type="hidden" name="PAYMENTCHANNEL" value="<?php echo $_POST['PAYMENTCHANNEL'];?>">
			<input type="hidden" name="SESSIONID" value="<?php echo $_POST['SESSIONID'];?>">
			<input type="submit" class="default-btn-page font-reg" id="BTN-CONTINUE" value="Lanjutkan">
		</form>				
				
		</div>
		
	</section>
	
	<div class="footer">
		<div id="copyright" class="">Copyright DOKU 2018</div>
	</div>
    
</body>
</html>
<?php
}
?>