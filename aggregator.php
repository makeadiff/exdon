<?php
require('common.php');

$madapp_db = 'makeadiff_madapp';
if(isset($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] == 'makeadiff.in') $madapp_db = 'makeadiff_madapp';

// Argument Parsing.
$city_id 		= i($QUERY,'city_id', 0);
$coach_id 		= i($QUERY,'coach_id', 0);
$donation_status= i($QUERY,'donation_status', 'any');
$donation_type 	= i($QUERY,'donation_type', 'any');
$group_type 	= i($QUERY,'group_type', 'any');
$vertical_id	= i($QUERY,'vertical_id', '0');
$format 		= i($QUERY, 'format', 'html');

// Build SQL with given Argument
$checks = array('1'	=> '1');
if($city_id) $checks[] 	= "U.city_id=$city_id";
if($coach_id) $checks[]	= "R.manager_id = $coach_id";
if($donation_status != 'any') {
	if($donation_status == 'DEPOSITED')
		$checks[] = "(D.donation_status = 'DEPOSIT_PENDING' OR D.donation_status = 'DEPOSIT COMPLETE' OR D.donation_status = 'RECEIPT SENT' OR D.donation_status = 'RECEIPT PENDING')";
	else if($donation_status == 'NOT_DEPOSITED')
		$checks[] = "(D.donation_status != 'DEPOSIT_PENDING' AND D.donation_status != 'DEPOSIT COMPLETE' AND D.donation_status != 'RECEIPT SENT' AND D.donation_status != 'RECEIPT PENDING')";
	else 
		$checks[] = "D.donation_status = '$donation_status'";
}
if($donation_type != 'any' and $donation_type != 'donut')		$checks[] = "D.donation_type = '$donation_type'";

include("../donutleaderboard/_city_filter.php");
$filter_array = array();
foreach ($city_date_filter as $this_city_id => $dates) {
	$filter_array[] = "(U.city_id=$this_city_id AND D.created_at >= '$dates[from] 00:00:00')";
}
$checks[] = "(" . implode(" OR ", $filter_array) . ")";

// Madapp Checks
$madapp_joins = array();
if($group_type != 'any') {
	$madapp_joins['UserGroup'] 	= "INNER JOIN $madapp_db.UserGroup MDUG ON U.madapp_user_id = MDUG.user_id";
	$madapp_joins['Group'] 		= "INNER JOIN $madapp_db.Group MDG ON MDG.id = MDUG.group_id";
	$checks[] = "MDG.type = '$group_type'";
}
if($vertical_id) {
	$madapp_joins['UserGroup'] 	= "INNER JOIN $madapp_db.UserGroup MDUG ON U.madapp_user_id = MDUG.user_id";
	$madapp_joins['Group'] 		= "INNER JOIN $madapp_db.Group MDG ON MDG.id = MDUG.group_id";
	$checks[] = "MDG.vertical_id = '$vertical_id'";
}
$all_madapp_joins = implode("\n", array_values($madapp_joins));

$all_group_types = array('national' => 'National', 'fellow' => 'Fellow', 'volunteer' => 'Volunteer', 'any' => 'Any');
$all_verticals = $sql->getById("SELECT id,name FROM `$madapp_db`.Vertical WHERE id NOT IN (6,10,11,12,13,14,15,16)");
$all_verticals[0] = 'Any';
$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");
$all_cities[0] = 'Any';

