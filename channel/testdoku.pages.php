<?php
// $orderid = $_POST['TRANSIDMERCHANT'];
// $order = new WC_Order($orderid);
// $EMAIL = trim($order->billing_email);
// $to = "rahdian@doku.com";
// $subject = "Learning how to send an Email in WordPress";
// $content = "WordPress knowledge";

// $status = wp_mail($to, $subject, $content);
// echo "Email Customer : ".$EMAIL." dari transaksi : ".$orderid;

$payment=$_POST['PAYMENTCHANNEL'];
$configarray = parse_ini_file("config.txt");
$channel=$configarray[$payment];
$arrChannel = explode(",", $channel);
$type = $arrChannel[1];
 echo $configarray[$type];

$config = $this->getServerConfig();
echo $config['MALL_ID']."  ".$config['SHARED_KEY']."  ".$config['CHAIN'];
?>
