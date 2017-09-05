<?php
require 'common.php';
require '../includes/classes/API.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$sql->options['error_handling'] = 'die';
$api = new API;

$api->post('/donation/add', function() {
	global $QUERY;

	if(isset($QUERY['created_at']) and $QUERY['created_at']) $QUERY['created_at'] = date("Y-m-d", strtotime($QUERY['created_at']));
	else $QUERY['created_at'] = date('Y-m-d H:i:s');
	if(isset($QUERY['donation_type']) and $QUERY['donation_type'] == 'gg') $QUERY['donation_type'] = 'globalgiving';

	$donations = new Donation;
	$donation_id = $donations->add($QUERY);

	if($donation_id) showSuccess("Donation inserted succesfully : Donation ID '.$donation_id.'", array("donation" => array("id" => $donation_id)));
	else showError("Failure in inserting donation at server. Try again after some time.");
});

$api->post('/donation/validate', function() {
	global $QUERY;

	if(isset($QUERY['created_at']) and $QUERY['created_at']) $QUERY['created_at'] = date("Y-m-d", strtotime($QUERY['created_at']));
	else $QUERY['created_at'] = date('Y-m-d H:i:s');
	if(isset($QUERY['donation_type']) and $QUERY['donation_type'] == 'gg') $QUERY['donation_type'] = 'globalgiving';

	$donation = new Donation;
	$result = $donation->validate($QUERY);

	if($result) showSuccess("Validated successfully");
	else showError($donation->error);
});

$api->get('/donation/get_donations_by_user/{fundraiser_id}', function ($fundraiser_id) {
	$donation = new Donation;
	$my_donations = $donation->getDonationsByUser($fundraiser_id);

	if($my_donations)
		showSuccess(count($my_donations) . " donation(s).", array('donations' => $my_donations));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations by this user";
		showError($error);
	}
});

$api->get('/donation/get_total_donation_by_email/{user_email}', function($user_email) {
	$donation = new Donation;
	$total = $donation->getTotalDonations(array('email' => $user_email));

	showSuccess("Donation Amount: $total", array('total' => $total));
});

$api->get('/donation/get_total_donation_by_email_for_fraise/{user_email}', function($user_email) {
	$donation = new Donation;
	$total = $donation->getTotalDonations(array('email' => $user_email));

	if($total) {
		print $total;
	}else{
		print "0";
	}

});

$api->get('/donation/get_donations_for_poc_approval/{poc_id}', function ($poc_id) {
	$donation = new Donation;
	$donations_for_approval = $donation->getDonationsForPocApproval($poc_id);

	if($donations_for_approval)
		showSuccess(count($donations_for_approval) . " donation(s) waiting for approval", array('donations' => $donations_for_approval));
	else {
		$error = $donation->error;
		if(!$error) $error = "No donations to be collected.";
		showError($error);
	}
});

$api->get('/donation/get_donations_for_fc_approval/{poc_id}', function ($poc_id) {
	$donation = new Donation;
	$donations_for_approval = $donation->getDonationsForFcApproval($poc_id);

	if($donations_for_approval)
		showSuccess(count($donations_for_approval) . " donation(s) waiting for approval", array('donations' => $donations_for_approval));
	else {
		$error = $donation->error;
		if(!$error) $error = "No donations to be deposited.";
		showError($error);
	}
});

$api->get('/donation/get_poc_approved_donations/{poc_id}', function ($poc_id) {
	$donation = new Donation;
	$approved_donations = $donation->getPocApprovedDonations($poc_id);

	if($approved_donations)
		showSuccess(count($approved_donations) . " approved donation(s).", array('donations' => $approved_donations));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that's collected.";
		showError($error);
	}
});

$api->get('/donation/get_fc_approved_donations/{fc_id}', function ($fc_id) {
	$donation = new Donation;
	$approved_donations = $donation->getFcApprovedDonations($fc_id);

	if($approved_donations)
		showSuccess(count($approved_donations) . " approved donation(s).", array('donations' => $approved_donations));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that's deposited.";
		showError($error);
	}
});

$api->get('/donation/get_donations/{poc_id}/{status}', function ($poc_id, $status) {
	$donation = new Donation;
	$donations_matched = $donation->search(array('poc_id' => $poc_id, 'status' => $status));

	if($donations_matched)
		showSuccess(count($donations_matched) . " donation(s).", array('donations' => $donations_matched));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations.";
		showError($error);
	}
});

$api->get('/donation/{donation_id}/poc_approve/{poc_id}', function ($donation_id, $poc_id) {
	$donation = new Donation;
	if($donation->pocApprove($donation_id, $poc_id)) {
		showSuccess("Donation approved", array('donation_id' => $donation_id));
	} else showError($donation->error);
});

$api->get('/donation/{donation_id}/poc_reject/{poc_id}', function ($donation_id, $poc_id) {
	$donation = new Donation;
	if($donation->pocReject($donation_id, $poc_id)) {
		showSuccess("Donation rejected", array('donation_id' => $donation_id));
	} else showError($donation->error);
});


$api->get('/donation/{donation_id}/fc_approve/{fc_id}', function ($donation_id, $fc_id) {
	$donation = new Donation;
	if($donation->fcApprove($donation_id, $fc_id)) {
		showSuccess("Donation approved", array('donation_id' => $donation_id));
	} else showError($donation->error);
});

$api->get('/donation/{donation_id}/fc_reject/{fc_id}', function ($donation_id, $fc_id) {
	$donation = new Donation;
	if($donation->fcReject($donation_id, $fc_id)) {
		showSuccess("Donation rejected", array('donation_id' => $donation_id));
	} else showError($donation->error);
});

$api->get('/donation/{donation_id}/delete/{poc_id}/{fc_poc}', function ($donation_id, $poc_id, $fc_poc) {
	$donation = new Donation;
	if($donation->remove($donation_id, $poc_id, $fc_poc)) {
		showSuccess("Donation deleted", array('donation_id' => $donation_id));
	} else showError($donation->error);
});

$api->request('/deposit/add', function() {
	global $QUERY;
	$deposit = new Deposit;

	$donation_ids = explode(",", $QUERY['donation_ids']);

	$deposit_id = $deposit->add($QUERY['collected_from_user_id'], $QUERY['given_to_user_id'], $donation_ids);

	if($deposit_id) showSuccess("Deposit made", array('deposit_id' => $deposit_id));
	else showError($deposit->error);
});
$api->request('/deposit/approve/{deposit_id}', function($deposit_id) {
	$deposit = new Deposit;
	$user_id = 151;
	$status = $deposit->approve($deposit_id, $user_id);

	if($status) showSuccess("Deposit Approved");
	else showError($deposit->error);
});

$api->request('/deposit/reject/{deposit_id}', function($deposit_id) {
	$deposit = new Deposit;
	$user_id = 151;
	$status = $deposit->reject($deposit_id, $user_id);

	if($status) showSuccess("Deposit Rejected");
	else showError($deposit->error);
});

$api->request("/user/login", function () {
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

$api->request("/user/get_subordinates/{user_id}", function ($user_id) {
	$user = new User;
	$subordinates = $user->getSubordinates($user_id);

	$return = array('subordinates' => $subordinates);

	showSuccess("Subordinate list returned", $return);
});

$api->notFound(function() {
	print "404";
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

/**
 * :TODO:
 * Authentication token!
 */