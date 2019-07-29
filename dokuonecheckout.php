<?php

/*
Plugin Name: DOKU Payment Gateway
Plugin URI: http://www.doku.com
Description: DOKU Payment Gateway plugin extentions for woocommerce and Wordpress version 5.x
Version: 2
Author: DOKU
Author URI: http://www.doku.com
 
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
 
//database
function install() 
{
	global $wpdb;
	global $db_version;
	$db_version = "1.0";
 	$table_name = $wpdb->prefix . "dokuonecheckout";
	$sql = "
		CREATE TABLE $table_name (
		  trx_id int( 11 ) NOT NULL AUTO_INCREMENT,
		  ip_address VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  process_type VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  process_datetime DATETIME NULL, 
		  doku_payment_datetime DATETIME NULL,   
		  transidmerchant VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  amount DECIMAL( 20,2 ) NOT NULL DEFAULT '0',
		  notify_type VARCHAR( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  response_code VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  status_code VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  result_msg VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  reversal INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  approval_code CHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  payment_channel VARCHAR( 2 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  payment_code VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  bank_issuer VARCHAR( 100 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  creditcard VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  words VARCHAR( 200 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',  
		  session_id VARCHAR( 48 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  verify_id VARCHAR( 30 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  verify_score INT( 3 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  verify_status VARCHAR( 10 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  check_status INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			count_check_status INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  message TEXT COLLATE utf8_unicode_ci,  
		  PRIMARY KEY (trx_id)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1		
		
	";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('dokuonecheckout_db_version', $db_version);
}

function uninstall() 
{
	delete_option('dokuonecheckout_db_version');
	global $wpdb;
	$table_name = $wpdb->prefix . "dokuonecheckout";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_activation_hook( __FILE__, 'install');
register_uninstall_hook(  __FILE__, 'uninstall');

add_action('plugins_loaded', 'woocommerce_gateway_dokuonecheckout_init', 0);

function woocommerce_gateway_dokuonecheckout_init() 
{
	
		if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	 
		/**
		 * Localisation
		 */
		load_plugin_textdomain('wc-gateway-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
		
		/**
		 * Gateway class
		 */
		class WC_dokuonecheckout_Gateway extends WC_Payment_Gateway 
		{
				public function __construct() 
				{				
						$this->id = 'dokuonecheckout';
						$this->ip_range = "103.10.129.";
					 	$this->method_title = 'dokuonecheckout';
						$this->has_fields = true;     // false

						$this->init_form_fields();
						$this->init_settings();
						$this->installmentonus = get_option(
							'dokuonecheckout_installmentonus',
							array(
								array(
									'desc_inc'   => $this->get_option( 'desc_inc' ),
									'acq_code' => $this->get_option( 'acq_code' ),
									'promo_code' => $this->get_option( 'promo_code' ),
									'tenor_inc'  => $this->get_option( 'tenor_inc' ),
								),
							)
						);

						$this->installmentoffus = get_option(
							'dokuonecheckout_installmentoffus',
							array(
								array(
									'desc_inc'   => $this->get_option( 'desc_inc' ),
									'acq_code' => $this->get_option( 'acq_code' ),
									'promo_code' => $this->get_option( 'promo_code' ),
									'tenor_inc'  => $this->get_option( 'tenor_inc' ),
								),
							)
						);

						$this->title       = $this->settings['name'];
						$this->instructions = $this->get_option( 'name' );

						$this->method_description = 'Easily accept payments on your WordPress site via DOKU payment gateway. Pay via Credit Card, Instant Installment, Conventional Store, Bank Transfer and Doku Wallet. Anything you want!';
						$this->description = 'Pay With DOKU.<br>
																	DOKU is an online payment platform that processes payments through many different methods, including Credit Card, ATM Transfer and DOKU Wallet.<br>
																	Check us out on <a href="http://www.doku.com">http://www.doku.com</a>';
						
						if ( empty($this->settings['server_dest']) || $this->settings['server_dest'] == '0' || $this->settings['server_dest'] == 0 )
						{
								$this->mall_id     = trim($this->settings['mall_id_dev']);
								$this->shared_key  = trim($this->settings['shared_key_dev']);
								$this->chain       = trim($this->settings['chain_dev']);
								$this->url				 = "https://staging.doku.com/Suite/Receive";
						}
						else
						{
								$this->mall_id     = trim($this->settings['mall_id_prod']);
								$this->shared_key  = trim($this->settings['shared_key_prod']);
								$this->chain       = trim($this->settings['chain_prod']);
								$this->url				 = "https://pay.doku.com/Suite/Receive";
						}

						$pattern = "/([^a-zA-Z0-9]+)/";
						$result  = preg_match($pattern, $this->prefixid, $matches, PREG_OFFSET_CAPTURE);
						
						add_action('init', array(&$this, 'check_dokuonecheckout_response'));
						add_action('valid_dokuonecheckout_request', array(&$this, 'sucessfull_request'));	
						add_action('woocommerce_receipt_dokuonecheckout', array(&$this, 'receipt_page'));
						
						if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
						{
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'save_installmentonus' ) );
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'save_installmentoffus' ) );

						} 
						else 
						{
								add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'save_installmentonus' ) );
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'save_installmentoffus' ) );
						}
						
						add_action( 'woocommerce_api_wc_dokuonecheckout_gateway', array( &$this, 'dokuonecheckout_callback' ) );
						add_action( 'woocommerce_thankyou_dokuonecheckout', array( $this, 'thankyou_page' ) );
						add_action( 'woocommerce_email_after_order_table', 'wc_add_payment_type_to_admin_emails', 15, 2 );
						add_action( 'woocommerce_email_before_order_table', array( $this, 'dokuonecheckout_email' ), 10, 2 );

						//add_action( 'woocommerce_email_after_order_table', 'wc_add_payment_type_to_admin_emails', 15, 2 );
						// add_action( 'woocommerce_email_before_order_table', array( $this, 'dokuonecheckout_email' ), 10, 2 );
						// add_action( 'woocommerce_email_before_order_table', 'dokuonecheckout_email', 10, 2 );
				}
				
			/**
			 * Initialisation form for Gateway Settings
			 */	
				function init_form_fields() 
				{
						$configarray = parse_ini_file("config.ini");
						$channel=$configarray['channel'];
						$arrChannel = explode(",", $configarray['channel']);
					$this->form_fields = array(
							'enabled' => array(
									'title' => __( 'Enable/Disable', 'woocommerce' ),
									'type' => 'checkbox',
									'label' => __( 'Enable DOKU Payment Gateway', 'woocommerce' ),
									'default' => 'yes'
							),
							'name' => array(
									'title' => __('Payment Name ', 'woocommerce'),
									'type' => 'text',
									'description' => __('Payment name to be displayed when checkout.', 'woocommerce'),
									'default' => 'DOKU Payment Gateway',
									'desc_tip' => true,
							),					
							'server_dest' => array(
									'title' => __( 'Server Destination', 'woocommerce' ),
									'type' => 'select',
									'description' => __( 'Choose server destination developmet or production.', 'woocommerce' ),
									'options' => array(
														'0' => __( 'Development', 'woocommerce' ),
														'1' => __( 'Production', 'woocommerce' )
									),
									'desc_tip' => true,
							),
							'edu' => array(
									'title' => __( 'EDU Service', 'woocommerce' ),
									'type' => 'checkbox',
									'description' => __( 'Early Detection Unit (EDU) is system that providing a verification process and analyzing of all transactions.', 'woocommerce' ),
									'default' => 'no'
							),							
							'identify' => array(
									'title' => __( 'Identify', 'woocommerce' ),
									'type' => 'checkbox',
									'description' => __( 'Identify is an additional service that allows you to identify the payment channel that customer has chosen.', 'woocommerce' ),
									'default' => 'no'
							),			
							'15' => array(
									'title' => __( explode(",", $configarray['15'])[0], 'woocommerce' ),
									'type' => 'checkbox',
									'label' => __( 'Enable Payment Channel '.explode(",", $configarray['15'])[0], 'woocommerce' ),
									'default' => 'yes'
							),																		
							'tokenization' => array(
									'title' => __( 'Credit Card With Tokenization', 'woocommerce' ),
									'type' => 'checkbox',
									'description' => __( 'Are you using DOKU Credit Card with Tokenization? Unchecked if you unsure.', 'woocommerce' ),
									'default' => 'no'
							),	
							'installment' => array(
									'title' => __( 'installment', 'woocommerce' ),
									'type' => 'checkbox',
									'description' => __( 'Are you using Installment? Unchecked if you unsure.	', 'woocommerce' ),
									'default' => 'no'
							),
							'minimum_installment' => array(
									'title' => __('Minimum amount transaksi installment ', 'woocommerce'),
									'type' => 'text',
									'description' => __('Minimum Amount for Transaction Installment', 'woocommerce'),
									'default' => '500000',
							),					
							'installmentonus' => array(
							'type' => 'installmentonus',
							),
							'installmentoffus' => array(
							'type' => 'installmentoffus',
							),
							'virtualaccount' => array(
									'title' => __( 'Channel Virtual Account', 'woocommerce' ),
									'type' => 'multiselect',
									'class'			=> 'wc-enhanced-select',
        							'css'			=> 'width: 450px;',
									'default' => '',
									'options'		=> array (
        							'22' => __( explode(",", $configarray['22'])[0], 'woocommerce' ),
									'29' => __( explode(",", $configarray['29'])[0], 'woocommerce' ),
        							'32' => __( explode(",", $configarray['32'])[0], 'woocommerce' ),
									'33' => __( explode(",", $configarray['33'])[0], 'woocommerce' ),
        							'34' => __( explode(",", $configarray['34'])[0], 'woocommerce' ),
									'36' => __( explode(",", $configarray['36'])[0], 'woocommerce' ),
        							'40' => __( explode(",", $configarray['40'])[0], 'woocommerce' ),
									'41' => __( explode(",", $configarray['41'])[0], 'woocommerce' ),
									'42' => __( explode(",", $configarray['42'])[0], 'woocommerce' ),
        							'43' => __( explode(",", $configarray['43'])[0], 'woocommerce' ),
									'44' => __( explode(",", $configarray['44'])[0], 'woocommerce' )
       								),
       								'custom_attributes' => array(
          							'data-placeholder' => __( 'Select some channel Virtual Account', 'woocommerce' ),
        							),
							),
							'internetbanking' => array(
									'title' => __( 'Channel Internet Banking', 'woocommerce' ),
									'type' => 'multiselect',
									'class'			=> 'wc-enhanced-select',
        							'css'			=> 'width: 450px;',
									'default' => '',
									'options'		=> array (
        							'02' => __( explode(",", $configarray['02'])[0], 'woocommerce' ),
									// '03' => __( explode(",", $configarray['03'])[0], 'woocommerce' ),
        							'06' => __( explode(",", $configarray['06'])[0], 'woocommerce' ),
									'18' => __( explode(",", $configarray['18'])[0], 'woocommerce' ),
        							'19' => __( explode(",", $configarray['19'])[0], 'woocommerce' ),
									'25' => __( explode(",", $configarray['25'])[0], 'woocommerce' ),
        							'26' => __( explode(",", $configarray['26'])[0], 'woocommerce' ),
									'28' => __( explode(",", $configarray['28'])[0], 'woocommerce' )
       								),
       								'custom_attributes' => array(
         							'data-placeholder' => __( 'Select some channel Internet Banking', 'woocommerce' ),
        							),
							),
							'store' => array(
									'title' => __( 'Channel Convenience store', 'woocommerce' ),
									'type' => 'multiselect',
									'class'			=> 'wc-enhanced-select',
        							'css'			=> 'width: 450px;',
									'default' => '',
									'options'		=> array (
        							'31' => __( explode(",", $configarray['31'])[0], 'woocommerce' ),
									'35' => __( explode(",", $configarray['35'])[0], 'woocommerce' )
       								),
       								'custom_attributes' => array(
          							'data-placeholder' => __( 'Select some channel Convenience Store', 'woocommerce' ),
        							),

							),
							'cardless' => array(
									'title' => __( 'Channel Cardless Credit', 'woocommerce' ),
									'type' => 'multiselect',
									'class'			=> 'wc-enhanced-select',
        							'css'			=> 'width: 450px;',
									'default' => '',
									'options'		=> array (
        							'37' => __( explode(",", $configarray['37'])[0], 'woocommerce' )
       								),
       								'custom_attributes' => array(
         						 	'data-placeholder' => __( 'Select some channel Cardless Credit', 'woocommerce' ),
        							),

							),
							'wallet' => array(
									'title' => __( 'Channel Wallet', 'woocommerce' ),
									'type' => 'multiselect',
									'class'			=> 'wc-enhanced-select',
        							'css'			=> 'width: 450px;',
									'default' => '',
									'options'		=> array (
        							'04' => __( explode(",", $configarray['04'])[0], 'woocommerce' ),
									// '45' => __( explode(",", $configarray['45'])[0], 'woocommerce' )
       								),
       								'custom_attributes' => array(
						          	'data-placeholder' => __( 'Select some channel Wallet', 'woocommerce' ),
        							),

							),
							'mall_id_dev' => array(
									'title' => __( 'Mall ID Development', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID Development get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_dev' => array(
									'title' => __( 'Shared Key Development', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key Development get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_dev' => array(
									'title' => __('Chain Number Development', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number Development get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),							
							'mall_id_prod' => array(
									'title' => __( 'Mall ID Production', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID Production get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_prod' => array(
									'title' => __( 'Shared Key Production', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key Production get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_prod' => array(
									'title' => __('Chain Number Production', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number Production get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),
							'mall_id_merchant_hosted_va' => array(
									'title' => __( 'Mall ID for Merchant Hosted Virtual Account', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID Merchant Hosted Virtual Account get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_merchant_hosted_va' => array(
									'title' => __( 'Shared Key Merchant Hosted Virtual Account', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key Merchant Hosted Virtual Account get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_merchant_hosted_va' => array(
									'title' => __('Chain Number Merchant Hosted Virtual Account', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number Merchant Hosted Virtual Account get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),							
							'mall_id_bca_klikpay' => array(
									'title' => __( 'Mall ID BCA Klikpay', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID BCA Klikpay get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_bca_klikpay' => array(
									'title' => __( 'Shared Key BCA Klikpay', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key BCA Klikpay get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_bca_klikpay' => array(
									'title' => __('Chain Number BCA Klikpay', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number BCA Klikpay get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),							
							'mall_id_kredivo' => array(
									'title' => __( 'Mall ID Kredivo', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID Kredivo get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_kredivo' => array(
									'title' => __( 'Shared Key Kredivo', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key Kredivo get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_kredivo' => array(
									'title' => __('Chain Number Kredivo', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number Kredivo get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),							
							'mall_id_bni_yap' => array(
									'title' => __( 'Mall ID BNI YAP', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID BNI YAP get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_bni_yap' => array(
									'title' => __( 'Shared Key BNI YAP', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key BNI YAP get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_bni_yap' => array(
									'title' => __('Chain Number BNI YAP', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number BNI YAP get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),							
							'mall_id_klikbca' => array(
									'title' => __( 'Mall ID KlikBCA', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Mall ID KlikBCA get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key_klikbca' => array(
									'title' => __( 'Shared Key KlikBCA', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key KlikBCA get from DOKU.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'chain_klikbca' => array(
									'title' => __('Chain Number KlikBCA', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input Chain Number KlikBCA get from DOKU.', 'woocommerce'),
									'default' => 'NA',
									'desc_tip' => true,
							),	
					);
					
				}
			
				public function admin_options() 
				{
						echo '<h2>'.__('DOKU Payment Gateway', 'woocommerce').'</h2>';
						echo '<p>' .__('DOKU is an online payment that can process many kind of payment method, include Credit Card, ATM Transfer and DOKU Wallet.<br>
														Check us at <a href="http://www.doku.com">http://www.doku.com</a>', 'woocommerce').'</p>';
						
						echo "<h3>dokuonecheckout Parameter</h3><br>\r\n";
						
						echo '<table class="form-table">';
						$this->generate_settings_html();
						echo '</table>';
						
						// URL                             
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
						
						echo "<h3>URL to put at DOKU Server</h3><br>\r\n";
						echo "<table>\r\n";
						echo "<tr><td width=\"100\">Verify URL</td><td width=\"3\">:</td><td>$mainurl/?wc-api=wc_dokuonecheckout_gateway&task=identify</td></tr>\r\n";
						echo "<tr><td>Notify URL</td><td>:</td><td>$mainurl/?wc-api=wc_dokuonecheckout_gateway&task=notify</td></tr>\r\n";
						echo "<tr><td>Redirect URL</td><td>:</td><td>$mainurl/?wc-api=wc_dokuonecheckout_gateway&task=redirect</td></tr>\r\n";
						echo "<tr><td>Review URL</td><td>:</td><td>$mainurl/?wc-api=wc_dokuonecheckout_gateway&task=edureview</td></tr>\r\n";
						echo "</table>";
						
				}
				/**
				 * Generate installment details html.
				 *
				 * @return string
				 */
				public function generate_installmentonus_html() 
				{
					ob_start();
					?>
					<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Installment on us', 'woocommerce' ); ?></th>
					<td class="forminp" id="dokuonecheckout_installmentonus">
					<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Name Description', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Acquirer Code Bank', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Promo Code', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Tenor', 'woocommerce' ); ?></th>
							</tr>
						</thead>
											<tbody class="installmentonus">
							<?php
							$i = -1;
							if ( $this->installmentonus ) {
								foreach ( $this->installmentonus as $installment ) {
									$i++;

									echo '<tr class="installmentonus">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( $installment['desc_inc'] ) . '" name="dokuonecheckout_installmentonus_desc[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['acq_code'] ) . '" name="dokuonecheckout_installmentonus_acq_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['promo_code'] ) . '" name="dokuonecheckout_installmentonus_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['tenor_inc'] ) . '" name="dokuonecheckout_installmentonus_tenor[' . esc_attr( $i ) . ']" /></td>
									</tr>';
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add Installment OnUs', 'woocommerce' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected Installment OnUs Config(s)', 'woocommerce' ); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#dokuonecheckout_installmentonus').on( 'click', 'a.add', function(){

							var size = jQuery('#dokuonecheckout_installmentonus').find('tbody .installmentonus').length;

							jQuery('<tr class="installmentonus">\
									<td class="sort"></td>\
									<td><input type="text" name="dokuonecheckout_installmentonus_desc[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentonus_acq_code[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentonus_code[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentonus_tenor[' + size + ']" /></td>\
								</tr>').appendTo('#dokuonecheckout_installmentonus table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
					<?php
		return ob_get_clean();
				}

					/**
			 * Save account details table.
	 		 */
		public function save_installmentonus() {

		$installment = array();

		if ( isset( $_POST['dokuonecheckout_installmentonus_desc'] ) && isset( $_POST['dokuonecheckout_installmentonus_acq_code'] ) && isset( $_POST['dokuonecheckout_installmentonus_code'] ) && isset( $_POST['dokuonecheckout_installmentonus_tenor'] ) ) {

			$desc_inc   = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentonus_desc'] ) );
			$acq_code = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentonus_acq_code'] ) );
			$promo_code = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentonus_code'] ) );
			$tenor_inc  = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentonus_tenor'] ) );

			foreach ( $desc_inc as $i => $name ) {
				if ( ! isset( $desc_inc[ $i ] ) ) {
					continue;
				}

				$installment[] = array(
					'desc_inc'   => $desc_inc[ $i ],
					'acq_code' => $acq_code[ $i ],
					'promo_code' => $promo_code[ $i ],
					'tenor_inc'  => $tenor_inc[ $i ],
				);
			}
		}
		// phpcs:enable

		update_option( 'dokuonecheckout_installmentonus', $installment );
	}

								/**
				 * Generate installment details html.
				 *
				 * @return string
				 */
				public function generate_installmentoffus_html() 
				{
					ob_start();
					?>
					<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Installment off us', 'woocommerce' ); ?></th>
					<td class="forminp" id="dokuonecheckout_installmentoffus">
					<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Name Description', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Acquirer Code Bank', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Promo Code', 'woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Tenor', 'woocommerce' ); ?></th>
							</tr>
						</thead>
											<tbody class="installmentoffus">
							<?php
							$i = -1;
							if ( $this->installmentoffus ) {
								foreach ( $this->installmentoffus as $installment ) {
									$i++;

									echo '<tr class="installmentoffus">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( $installment['desc_inc'] ) . '" name="dokuonecheckout_installmentoffus_desc[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['acq_code'] ) . '" name="dokuonecheckout_installmentoffus_acq_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['promo_code'] ) . '" name="dokuonecheckout_installmentoffus_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $installment['tenor_inc'] ) . '" name="dokuonecheckout_installmentoffus_tenor[' . esc_attr( $i ) . ']" /></td>
									</tr>';
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add Installment OffUs', 'woocommerce' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected Installment OffUs Config(s)', 'woocommerce' ); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#dokuonecheckout_installmentoffus').on( 'click', 'a.add', function(){

							var size = jQuery('#dokuonecheckout_installmentoffus').find('tbody .installmentoffus').length;

							jQuery('<tr class="installmentoffus">\
									<td class="sort"></td>\
									<td><input type="text" name="dokuonecheckout_installmentoffus_desc[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentoffus_acq_code[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentoffus_code[' + size + ']" /></td>\
									<td><input type="text" name="dokuonecheckout_installmentoffus_tenor[' + size + ']" /></td>\
								</tr>').appendTo('#dokuonecheckout_installmentoffus table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
					<?php
		return ob_get_clean();
				}

					/**
			 * Save account details table.
	 		 */
		public function save_installmentoffus() {

		$installment = array();

		if ( isset( $_POST['dokuonecheckout_installmentoffus_desc'] ) && isset( $_POST['dokuonecheckout_installmentoffus_acq_code'] ) && isset( $_POST['dokuonecheckout_installmentoffus_code'] ) && isset( $_POST['dokuonecheckout_installmentoffus_tenor'] ) ) {

			$desc_inc   = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentoffus_desc'] ) );
			$acq_code = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentoffus_acq_code'] ) );
			$promo_code = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentoffus_code'] ) );
			$tenor_inc  = wc_clean( wp_unslash( $_POST['dokuonecheckout_installmentoffus_tenor'] ) );

			foreach ( $desc_inc as $i => $name ) {
				if ( ! isset( $desc_inc[ $i ] ) ) {
					continue;
				}

				$installment[] = array(
					'desc_inc'   => $desc_inc[ $i ],
					'acq_code' => $acq_code[ $i ],
					'promo_code' => $promo_code[ $i ],
					'tenor_inc'  => $tenor_inc[ $i ],
				);
			}
		}
		// phpcs:enable

		update_option( 'dokuonecheckout_installmentoffus', $installment );
	}
				/**
				* Generate form
				*
				* @param mixed $order_id
				* @return string
				*/

			
				public function generate_dokuonecheckout_form($order_id) 
				{
					
						global $woocommerce;
						global $wpdb;
						static $basket;
		
						$order = new WC_Order($order_id);
						$counter = 0;
		
						$BASKET = "";
						
						// Order Items
						if( sizeof( $order->get_items() ) > 0 )
						{
								foreach( $order->get_items() as $item )
								{
										$item_name = preg_replace("/([^a-zA-Z0-9.\-=:&% ]+)/", " ", $item['name']);						
										$BASKET .= $item_name . "," . number_format($order->get_item_subtotal($item), 2, '.', '') . "," . $item['qty'] . "," . number_format($order->get_item_subtotal($item)*$item['qty'], 2, '.', '') . ";";
								}
						}
						
						// Shipping Fee
						if( $order->order_shipping > 0 )
						{
								$BASKET .= "Shipping Fee," . number_format($order->order_shipping, 2, '.', '') . ",1," . number_format($order->order_shipping, 2, '.', '') . ";";
						}					
						
						// Tax
						if( $order->get_total_tax() > 0 )
						{
								$BASKET .= "Tax," . $order->get_total_tax() . ",1," . $order->get_total_tax() . ";";
						}
			
						// Fees
						if ( sizeof( $order->get_fees() ) > 0 )
						{
								$fee_counter = 0;
								foreach ( $order->get_fees() as $item )
								{
										$fee_counter++;
										$BASKET .= "Fee Item," . $item['line_total'] . ",1," . $item['line_total'] . ";";																		
								}
						}
						
						$MALL_ID             = trim($this->mall_id);
						$SHARED_KEY          = trim($this->shared_key);
						$CHAIN               = trim($this->chain);
						$URL                 = $this->url;
						$CURRENCY            = 360;
						$TRANSIDMERCHANT     = $order_id;
						$NAME                = trim($order->billing_first_name . " " . $order->billing_last_name);
						$EMAIL               = trim($order->billing_email);
						$ADDRESS             = trim($order->billing_address_1 . " " . $order->billing_address_2);
						$CITY                = trim($order->billing_city);
						$ZIPCODE             = trim($order->billing_postcode);
						$STATE               = trim($order->billing_city);
						
						date_default_timezone_set('Asia/Jakarta');						
						$REQUEST_DATETIME    = date("YmdHis");
						$IP_ADDRESS          = $this->getipaddress();
						$PROCESS_DATETIME    = date("Y-m-d H:i:s");
						$PROCESS_TYPE        = "REQUEST";
						$AMOUNT              = number_format($order->order_total, 2, '.', '');
						$PHONE               = trim($order->billing_phone);
						$PAYMENT_CHANNEL     = "";
						$SESSION_ID          = COOKIEHASH;
						$WORDS               = sha1(trim($AMOUNT).
																				trim($MALL_ID).
																				trim($SHARED_KEY).
																				trim($TRANSIDMERCHANT));

						$dokuonecheckout_args = array(
							'MALLID'           => $MALL_ID,
							'CHAINMERCHANT'    => $CHAIN,
							'AMOUNT'           => $AMOUNT,
							'PURCHASEAMOUNT'   => $AMOUNT,
							'TRANSIDMERCHANT'  => $TRANSIDMERCHANT,
							'WORDS'            => $WORDS,
							'REQUESTDATETIME'  => $REQUEST_DATETIME,
							'CURRENCY'         => $CURRENCY,
							'PURCHASECURRENCY' => $CURRENCY,
							'SESSIONID'        => $SESSION_ID,
							// 'PAYMENTCHANNEL'   => $PAYMENT_CHANNEL,
							'NAME'             => $NAME,
							'EMAIL'            => $EMAIL,
							'HOMEPHONE'        => $PHONE,
							'MOBILEPHONE'      => $PHONE,
							'BASKET'           => $BASKET,
							'ADDRESS'          => $ADDRESS,
							'CITY'             => $CITY,
							'STATE'            => $STATE,
							'ZIPCODE'          => $ZIPCODE							
						);

						$trx['ip_address']          = $IP_ADDRESS;
						$trx['process_type']        = $PROCESS_TYPE;
						$trx['process_datetime']    = $PROCESS_DATETIME;
						$trx['transidmerchant']     = $TRANSIDMERCHANT;
						$trx['amount']              = $AMOUNT;
						$trx['session_id']          = $SESSION_ID;
						$trx['words']               = $WORDS;
						$trx['message']             = "Transaction request start";

						# Insert transaction request to table dokuonecheckout
						$this->add_dokuonecheckout($trx);					
						
						// Form
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

						$configarray = parse_ini_file("config.ini");
						$dokuonecheckout_args_array = array();
						foreach($dokuonecheckout_args as $key => $value)
						{
								$dokuonecheckout_args_array[] = "<input type='hidden' name='$key' value='$value' />";
						}

						$channel_virtualaccount_array = array();
						if ($this->settings['virtualaccount']){
							$channel_virtualaccount_array[]='<a>Payment With Virtual Account</a><br>';
							$countva=count($this->settings['virtualaccount'])-1;
							for ($x = 0; $x <= $countva; $x++) {
								$paymentchannel=$this->settings['virtualaccount'][$x];
								$channelDesc=$configarray[$paymentchannel];
								$arrChannelDesc = explode(",", $channelDesc);
								$images = $mainurl."/wp-content/plugins/dokuonecheckout/images/".$paymentchannel.".png";
							$channel_virtualaccount_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$paymentchannel' />&nbsp;&nbsp;<img src='$images' style='width:80px;'> &nbsp;&nbsp; $arrChannelDesc[0]<br>";
							}
							$channel_virtualaccount_array[] ='<br>';
						}

						
						$channel_store_array = array();
						if ($this->settings['store']){
							$channel_store_array[]='<a>Payment With Convenience Store</a><br>';
							$countva=count($this->settings['store'])-1;
							for ($x = 0; $x <= $countva; $x++) {
								$paymentchannel=$this->settings['store'][$x];
								$channelDesc=$configarray[$paymentchannel];
								$arrChannelDesc = explode(",", $channelDesc);
								$images = $mainurl."/wp-content/plugins/dokuonecheckout/images/".$paymentchannel.".png";
							$channel_store_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$paymentchannel' /> &nbsp;&nbsp;<img src='$images' style='width:80px;'> &nbsp;&nbsp; $arrChannelDesc[0]<br>";
							}
							$channel_store_array[] ='<br>';
						}


						$channel_internetbanking_array = array();
						if ($this->settings['internetbanking']){
							$channel_internetbanking_array[]='<a>Payment With Internet Banking</a><br>';
							$countib=count($this->settings['internetbanking'])-1;
							for ($x = 0; $x <= $countib; $x++) {
								$paymentchannel=$this->settings['internetbanking'][$x];
								$channelDesc=$configarray[$paymentchannel];
								$arrChannelDesc = explode(",", $channelDesc);
								$images = $mainurl."/wp-content/plugins/dokuonecheckout/images/".$paymentchannel.".png";
							$channel_internetbanking_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$paymentchannel' /> &nbsp;&nbsp;<img src='$images' style='width:80px;'> &nbsp;&nbsp; $arrChannelDesc[0]<br>";
							}
							$channel_internetbanking_array[] ='<br>';
						}

						$channel_cardless_credit_array = array();
						if ($this->settings['cardless']){
							$channel_cardless_credit_array[]='<a>Payment With Cardless Credit</a><br>';
							$countib=count($this->settings['cardless'])-1;
							for ($x = 0; $x <= $countib; $x++) {
								$paymentchannel=$this->settings['cardless'][$x];
								$channelDesc=$configarray[$paymentchannel];
								$arrChannelDesc = explode(",", $channelDesc);
								$images = $mainurl."/wp-content/plugins/dokuonecheckout/images/".$paymentchannel.".png";
							$channel_cardless_credit_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$paymentchannel' /> &nbsp;&nbsp;<img src='$images' style='width:80px;'> &nbsp;&nbsp; $arrChannelDesc[0]<br>";
							}
							$channel_cardless_credit_array[] ='<br>';
						}

						$channel_wallet_array = array();
						if ($this->settings['wallet']){
							$channel_wallet_array[]='<a>Payment With Wallet</a><br>';
							$countib=count($this->settings['wallet'])-1;
							for ($x = 0; $x <= $countib; $x++) {
								$paymentchannel=$this->settings['wallet'][$x];
								$channelDesc=$configarray[$paymentchannel];
								$arrChannelDesc = explode(",", $channelDesc);
								$images = $mainurl."/wp-content/plugins/dokuonecheckout/images/".$paymentchannel.".png";
							$channel_wallet_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$paymentchannel' /> &nbsp;&nbsp;<img src='$images' style='width:80px;'> &nbsp;&nbsp; $arrChannelDesc[0]<br>";
							}
							$channel_wallet_array[] ='<br>';
						}

						$channel_creditcard_array = array();
						if ($this->settings['15']=='yes'){
							$images = $mainurl.'/wp-content/plugins/dokuonecheckout/images/visa-master.png';
							$channel_wallet_array[]="<a>Payment With Credit Card</a> &nbsp;&nbsp; <img src='$images' style='width:120px;'><br>";
								if ($this->settings['tokenization']=='yes'){
									$channelDesc=$configarray['16'];
									$arrChannelDesc = explode(",", $channelDesc);
							$channel_creditcard_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='16' /> $arrChannelDesc[0]<br>";
							}else{
									$channelDesc=$configarray['15'];
									$arrChannelDesc = explode(",", $channelDesc);
							$channel_creditcard_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='15' /> $arrChannelDesc[0]<br>";	
							}
						}
						$i = -1;
							if ( $this->settings['installment'] ) {
								if ($AMOUNT>=$this->settings['minimum_installment']) {
								foreach ( $this->installmentonus as $installment ) {
									$i++;
									$tenor_inc=$installment['tenor_inc'];
									$tenor=str_replace('0', '', $tenor_inc);
							$desc=$installment['desc_inc'].' '.$tenor.' bulan';
							$values='onus_'.$installment['acq_code'].'_'.$installment['tenor_inc'].'_'.$installment['promo_code'];
							if ($installment['acq_code'] && $installment['tenor_inc'] && $installment['promo_code']){
							$channel_creditcard_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$values' /> $desc<br>";
								}
							}
							foreach ( $this->installmentoffus as $installment ) {
									$i++;
									$tenor_inc=$installment['tenor_inc'];
									$tenor=str_replace('0', '', $tenor_inc);
							$desc=$installment['desc_inc'].' '.$tenor.' bulan';
							$values='offus_'.$installment['acq_code'].'_'.$installment['tenor_inc'].'_'.$installment['promo_code'];
							if ($installment['acq_code'] && $installment['tenor_inc'] && $installment['promo_code']){
							$channel_creditcard_array[] = "<input type='radio' name='PAYMENTCHANNEL' value='$values' /> $desc<br>";
								}
							}
						}
					}


						$this->url          = $mainurl.'/?wc-api=wc_dokuonecheckout_gateway&task=request';
						return '<form action="'.$this->url.'" method="post" id="dokuonecheckout_payment_form">'.
										'<h4>Please Choose Payment Channel</h4>'.
										implode(" \r\n", $dokuonecheckout_args_array).
										implode(" \r\n", $channel_virtualaccount_array).
										implode(" \r\n", $channel_store_array).
										implode(" \r\n", $channel_internetbanking_array).
										implode(" \r\n", $channel_cardless_credit_array).
										implode(" \r\n", $channel_wallet_array).
										implode(" \r\n", $channel_creditcard_array).
										'<br><input type="submit" class="button-alt" id="submit_dokuonecheckout_payment_form" value="'.__('Pay via DOKU', 'woocommerce').'" />
										<!--
										<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
										-->
										
										<script type="text/javascript">
										jQuery(function(){
										jQuery("body").block(
										{
												message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to dokuonecheckout to make payment.', 'woocommerce').'",
												overlayCSS:
										{
										background: "#fff",
										opacity: 0.6
										},
										css: {
													padding:        20,
													textAlign:      "center",
													color:          "#555",
													border:         "3px solid #aaa",
													backgroundColor:"#fff",
													cursor:         "wait",
													lineHeight:     "32px"
												}
										});
<!--										jQuery("#submit_dokuonecheckout_payment_form").click();}); -->
										</script>
										</form>';
									
			 
				}
			
				public function process_payment($order_id)
				{
						global $woocommerce;
						$order = new WC_Order($order_id);
						return array(
								'result' => 'success',
								'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
						);	
				}
			
				public function receipt_page($order)
				{
						echo $this->generate_dokuonecheckout_form($order);
				}

				public function thankyou_page( $order_id ) {
				$order      = new WC_Order($order_id);
				$status = $order->get_status();
				$text_status = "Status Transaction is ";
				echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $text_status.'<strong>'.$status.'</strong>' ) ) ) );
				if ($status=='on-hold'){
					$configarray = parse_ini_file("config.ini");
					$paymentcode=$this->checkTransactionPaymentCode($order_id);
					if ($paymentcode){
					$paymentchannel=$this->checkTransactionPaymentChannel($order_id);
					$channel=$configarray[$paymentchannel];
					$arrChannel = explode(",", $channel);
					$desc_channel = $arrChannel[0];
					$textpaymentcode='please make a payment using payment code : ';
					$textpaymentchannel=' with ';
				echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $textpaymentcode.'<strong>'.$paymentcode.'</strong>'.$textpaymentchannel .'<strong>'.$desc_channel.'</strong>') ) ) );
						}
					}

				}

			function dokuonecheckout_email( $order, $sent_to_admin, $plain_text = false)
				{
					// if( $order->get_payment_method() == 'dokuonecheckout' )
				$status = $order->get_status();
				$text_status = "Status Transaction is ";
				echo wp_kses_post( wpautop( wptexturize( $text_status.'<strong>'.$status.'</strong>' ) ) . PHP_EOL );
				if ($status=='on-hold'){
					$order_id = $order->get_id();
					$configarray = parse_ini_file("config.ini");
					$paymentcode=$this->checkTransactionPaymentCode($order_id);
					if ($paymentcode){
					$paymentchannel=$this->checkTransactionPaymentChannel($order_id);
					$channel=$configarray[$paymentchannel];
					$arrChannel = explode(",", $channel);
					$desc_channel = $arrChannel[0];
					$textpaymentcode='please make a payment using payment code : ';
					$textpaymentchannel=' with ';
				echo wp_kses_post( wpautop( wptexturize( $textpaymentcode.'<strong>'.$paymentcode.'</strong>'.$textpaymentchannel .'<strong>'.$desc_channel.'</strong>') ) . PHP_EOL );
				echo $paymentcode.$paymentchannel;
						}
					}
 				}


				function getServerConfig()
				{
						if ( empty($this->settings['server_dest']) || $this->settings['server_dest'] == '0' || $this->settings['server_dest'] == 0 )
						{
								$MALL_ID    	= trim($this->settings['mall_id_dev']);
								$SHARED_KEY 	= trim($this->settings['shared_key_dev']);
								$CHAIN      	= trim($this->settings['chain_dev']);
								$URL_CHECK  	= "http://staging.doku.com/Suite/CheckStatus";
								$DESTINATION 	= "DEVELOPMENT";
						}
						else
						{
								$MALL_ID    	= trim($this->settings['mall_id_prod']);
								$SHARED_KEY 	= trim($this->settings['shared_key_prod']);
								$CHAIN      	= trim($this->settings['chain_prod']);
								$URL_CHECK  	= "https://pay.doku.com/Suite/CheckStatus";
								$DESTINATION 	= "PRODUCTION";
						}  
						
						$MALLID_MHVA  			= trim($this->settings['mall_id_merchant_hosted_va']);
						$SHAREDKEY_MHVA 		= trim($this->settings['shared_key_merchant_hosted_va']);
						$CHAIN_MHVA     		= trim($this->settings['chain_merchant_hosted_va']);
						$MALLID_BCAKLIKPAY 		= trim($this->settings['mall_id_bca_klikpay']);
						$SHAREDKEY_BCAKLIKPAY 	= trim($this->settings['shared_key_bca_klikpay']);
						$CHAIN_BCAKLIKPAY 		= trim($this->settings['chain_bca_klikpay']);
						$MALLID_KREDIVO 		= trim($this->settings['mall_id_kredivo']);
						$SHAREDKEY_KREDIVO 		= trim($this->settings['shared_key_kredivo']);
						$CHAIN_KREDIVO     		= trim($this->settings['chain_kredivo']);
						$MALLID_KLIKBCA  		= trim($this->settings['mall_id_klikbca']);
						$SHAREDKEY_KLIKBCA 		= trim($this->settings['shared_key_klikbca']);
						$CHAIN_KLIKBCA     		= trim($this->settings['chain_klikbca']);
						$MALLID_BNIYAP  		= trim($this->settings['mall_id_bni_yap']);
						$SHAREDKEY_BNIYAP 		= trim($this->settings['shared_key_bni_yap']);
						$CHAIN_BNIYAP     		= trim($this->settings['chain_bni_yap']);
						$USE_EDU      			= trim($this->settings['edu']);
						$USE_IDENTIFY 			= trim($this->settings['identify']);
						
						$config = array( 				 "MALL_ID"      		=> $MALL_ID, 
														 "SHARED_KEY"   		=> $SHARED_KEY,
														 "CHAIN"        		=> $CHAIN,
														 "USE_EDU"      		=> $USE_EDU,
														 "USE_IDENTIFY" 		=> $USE_IDENTIFY,
                             							 "URL_CHECK"    		=> $URL_CHECK,
                             							 "MALLID_MHVA"  		=> $MALLID_MHVA,
														 "SHAREDKEY_MHVA"		=> $SHAREDKEY_MHVA,
														 "CHAIN_MHVA"   		=> $CHAIN_MHVA,
														 "MALLID_BCAKLIKPAY"	=> $MALLID_BCAKLIKPAY,
                             							 "SHAREDKEY_BCAKLIKPAY"	=> $SHAREDKEY_BCAKLIKPAY,
                             							 "CHAIN_BCAKLIKPAY"   	=> $CHAIN_BCAKLIKPAY,
														 "MALLID_KREDIVO"       => $MALLID_KREDIVO,
														 "SHAREDKEY_KREDIVO"    => $SHAREDKEY_KREDIVO,
														 "CHAIN_KREDIVO" 		=> $CHAIN_KREDIVO,
                             							 "MALLID_KLIKBCA"    	=> $MALLID_KLIKBCA,
                             							 "SHAREDKEY_KLIKBCA"   	=> $SHAREDKEY_KLIKBCA,
														 "CHAIN_KLIKBCA"        => $CHAIN_KLIKBCA,
														 "MALLID_BNIYAP"      	=> $MALLID_BNIYAP,
														 "SHAREDKEY_BNIYAP" 	=> $SHAREDKEY_BNIYAP,
                             							 "CHAIN_BNIYAP"    		=> $CHAIN_BNIYAP,
                              							 "DESTINATION"			=> $DESTINATION );  
										
						return $config;					
				}
				
				private function getipaddress()    
				{
						if (!empty($_SERVER['HTTP_CLIENT_IP']))
						{
							$ip=$_SERVER['HTTP_CLIENT_IP'];
						}
						elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
						{
							$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
						}
						else
						{
							$ip=$_SERVER['REMOTE_ADDR'];
						}
					
						return $ip;
				} 

				private function checkTrx($trx, $process='REQUEST', $result_msg='')
				{
						global $wpdb;
						
						if ( $result_msg == "PENDING" ) return 0;
					
						$db_prefix = $wpdb->prefix;
						
						$check_result_msg = "";
						if ( !empty($result_msg) )
						{
							$check_result_msg = " AND result_msg = '$result_msg'";
						}
						
						$wpdb->get_results("SELECT * FROM ".$db_prefix."dokuonecheckout" .
															 " WHERE process_type = '$process'" .
															 $check_result_msg.
															 " AND transidmerchant = '" . $trx['transidmerchant'] . "'" .
															 " AND amount = '". $trx['amount'] . "'".
															 " AND session_id = '". $trx['session_id'] . "'" );        
															 
						return $wpdb->num_rows;
				}
								
				private function add_dokuonecheckout($datainsert) 
				{
						global $wpdb;
						
						$SQL = "";
						
						foreach ( $datainsert as $field_name=>$field_data )
						{
								$SQL .= " $field_name = '$field_data',";
						}
						$SQL = substr( $SQL, 0, -1 );
				
						$wpdb->query("INSERT INTO ".$wpdb->prefix."dokuonecheckout SET $SQL");
				}
				
				private function getCheckStatusList($trx='')
				{
						global $wpdb;
						
						$db_prefix = $wpdb->prefix;
					
						$query = "";
						if ( !empty($trx) )
						{
								$query  = " AND transidmerchant = '".$trx['transidmerchant']."'";
								$query .= " AND amount = '". $trx['amount'] . "'";
								$query .= " AND session_id = '". $trx['session_id'] . "'";
						}
						else
						{
								$query  = " AND check_status = 0";
						}
						
						$result = $wpdb->get_row("SELECT * FROM ".$db_prefix."dokuonecheckout" .
													           " WHERE process_type = 'REQUEST'" .
													           $query.
													           " AND count_check_status < 3" );
						
						if ( $wpdb->num_rows > 0 )
						{
								return $result;
						}
						else
						{
								return 0;
						}
				}				

				function checkTransactionPaymentCode($order_id){
					global $wpdb;
					$db_prefix = $wpdb->prefix;
					$query="SELECT payment_code FROM ".$db_prefix."dokuonecheckout where transidmerchant=".$order_id." ORDER BY trx_id DESC LIMIT 1";
					$result = $wpdb->get_var($query);
					// echo $query;
					return $result;
				}
				function checkTransactionPaymentChannel($order_id){
					global $wpdb;
					$db_prefix = $wpdb->prefix;
					$query="SELECT payment_channel FROM ".$db_prefix."dokuonecheckout where transidmerchant=".$order_id." ORDER BY trx_id DESC LIMIT 1";
					$result = $wpdb->get_var($query);
					return $result;
				}
				private function updateCountCheckStatusTrx($trx)
				{
						global $wpdb;
						
						$db_prefix = $wpdb->prefix;
						
						$wpdb->get_results("UPDATE ".$db_prefix."dokuonecheckout" .
															 " SET count_check_status = count_check_status + 1,".
															 " check_status = 0".
															 " WHERE process_type = 'REQUEST'" .
															 " AND transidmerchant = '" . $trx['transidmerchant'] . "'" .
															 " AND amount = '". $trx['amount'] . "'".
															 " AND session_id = '". $trx['session_id'] . "'" );        
				}				

				private function doku_check_status($transaction)
				{
						$config = $this->getServerConfig();
						$result = $this->getCheckStatusList($transaction);
						
						if ( $result == 0 )
						{
								return "FAILED";
						}
						
						$trx     = $result;
						
						$words   = sha1( trim($config['MALL_ID']).
																		 trim($config['SHARED_KEY']).
																		 trim($trx->transidmerchant) );
																
						$data = "MALLID=".$config['MALL_ID']."&CHAINMERCHANT=".$config['CHAIN']."&TRANSIDMERCHANT=".$trx->transidmerchant."&SESSIONID=".$trx->session_id."&PAYMENTCHANNEL=&WORDS=".$words;
						
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $config['URL_CHECK']);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_POST, true);        
						curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
						$output = curl_exec($ch);
						$curl_errno = curl_errno($ch);
						$curl_error = curl_error($ch);
						curl_close($ch);        
						
						if ($curl_errno > 0)
						{
								#return "Stop : Connection Error";
						}             
						
						libxml_use_internal_errors(true);
						$xml = simplexml_load_string($output);						
						
						if ( !$xml )
						{
								$this->updateCountCheckStatusTrx($transaction);
						}                
						else
						{
								$trx = array();
								$trx['ip_address']            = $this->getipaddress();
								$trx['process_type']          = "CHECKSTATUS";
								$trx['process_datetime']      = date("Y-m-d H:i:s");
								$trx['transidmerchant']       = (string) $xml->TRANSIDMERCHANT;
								$trx['amount']                = (string) $xml->AMOUNT;
								$trx['notify_type']           = (string) $xml->STATUSTYPE;
								$trx['response_code']         = (string) $xml->RESPONSECODE;
								$trx['result_msg']            = (string) $xml->RESULTMSG;
								$trx['approval_code']         = (string) $xml->APPROVALCODE;
								$trx['payment_channel']       = (string) $xml->PAYMENTCHANNEL;
								$trx['payment_code']          = (string) $xml->PAYMENTCODE;
								$trx['words']                 = (string) $xml->WORDS;
								$trx['session_id']            = (string) $xml->SESSIONID;
								$trx['bank_issuer']           = (string) $xml->BANK;
								$trx['creditcard']            = (string) $xml->MCN;
								$trx['verify_id']             = (string) $xml->VERIFYID;
								$trx['verify_score']          = (int) $xml->VERIFYSCORE;
								$trx['verify_status']         = (string) $xml->VERIFYSTATUS;            
								
								# Insert transaction check status to table onecheckout
								$this->add_dokuonecheckout($trx);

								if ( $trx['payment_channel'] <> '01'  )
								{
										return "NOT SUPPORT";
								}
								
								return $xml->RESULTMSG;
						}		
				}
				
				function clear_cart()
				{
						add_action( 'init', 'woocommerce_clear_cart_url' );
						global $woocommerce;
						
						$woocommerce->cart->empty_cart(); 														
				}
				
				function dokuonecheckout_callback()
				{
						require_once(dirname(__FILE__) . "/dokuonecheckout.pages.inc");
						die;
				}
				
		}
		
		/**
		* Add the Gateway to WooCommerce
		**/
		function woocommerce_add_gateway_dokuonecheckout_gateway($methods)
		{
				$methods[] = 'WC_dokuonecheckout_Gateway';
				return $methods;
		}
		
		add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_dokuonecheckout_gateway' );
		
}

?>
