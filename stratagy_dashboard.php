<?php
require 'common.php';
require '../donutleaderboard/_city_filter.php';
$html = new HTML;

$city_id = i($QUERY,'city_id', 44);
$donation_status = i($QUERY,'donation_status', 'any');

$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");

$all_coaches = $sql->getById("SELECT U.id, CONCAT(U.first_name, U.last_name) AS name, COALESCE(groups.name,'-') as group_name FROM users U
		INNER JOIN user_role_maps UR ON UR.user_id=U.id
		LEFT OUTER JOIN groups ON groups.id = U.group_id
		WHERE UR.role_id=9 AND U.is_deleted=0 AND U.city_id=$city_id");

$couch_volunteers_count = $sql->getById("SELECT R.manager_id, COUNT(U.id) 
	FROM users U
	INNER JOIN reports_tos R ON R.user_id=U.id
	WHERE R.manager_id IN (". implode(",", array_keys($all_coaches)) . ") 
	GROUP BY R.manager_id");
$total_volunteers = array_sum(array_values($couch_volunteers_count));


$all_donations = array();
$external = array();
$donut = array();

$donation_status_check = '';
if(isset($city_checks)) $city_checks = ' AND ' . $city_checks;
else $city_checks = '';

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='RECEIPT SENT'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='RECEIPT SENT'"; // TO BE APPROVED BY POC and HAND OVER TO FC PENDING is not deposited. Rest all is deposited
$donut = $sql->getAll("SELECT D.fundraiser_id AS id, SUM(D.donation_amount) AS donation_amount, R.manager_id 
		FROM donations D 
		INNER JOIN reports_tos R ON R.user_id=D.fundraiser_id
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE R.manager_id IN (". implode(",", array_keys($all_coaches)).") $donation_status_check $city_checks
		GROUP BY D.fundraiser_id");

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='DEPOSIT COMPLETE'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='DEPOSIT COMPLETE'";
$external = $sql->getAll("SELECT D.fundraiser_id AS id, SUM(D.amount) AS donation_amount, R.manager_id 
		FROM external_donations D 
		INNER JOIN reports_tos R ON R.user_id=D.fundraiser_id
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE R.manager_id IN (". implode(",", array_keys($all_coaches)).") $donation_status_check $city_checks
		GROUP BY D.fundraiser_id");


$all_donations = array_merge($external, $donut);
$amount_template = array(
		'100'			=>	0,
		'100_amount'	=>	0,
		'100_percent'	=>	0,
		'12K'			=>	0,
		'12K_amount'	=>	0,
		'12K_percent'	=>	0,
		'1L'			=>	0,
		'1L_amount'		=>	0,
		'1L_percent'	=>	0,
	);
$donations = array('total'	=> $amount_template);
foreach ($all_coaches as $coach_id => $name) {
	$donations[$coach_id] = $amount_template;
}

foreach ($all_donations as $i => $don) {
	if($don['donation_amount'] > 100000) {
		$donations['total']['1L']++;
		$donations['total']['1L_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['1L']++;
		$donations[$don['manager_id']]['1L_amount'] += $don['donation_amount'];		
	} elseif($don['donation_amount'] > 12000) {
		$donations['total']['12K']++;
		$donations['total']['12K_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['12K']++;
		$donations[$don['manager_id']]['12K_amount'] += $don['donation_amount'];
	} elseif($don['donation_amount'] > 100) {
		$donations['total']['100']++;
		$donations['total']['100_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['100']++;
		$donations[$don['manager_id']]['100_amount'] += $don['donation_amount'];		
	}
}
$total_donation_count = count($all_donations);
if($total_donation_count) {
	foreach($donations as $index => $value) {
		$donations[$index]['100_percent'] = round($donations[$index]['100'] / $total_volunteers * 100, 2);
		$donations[$index]['12K_percent'] = round($donations[$index]['12K'] / $total_volunteers * 100, 2);
		$donations[$index]['1L_percent'] = round($donations[$index]['1L'] / $total_volunteers * 100, 2);
	}
}


render();
