<?php
include('../../../wp-load.php');
copy(dirname(__FILE__) . '/automatic_response.php', dirname(dirname(dirname(dirname(__FILE__)))) . '/automatic_response.php');
$post_data['DATA'] = 'sandbox'; //Needed

$post_data['sandbox'] = 'NULL!0!2!005009461440410!fr!100!8755900!CB!10-02-2012!11:50!10-02-2012!004!certif!22!978!4974!545!1!22!Comp!sandbox!return!caddie!Merci!fr!fr!001!741!my@email.com!1.10.21.192!30!direct!data';

$response = wp_remote_post('http://wpcb.fr/woocommerce/automatic_response.php', ['body' => $post_data]);