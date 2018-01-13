<?php
require '../common.php';

if(i($QUERY, 'action')) {
	$donation_id = i($QUERY, 'donation_id');
	$amount = i($QUERY, 'amount');

	$sql->execQuery("UPDATE external_donations SET donation_status='DEPOSIT COMPLETE', updated_at=NOW(), amount='$amount' WHERE id=$donation_id");
}

$status = [
	'TO_BE_APPROVED_BY_POC'	=> 'Pending Approval',
	'DEPOSIT COMPLETE'		=> 'Approved'
];

$nach = new SqlPager("SELECT D.*, DON.first_name AS donor_name, DON.phone_no AS donor_phone, DON.email_id AS donor_email, U.first_name AS fundraiser 
	FROM external_donations D
	INNER JOIN donours DON ON DON.id=donor_id
	INNER JOIN users U ON U.id=D.fundraiser_id
	WHERE donation_type = 'nach' AND D.created_at > '2017-08-01 00:00:00' -- AND donation_status='TO_BE_APPROVED_BY_POC'
	ORDER BY created_at DESC", 50);

$page = $nach->getPage();


render();
