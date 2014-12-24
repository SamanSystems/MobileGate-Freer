<?php
/**
 * Plugin Name: Zarinpal Mobile Gate Module For Freer
 * Plugin URI: http://omidtak.ir
 * Version: 1.0 
 * Release Date : 2014 20 December
 * Author: Omid Aran
 * Author Email: info[at]omidtak[dot]ir
 */
 
require_once (dirname(__FILE__).'/include/configuration.php');
require_once (dirname(__FILE__).'/include/startSmarty.php');

$order_id = intval($post['order_id']);
$sql = "SELECT * FROM payment WHERE payment_rand = '{$order_id}' LIMIT 1;";		
$payment = $db->fetch($sql);
if ($payment[payment_status] == 2)
	die('ok');
exit;
?>