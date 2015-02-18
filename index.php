<?php
/*
Plugin Name: JigoshopAtos
Plugin URI: https://github.com/chtipepere/jigoshopAtosPlugin
Description: Extends Jigoshop with Atos SIPS gateway (French bank).
Version: 1.0
Author: Chtipepere

http://blog.manit4c.com/2009/12/18/installation-dun-paiement-atos-sips-tutoriel-premiere-partie/

http://thomasdt.com/woocommerce/
*/

add_action( 'plugins_loaded', 'jigoshop_atos_init', 0 );

function jigoshop_atos_init() {

	if ( ! class_exists( 'jigoshop_payment_gateway' ) ) {
		return;
	}

	/** Translations */
	load_plugin_textdomain('JigoshopAtos', false, dirname(plugin_basename(__FILE__)) . '/languages/');

	/**
	 * Add the gateway to Jigoshop
	 */
	add_filter('jigoshop_payment_gateways', function ($methods) {
		$methods[] = 'Jigoshop_atos';
		return $methods;
	});

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
			$this->enabled                  = $options->get('jigoshop_atos_is_enabled');
			$this->title                    = $options->get('jigoshop_atos_title');
			$this->description              = $options->get('jigoshop_atos_description');
			$this->merchant_id              = $options->get('jigoshop_atos_merchant_id');
			$this->pathfile                 = $options->get('jigoshop_atos_pathfile');
			$this->path_bin_request         = $options->get('jigoshop_atos_path_bin_request');
			$this->path_bin_response        = $options->get('jigoshop_atos_path_bin_response');
			$this->cancel_return_url        = $options->get('jigoshop_atos_cancel_return_url');
			$this->automatic_response_url   = '/automatic_response.php'; // file must be copy manually, for the moment
			$this->normal_return_url        = $options->get('jigoshop_atos_normal_return_url');
			$this->logo_id2                 = $options->get('jigoshop_atos_logo_id2');
			$this->advert                   = $options->get('jigoshop_atos_advert');
			$this->currency                 = $options->get('jigoshop_currency');
			$this->notify_url               = jigoshop_request_api::query_request('?js-api=JS_Gateway_Atos', false);

			$this->msg['message']           = '';
			$this->msg['class']             = '';

			add_action('receipt_jigoshop_atos', [$this, 'receipt_page']);
			//add_action('valid-atos-request', [$this, 'successful_request']);

			//add_action('jigoshop_api_js_gateway_atos', array($this, 'check_atos_response'));
		}

		/**
		 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
		 * These will be installed on the Jigoshop_Options 'Payment Gateways' tab by the parent class 'jigoshop_payment_gateway'
		 */
		protected function get_default_options(){
			$defaults = [];
			// Define the Section name for the Jigoshop_Options
			$defaults[] = [
				'name' => sprintf(__('Atos Standard %s', 'JigoshopAtos'), '<img style="vertical-align:middle;margin-top:-4px;margin-left:10px;" src="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/logo.gif" alt="Atos">'),
				'type' => 'title',
				'desc' => __('Atos works by sending the user to your bank to enter their payment information.', 'JigoshopAtos')
			];

			// List each option in order of appearance with details
			$defaults[] = [
				'name'      => __('Enable Atos', 'JigoshopAtos'),
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
				'std'   => __('Carte bleue', 'jigoshop'),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Customer Message', 'jigoshop'),
				'tip'   => __('This controls the description which the user sees during checkout.', 'jigoshop'),
				'id'    => 'jigoshop_atos_description',
				'std'   => __( 'Paiement sécurisé par Carte Bancaire (Atos)', 'JigoshopAtos' ),
				'type'  => 'longtext'
			];
			$defaults[] = [
				'name'  => __('Merchant id', 'atos'),
				'tip'   => __('Identifiant de marchand donné par votre banque', 'JigoshopAtos'),
				'id'    => 'jigoshop_atos_merchant_id',
				'std'   => '010101010101010',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Pathfile', 'JigoshopAtos'),
				'tip'   => __( 'Chemin vers le fichier pathfile donné par votre banque', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_pathfile',
				'std'   => '/var/www/site/cgi-bin/pathfile',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Path bin request', 'JigoshopAtos'),
				'tip'   => __( 'Chemin vers le fichier request donné par votre banque', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_path_bin_request',
				'std'   => '/var/www/site/cgi-bin/request',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Path bin response', 'JigoshopAtos'),
				'tip'   => __( 'Chemin vers le fichier response donné par votre banque', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_path_bin_response',
				'std'   => '/var/www/site/cgi-bin/response',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Cancel return url', 'JigoshopAtos'),
				'tip'   => __( 'Url de retour en cas d\'annulation', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_cancel_return_url',
				'std'   => site_url( '/cancel' ),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Normal return url', 'JigoshopAtos'),
				'tip'   => __( 'Url de retour en cas de clic sur le bouton << Retour à la boutique >>', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_normal_return_url',
				'std'   => site_url( '/thankyou' ),
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Logo id2', 'JigoshopAtos'),
				'tip'   => __( 'Image logo_id2.gif', 'JigoshopAtos' ),
				'id'    => 'jigoshop_atos_logo_id2',
				'std'   => 'logo_id2.gif',
				'type'  => 'text'
			];
			$defaults[] = [
				'name'  => __('Advert', 'JigoshopAtos'),
				'tip'   => __( 'Image advert.gif', 'JigoshopAtos' ),
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
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay with atos.',
					'JigoshopAtos' ) . '</p>';
			echo $this->generate_atos_form( $order_id );
		}

		public function thankyou_page() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->mercitxt ) );
			}
		}

/*
		function process_payment( $order_id ) {
			$order = &new woocommerce_order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => add_query_arg( 'order',
					$order->id, add_query_arg( 'key', $order->order_key,
						get_permalink( get_option( 'woocommerce_pay_page_id' ) ) ) )
			);
		}*/

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

				$message = "<p>" . __( 'Error calling the atos api : exec request not found',
						'JigoshopAtos' ) . "  $path_bin_request</p>";

			} elseif ( $code != 0 ) {

				$message = "<p>" . __( 'Atos API error : ', 'JigoshopAtos' ) . " $error</p>";

			} else {

				// Affiche le formulaire avec le choix des cartes bancaires :
				$message = $tableau[3];
			}
			$parm_pretty = str_replace( ' ', '<br/>', $parm );
			$message .= '<p>You see this because you are in debug mode :</p><pre>' . $parm_pretty . '</pre><p>End of debug mode</p>';

			return $message;
		}
	}
}
