<?php
$configarray = parse_ini_file("config.ini");
if ( isset($_GET['task']) )
{
		switch ( strtolower($_GET['task']) )
		{
		
				case "identify":
						
						$config = $this->getServerConfig();
						$use_identify = $config['USE_IDENTIFY'];
										
						if ( $use_identify=="yes" )
						{						
								if ( empty($_POST) )
								{
										echo "Stop : Access Not Valid";
										die;
								}
															 
								if (substr($this->getipaddress(),0,strlen($this->ip_range)) !== $this->ip_range)
								{
										echo "Stop : IP Not Allowed : ".getipaddress();
								}
								else
								{	    
									$channel=$configarray[$_POST['PAYMENTCHANNEL']];
									$paymentcode = $_POST['PAYMENTCODE'];
									$TRANSIDMERCHANT = $_POST['TRANSIDMERCHANT'];
									$AMOUNT = $_POST['AMOUNT'];
									$arrChannel = explode(",", $channel);
									$type = $arrChannel[1];

									# Skip identify checking for VA / ATM / ALFA Payment
										$trx = array();														
		
										$trx['amount']           = $_POST['AMOUNT'];
										$trx['transidmerchant']  = $_POST['TRANSIDMERCHANT']; 
										$trx['payment_code']	 = $_POST['PAYMENTCODE']; 
										$trx['payment_channel']  = $_POST['PAYMENTCHANNEL'];
										$trx['session_id']       = $_POST['SESSIONID'];
										$trx['process_datetime'] = date("Y-m-d H:i:s");
										$trx['process_type']     = 'IDENTIFY';
										$trx['ip_address']       = $this->getipaddress();
										$trx['message']          = "Identify process message come from DOKU";
		
										
										# Insert transaction identify to table onecheckout
										$this->add_dokuonecheckout($trx);
									if ( in_array($type, array("M","DT")) )
									{
										$order      = new WC_Order($trx['transidmerchant']);	
										$message = "Identify process message come from DOKU with channel ".$trx['payment_channel']." = ".$arrChannel[0];;
									$status         = "on-hold";

									$order->update_status($status, __( $message, 'woocommerce' ));
									echo "Continue with email to customer";

									}
										echo "Continue";							
								}
						}
						
				break;

				case "notify":
						
						if ( empty($_POST) )
						{
							 echo "Stop : Access Not Valid";
							 die;
						}
														 
						if (substr($this->getipaddress(),0,strlen($this->ip_range)) !== $this->ip_range)
						{
								echo "Stop : IP Not Allowed : ".$this->getipaddress();
						}
						else
						{
								$trx = array();
								
								$trx['words']                     = $_POST['WORDS'];
								$trx['amount']                    = $_POST['AMOUNT'];
								$trx['transidmerchant']           = $_POST['TRANSIDMERCHANT'];
								$trx['result_msg']                = $_POST['RESULTMSG'];            
								$trx['verify_status']             = $_POST['VERIFYSTATUS'];        
								
								$config = $this->getServerConfig();
								$paychannel = $_POST['PAYMENTCHANNEL'];
								$codetype = $configarray[$paychannel];
								if ($codetype=='M'){
									$mallid=$config['MALLID_MHVA'];
									$sharedkey=$config['SHAREDKEY_MHVA'];
								}else if ($codetype=='K'){
									$mallid=$config['MALLID_KREDIVO'];
									$sharedkey=$config['SHAREDKEY_KREDIVO'];
								}else if ($codetype=='B'){
									$mallid=$config['MALLID_BCAKLIKPAY'];
									$sharedkey=$config['SHAREDKEY_BCAKLIKPAY'];
								}else if ($codetype=='KB'){
									$mallid=$config['MALLID_KLIKBCA'];
									$sharedkey=$config['SHAREDKEY_KLIKBCA'];
								}else if ($codetype=='BN'){
									$mallid=$config['MALLID_BNIYAP'];
									$sharedkey=$config['SHAREDKEY_BNIYAP'];
								}else{
									$mallid=$config['MALL_ID'];
									$sharedkey=$config['SHARED_KEY'];
								}
								
								$words = sha1(trim($trx['amount']).
															trim($mallid).
															trim($sharedkey).
															trim($trx['transidmerchant']).
															trim($trx['result_msg']).
															trim($trx['verify_status']));
								
								if ( $trx['words']==$words )
								{            
										$trx['ip_address']            = $this->getipaddress();
										$trx['response_code']         = $_POST['RESPONSECODE'];
										$trx['approval_code']         = $_POST['APPROVALCODE'];
										$trx['payment_channel']       = $_POST['PAYMENTCHANNEL'];
										$trx['payment_code']          = $_POST['PAYMENTCODE'];
										$trx['session_id']            = $_POST['SESSIONID'];
										$trx['bank_issuer']           = $_POST['BANK'];
										$trx['creditcard']            = $_POST['MCN'];                   
										$trx['doku_payment_datetime'] = $_POST['PAYMENTDATETIME'];
										$trx['process_datetime']      = date("Y-m-d H:i:s");
										$trx['verify_id']             = $_POST['VERIFYID'];
										$trx['verify_score']          = (int) $_POST['VERIFYSCORE'];
										$trx['notify_type']           = $_POST['STATUSTYPE'];

										switch ( $trx['notify_type'] )
										{
												case "P":
												$trx['process_type'] = 'NOTIFY';
												break;
										
												case "V":
												$trx['process_type'] = 'REVERSAL';
												break;
										}
										
										$result = $this->checkTrx($trx);

										if ( $result < 1 )
										{
												echo "Stop : Transaction Not Found";
												die;            
										}
										else
										{
												$use_edu    = $config['USE_EDU'];
												$order      = new WC_Order($trx['transidmerchant']);	
												$channel=$configarray[$trx['payment_channel']];
												$arrChannel = explode(",", $channel);
												$type = $arrChannel[1];								
												
												switch (TRUE)
												{
														case ( $trx['result_msg']=="SUCCESS" && $trx['notify_type']=="P" && in_array($type, array("M","DT")) ):
														$trx['message'] = "Notify process message come from DOKU. Success : completed with channel ".$trx['payment_channel']." = ".$arrChannel[0];
														$status         = "completed";
														$order->payment_complete($trx['transidmerchant']);
														#$order->reduce_order_stock();											
														break;
				
														case ( $trx['result_msg']=="SUCCESS" && $trx['notify_type']=="P" && $use_edu=="yes" ):
														$trx['message'] = "Notify process message come from DOKU. Success but using EDU : pending with channel ".$trx['payment_channel']." = ".$arrChannel[0];
														$status         = "on-hold";
														break;
														
														case ( $trx['result_msg']=="SUCCESS" && $trx['notify_type']=="P" && $use_edu=="no" ):
														$trx['message'] = "Notify process message come from DOKU. Success : completed with channel ".$trx['payment_channel']." = ".$arrChannel[0];
														$status         = "completed";
														$order->payment_complete($trx['transidmerchant']);
														#$order->reduce_order_stock();
														break;
												
														case ( $trx['result_msg']=="FAILED" && $trx['notify_type']=="P" && $trx['response_code']=="5510" ):
														$trx['message'] = "Notify process message come from DOKU. Transaction failed : Customer Click Cancel Button";
														$status         = "cancelled";
														break;
												
														#edited line di bawah, dari cancelled jadi failed
														case ( $trx['result_msg']=="FAILED" && $trx['notify_type']=="P" ):
														$trx['message'] = "Notify process message come from DOKU. Transaction failed : FAILED";
														$status         = "failed";
														break;
														
														case ( $trx['notify_type']=="V" ):
														$trx['message'] = "Notify process message come from DOKU. Void by EDU : cancelled";
														$status         = "failed";
														break; 
														
														default:
														$trx['message'] = "Notify process message come from DOKU, use default rule : cancelled";
														$status         = "cancelled";
														break;
												}
						
												$order->update_status($status, __( $trx['message'], 'woocommerce' ));
												# $order->payment_complete($trx['transidmerchant']);
						
												# Insert transaction notify to table onecheckout
												$this->add_dokuonecheckout($trx);
												
												echo "Continue";
										}
								}
								else
								{
										echo "Stop : Request Not Valid";
										die;
								}
						}
						
				break;

				case "redirect":
						
						if ( empty($_POST) )
						{
						$config = $this->getServerConfig();
							if ($_GET['TRANSIDMERCHANT']){
						$trx['words']                = $_REQUEST['WORDS'];
						$trx['amount']               = $_REQUEST['AMOUNT'];
						$trx['transidmerchant']      = $_REQUEST['TRANSIDMERCHANT']; 
						$trx['status_code']          = $_REQUEST['STATUSCODE'];
						$order   = new WC_Order($trx['transidmerchant']);
						$trx['payment_channel']  = $_REQUEST['PAYMENTCHANNEL'];
						$trx['session_id']       = $_REQUEST['SESSIONID'];
						$trx['ip_address']       = $this->getipaddress();
						$trx['process_datetime'] = date("Y-m-d H:i:s");
						$trx['process_type']     = 'REDIRECT';
						$status1 = $order->get_status();

						if ($status1=='completed'){
							$trx['message'] = "Redirect process come from DOKU. Transaction is Success for payment";  
							$status         = "completed";		
						} else if ($status1=='failed'){
							$trx['message'] = "Redirect process come from DOKU. Transaction is Failed for payment";  
							$status         = "failed";		
						} else {
							$trx['message'] = "Redirect process come from DOKU. Transaction is pending for payment";  
							$status         = "on-hold";		
						}

						$order->update_status($status, __( $trx['message'], 'woocommerce' ));										
						# $order->payment_complete($trx['transidmerchant']);										
						
						# Insert transaction redirect to table onecheckout
						$this->add_dokuonecheckout($trx);
						
						// get_header();
						// echo "<br /><br /><h3><center>$return_message<h3><br /><br /><br />";
						// get_footer();

						if( $status=='on-hold' || $status=='completed'){
							wp_redirect($this->get_return_url( $order ));
						} else {
						    wp_redirect($order->get_view_order_url());
						}
						die;
							}else{
								echo "Stop : Access Not Valid";
								die;
								}
						}
						
						$trx = array();
						
						$trx['words']                = $_POST['WORDS'];
						$trx['amount']               = $_POST['AMOUNT'];
						$trx['transidmerchant']      = $_POST['TRANSIDMERCHANT']; 
						$trx['status_code']          = $_POST['STATUSCODE'];
						
						if ( isset($_POST['PAYMENTCODE']) ) $trx['payment_code'] = $_POST['PAYMENTCODE'];
					
						$config = $this->getServerConfig();
						
						$words = sha1(trim($trx['amount']).
											trim($config['SHARED_KEY']).
											trim($trx['transidmerchant']).
											trim($trx['status_code']));	
						
						if ( $trx['words']==$trx['words'] )
						
						{
					
								$use_edu = $config['USE_EDU'];
								$order   = new WC_Order($trx['transidmerchant']);
								
								$trx['payment_channel']  = $_POST['PAYMENTCHANNEL'];
								$trx['session_id']       = $_POST['SESSIONID'];
								$trx['ip_address']       = $this->getipaddress();
								$trx['process_datetime'] = date("Y-m-d H:i:s");
								$trx['process_type']     = 'REDIRECT';
								
								$channel=$configarray[$trx['payment_channel']];
								$arrChannel = explode(",", $channel);
								$type = $arrChannel[1];

								# Skip notify checking for VA / ATM / ALFA Payment
								if ( in_array($type, array("M","DT")) && $trx['status_code'] == "5511" )
								{
										$trx['message'] = "Redirect process come from DOKU. Transaction is pending for payment";  
										$status         = "on-hold";									
										$return_message = "This is your Payment Code : ".$trx['payment_code']."<br>Please do the payment before transaction expired.<br>If you need help for payment, please contact our customer service.<br>";
										
										$this->add_dokuonecheckout($trx);
										
										$order->update_status($status, __( $trx['message'], 'woocommerce' ));
										
										# Insert transaction redirect to table onecheckout
										
										// get_header();
										// echo "<br /><br /><h3><center>$return_message<h3><br /><br /><br />";
										// get_footer();

										if( $order->has_status('on-hold') || $order->has_status('completed')){
											wp_redirect($this->get_return_url( $order ));
										} else {
										    wp_redirect($order->get_view_order_url());
										}

								}
								else if ( in_array($type, array("M","DT")) && $trx['status_code'] == "9999" ){
										$trx['message'] = "Redirect process come from DOKU. Transaction is failed for payment";  
										$status         = "failed";				
										$return_message = "Your payment is failed. Please check your payment detail or please try again later.";

										$this->add_dokuonecheckout($trx);

										$order->update_status($status, __( $trx['message'], 'woocommerce' ));
										
										# Insert transaction redirect to table onecheckout
										
										// get_header();
										// echo "<br /><br /><h3><center>$return_message<h3><br /><br /><br />";
										// get_footer();

										if( $order->has_status('on-hold') || $order->has_status('completed')){
											wp_redirect($this->get_return_url( $order ));
										} else {
										    wp_redirect($order->get_view_order_url());
										}
								}
								else
								{
																	
										switch ($trx['status_code'])
										{
												case "0000":
												$result_msg = "SUCCESS";
												break;
																								
												default:
												$result_msg = "FAILED";
												break;
										}
											
										$result = $this->checkTrx($trx, 'NOTIFY', $result_msg);
		
										if ( $result < 1 )
										{
												$check_result_msg = $this->doku_check_status($trx);
												$order            = new WC_Order($trx['transidmerchant']);										
		
												if ( $check_result_msg == 'SUCCESS' )
												{
														if ( $use_edu == "yes" )
														{					
																$trx['message'] = "Redirect process with no notify message come from DOKU. Transaction is Success, wait for EDU Verification. Please check on Back Office. With channel ".$trx['payment_channel']." = ".$arrChannel[0];  
																$status         = "on-hold";									
																$return_message = "Thank you for shopping with us. We will process your payment soon.";
																$this->clear_cart();
																
														}
														else
														{
																$trx['message'] = "Redirect process with no notify message come from DOKU. Transaction is Success. Please check on Back Office. With channel ".$trx['payment_channel']." = ".$arrChannel[0];  
																$status         = "completed";				
																$return_message = "Your payment is success. We will process your order. Thank you for shopping with us.";
																#$order->reduce_order_stock();
																$this->clear_cart();
														}				
												}
												else
												{
														if ( $trx['status_code']=="0000" && $check_result_msg == "NOT SUPPORT" )
														{
																$trx['message'] = "Redirect process with no notify message come from DOKU. Transaction got Success Status Code. Please check on Back Office. With channel ".$trx['payment_channel']." = ".$arrChannel[0]; 
																$status         = "pending";				
																$return_message = "Thank you for shopping with us. We will check your payment detail to complete the transaction.";
																$this->clear_cart();
														}
														else
														{
																$trx['message'] = "Redirect process with no notify message come from DOKU. Transaction is Failed. Please check on Back Office. With channel ".$trx['payment_channel']." = ".$arrChannel[0]; 
																#edited status di bawah dari cancelled jadi failed.
																$status         = "failed";				
																$return_message = "Your payment is failed. Please check your payment detail or please try again later.";
																$this->clear_cart();
														}
												}
										}
										else
										{								
												if ( $trx['status_code']=="0000" )
												{
														if ( $use_edu == "yes" )
														{					
																$trx['message'] = "Redirect process message come from DOKU. Transaction is Success, wait for EDU Verification with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
																$status         = "on-hold";									
																$return_message = "Thank you for shopping with us. We will process your payment soon.";
																$this->clear_cart();
														}
														else
														{
																$trx['message'] = "Redirect process message come from DOKU. Transaction is Success with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
																$status         = "completed";				
																$return_message = "Your payment is success. We will process your order. Thank you for shopping with us.";
																$this->clear_cart();
														}
												}
												# start add 5510
												else if	( $trx['status_code']=="5510" )
														{
																$trx['message'] = "Redirect process message come from DOKU. Transaction is Failed, Customer Click Cancel Button.";
																$status         = "cancelled";				
																$return_message = "Your payment is failed. Please check your payment detail or please try again later.";
																$this->clear_cart();
														}
														# end add 5510
														else
														{
																$trx['message'] = "Redirect process message come from DOKU. Transaction is Failed";
																#edited status di bawah dari cancelled jadi failed.  
																$status         = "failed";				
																$return_message = "Your payment is failed. Please check your payment detail or please try again later.";
																$this->clear_cart();
														}
																									
										}								
										
										$order->update_status($status, __( $trx['message'], 'woocommerce' ));										
										# $order->payment_complete($trx['transidmerchant']);										
										
										# Insert transaction redirect to table onecheckout
										$this->add_dokuonecheckout($trx);
										
										// get_header();
										// echo "<br /><br /><h3><center>$return_message<h3><br /><br /><br />";
										// get_footer();

										if( $order->has_status('on-hold') || $order->has_status('completed')){
											wp_redirect($this->get_return_url( $order ));
										} else {
										    wp_redirect($order->get_view_order_url());
										}										
								}
								
						}
						else
						{
							echo "Stop : Request Not Valid";
							die;
						}
						
				break;
				
				case "edureview":
						if ( empty($_POST) )
						{
							 echo "Stop : Access Not Valid";
							 die;
						}
														 
						if (substr($this->getipaddress(),0,strlen($this->ip_range)) !== $this->ip_range)
						{
								echo "Stop : IP Not Allowed : ".$this->getipaddress();
						}
						else
						{
								$config = $this->getServerConfig();
								
								$use_edu = $config['USE_EDU'];
								if ( $use_edu=="no" )
								{
										echo "Stop : Access Not Authenticate";
										die;
								}								
								
								$trx = array();
								
								$trx['words']                     = $_POST['WORDS'];
								$trx['amount']                    = $_POST['AMOUNT'];
								$trx['transidmerchant']           = $_POST['TRANSIDMERCHANT'];
								$trx['result_msg']                = $_POST['RESULTMSG'];            
								$trx['verify_status']             = $_POST['VERIFYSTATUS'];        																
								
								$words = sha1(trim($trx['amount']).
															trim($config['MALL_ID']).
															trim($config['SHARED_KEY']).
															trim($trx['transidmerchant']).
															trim($trx['result_msg']).
															trim($trx['verify_status']));
								
								if ( $trx['words']==$words )
								{            
										$trx['ip_address']            = $this->getipaddress();
										$trx['response_code']         = $_POST['RESPONSECODE'];
										$trx['approval_code']         = $_POST['APPROVALCODE'];
										$trx['payment_channel']       = $_POST['PAYMENTCHANNEL'];
										$trx['payment_code']          = $_POST['PAYMENTCODE'];
										$trx['session_id']            = $_POST['SESSIONID'];
										$trx['bank_issuer']           = $_POST['BANK'];
										$trx['creditcard']            = $_POST['MCN'];                   
										$trx['doku_payment_datetime'] = $_POST['PAYMENTDATETIME'];
										$trx['process_datetime']      = date("Y-m-d H:i:s");
										$trx['verify_id']             = $_POST['VERIFYID'];
										$trx['verify_score']          = (int) $_POST['VERIFYSCORE'];
										$trx['notify_type']           = $_POST['STATUSTYPE'];
										$trx['process_type'] 					= 'REVIEW';
										
										$result = $this->checkTrx($trx);

										if ( $result < 1 )
										{
												echo "Stop : Transaction Not Found";
												die;            
										}
										else
										{
												$order      = new WC_Order($trx['transidmerchant']);	
												$channel=$configarray[$trx['payment_channel']];
												$arrChannel = explode(",", $channel);
												$type = $arrChannel[1];									
												
												switch (TRUE)
												{
														case ( $trx['verify_status']=="APPROVE" ):
														$trx['message'] = "EDU Review process message come from DOKU. DOKU Payment and Verification Success : ".$trx['verify_status'] ." with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
														$status         = "completed";
														$order->payment_complete($trx['transidmerchant']);
														#$order->reduce_order_stock();
														break;
			
														case ( $trx['verify_status']=="REVIEW" ):
														$trx['message'] = "EDU Review process message come from DOKU. DOKU Payment Success with no history card data : ".$trx['verify_status'] ." with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
														$status         = "completed";
														$order->payment_complete($trx['transidmerchant']);
														#$order->reduce_order_stock();
														break;
			
														case ( $trx['verify_status']=="REJECT" || $trx['verify_status']=="HIGHRISK" || $trx['verify_status']=="NA" ):
														$trx['message'] = "EDU Review process message come from DOKU. DOKU Verification result is bad : ".$trx['verify_status']." with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
														$status         = "cancelled";
														break;
			
														default:
														$trx['message'] = "EDU Review process message come from DOKU. DOKU Verification result is bad : ". $trx['verify_status']." with channel ".$trx['payment_channel']." = ".$arrChannel[0];  
														$status         = "cancelled";
														break;														
												}
						
												$order->update_status($status, __( $trx['message'], 'woocommerce' ));
												# $order->payment_complete($trx['transidmerchant']);
												
												# Insert transaction notify to table onecheckout
												$this->add_dokuonecheckout($trx);
												
												echo "Continue";
										}
								}
								else
								{
										echo "Stop : Request Not Valid";
										die;
								}
						}						
				break;	

				case "request1";
$transidmerchant= $_POST['TRANSIDMERCHANT'];
				$order   = new WC_Order($transidmerchant);
						$status1 = $order->get_status();
						$trx['message'] = "EDU Review process message come from DOKU. DOKU Payment";  
						$status         = "completed";
						$order->update_status($status, __( $trx['message'], 'woocommerce' ));
						$status1 = $order->get_status();
						echo $status1."    ".$transidmerchant;
				break;
				case "request";
				$payment=$_POST['PAYMENTCHANNEL'];
				if (!$payment){
					require_once(dirname(__FILE__) . "/channel/doku_hosted.php");
				}
				$arrpayment=explode("_", $payment);
				$paymentchannel = $arrpayment[1];
				if (!$paymentchannel){
				$channel=$configarray[$payment];
				$arrChannel = explode(",", $channel);
				$type = $arrChannel[1];
				$type_desc = $configarray[$type];

				require_once(dirname(__FILE__) . "/channel/".$type_desc.".php");
			} else {
				if ($arrpayment[0]=='offus'){
				require_once(dirname(__FILE__) . "/channel/installmentoffus.php");
				} else {
				require_once(dirname(__FILE__) . "/channel/installment.php");
				}				
			}
				break;

				default:
						echo "Stop : Request Not Recognize";
				break;
		}
}

?>