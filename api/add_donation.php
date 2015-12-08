<?php
require('../common.php');

$fundraiser_id 	= param('fundraiser_id');
$format 		= param('format', 'json');
$donor_name 	= param('name');
$donor_email 	= param('email');
$donor_phone 	= param('phone');
$amount 		= param('amount');
$created_at 	= date("Y-m-d", strtotime(param('created_at')));
$donation_type	= param('donation_type');

// Find the donor - both email and phone must be same
$sql->options['error_handling'] = 'die';
$donor_id = $sql->getOne("SELECT id FROM donours WHERE email_id='$donor_email' AND phone_no='$donor_phone'");

// If we can't find the donor, add a new one.
if(!$donor_id) {
	$donor_id = $sql->insert("donours", array(
			'first_name'	=> $donor_name,
			'email_id'		=> $donor_email,
			'phone_no'		=> $donor_phone,
			'created_at'	=> 'NOW()',
			'updated_at'	=> 'NOW()',
		));
}

// Insert the donation
$donation_id = $sql->insert("external_donations", array(
		'donation_type'	=> $donation_type,
		'donor_id'		=> $donor_id,
		'fundraiser_id'	=> $fundraiser_id,
		'amount'		=> $amount,
		'created_at'	=> $created_at,
		'updated_at'	=> 'NOW()',
		'donation_status'	=> 'TO_BE_APPROVED_BY_POC',
	));
// print $sql->_query;

if($donation_id)
	print '{"success": "Donation inserted succesfully : Donation ID '.$donation_id.'", "donation": {"id": '. $donation_id.'}}';
else 
	print '{"success": false, "error": "Failure in insterting dontaion at server. Try again after some time."}';