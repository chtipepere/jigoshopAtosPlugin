<?php
/**
Plugin Name: JigoshopAtos
Text Domain: jigoshop-atos
Plugin URI: https://github.com/chtipepere/jigoshopAtosPlugin
Description: Extends Jigoshop with Atos SIPS gateway (French bank).
Version: 1.3
Author: πR
 **/

if (!defined('JIGOSHOP_VERSION')) {
	function jigoshop_required(){
		echo '<div class="error"><p>'.
		     __('<strong>Error!</strong> Jigoshop is mandatory. Please install it.', 'jigoshop-atos').
		     '</p></div>';
	}
	add_action('admin_notices', 'jigoshop_required');
	return;
}

define('JIGOSHOPATOS_PHP_VERSION', '5.4');
define('JIGOSHOP_MINIMUM_VERSION', '1.8');

if(!version_compare(PHP_VERSION, JIGOSHOPATOS_PHP_VERSION, '>=')) {
	function jigoshopatos_required_version(){
		echo '<div class="error"><p>'.
		     sprintf(__('<strong>Error!</strong> JigoshopAtos requires at least PHP %s! Your version is: %s. Please upgrade.', 'jigoshop-atos'), JIGOSHOPATOS_PHP_VERSION, PHP_VERSION).
		     '</p></div>';
	}
	add_action('admin_notices', 'jigoshopatos_required_version');
	return;
}

if(!version_compare(JIGOSHOP_VERSION, JIGOSHOP_MINIMUM_VERSION, '>=')) {
	function jigoshop_minimum_version(){
		echo '<div class="error"><p>'.
		     sprintf(__('<strong>Error!</strong> JigoshopAtos requires at least Jigoshop %s! Your version is: %s. Please upgrade.', 'jigoshop-atos'), JIGOSHOP_MINIMUM_VERSION, JIGOSHOP_VERSION).
		     '</p></div>';
	}
	add_action('admin_notices', 'jigoshop_minimum_version');
	return;
}

if (function_exists('add_action')) {
	add_action( 'plugins_loaded', 'jigoshop_atos_init', 0 );
}

