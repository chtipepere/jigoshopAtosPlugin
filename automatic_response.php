<?php
require_once( '../../../wp-load.php' );
//require_once( 'wp-load.php' ); // Necessaire pour aller chercher les options

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
		'data'               => $tableau[32],

	);
	$order    = new jigoshop_order( $response['orderid'] );
	if ( ( $response['code'] == '' ) && ( $response['error'] == '' ) ) {

		$message = "erreur appel response\n executable response non trouve $path_bin_response\n Session Id : $sessionid";

		jigoshop_log($message);

		$atos->msg['class']   = 'error';
		$atos->msg['message'] = 'Thank you for shopping with us. However, the transaction has been declined.';

	} elseif ( $response['code'] != 0 ) {

		$message = sprintf(" API call error.\n Error message: %s\n Session id: %s", $error, $sessionid);

		jigoshop_log($message);


		$atos->msg['class']   = 'error';
		$atos->msg['message'] = 'Thank you for shopping with us. However, the transaction has been declined.';

	} else {

		// Ok, Sauvegarde dans la base de donnée du shop.

		if ( $response['code'] == 00 ) {

			$message = "-----------SALES----------------------------\n";

			foreach ( $response as $k => $v ) {

				$message .= $k . " = " . $v . "\n";

			}

			$message .= "-------------------------------------------\n";

			jigoshop_log($message);

			$transauthorised = true;
			$order->add_order_note( 'Paiement CB reçu en banque' );
			$order->payment_complete();
			$woocommerce->cart->empty_cart();

		}

	}
	if ( $transauthorised == false ) {
		$order->update_status( 'failed' );
		$order->add_order_note( 'Failed' );
		$order->add_order_note( $atos->msg['message'] );
	}
} // end of check post