$all_coaches = $sql->getById("SELECT U.id, CONCAT(U.first_name, U.last_name) AS name, U.city_id FROM users U 
		INNER JOIN user_role_maps UR ON UR.user_id=U.id
		WHERE UR.role_id=9 AND U.is_deleted=0
		ORDER BY name");
$coaches = array();
foreach ($all_coaches as $this_coach_id => $coach_data) {
	if(!isset($coaches[$coach_data['city_id']])) $coaches[$coach_data['city_id']] = array('Any');
	$coaches[$coach_data['city_id']][$this_coach_id] = $coach_data['name'];
}
$coaches[0] = array("Any");

// Init
setlocale(LC_MONETARY, 'en_IN');
$external = array();
$donut = array();

if($donation_type != 'donut')
	$external = $sql->getAll("SELECT DISTINCT D.id,amount AS donation_amount, donation_type, DON.first_name AS donor_name, TRIM(CONCAT(U.first_name,' ', U.last_name)) AS fundraiser_name, U.phone_no AS fundraiser_phone, U.email AS fundraiser_email,
			donation_status, D.created_at,'external' AS source 
		FROM external_donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
		LEFT JOIN reports_tos R ON U.id=R.user_id
		INNER JOIN donours DON ON DON.id=D.donor_id
		$all_madapp_joins
		WHERE " . implode(" AND ", $checks));
if($donation_type == 'donut' or $donation_type == 'any')
	$donut = $sql->getAll("SELECT  DISTINCT D.id,donation_amount, 'donut' AS donation_type, DON.first_name AS donor_name, CONCAT(U.first_name,' ', U.last_name) AS fundraiser_name, U.phone_no AS fundraiser_phone, U.email AS fundraiser_email, 
			donation_status,  D.created_at, 'donut' AS source 
		FROM donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
		LEFT JOIN reports_tos R ON U.id=R.user_id
		INNER JOIN donours DON ON DON.id=D.donour_id
		$all_madapp_joins
		WHERE donation_type='GEN' AND " . implode(" AND ", $checks));
$all_donations = array_merge($external, $donut);

$total_amount = 0;
$total_deposited = 0;
$total_late = 0;
$total_late_1_weeks = 0;
$total_late_2_weeks = 0;
$total_late_3_weeks = 0;
$total_late_4_or_more_weeks = 0;
foreach ($all_donations as $i => $don) {
	$all_donations[$i]['amount_deposited'] = 0;
	$all_donations[$i]['amount_late_1_weeks'] = 0;
	$all_donations[$i]['amount_late_2_weeks'] = 0;
	$all_donations[$i]['amount_late_3_weeks'] = 0;
	$all_donations[$i]['amount_late_4_or_more_weeks'] = 0;
	
	// Deposited donations.
	if($don['donation_status'] == 'DEPOSIT COMPLETE' or $don['donation_status'] == 'RECEIPT SENT' or $don['donation_status'] == 'DEPOSIT_PENDING' or $don['donation_status'] == 'RECEIPT PENDING') { // or $don['donation_type'] != 'donut'
		$all_donations[$i]['amount_deposited'] = $don['donation_amount'];
		$total_deposited += $don['donation_amount'];
	
	// Undeposited donations
	} else {
		$datetime1 = new DateTime($don['created_at']);
		$datetime2 = new DateTime(date("Y-m-d H:i:s"));
		$interval = $datetime1->diff($datetime2);

		if($interval->format("%a") > 28) {
			$all_donations[$i]['amount_late_4_or_more_weeks'] = $don['donation_amount'];
			$total_late_4_or_more_weeks += $don['donation_amount'];
		} else if($interval->format("%a") > 21) {
			$all_donations[$i]['amount_late_3_weeks'] = $don['donation_amount'];
			$total_late_3_weeks += $don['donation_amount'];
		} else if($interval->format("%a") > 14) {
			$all_donations[$i]['amount_late_2_weeks'] = $don['donation_amount'];
			$total_late_2_weeks += $don['donation_amount'];
		} else if($interval->format("%a") > 7) {
			$all_donations[$i]['amount_late_1_weeks'] = $don['donation_amount'];
			$total_late_1_weeks += $don['donation_amount'];
		}
		$total_late += $don['donation_amount'];
	}

	$total_amount += $don['donation_amount'];
}

$all_donation_types = array(
		'donut'			=> 'Cash/Cheque',
		'nach' 			=> 'NACH',
		'global_giving'	=> 'Global Giving',
		'mad_website'	=> 'MAD Website',
		'give_india' 	=> 'Give India',
		'other'			=> "Other",
		'any'			=> 'Any'
	);
$all_donation_status = array(
		'any'					=> 'Any',
		'DEPOSITED'				=> 'Not Deposited',
		'NOT_DEPOSITED'			=> 'Deposited',
		'TO_BE_APPROVED_BY_POC'	=> 'With Volunteer',
		'HAND_OVER_TO_FC_PENDING'=>'With Coach',
		'DEPOSIT_PENDING'		=> 'In National Account(Unapproved)',
		'DEPOSIT COMPLETE'		=> 'In National Account(Approved)',
		'RECEIPT PENDING'		=> 'In National Account(Unapproved)',
		'RECEIPT SENT'			=> 'In National Account(Approved)',
	);

$html = new HTML;

// The other includes
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);

$page_title = 'Deposite Aggregator';

if($format == 'csv') render('aggregator_csv.php', false);
else render();