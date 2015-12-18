<?php
require('common.php');

// Argument Parsing.
$city_id = i($QUERY,'city_id', 44);
$coach_id = i($QUERY,'coach_id', 0);
$donation_status = i($QUERY,'donation_status', 'any');
$donation_type = i($QUERY,'donation_type', 'any');

// Build SQL with given Argument
$checks = array('1'	=> '1');
if($city_id) $checks[] 	= "U.city_id=$city_id";
if($coach_id) $checks[]	= "R.manager_id = $coach_id";
if($donation_status != 'any')	{
	if($donation_status == 'DEPOSIT COMPLETE')
		$checks[] = "(D.donation_status = '$donation_status' OR D.donation_status = 'RECEIPT SENT')";
	else 
		$checks[] = "(D.donation_status != 'DEPOSIT COMPLETE' AND D.donation_status != 'RECEIPT SENT')";
}
if($donation_type != 'any' and $donation_type != 'donut')		$checks[] = "D.donation_type = '$donation_type'";

include("../donutleaderboard/_city_filter.php");
$filter_array = array();
foreach ($city_date_filter as $this_city_id => $dates) {
	$filter_array[] = "(U.city_id=$this_city_id AND D.created_at >= '$dates[from] 00:00:00')";
}
$checks[] = "(" . implode(" OR ", $filter_array) . ")";


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

// Init
setlocale(LC_MONETARY, 'en_IN');
$external = array();
$donut = array();

if($donation_type != 'donut')
	$external = $sql->getAll("SELECT DISTINCT D.id,amount AS donation_amount, donation_type, DON.first_name AS donor_name, CONCAT(U.first_name,' ', U.last_name) AS fundraiser_name, 
			donation_status, D.created_at,'external' AS source 
		FROM external_donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
		INNER JOIN reports_tos R ON U.id=R.user_id
		INNER JOIN donours DON ON DON.id=D.donor_id
		WHERE " . implode(" AND ", $checks));
if($donation_type == 'donut' or $donation_type == 'any')
	$donut = $sql->getAll("SELECT  DISTINCT D.id,donation_amount, 'donut' AS donation_type, DON.first_name AS donor_name, CONCAT(U.first_name,' ', U.last_name) AS fundraiser_name, 
			donation_status,  D.created_at, 'donut' AS source 
		FROM donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
		INNER JOIN reports_tos R ON U.id=R.user_id
		INNER JOIN donours DON ON DON.id=D.donour_id
		WHERE donation_type='GEN' AND " . implode(" AND ", $checks));
$all_donations = array_merge($external, $donut);

$total_amount = 0;
$total_deposited = 0;
$total_late = 0;
foreach ($all_donations as $i => $don) {
	$all_donations[$i]['amount_deposited'] = 0;
	$all_donations[$i]['amount_late'] = 0;
	
	// Deposited donations.
	if($don['donation_status'] == 'DEPOSIT COMPLETE' or $don['donation_status'] == 'RECEIPT SENT') {
		$all_donations[$i]['amount_deposited'] = $don['donation_amount'];
		$total_deposited += $don['donation_amount'];
	
	// Undeposited donations
	} else {
		$datetime1 = new DateTime($don['created_at']);
		$datetime2 = new DateTime(date("Y-m-d H:i:s"));
		$interval = $datetime1->diff($datetime2);
		if($interval->format("%a") > 21) {
			$all_donations[$i]['amount_late'] = $don['donation_amount'];
			$total_late += $don['donation_amount'];
		}
	}

	$total_amount += $don['donation_amount'];
}


// $crud = new Crud("external_donations");
// $crud->title = "All Donations";
// $crud->allow['add'] = false;
// $crud->allow['searching'] = false;
// $crud->allow['bulk_operations'] = false;
// $crud->allow['edit'] = false;
// $crud->allow['delete'] = false;
// $crud->allow['sorting'] = false;

$all_donation_types = array(
		'donut'			=> 'Donut',
		'ecs' 			=> 'ECS',
		'global_giving'	=> 'Global Giving',
		'online'		=> 'Online',
		'other'			=> "Other",
		'any'			=> 'Any'
	);
$all_donation_status = array(
		'TO_BE_APPROVED_BY_POC'	=> 'Not Deposited',
		'DEPOSIT COMPLETE'		=> 'Deposited',
		'any'					=> 'Any'
	);

// $crud->addField("donation_type", 'Type', 'enum', array(), $all_donation_types, 'select');
// $crud->addListDataField("donor_id", "donours", "Donor", "", array('fields' => 'id,first_name'));
// $crud->addListDataField("fundraiser_id", "users", "Fundraiser", "", array('fields' => 'id,CONCAT(first_name, " ", last_name) AS name'));

$html = new HTML;

// The other includes
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);


render();