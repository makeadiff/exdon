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
if(isset($city_checks)) $city_checks = ' AND ' . $city_checks . ' AND users.is_deleted = 0';
else $city_checks = 'users.is_deleted = 0';


if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='RECEIPT SENT'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='RECEIPT SENT'"; // TO BE APPROVED BY POC and HAND OVER TO FC PENDING is not deposited. Rest all is deposited
$donut = $sql->getById("SELECT D.fundraiser_id AS id, SUM(D.donation_amount) AS donation_amount, R.manager_id
		FROM donations D 
		INNER JOIN reports_tos R ON R.user_id=D.fundraiser_id
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE R.manager_id IN (". implode(",", array_keys($all_coaches)).") $donation_status_check $city_checks
		GROUP BY D.fundraiser_id");

if($donation_status == 'deposited') $donation_status_check = " AND D.donation_status='DEPOSIT COMPLETE'";
if($donation_status == 'not_deposited') $donation_status_check = " AND D.donation_status!='DEPOSIT COMPLETE'";
$external = $sql->getById("SELECT D.fundraiser_id AS id, SUM(D.amount) AS donation_amount, R.manager_id
		FROM external_donations D 
		INNER JOIN reports_tos R ON R.user_id=D.fundraiser_id
		INNER JOIN users ON users.id=D.fundraiser_id
		WHERE R.manager_id IN (". implode(",", array_keys($all_coaches)).") $donation_status_check $city_checks
		GROUP BY D.fundraiser_id");
$all_donations = array();


foreach ($donut as $fundraiser_id => $data) {

	if (isset($external[$fundraiser_id])) {
		$all_donations[$fundraiser_id]['donation_amount'] = $donut[$fundraiser_id]['donation_amount'] + $external[$fundraiser_id]['donation_amount'];
		$all_donations[$fundraiser_id]['manager_id'] = $data['manager_id'];
	} else {
		$all_donations[$fundraiser_id] = $data;
	}
}

foreach ($external as $fundraiser_id => $data) {

	if (!isset($donut[$fundraiser_id])) {
		$all_donations[$fundraiser_id] = $data;
	}
}


$amount_template = array(
		'donuted'			=>	0,
		'donuted_amount'	=>	0,
		'donuted_percent'	=>	0,
		'4K'			=>	0,
		'4K_amount'	=>	0,
		'4K_percent'	=>	0,
		'8K'			=>	0,
		'8K_amount'	=>	0,
		'8K_percent'	=>	0,
		'6K'			=>	0,
		'6K_amount'	=>	0,
		'6K_percent'	=>	0,
		'12K'			=>	0,
		'12K_amount'	=>	0,
		'12K_percent'	=>	0,
		'1L'			=>	0,
		'1L_amount'		=>	0,
		'1L_percent'	=>	0,
		'total'			=>	0,
	);
$donations = array('total'	=> $amount_template);
foreach ($all_coaches as $coach_id => $name) {
	$donations[$coach_id] = $amount_template;
}

foreach ($all_donations as $i => $don) {

	$donations[$don['manager_id']]['total'] += $don['donation_amount'];
	$donations['total']['total'] += $don['donation_amount'];

	if($don['donation_amount'] > 100000) {
		$donations['total']['1L']++;
		$donations['total']['1L_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['1L']++;
		$donations[$don['manager_id']]['1L_amount'] += $don['donation_amount'];

	}
	if($don['donation_amount'] > 12000) {
		$donations['total']['12K']++;
		$donations['total']['12K_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['12K']++;
		$donations[$don['manager_id']]['12K_amount'] += $don['donation_amount'];

	}
	if($don['donation_amount'] > 8000) {
		$donations['total']['8K']++;
		$donations['total']['8K_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['8K']++;
		$donations[$don['manager_id']]['8K_amount'] += $don['donation_amount'];

	}
	if($don['donation_amount'] > 6000) {
		$donations['total']['6K']++;
		$donations['total']['6K_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['6K']++;
		$donations[$don['manager_id']]['6K_amount'] += $don['donation_amount'];

	}
	if($don['donation_amount'] > 4000) {
		$donations['total']['4K']++;
		$donations['total']['4K_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['4K']++;
		$donations[$don['manager_id']]['4K_amount'] += $don['donation_amount'];

	}
	if($don['donation_amount'] > 0) {
		$donations['total']['donuted']++;
		$donations['total']['donuted_amount'] += $don['donation_amount'];

		$donations[$don['manager_id']]['donuted']++;
		$donations[$don['manager_id']]['donuted_amount'] += $don['donation_amount'];
	}
}
$total_donation_count = count($all_donations);
if($total_donation_count) {
	foreach($donations as $index => $value) {
		if($index == 'total' or !isset($couch_volunteers_count[$index]))
			continue;
		$donations[$index]['donuted_percent'] = round($donations[$index]['donuted'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
		$donations[$index]['4K_percent'] = round($donations[$index]['4K'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
		$donations[$index]['8K_percent'] = round($donations[$index]['8K'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
		$donations[$index]['6K_percent'] = round($donations[$index]['6K'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
		$donations[$index]['12K_percent'] = round($donations[$index]['12K'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
		$donations[$index]['1L_percent'] = round($donations[$index]['1L'] / $couch_volunteers_count[$index] * 100, 0,PHP_ROUND_HALF_DOWN);
	}

	$donations['total']['donuted_percent'] = round($donations['total']['donuted'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
	$donationsPHP_ROUND_HALF_DOWN['total']['4K_percent'] = round($donations['total']['4K'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
	$donationsPHP_ROUND_HALF_DOWN['total']['6K_percent'] = round($donations['total']['6K'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
	$donationsPHP_ROUND_HALF_DOWN['total']['8K_percent'] = round($donations['total']['8K'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
	$donationsPHP_ROUND_HALF_DOWN['total']['12K_percent'] = round($donations['total']['12K'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
	$donations['total']['1L_percent'] = round($donations['total']['1L'] / $total_volunteers * 100, 0,PHP_ROUND_HALF_DOWN);
}


render();
