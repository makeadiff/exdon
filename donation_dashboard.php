<?php
require 'common.php';

$html = new HTML;

$fundraiser_id = i($QUERY,'fundraiser_id', 0);
$donation_status = i($QUERY,'donation_status', 'any');

$all_donations = array();
$external = array();
$donut = array();

$donation_status_check = '';

$fundraiser_name = $sql->getOne("SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE id=$fundraiser_id");

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='RECEIPT SENT'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='RECEIPT SENT'";
$donut = $sql->getAll("SELECT D.id, donation_amount AS amount, CONCAT(DON.first_name, ' ', DON.last_name) AS donor, D.created_at, D.donation_status
		FROM donations D 
		INNER JOIN donours DON ON DON.id=D.donour_id
		WHERE D.fundraiser_id = $fundraiser_id $donation_status_check");


if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='DEPOSIT COMPLETE'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='DEPOSIT COMPLETE'";
$external = $sql->getAll("SELECT D.id, D.amount, CONCAT(DON.first_name, ' ', DON.last_name) AS donor, D.created_at, D.donation_status
		FROM external_donations D 
		INNER JOIN donours DON ON DON.id=D.donor_id
		WHERE D.fundraiser_id = $fundraiser_id $donation_status_check");

$all_donations = array_merge($external, $donut);

render();
