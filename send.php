<?php
	require_once 'way.php';
	$to = $_REQUEST['to'];
	$msg = urldecode($_REQUEST['msg']);
	$sms = new way2sms();
	if(!$sms->login($_REQUEST['username'],$_REQUEST['password']))
	{
		echo "Error: Invalid username/password!";
		die();
	}
	$sms->sendsms($to, $msg);
	$sms->logout();
	echo "Sent successfully to $to";
?>