function jigoshop_atos_init() {

	if ( ! class_exists( 'jigoshop_payment_gateway' ) ) {
		return;
	}

	// Jigoshop 1.8+ required
	if (true === version_compare(JIGOSHOP_VERSION, '1.8', '<')) return;

	/** Translations */
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('jigoshop-atos', false, $plugin_dir . '/languages/');

	/**
	 * Add the gateway to Jigoshop
	 */
	add_filter('jigoshop_payment_gateways', function ($methods) {
		$methods[] = 'Jigoshop_atos';
		return $methods;
	});

	include_once( 'automatic_response.php' );

	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

	function add_action_links ( $links ) {
		$mylinks = [
			'<a href="' . admin_url( sprintf('admin.php?page=jigoshop_settings&tab=%s', __('payment', 'jigoshop-atos')) ) . '">'.__('Settings', 'jigoshop') . '</a>',
			'<a href="https://github.com/chtipepere/jigoshopAtosPlugin/blob/master/README.md">'.__('Docs', 'jigoshop-atos') . '</a>'
		];
		return array_merge( $links, $mylinks );
	}

	/**
	 * Gateway class
	 */
	class Jigoshop_atos extends jigoshop_payment_gateway {

		public $msg = [];
		protected $settings;

		public function __construct() {

			parent::__construct();
			$options = Jigoshop_Base::get_options();

			// Go wild in here
			$this->id                       = 'jigoshop_atos';
			$this->method_title             = 'Atos';
			$this->icon                     = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/logo.gif';
			$this->has_fields               = false;
			$this->enabled                  = $options->get_option('jigoshop_atos_is_enabled');
			$this->title                    = $options->get_option('jigoshop_atos_title');
			$this->description              = $options->get_option('jigoshop_atos_description');
			$this->merchant_id              = $options->get_option('jigoshop_atos_merchant_id');
			$this->merchant_name            = $options->get_option('jigoshop_atos_merchant_name');
			$this->pathfile                 = $options->get_option('jigoshop_atos_pathfile');
			$this->path_bin_request         = $options->get_option('jigoshop_atos_path_bin_request');
			$this->path_bin_response        = $options->get_option('jigoshop_atos_path_bin_response');
			$this->cancel_return_url        = $options->get_option('jigoshop_atos_cancel_return_url');
			$this->automatic_response_url   = $options->get_option('jigoshop_atos_automatic_response_url');
			$this->normal_return_url        = $options->get_option('jigoshop_atos_normal_return_url');
			$this->logo_id2                 = $options->get_option('jigoshop_atos_logo_id2');
			$this->advert                   = $options->get_option('jigoshop_atos_advert');
			$this->currency                 = $options->get_option('jigoshop_currency');
			$this->notify_url               = jigoshop_request_api::query_request('?js-api=JS_Gateway_Atos', false);

			$this->msg['message']           = '';
			$this->msg['class']             = '';

			add_action('receipt_jigoshop_atos', [$this, 'receipt_page']);
		}

		/**
		 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
		 * These will be installed on the Jigoshop_Options 'Payment Gateways' tab by the parent class 'jigoshop_payment_gateway'
		 */
		protected function get_default_options(){
			$defaults = [];
			// Define the Section name for the Jigoshop_Options
			$defaults[] = [
				'name' => sprintf(__('Atos Standard %s', 'jigoshop-atos'), '<img style="vertical-align:middle;margin-top:-4px;margin-left:10px;" src="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/logo.gif" alt="Atos">'),
				'type' => 'title',
				'desc' => __('Atos works by sending the user to your bank to enter their payment information.', 'jigoshop-atos')
			];

			// List each option in order of appearance with details
			$defaults[] = [
				'name'      => __('Enable Atos', 'jigoshop-atos'),
				'id'        => 'jigoshop_atos_is_enabled',
				'std'       => 'no',
				'type'      => 'checkbox',
				'choices'   => [
					'no'    => __('No', 'jigoshop'),
					'yes'   => __('Yes', 'jigoshop')
				]
			];
			$defaults[] = [
				'name'  => __('Method Title', 'jigoshop'),
				'tip'   => __('This controls the title which the user sees during checkout.', 'jigoshop'),
				'id'    => 'jigoshop_atos_title',
				'std'   => __('Credit card', 'jigoshop'),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Customer Message', 'jigoshop'),
				'tip'   => __('This controls the description which the user sees during checkout.', 'jigoshop'),
				'id'    => 'jigoshop_atos_description',
				'std'   => __( 'Paiement sécurisé par Carte Bancaire (Atos)', 'jigoshop-atos' ),
				'type'  => 'longtext'
			];
			$defaults[] = [
				'name'  => __('Merchant id', 'jigoshop-atos'),
				'tip'   => __('Merchant id given by your bank', 'jigoshop-atos'),
				'id'    => 'jigoshop_atos_merchant_id',
				'std'   => '014022286611112',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Pathfile file', 'jigoshop-atos'),
				'tip'   => __( 'Path to the pathfile file given by your bank', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_pathfile',
				'std'   => '/var/www/site/param/pathfile',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Request bin file path', 'jigoshop-atos'),
				'tip'   => __( 'Path to the request bin file given by your bank', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_path_bin_request',
				'std'   => '/var/www/site/bin/static/request',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Response bin file path', 'jigoshop-atos'),
				'tip'   => __( 'Path to the response bin file given by your bank', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_path_bin_response',
				'std'   => '/var/www/site/bin/static/response',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Cancel return url', 'jigoshop-atos'),
				'tip'   => __( 'Return url in case of canceled transaction', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_cancel_return_url',
				'std'   => site_url( '/cancel' ),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Normal return url', 'jigoshop-atos'),
				'tip'   => __( 'Return url when a user click on the << Back to the shop >> button', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_normal_return_url',
				'std'   => site_url( '/thankyou' ),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Automatic response url', 'jigoshop-atos'),
				'tip'   => __( 'URL called in case of success payment', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_automatic_response_url',
				'std'   => site_url( '?page=12' ),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Logo id2', 'jigoshop-atos'),
				'tip'   => __( 'Right image on Atos page', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_logo_id2',
				'std'   => 'logo_id2.gif',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Advert', 'jigoshop-atos'),
				'tip'   => __( 'Center image on Atos page', 'jigoshop-atos' ),
				'id'    => 'jigoshop_atos_advert',
				'std'   => 'advert.gif',
				'type'  => 'text'
			];

			return $defaults;
		}

		/**
		 * Process the payment and return the result
		 */
		public function process_payment($order_id)
		{
			$order = new jigoshop_order($order_id);
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
			);
		}

		/**
		 *  There are no payment fields for atos, but we want to show the description if set.
		 **/
		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}

		function receipt_page( $order_id ) {
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay.',
					'jigoshop-atos' ) . '</p>';
			echo $this->generate_atos_form( $order_id );
		}

		public function thankyou_page() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->mercitxt ) );
			}
		}

		public function showMessage( $content ) {
			return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
		}

		/**
		 * Generate atos button link
		 **/
		public function generate_atos_form( $order_id )
		{
			$order = new jigoshop_order( $order_id );
			// La variable order contient toutes les infos du panier et du client

			$pathfile = $this->pathfile;

			$path_bin_request = $this->path_bin_request;
			$parm             = 'merchant_id=' . $this->merchant_id;

			$parm   = "$parm merchant_country=fr";
			$amount = ( $order->order_total ) * 100;

			$amount = str_pad( $amount, 3, '0', STR_PAD_LEFT );

			$parm = "$parm amount=" . $amount;

			$parm = "$parm currency_code=978";

			$parm = "$parm pathfile=" . $pathfile;

			$parm = "$parm normal_return_url=" . $this->normal_return_url;

			$parm = "$parm cancel_return_url=" . $this->cancel_return_url;

			$parm = "$parm automatic_response_url=" . $this->automatic_response_url;

			$parm = "$parm language=fr";

			$parm = "$parm payment_means=CB,2,VISA,2,MASTERCARD,2";

			$parm = "$parm header_flag=no";

			$parm = "$parm order_id=$order_id";

			$parm = "$parm logo_id2=" . $this->logo_id2;

			$parm = "$parm advert=" . $this->advert;

			$parm = escapeshellcmd($parm);
			$result = exec( "$path_bin_request $parm" );

			$tableau = explode( "!", "$result" );

			$code = $tableau[1];

			$error = $tableau[2];

			if ( ( $code == '' ) && ( $error == '' ) ) {

				$message = '<p>' . __( 'Error calling the atos api: exec request not found',
						'jigoshop-atos' ) . "  $path_bin_request</p>";

			} elseif ( $code != 0 ) {

				$message = '<p>' . __( 'Atos API error:', 'jigoshop-atos' ) . " $error</p>";

			} else {

				// Affiche le formulaire avec le choix des cartes bancaires :
				$message = $tableau[3];
			}
			$parm_pretty = str_replace( ' ', '<br/>', $parm );
			if (Jigoshop_Base::get_options()->get_option('jigoshop_demo_store') == 'yes') {
				$message .= '<p>' . __('You see this because you are in demo store mode.', 'jigoshop-atos') . '</p>';
				$message .= '<pre>' . $parm_pretty . '</pre>';
				$message .= '<p>' . __('End of debug mode', 'jigoshop-atos') . '</p>';
			}

			return $message;
		}
	}
}
