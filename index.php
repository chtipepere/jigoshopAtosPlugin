<?php
/*
Plugin Name: Jigoshop Atos SIPS gateway
Plugin URI: https://github.com/chtipepere/jigoshopAtosPlugin
Description: Extends Jigoshop with Atos SIPS gateway (French bank).
Version: 1.0
Author: Chtipepere

http://blog.manit4c.com/2009/12/18/installation-dun-paiement-atos-sips-tutoriel-premiere-partie/
https://github.com/jigoshop/jigoshop/blob/master/gateways/worldpay.php
https://github.com/chtipepere/jigoshopAtosPlugin
http://thomasdt.com/woocommerce/
http://jigoshop.local/wp-admin/plugins.php?error=true&charsout=2766&plugin=jigoshopAtosPlugin%2Findex.php&plugin_status=all&paged=1&s&_error_nonce=54a3c7100b
http://jigoshop.local/wp-admin/admin.php?page=jigoshop_settings&tab=payment-gateways
https://www.jigoshop.com/documentation/jigoshop-amazon-simple-pay-gateway/

*/


register_activation_hook( __FILE__, 'jigoshop_atos_activate' );

function jigoshop_atos_activate() {

	copy( dirname( __FILE__ ) . '/automatic_response.php',
		dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/automatic_response.php' );

}


add_action( 'plugins_loaded', 'jigoshop_atos_init', 0 );

function jigoshop_atos_init() {

	if ( ! class_exists( 'jigoshop_payment_gateway' ) ) {
		return;
	}

	/**
	 * Localisation
	 */
	//load_plugin_textdomain( 'wc-atos-ccave', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	/**
	 * Gateway class
	 */
	class Jigoshop_atos extends jigoshop_payment_gateway {
		protected $msg = array();

		public function __construct() {

			parent::__construct();
			$options = Jigoshop_Base::get_options();

			// Go wild in here
			$this->id           = 'atos';
			$this->method_title = __( 'Atos', 'atos' );
			$this->icon         = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/logo.gif';
			$this->has_fields   = false;
			//$this->init_form_fields();
			//$this->init_settings();
			$this->title                  = $this->settings['title'];
			$this->description            = $this->settings['description'];
			$this->merchantid             = $this->settings['merchantid'];
			$this->pathfile               = $this->settings['pathfile'];
			$this->path_bin_request       = $this->settings['path_bin_request'];
			$this->path_bin_response      = $this->settings['path_bin_response'];
			$this->logfile                = $this->settings['logfile'];
			$this->automatic_response_url = $this->settings['automatic_response_url'];
			$this->normal_return_url      = $this->settings['normal_return_url'];
			$this->cancel_return_url      = $this->settings['cancel_return_url'];
			$this->logo_id2               = $this->settings['logo_id2'];
			$this->advert                 = $this->settings['advert'];
			$this->msg['message']         = '';
			$this->msg['class']           = '';

			// add_action( 'valid-atos-request', array( &$this, 'successful_request' ) );
			// add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			// add_action( 'woocommerce_receipt_atos', array( &$this, 'receipt_page' ) );
			// add_action( 'woocommerce_thankyou_atos', array( &$this, 'thankyou_page' ) );

			//add_action('jigoshop_settings_scripts', array($this, 'admin_scripts'));
			add_action('receipt_atos', array($this, 'receipt_page'));
			add_action('valid-atos-request', array($this, 'successful_request'));
		}

		/**
		 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
		 * These will be installed on the Jigoshop_Options 'Payment Gateways' tab by the parent class 'jigoshop_payment_gateway'
		 */
		protected function get_default_options(){
			$defaults = array();
			// Define the Section name for the Jigoshop_Options
			$defaults[] = array(
				'name' => sprintf(__('Atos Standard %s', 'jigoshop'), '<img style="vertical-align:middle;margin-top:-4px;margin-left:10px;" src="' . WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/logo.gif" alt="Atos">'),
				'type' => 'title',
				'desc' => __('Atos works by sending the user to your bank to enter their payment information.', 'jigoshop')
			);
			// List each option in order of appearance with details
			$defaults[] = array(
				'name' => __('Enable Atos', 'jigoshop'),
				'desc' => '',
				'tip' => '',
				'id' => 'jigoshop_atos_enabled',
				'std' => 'yes',
				'type' => 'checkbox',
				'choices' => array(
					'no' => __('No', 'jigoshop'),
					'yes' => __('Yes', 'jigoshop')
				)
			);
			$defaults[] = array(
				'name' => __('Method Title', 'jigoshop'),
				'desc' => '',
				'tip' => __('This controls the title which the user sees during checkout.', 'jigoshop'),
				'id' => 'jigoshop_atos_title',
				'std' => __('Carte bleue', 'jigoshop'),
				'type' => 'text'
			);
			$defaults[] = array(
				'name' => __('Customer Message', 'jigoshop'),
				'desc' => '',
				'tip' => __('This controls the description which the user sees during checkout.', 'jigoshop'),
				'id' => 'jigoshop_atos_description',
				'std' => __("Pay via Atos! You can pay with many credit card", 'jigoshop'),
				'type' => 'longtext'
			);
			$defaults[] = array(
				'name' => __('Atos email address', 'jigoshop'),
				'desc' => '',
				'tip' => __('Please enter your Atos email address; this is needed in order to take payment!', 'jigoshop'),
				'id' => 'jigoshop_atos_email',
				'std' => '',
				'type' => 'email'
			);
			$defaults[] = array(
				'name' => __('Send shipping details to Atos', 'jigoshop'),
				'desc' => '',
				'tip' => __('If your checkout page does not ask for shipping details, or if you do not want to send shipping information to PayPal, set this option to no. If you enable this option PayPal may restrict where things can be sent, and will prevent some orders going through for your protection.', 'jigoshop'),
				'id' => 'jigoshop_atos_send_shipping',
				'std' => 'no',
				'type' => 'checkbox',
				'choices' => array(
					'no' => __('No', 'jigoshop'),
					'yes' => __('Yes', 'jigoshop')
				)
			);
/*			$defaults[] = array(
				'name' => __('Force payment when free', 'jigoshop'),
				'desc' => '',
				'tip' => __('If product totals are free and shipping is also free (excluding taxes), this will force 0.01 to allow paypal to process payment. Shop owner is responsible for refunding customer.', 'jigoshop'),
				'id' => 'jigoshop_paypal_force_payment',
				'std' => 'no',
				'type' => 'checkbox',
				'choices' => array(
					'no' => __('No', 'jigoshop'),
					'yes' => __('Yes', 'jigoshop')
				)
			);*/
			$defaults[] = array(
				'name' => __('Enable Atos sandbox', 'jigoshop'),
				'desc' => __('Turn on to enable the Atos sandbox for testing.', 'jigoshop'),
				'tip' => '',
				'id' => 'jigoshop_atos_testmode',
				'std' => 'no',
				'type' => 'checkbox',
				'choices' => array(
					'no' => __('No', 'jigoshop'),
					'yes' => __('Yes', 'jigoshop')
				)
			);
			$defaults[] = array(
				'name' => __('Sandbox email address', 'jigoshop'),
				'desc' => '',
				'tip' => __('Please enter your Sandbox Merchant email address for use as your sandbox storefront if you have enabled the Atos sandbox.', 'jigoshop'),
				'id' => 'jigoshop_sandbox_email',
				'std' => '',
				'type' => 'midtext'
			);
			return $defaults;
		}

		/**
		 * Process the payment and return the result
		 */
		function process_payment($order_id){
			$order = new jigoshop_order($order_id);
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
			);
		}

/*		function init_form_fields() {

			$this->form_fields = array(
				'enabled'                => array(
					'title'   => __( 'Enable/Disable', 'atos' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Atos SIPS Module.', 'atos' ),
					'default' => 'no'
				),
				'title'                  => array(
					'title'       => __( 'Title:', 'atos' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'atos' ),
					'default'     => __( 'Cartes Bancaires', 'atos' )
				),
				'description'            => array(
					'title'       => __( 'Description:', 'atos' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'atos' ),
					'default'     => __( 'Paiement sécurisé par Carte Bancaire (Atos)', 'atos' )
				),
				'merchantid'             => array(
					'title'       => __( 'Merchant Id', 'atos' ),
					'type'        => 'text',
					'description' => __( 'identifiant de marchand donné par votre banque' ),
					'default'     => '005009461440411'
				),
				'pathfile'               => array(
					'title'       => __( 'pathfile', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Chemin vers le fichier pathfile donné par votre banque', 'atos' ),
					'default'     => '/homez.136/littlebii/cgi-bin/pathfile'
				),
				'path_bin_request'       => array(
					'title'       => __( 'path_bin_request', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Chemin vers le fichier request donné par votre banque', 'atos' ),
					'default'     => '/homez.136/littlebii/cgi-bin/request'
				),
				'path_bin_response'      => array(
					'title'       => __( 'path_bin_response', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Chemin vers le fichier response donné par votre banque', 'atos' ),
					'default'     => '/homez.136/littlebii/cgi-bin/response'
				),
				'cancel_return_url'      => array(
					'title'       => __( 'cancel_return_url', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Url de retour en cas d\'annulation', 'atos' ),
					'default'     => site_url( '/cancel' )
				),
				'normal_return_url'      => array(
					'title'       => __( 'normal_return_url', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Url de retour en cas de clic sur le bouton << Retour à la Boutique >>',
						'atos' ),
					'default'     => site_url( '/merci' )
				),
				'logo_id2'               => array(
					'title'       => __( 'logo_id2', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Image logo_id2.gif', 'atos' ),
					'default'     => 'logo_id2.gif'
				),
				'advert'                 => array(
					'title'       => __( 'advert', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Image advert.gif', 'atos' ),
					'default'     => 'advert.gif'
				),
				'logfile'                => array(
					'title'       => __( 'logfile', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Chemin vers le fichier texte log', 'atos' ),
					'default'     => '/homez.136/littlebii/cgi-bin/woocommerce/log.txt'
				),
				'automatic_response_url' => array(
					'title'       => __( 'automatic_response_url', 'atos' ),
					'type'        => 'text',
					'description' => __( 'Ne pas modifier', 'atos' ),
					'default'     => site_url( '/automatic_response_url.php' )
				),
			);
		}*/

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 **/
		public function admin_options() {
			echo '<h3>Atos SIPS</h3>';
			if ( ! file_exists( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/automatic_response.php' ) ) {
				$nonce_url = admin_url( '/admin.php?page=woocommerce&tab=payment_gateways&action=copyautomaticresponse' );

				echo '<p>Installation : Copier les fichiers atos <a href="' . $nonce_url . '">en cliquant ici</a></p>';
			} else {

				echo '<p>Verifier que en cliquant <a href="' . site_url( 'automatic_response.php' ) . '">ici</a> vous avez une page blanche</p>';
			}
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
			//echo '<p>Pour les developpers : <a href="http://wpcb.fr/woocommerce/wp-content/plugins/atos-gateway-woocommerce/sandbox.php">tester la reponse automatique</a></p>';
			if ( ( isset( $_GET['action'] ) ) && ( $_GET['action'] == 'copyautomaticresponse' ) ) {

				copy( dirname( __FILE__ ) . '/automatic_response.php',
					dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/automatic_response.php' );

			}
		}

		/**
		 *  There are no payment fields for atos, but we want to show the description if set.
		 **/
		function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}

		function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay with atos.',
					'atos' ) . '</p>';
			echo $this->generate_atos_form( $order );
		}

		function thankyou_page() {
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

		function showMessage( $content ) {
			return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
		}

		/**
		 * Generate atos button link
		 **/
		public function generate_atos_form( $order_id ) {
			global $woocommerce;
			$order = &new woocommerce_order( $order_id );
			// La variable order contient toutes les infos du panier et du client

			$pathfile = $this->pathfile;

			$path_bin_request = $this->path_bin_request;
			$parm             = 'merchant_id=' . $this->merchantid;

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

			$result = exec( "$path_bin_request $parm" );

			$tableau = explode( "!", "$result" );

			$code = $tableau[1];

			$error = $tableau[2];

			if ( ( $code == "" ) && ( $error == "" ) ) {

				$message = "<p>" . __( 'Error calling the atos api : exec request not found',
						'wpcb' ) . "  $path_bin_request</p>";

			} elseif ( $code != 0 ) {

				$message = "<p>" . __( 'Atos API error : ', 'wpcb' ) . " $error</p>";

			} else {

				// Affiche le formulaire avec le choix des cartes bancaires :

				$message = $tableau[3];
			}
			$parm_pretty = str_replace( ' ', '<br/>', $parm );
			$message .= '<p>You see this because you are in debug mode :</p><pre>' . $parm_pretty . '</pre><p>End of debug mode</p>';

			return $message;
		}
	}

	/**
	 * Add the gateway to Jigoshop
	 */
	add_filter('jigoshop_payment_gateways', function ($methods){
		$methods[] = 'Jigoshop_atos';
		return $methods;
	});
}
