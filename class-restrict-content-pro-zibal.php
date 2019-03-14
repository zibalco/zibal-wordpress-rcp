<?php

if (!defined('ABSPATH')) {

	die('This file cannot be accessed directly');
}

if (!class_exists('RCP_Zibal')) {

	class RCP_Zibal
	{
		public function __construct()
		{
			add_action('init', array($this, 'Zibal_Verify'));
			add_action('rcp_payments_settings', array($this, 'Zibal_Setting'));
			add_action('rcp_gateway_Zibal', array($this, 'Zibal_Request'));

			add_filter('rcp_payment_gateways', array($this, 'Zibal_Register'));

			if (!function_exists('Zibal_Currencies') && !function_exists('Zibal_Currencies')) {

				add_filter('rcp_currencies', array($this, 'Zibal_Currencies'));
			}
		}

		public function Zibal_Currencies($currencies)
		{
			unset($currencies['RIAL']);

			$currencies['تومان'] = __('تومان', 'rcp_zibal');
			$currencies['ریال'] = __('ریال', 'rcp_zibal');

			return $currencies;
		}
				
		public function Zibal_Register($gateways)
		{
			global $rcp_options;

			$zibal = 'درگاه پرداخت زیبال';

			if (version_compare(RCP_PLUGIN_VERSION, '2.1.0', '<')) {

				$gateways['Zibal'] = isset($rcp_options['zibal_name']) ? $rcp_options['zibal_name'] : __($zibal, 'rcp_zibal');

			} else {

				$gateways['Zibal'] = array(

					'label'       => isset($rcp_options['zibal_name']) ? $rcp_options['zibal_name'] : __($zibal, 'rcp_zibal'),
					'admin_label' => isset($rcp_options['zibal_name']) ? $rcp_options['zibal_name'] : __($zibal, 'rcp_zibal'),
				);
			}

			return $gateways;
		}

		public function Zibal_Setting($rcp_options)
		{
		?>	
			<hr/>
			<table class="form-table">
				<?php do_action('RCP_Zibal_before_settings', $rcp_options); ?>
				<tr valign="top">
					<th colspan="2">
						<h3><?php _e('تنظیمات درگاه پرداخت زیبال', 'rcp_zibal'); ?></h3>
					</th>
				</tr>
				<tr valign="top">
					<th>
						<label for="rcp_settings[zibal_merchant]"><?php _e('کلید merchant', 'rcp_zibal'); ?></label>
					</th>
					<td>
						<input class="regular-text" id="rcp_settings[zibal_merchant]" style="width:300px;" name="rcp_settings[zibal_merchant]" value="<?php if (isset($rcp_options['zibal_merchant'])) { echo $rcp_options['zibal_merchant']; } ?>"/>
					</td>
				</tr>
                <tr valign="top">
                    <th>
                        <label for="rcp_settings[zibal_direct]"><?php _e('درگاه مستقیم (زیبال دایرکت)', 'rcp_zibal'); ?></label>
                    </th>
                    <td>
                        <input class="regular-text" id="rcp_settings[zibal_direct]" name="rcp_settings[zibal_direct]" value="1" type="checkbox" <?php if (isset($rcp_options['zibal_direct'])) { echo " checked "; } ?>"/>
                    </td>
                </tr>
                <tr valign="top">
					<th>
						<label for="rcp_settings[zibal_query_name]"><?php _e('نام لاتین درگاه پرداخت', 'rcp_zibal'); ?></label>
					</th>
					<td>
						<input class="regular-text" id="rcp_settings[zibal_query_name]" style="width:300px;" name="rcp_settings[zibal_query_name]" value="<?php echo isset($rcp_options['zibal_query_name']) ? $rcp_options['zibal_query_name'] : 'Zibal'; ?>"/>
						<div class="description"><?php _e('این نام در هنگام بازگشت از بانک در آدرس بازگشت از بانک نمایان خواهد شد<br/>این نام باید با نام سایر درگاه ها متفاوت باشد', 'rcp_zibal'); ?></div>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<label for="rcp_settings[zibal_name]"><?php _e('نام نمایشی درگاه پرداخت', 'rcp_zibal'); ?></label>
					</th>
					<td>
						<input class="regular-text" id="rcp_settings[zibal_name]" style="width:300px;" name="rcp_settings[zibal_name]" value="<?php echo isset($rcp_options['zibal_name']) ? $rcp_options['zibal_name'] : __('درگاه پرداخت زیبال', 'rcp_zibal'); ?>"/>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<label><?php _e('تذکر ', 'rcp_zibal'); ?></label>
					</th>
					<td>
						<div class="description"><?php _e('از سربرگ مربوط به ثبت نام در تنظیمات افزونه حتما یک برگه برای بازگشت از بانک انتخاب نمایید<br/>ترجیحا نامک برگه را لاتین قرار دهید<br/> نیازی به قرار دادن شورت کد خاصی در برگه نیست و میتواند برگه ی خالی باشد', 'rcp_zibal'); ?></div>
					</td>
				</tr>
				<?php do_action('RCP_Zibal_after_settings', $rcp_options); ?>
			</table>
			<?php
		}
		
		public function Zibal_Request($subscription_data)
		{
			$new_subscription_id = get_user_meta($subscription_data['user_id'], 'rcp_subscription_level', TRUE);

			if (!empty($new_subscription_id)) {

				update_user_meta($subscription_data['user_id'], 'rcp_subscription_level_new', $new_subscription_id);
			}
			
			$old_subscription_id = get_user_meta($subscription_data['user_id'], 'rcp_subscription_level_old', TRUE);

			update_user_meta($subscription_data['user_id'], 'rcp_subscription_level', $old_subscription_id);
			
			global $rcp_options;

			ob_start();

			$query  = isset($rcp_options['zibal_query_name']) ? $rcp_options['zibal_query_name'] : 'Zibal';
			$amount = str_replace(',', '', $subscription_data['price']);

			$zibal_payment_data = array(

				'user_id'           => $subscription_data['user_id'],
				'subscription_name' => $subscription_data['subscription_name'],
				'subscription_key'  => $subscription_data['key'],
				'amount'            => $amount
			);		
			

			@session_start();

			$_SESSION['zibal_payment_data'] = $zibal_payment_data;

			do_action('RCP_Before_Sending_to_Zibal', $subscription_data);

			if (extension_loaded('curl')) {

				$currency = $rcp_options['currency'];
				

				if ($currency == 'تومان' || $currency == 'TOMAN' || $currency == 'تومان ایران' || $currency == 'IRT' || $currency == 'Iranian Toman') {

					$amount = $amount * 10;
				}

				$api_key  = isset($rcp_options['zibal_merchant']) ? $rcp_options['zibal_merchant'] : NULL;
				$direct  = isset($rcp_options['zibal_direct']) ? $rcp_options['zibal_direct'] : NULL;
				$callback = add_query_arg('gateway', $query, $subscription_data['return_url']);

				$params = array(
					'merchant'          => $api_key,
					'amount'       => intval($amount),
					'callbackUrl'     => urlencode($callback),
					'orderId' => $subscription_data['post_data']['rcp_register_nonce']
				);

				

				$result = $this->postToZibal('request', $params);

				if ($result && isset($result->result) && $result->result == 100) {

					$gateway_url = 'https://gateway.zibal.ir/start/' . $result->trackId;
                    if($direct=='1')$gateway_url.="/direct";
					wp_redirect($gateway_url);

				} else {

					$fault = 'در ارتباط با وب سرویس zibal.ir خطایی رخ داده است';
					$fault = isset($result->message) ? $result->message : $fault;

					wp_die(sprintf(__('متاسفانه پرداخت به دلیل خطای زیر امکان پذیر نمی باشد<br/><b>%s</b>', 'rcp_zibal'), $fault));
				}

			} else {

				$fault = 'تابع cURL در سرور فعال نمی باشد';

				wp_die(sprintf(__('متاسفانه پرداخت به دلیل خطای زیر امکان پذیر نمی باشد<br/><b>%s</b>', 'rcp_zibal'), $fault));
			}

			exit;
		}
		
		public function Zibal_Verify()
		{
			if (!isset($_GET['gateway'])) {

				return;
			}

			if (!class_exists('RCP_Payments')) {

				return;
			}

			global $rcp_options, $wpdb, $rcp_payments_db_name;
			

			@session_start();

			$zibal_payment_data = isset($_SESSION['zibal_payment_data']) ? $_SESSION['zibal_payment_data'] : NULL;
			

			$query = isset($rcp_options['zibal_query_name']) ? $rcp_options['zibal_query_name'] : 'Zibal';

			if (($_GET['gateway'] == $query) && $zibal_payment_data) {

				$user_id           = $zibal_payment_data['user_id'];
				$subscription_name = $zibal_payment_data['subscription_name'];
				$subscription_key  = $zibal_payment_data['subscription_key'];
				$amount            = $zibal_payment_data['amount'];
				

				$payment_method = isset($rcp_options['zibal_name']) ? $rcp_options['zibal_name'] : __('درگاه پرداخت زیبال', 'rcp_zibal');

				$new_payment = TRUE;

				$get_result = $wpdb->get_results($wpdb->prepare("SELECT id FROM " . $rcp_payments_db_name . " WHERE `subscription_key`='%s' AND `payment_type`='%s';", $subscription_key, $payment_method));

				if ($get_result) {

					$new_payment = FALSE;
				}

				unset($GLOBALS['zibal_new']);

				$GLOBALS['zibal_new'] = $new_payment;

				global $new;

				$new = $new_payment;

				if ($new_payment == 1) {

					if (isset($_GET['success']) && isset($_GET['trackId']) && isset($_GET['status'])) {	


						$success        = sanitize_text_field($_GET['success']);
						$status        = sanitize_text_field($_GET['status']);
						$trackId      = sanitize_text_field($_GET['trackId']);
						$orderId = sanitize_text_field($_GET['orderId']);

						if (isset($success) && $success == 1) {

							$api_key = isset($rcp_options['zibal_merchant']) ? $rcp_options['zibal_merchant'] : NULL;

							$params = array (

								'merchant'     => $api_key,
								'trackId' => $trackId
							);

							$result = $this->postToZibal('verify', $params);

							if ($result && isset($result->result) && $result->result == 100) {

								$currency = $rcp_options['currency'];

								if ($currency == 'تومان' || $currency == 'TOMAN' || $currency == 'تومان ایران' || $currency == 'IRT' || $currency == 'Iranian Toman') {

									$amount = $amount * 10;
								}
								
								

								if (intval($amount) == $result->amount) {
								
									$fault = NULL;

									$payment_status = 'completed';
									$transaction_id = $trackId;

								} else {

									$fault = 'رقم تراكنش با رقم پرداخت شده مطابقت ندارد';

									$payment_status = 'failed';
									$transaction_id = $trackId;
								}

							} else {

								$fault = 'در ارتباط با وب سرویس zibal.ir و بررسی تراکنش خطایی رخ داده است';
								$fault = isset($result->message) ? $result->message : $fault;

								$payment_status = 'failed';
								$transaction_id = $trackId;
							}

						} else {


								$fault = 'تراكنش با خطا مواجه شد و یا توسط پرداخت کننده کنسل شده است';

								$payment_status = 'cancelled';
								$transaction_id = $trackId;

						}

					} else {

						$fault = 'اطلاعات ارسال شده مربوط به تایید تراکنش ناقص و یا غیر معتبر است';

						$payment_status = 'failed';
						$transaction_id = NULL;
					}

					unset($GLOBALS['zibal_payment_status']);
					unset($GLOBALS['zibal_transaction_id']);
					unset($GLOBALS['zibal_fault']);
					unset($GLOBALS['zibal_subscription_key']);

					$GLOBALS['zibal_payment_status']   = $payment_status;
					$GLOBALS['zibal_transaction_id']   = $transaction_id;
					$GLOBALS['zibal_subscription_key'] = $subscription_key;
					$GLOBALS['zibal_fault']            = $fault;

					global $zibal_transaction;

					$zibal_transaction = array();

					$zibal_transaction['zibal_payment_status']   = $payment_status;
					$zibal_transaction['zibal_transaction_id']   = $transaction_id;
					$zibal_transaction['zibal_subscription_key'] = $subscription_key;
					$zibal_transaction['zibal_fault']            = $fault;

					if ($payment_status == 'completed') {

						$payment_data = array(

							'date'             => date('Y-m-d g:i:s'),
							'subscription'     => $subscription_name,
							'payment_type'     => $payment_method,
							'subscription_key' => $subscription_key,
							'amount'           => $zibal_payment_data['amount'],
							'user_id'          => $user_id,
							'transaction_id'   => $transaction_id
						);

						do_action('RCP_Zibal_Insert_Payment', $payment_data, $user_id);

						$rcp_payments = new RCP_Payments();

						$rcp_payments->insert($payment_data);

						$new_subscription_id = get_user_meta($user_id, 'rcp_subscription_level_new', TRUE);

						if (!empty($new_subscription_id)) {

							update_user_meta($user_id, 'rcp_subscription_level', $new_subscription_id);
						}

						rcp_set_status($user_id, 'active');

						if (version_compare(RCP_PLUGIN_VERSION, '2.1.0', '<')) {

							rcp_email_subscription_status($user_id, 'active');

							if (! isset($rcp_options['disable_new_user_notices'])) {

								wp_new_user_notification($user_id);
							}
						}

						update_user_meta($user_id, 'rcp_payment_profile_id', $user_id);
						update_user_meta($user_id, 'rcp_signup_method', 'live');
						update_user_meta($user_id, 'rcp_recurring', 'no'); 
					
						$subscription = rcp_get_subscription_details(rcp_get_subscription_id($user_id));
						$member_new_expiration = date('Y-m-d H:i:s', strtotime('+' . $subscription->duration . ' ' . $subscription->duration_unit . ' 23:59:59'));

						rcp_set_expiration_date($user_id, $member_new_expiration);
						delete_user_meta($user_id, '_rcp_expired_email_sent');

						$post_title   = __('تایید پرداخت', 'rcp_zibal');
						$post_content = __('پرداخت با موفقیت انجام شد شماره تراکنش: ' . $transaction_id, 'rcp_zibal') . __(' روش پرداخت: ', 'rcp_zibal');

						$log_data = array(

							'post_title'   => $post_title,
							'post_content' => $post_content . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(

							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log($log_data, $log_meta);

						do_action('RCP_Zibal_Completed', $user_id);
					}

					if ($payment_status == 'cancelled') {

						$post_title   = __('انصراف از پرداخت', 'rcp_zibal');
						$post_content = __('تراکنش به دلیل خطای رو به رو ناموفق باقی ماند: ', 'rcp_zibal') . $fault . __(' روش پرداخت: ', 'rcp_zibal');

						$log_data = array(

							'post_title'   => $post_title,
							'post_content' => $post_content . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(

							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log($log_data, $log_meta);

						do_action('RCP_Zibal_Cancelled', $user_id);
					}

					if ($payment_status == 'failed') {

						$post_title   = __('خطا در پرداخت', 'rcp_zibal');
						$post_content = __('تراکنش به دلیل خطای رو به رو ناموفق باقی ماند: ', 'rcp_zibal') . $fault . __(' روش پرداخت: ', 'rcp_zibal');

						$log_data = array(

							'post_title'   => $post_title,
							'post_content' => $post_content . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(

							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log($log_data, $log_meta);

						do_action('RCP_Zibal_Failed', $user_id);
					}
				}

				add_filter('the_content', array($this, 'Zibal_Content_After_Return'));
			}
		}

		public function Zibal_Content_After_Return($content)
		{ 
			global $zibal_transaction, $new;

			@session_start();

			$new_payment = isset($GLOBALS['zibal_new']) ? $GLOBALS['zibal_new'] : $new;
			
			$payment_status = isset($GLOBALS['zibal_payment_status']) ? $GLOBALS['zibal_payment_status'] : $zibal_transaction['zibal_payment_status'];
			$transaction_id = isset($GLOBALS['zibal_transaction_id']) ? $GLOBALS['zibal_transaction_id'] : $zibal_transaction['zibal_transaction_id'];

			$fault = isset($GLOBALS['zibal_fault']) ? $GLOBALS['zibal_fault'] : $zibal_transaction['zibal_fault'];
			
			if ($new_payment == 1)  {
			
				$zibal_data = array(

					'payment_status' => $payment_status,
					'transaction_id' => $transaction_id,
					'fault'          => $fault
				);
				
				$_SESSION['zibal_data'] = $zibal_data;
			
			} else {

				$zibal_payment_data = isset($_SESSION['zibal_data']) ? $_SESSION['zibal_data'] : NULL;
			
				$payment_status = isset($zibal_payment_data['payment_status']) ? $zibal_payment_data['payment_status'] : NULL;
				$transaction_id = isset($zibal_payment_data['transaction_id']) ? $zibal_payment_data['transaction_id'] : NULL;

				$fault = isset($zibal_payment_data['fault']) ? $zibal_payment_data['fault'] : NULL;
			}

			$message = NULL;

			if ($payment_status == 'completed') {

				$message = '<br/>' . __('تراکنش با موفقیت انجام شد. شماره پیگیری تراکنش ', 'rcp_zibal') . $transaction_id . '<br/>';
			}

			if ($payment_status == 'cancelled') {

				$message = '<br/>' . __('تراکنش به دلیل انصراف شما نا تمام باقی ماند', 'rcp_zibal');
			}

			if ($payment_status == 'failed') {

				$message = '<br/>' . __('تراکنش به دلیل خطای زیر ناموفق باقی باند', 'rcp_zibal') . '<br/>' . $fault . '<br/>';
			}

			return $content . $message;
		}

        /**
         * connects to zibal's rest api
         * @param $path
         * @param $parameters
         * @return stdClass
         */
        private function postToZibal($path, $parameters)
        {
            $url = 'https://gateway.zibal.ir/v1/'.$path;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            curl_close($ch);
            return json_decode($response);
        }
	}
}

new RCP_Zibal();

if (!function_exists('change_cancelled_to_pending')) {	

	add_action('rcp_set_status', 'change_cancelled_to_pending', 10, 2);

	function change_cancelled_to_pending($status, $user_id)
	{
		if ($status == 'cancelled') {

			rcp_set_status($user_id, 'expired');

			return TRUE;
		}
	}
}

if (!function_exists('RCP_User_Registration_Data') && !function_exists('RCP_User_Registration_Data')) {

	add_filter('rcp_user_registration_data', 'RCP_User_Registration_Data');

	function RCP_User_Registration_Data($user)
	{
		$old_subscription_id = get_user_meta($user['id'], 'rcp_subscription_level', TRUE);

		if (!empty($old_subscription_id)) {

			update_user_meta($user['id'], 'rcp_subscription_level_old', $old_subscription_id);
		}

		$user_info = get_userdata($user['id']);
		
		$old_user_role = implode(', ', $user_info->roles);

		if (!empty($old_user_role)) {

			update_user_meta($user['id'], 'rcp_user_role_old', $old_user_role);
		}

		return $user;
	}
}
