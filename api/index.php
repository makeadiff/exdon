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

$api->get('/donation/for_review_by/{reviewer_id}', function ($reviewer_id) {
	$donation = new Donation;
	$donations_for_approval = $donation->search(array('reviewer_id' => $reviewer_id));

	if($donations_for_approval)
		showSuccess(count($donations_for_approval) . " donation(s) waiting for review", array('donations' => $donations_for_approval));
	else {
		$error = $donation->error;
		if(!$error) $error = "No donations to be collected.";
		showError($error);
	}
});

$api->get('/donation/approved_by/{reviewer_id}', function ($reviewer_id) {
	$donation = new Donation;
	$approved_donations = $donation->search(array('approver_id' => $reviewer_id));

	if($approved_donations)
		showSuccess(count($approved_donations) . " approved donation(s).", array('donations' => $approved_donations));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that's collected.";
		showError($error);
	}
});

$api->get('/donation/approved_from_fundraiser/{fundraiser_id}', function ($fundraiser_id) {
	$donation = new Donation;
	$approved_donations = $donation->search(array('fundraiser_id' => $fundraiser_id, 'deposit_status' => true));

	if($approved_donations)
		showSuccess(count($approved_donations) . " approved donation(s).", array('donations' => $approved_donations));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that's collected.";
		showError($error);
	}
});

// $api->get('/donation/{donation_id}/delete/{poc_id}/{fc_poc}', function ($donation_id, $poc_id, $fc_poc) {
// 	$donation = new Donation;
// 	if($donation->remove($donation_id, $poc_id, $fc_poc)) {
// 		showSuccess("Donation deleted", array('donation_id' => $donation_id));
// 	} else showError($donation->error);
// });

/// Get all donations donuted by this user but hasn't been deposited yet
$api->request('/donation/undeposited/{fundraiser_id}', function ($fundraiser_id) {
	$donation = new Donation;
	$donations_matched = $donation->search(array('fundraiser_id' => $fundraiser_id, 'deposited' => false, 'include_deposit_info' => true));
	$approved_donations_matched = array();

	// Include donations raised by others - but approved by current user. For POCs
	$user = new User($fundraiser_id);
	if($user->hasRole($user->role_ids['CFR POC']) or $user->hasRole($user->role_ids['FC'])) {
		$approved_donations_matched = $donation->search(array('updated_by' => $fundraiser_id, 'deposited' => false, 'include_deposit_info' => true));
	}

	$donation_count = count($approved_donations_matched) + count($donations_matched);
	
	if($donation_count)
		showSuccess($donation_count . " donation(s).", array(
							'donations' => $donations_matched, 
							'approved_donations' => $approved_donations_matched,
							'count'	=> $donation_count
						));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations that needs to be deposited.";
		showError($error);
	}
});
$api->request('/donation/deposited/{fundraiser_id}', function ($fundraiser_id) {
	$donation = new Donation;
	$donations_matched = $donation->search(array('fundraiser_id' => $fundraiser_id, 'deposit_status' => 'approved', 'include_deposit_info' => true));

	if($donations_matched)
		showSuccess(count($donations_matched) . " donation(s).", array('donations' => $donations_matched));
	else {
		$error = $donation->error;
		if(!$error) $error = "Can't find any donations.";
		showError($error);
	}
});



$api->request('/deposit/add', function() {
	global $QUERY;
	$deposit = new Deposit;

	$donation_ids = explode(",", $QUERY['donation_ids']);

	$deposit_id = $deposit->add($QUERY['collected_from_user_id'], $QUERY['given_to_user_id'], $donation_ids);

	if($deposit_id) showSuccess("Deposit made", array('deposit_id' => $deposit_id));
	else showError($deposit->error);
});

/**
 * [{"deposit_id": 3, "amount": 3500, "collected_from_user_id": 16634, "given_to_user_id": 151, "added_on": "2017-09-09 16:25:48", "status": "pending", 
 * 		"donations": [ ... ]}]
 */
$api->get('/deposit/for_review_by/{reviewer_id}', function ($reviewer_id) {
	$deposit = new Deposit;
	$deposits_for_approval = $deposit->search(array('reviewer_id' => $reviewer_id));

	if($deposits_for_approval)
		showSuccess(count($deposits_for_approval) . " deposit(s) waiting for review", array('deposits' => $deposits_for_approval));
	else {
		$error = $deposit->error;
		if(!$error) $error = "No donations to be collected.";
		showError($error);
	}
});
$api->request('/deposit/{deposit_id}/approve/{reviewer_id}', function($deposit_id, $reviewer_id) {
	$deposit = new Deposit;
	$status = $deposit->approve($deposit_id, $reviewer_id);

	if($status) showSuccess("Deposit Approved", array('deposit_id' => $deposit_id));
	else showError($deposit->error);
});

$api->request('/deposit/{deposit_id}/reject/{reviewer_id}', function($deposit_id, $reviewer_id) {
	$deposit = new Deposit;
	$status = $deposit->reject($deposit_id, $reviewer_id);

	if($status) showSuccess("Deposit Rejected", array('deposit_id' => $deposit_id));
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

$api->get("/user/get_coaches_in_city/{city_id}", function($city_id) {
	$user = new User;
	$coaches = $user->getCoachesInCity($city_id);
	showSuccess(count($coaches) . ' coach(es) in city.', array('users' => $coaches));
});
$api->get("/user/get_finace_fellow_in_city/{city_id}", function($city_id) {
	$user = new User;
	$fc = $user->getFinanceFellowInCity($city_id);
	showSuccess(count($fc) . ' finance fellow in city.', array('users' => $fc));
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