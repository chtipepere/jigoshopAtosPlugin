<?php
/** JigoshopAtos **/

add_shortcode( 'jigoshop_atos_automatic_response', 'jigoshop_atos_automatic_response' );

function jigoshop_atos_automatic_response( $atts ) {
	$atos = new Jigoshop_atos();

	if ( isset( $_POST['DATA'] ) ) {
		$transauthorised = false;

		$data = escapeshellcmd( sanitize_text_field($_POST['DATA']) );

		$message = sprintf('message=%s', $data);
		$pathfile = sprintf('pathfile=%s', $atos->pathfile);

		$path_bin_response = $atos->path_bin_response;
		$result = exec( "$path_bin_response $pathfile $message" );

		$results = explode( '!', $result );

		$response = array(
			'code'               => $results[1],
			'error'              => $results[2],
			'merchantid'         => $results[3],
			'merchantcountry'    => $results[4],
			'amount'             => $results[5],
			'transactionid'      => $results[6],
			'paymentmeans'       => $results[7],
			'transmissiondate'   => $results[8],
			'paymenttime'        => $results[9],
			'paymentdate'        => $results[10],
			'responsecode'       => $results[11],
			'paymentcertificate' => $results[12],
			'authorisationid'    => $results[13],
			'currencycode'       => $results[14],
			'cardnumber'         => $results[15],
			'cvvflag'            => $results[16],
			'cvvresponsecode'    => $results[17],
			'bankresponsecode'   => $results[18],
			'complementarycode'  => $results[19],
			'complementaryinfo'  => $results[20],
			'returncontext'      => $results[21],
			'caddie'             => $results[22],
			'receiptcomplement'  => $results[23],
			'merchantlanguage'   => $results[24],
			'language'           => $results[25],
			'customerid'         => $results[26],
			'orderid'            => $results[27],
			'customeremail'      => $results[28],
			'customeripaddress'  => $results[29],
			'captureday'         => $results[30],
			'capturemode'        => $results[31],
			'data'               => $results[32]
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
		echo __('Precondition failed.', 'jigoshop-atos');
	}
}
