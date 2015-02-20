<?php
/**
Plugin Name: JigoshopAtos
Text Domain: jigoshop-atos
Plugin URI: https://github.com/chtipepere/jigoshopAtosPlugin
Description: Extends Jigoshop with Atos SIPS gateway (French bank).
Version: 1.1
Author: πR

http://thomasdt.com/woocommerce/
**/

add_shortcode( 'jigoshop_atos_automatic_response', 'jigoshop_atos_automatic_response' );

function jigoshop_atos_automatic_response( $atts ) {
	$atos = new Jigoshop_atos();

	if ( isset( $_POST['DATA'] ) ) {
		$transauthorised = false;

		$data = escapeshellcmd( $_POST['DATA'] );

		$message = sprintf('message=%s', $data);
		$pathfile = sprintf('pathfile=%s', $atos->pathfile);

		$path_bin_response = $atos->path_bin_response;
		if ( $_POST['DATA'] == 'sandbox' ) {
			$result = $_POST['sandbox'];
		} else {
			$result = exec( "$path_bin_response $pathfile $message" );
		}

		$tableau = explode( '!', $result );

		$response = array(
			'code'               => $tableau[1],
			'error'              => $tableau[2],
			'merchantid'         => $tableau[3],
			'merchantcountry'    => $tableau[4],
			'amount'             => $tableau[5],
			'transactionid'      => $tableau[6],
			'paymentmeans'       => $tableau[7],
			'transmissiondate'   => $tableau[8],
			'paymenttime'        => $tableau[9],
			'paymentdate'        => $tableau[10],
			'responsecode'       => $tableau[11],
			'paymentcertificate' => $tableau[12],
			'authorisationid'    => $tableau[13],
			'currencycode'       => $tableau[14],
			'cardnumber'         => $tableau[15],
			'cvvflag'            => $tableau[16],
			'cvvresponsecode'    => $tableau[17],
			'bankresponsecode'   => $tableau[18],
			'complementarycode'  => $tableau[19],
			'complementaryinfo'  => $tableau[20],
			'returncontext'      => $tableau[21],
			'caddie'             => $tableau[22],
			'receiptcomplement'  => $tableau[23],
			'merchantlanguage'   => $tableau[24],
			'language'           => $tableau[25],
			'customerid'         => $tableau[26],
			'orderid'            => $tableau[27],
			'customeremail'      => $tableau[28],
			'customeripaddress'  => $tableau[29],
			'captureday'         => $tableau[30],
			'capturemode'        => $tableau[31],
			'data'               => $tableau[32]
		);
		$order    = new jigoshop_order( $response['orderid'] );
		if ( ( $response['code'] == '' ) && ( $response['error'] == '' ) ) {

			$message = sprintf(__("Response call error\n Response bin not found %s\n Session Id: %s", 'jigoshop-atos'), $path_bin_response, session_id());

			jigoshop_log($message);

			$atos->msg['class']   = 'error';
			$atos->msg['message'] = __('Thank you for shopping with us. However, the transaction has been declined.', 'jigoshop-atos');

		} elseif ( $response['code'] != 0 ) {

			$message = sprintf(__("API call error.\n Error message: %s\n Session id: %s", 'jigoshop-atos'), $response['error'], session_id());

			jigoshop_log($message);


			$atos->msg['class']   = 'error';
			$atos->msg['message'] = __('Thank you for shopping with us. However, the transaction has been declined.', 'jigoshop-atos');

		} else {

			if ( $response['code'] == 00 ) {

				$message = "-----------SALES----------------------------\n";

				foreach ( $response as $k => $v ) {

					$message .= $k . " = " . $v . "\n";

				}

				$message .= "-------------------------------------------\n";

				jigoshop_log($message);

				$transauthorised = true;
				$order->add_order_note( __('Payment accepted by the bank', 'jigoshop-atos') );
				$order->payment_complete();
			}

		}
		if ( $transauthorised == false ) {
			$order->update_status( 'failed' );
			$order->add_order_note( 'Failed' );
			$order->add_order_note( $atos->msg['message'] );
		}
	} else { // end of check post
		echo 'Precontdition failed.';
	}
}
