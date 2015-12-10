<?php
require('common.php');

// Argument Parsing.
$city_id = i($QUERY,'city_id', 0);
$from = i($QUERY,'from', '2015-06-01');
$to = i($QUERY,'to', date('Y-m-d'));
$donation_status = i($QUERY,'donation_status', 'any');
$donation_type = i($QUERY,'donation_type', 'any');

// Build SQL with given Argument
$checks = array('1'	=> '1');
if($city_id) $checks[] 	= "U.city_id=$city_id";
if($from) $checks[] 	= "D.created_at > '$from 00:00:00'";
if($to) $checks[] 		= "D.created_at < '$to 23:59:59'";
if($donation_status != 'any')	{
	if($donation_status == 'DEPOSIT COMPLETE')
		$checks[] = "(D.donation_status = '$donation_status' OR D.donation_status = 'RECEIPT SENT')";
	else 
		$checks[] = "(D.donation_status != 'DEPOSIT COMPLETE' AND D.donation_status != 'RECEIPT SENT')";
}
if($donation_type != 'any' and $donation_type != 'donut')		$checks[] = "D.donation_type = '$donation_type'";

// Init
setlocale(LC_MONETARY, 'en_IN');
$external = array();
$donut = array();

if($donation_type != 'donut')
	$external = $sql->getAll("SELECT D.id,amount AS donation_amount, donation_type, donor_id, fundraiser_id, donation_status, D.created_at,'external' AS source 
		FROM external_donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
		WHERE " . implode(" AND ", $checks));
if($donation_type == 'donut' or $donation_type == 'any')
	$donut = $sql->getAll("SELECT D.id,donation_amount, 'donut' AS donation_type, donour_id AS donor_id, fundraiser_id, donation_status,  D.created_at, 'donut' AS source 
		FROM donations D
		INNER JOIN users U ON U.id=D.fundraiser_id
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

// This adds the total at the top
array_unshift($all_donations, array(
		'id'				=> 0,
		'type'				=> 'Total',
		'donation_amount'	=> money_format("%n", $total_amount),
		'amount_deposited'	=> money_format("%n", $total_deposited),
		'amount_late'		=> money_format("%n", $total_late),
		'donor_id'			=> '',
		'fundraiser_id'		=> '',
		'donation_status'	=> '',
	));

$crud = new Crud("external_donations");
$crud->title = "All Donations";
$crud->allow['add'] = false;
$crud->allow['searching'] = false;
$crud->allow['bulk_operations'] = false;
$crud->allow['edit'] = false;
$crud->allow['delete'] = false;
$crud->allow['sorting'] = false;

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

$crud->addField("donation_type", 'Type', 'enum', array(), $all_donation_types, 'select');
$crud->addListDataField("donor_id", "donours", "Donor", "", array('fields' => 'id,first_name'));
$crud->addListDataField("fundraiser_id", "users", "Fundraiser", "", array('fields' => 'id,CONCAT(first_name, " ", last_name) AS name'));

$html = new HTML;
$html->options['output'] = 'return';
$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");
$all_cities[0] = 'Any';

// Filtering code - goes on the top.
$crud->code['before_content'] = '<form action="" method="get" class="form-area">'
	. $html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities))
	. $html->buildInput("donation_type", 'Type', 'select', $donation_type, array('options' => $all_donation_types))
	. $html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => $all_donation_status))
	. $html->buildInput('from', 'From', 'text', $from, array('class' => 'date-picker'))
	. $html->buildInput('to', 'To', 'text', $to, array('class' => 'date-picker'))
	. $html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'))
	. '</form><br /><br />';
$html->options['output'] = 'print';


// The other includes
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);


render();