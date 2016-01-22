<?php
require 'common.php';
require '../includes/classes/API.php';

header("Access-Control-Allow-Origin: *");

$sql->options['error_handling'] = 'die';
$api = new API;

$api->post('/donation/add', function() {
	global $QUERY;

	$QUERY['created_at'] 	= date("Y-m-d", strtotime($QUERY['created_at']));
	if($QUERY['donation_type'] == 'gg') $QUERY['donation_type'] = 'globalgiving';

	$donations = new Donation;
	$donation_id = $donations->add($QUERY);

	if($donation_id) showSuccess("Donation inserted succesfully : Donation ID '.$donation_id.'", array("donation" => array("id" => $donation_id)));
	else showError("Failure in insterting dontaion at server. Try again after some time.");
});

$api->get('/donation/get_donations_for_approval/{poc_id}', function ($poc_id) {
	$donation = new Donation;
	$donations_for_approval = $donation->getDonationsForApproval($poc_id);

	if($donations_for_approval)
		showSuccess(count($donations_for_approval) . " donation(s) waiting for approval", array('donations' => $donations_for_approval));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that need approval for this user";
		showError($error);
	}
});

$api->get('/donation/{donation_id}/approve/{poc_id}', function ($donation_id, $poc_id) {
	$donation = new Donation;
	$donation->approveDonation($donation_id, $poc_id);

	showSuccess("Donation approved", array('donation_id' => $donation_id));
});

$api->any("/user/login", function () {
	global $QUERY;
	$user = new User;

	$phone = i($QUERY, 'phone');
	$password = i($QUERY, 'password');
	if(!$user->login($phone, $password)) {
		showError($user->error, array('')); exit;
	}

	$return = array('user' => $user->user);
	$return['user']['roles'] = $user->getRoles();

	showSuccess("Login successful", $return);
});

$api->handle();


function showSuccess($message, $extra = array()) {
	showSituation('success', $message, $extra);
}
function showError($message, $extra = array()) {
	showSituation('error', $message, $extra);
}

function showSituation($status, $message, $extra) {
	$other_status = ($status == 'success') ? 'error' : 'success';
	$return = array($status => true, $other_status => false);

	if(is_string($message)) {
		$return[$status] = $message;

	} elseif(is_array($message)) {
		$return = array_merge($return, $message);
	} 

	$return = array_merge($return, $extra);

	print json_encode($return);
}