<?php
$config = $this->getServerConfig();
$destination = $config['DESTINATION'];
$mallid = $config['MALLID_KREDIVO'];
$sharedkey = $config['SHAREDKEY_KREDIVO'];
$chainmerchant = $config['CHAIN_KREDIVO'];
$WORDS = sha1($_POST['AMOUNT'].$mallid.$sharedkey.$_POST['TRANSIDMERCHANT']);
if ($destination=="DEVELOPMENT"){
$URL_REDIRECT='https://staging.doku.com/Suite/Receive';
}else{
$URL_REDIRECT='https://pay.doku.com/Suite/Receive';
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

<body onload="document.formRedirect.submit()">

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
		    
		<form action="<?php echo $URL_REDIRECT;?>" method="POST" id="formRedirect" name="formRedirect">
			<input name="MALLID" value="<?php echo $mallid; ?>" type="hidden"> 
			<input name="CHAINMERCHANT" value="<?php echo $chainmerchant; ?>" type="hidden"> 
			<input name="PAYMENTCHANNEL" value="<?php echo $_POST['PAYMENTCHANNEL']; ?>" type="hidden"> 
			<input name="AMOUNT" value="<?php echo $_POST['AMOUNT']; ?>" type="hidden"> 
			<input name="PURCHASEAMOUNT" value="<?php echo $_POST['PURCHASEAMOUNT']; ?>" type="hidden"> 
			<input name="TRANSIDMERCHANT" value="<?php echo $_POST['TRANSIDMERCHANT']; ?>" type="hidden"> 
			<input name="WORDS" value="<?php echo $WORDS; ?>" type="hidden"> 
			<input name="REQUESTDATETIME" value="<?php echo $_POST['REQUESTDATETIME']; ?>" type="hidden"> 
			<input name="CURRENCY" value="<?php echo $_POST['CURRENCY']; ?>" type="hidden"> 
			<input name="PURCHASECURRENCY" value="<?php echo $_POST['PURCHASECURRENCY']; ?>" type="hidden"> 
			<input name="SESSIONID" value="<?php echo $_POST['SESSIONID']; ?>" type="hidden"> 
			<input name="NAME" value="<?php echo $_POST['NAME']; ?>" type="hidden"> 
			<input name="EMAIL" value="<?php echo $_POST['EMAIL']; ?>" type="hidden"> 
			<input name="HOMEPHONE" value="<?php echo $_POST['HOMEPHONE']; ?>" type="hidden"> 
			<input name="MOBILEPHONE" value="<?php echo $_POST['MOBILEPHONE']; ?>" type="hidden"> 
			<input name="BASKET" value="<?php echo $_POST['BASKET']; ?>" type="hidden"> 
			<input name="ADDRESS" value="<?php echo $_POST['ADDRESS']; ?>" type="hidden"> 
			<input name="CITY" value="<?php echo $_POST['CITY']; ?>" type="hidden"> 
			<input name="STATE" value="<?php echo $_POST['STATE']; ?>" type="hidden"> 
			<input name="ZIPCODE" value="<?php echo $_POST['ZIPCODE']; ?>" type="hidden">
			<input type=hidden name="SHIPPING_COUNTRY" value="ID"> 
			<input type=hidden name="SHIPPING_ADDRESS" value="<?php echo $_POST['ADDRESS']; ?>"> 
    		<input type=hidden name="SHIPPING_CITY"    value="<?php echo $_POST['CITY']; ?>"> 
    		<input type=hidden name="SHIPPING_ZIPCODE" value="<?php echo $_POST['ZIPCODE']; ?>"> 				
			<input type="submit" class="default-btn-page font-reg" id="BTN-CONTINUE" value="Lanjutkan">
		</form>				
				
		</div>
		
	</section>
	
	<div class="footer">
		<div id="copyright" class="">Copyright DOKU 2019</div>
	</div>
    
</body>
</html>