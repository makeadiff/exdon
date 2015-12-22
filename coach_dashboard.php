<?php
require 'common.php';
require '../donutleaderboard/_city_filter.php';

$html = new HTML;

$coach_id = i($QUERY,'coach_id', 0);
$donation_status = i($QUERY,'donation_status', 'any');

$coach_name = $sql->getOne("SELECT CONCAT(first_name, last_name) AS name FROM users WHERE id=$coach_id");

$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");

$all_volunteers = $sql->getById("SELECT U.id, CONCAT(U.first_name, U.last_name) AS name FROM users U 
		INNER JOIN reports_tos RT ON RT.user_id=U.id
		WHERE U.is_deleted=0 AND RT.manager_id=$coach_id");

$all_donations = array();
$external = array();
$donut = array();

$donation_status_check = '';

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='RECEIPT SENT'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='RECEIPT SENT'";
$donut = $sql->getAll("SELECT D.fundraiser_id AS id, SUM(D.donation_amount) AS donation_amount
		FROM donations D 
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE D.fundraiser_id IN (". implode(",", array_keys($all_volunteers)).") $donation_status_check AND $city_checks
		GROUP BY D.fundraiser_id");

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='DEPOSIT COMPLETE'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='DEPOSIT COMPLETE'";
$external = $sql->getAll("SELECT D.fundraiser_id AS id, SUM(D.amount) AS donation_amount
		FROM external_donations D 
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE D.fundraiser_id IN (". implode(",", array_keys($all_volunteers)).") $donation_status_check AND $city_checks
		GROUP BY D.fundraiser_id");


$all_donations = array_merge($external, $donut);
$amount_template = array(
		'100'			=>	0,
		'100_amount'	=>	0,
		'12K'			=>	0,
		'12K_amount'	=>	0,
		'1L'			=>	0,
		'1L_amount'		=>	0,
	);
$donations = array('total'	=> $amount_template);
foreach ($all_volunteers as $volunteer_id => $name) {
	$donations[$volunteer_id] = $amount_template;
}

foreach ($all_donations as $i => $don) {
	if($don['donation_amount'] > 100000) {
		$donations['total']['1L']++;
		$donations['total']['1L_amount'] += $don['donation_amount'];

		$donations[$don['id']]['1L']++;
		$donations[$don['id']]['1L_amount'] += $don['donation_amount'];		
	} elseif($don['donation_amount'] > 12000) {
		$donations['total']['12K']++;
		$donations['total']['12K_amount'] += $don['donation_amount'];

		$donations[$don['id']]['12K']++;
		$donations[$don['id']]['12K_amount'] += $don['donation_amount'];
	} elseif($don['donation_amount'] > 100) {
		$donations['total']['100']++;
		$donations['total']['100_amount'] += $don['donation_amount'];

		$donations[$don['id']]['100']++;
		$donations[$don['id']]['100_amount'] += $don['donation_amount'];		
	}
}

render();
