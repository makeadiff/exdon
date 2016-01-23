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
if($donation_status != 'any')	$checks[] = "D.donation_status = '$donation_status'";
if($donation_type != 'any')		$checks[] = "D.donation_type = '$donation_type'";

// Approval and un approval 
if(i($QUERY, 'status_action') == 'approve') {
	$id = $QUERY['select_row'][0];
	$sql->update("external_donations", array('donation_status' => 'DEPOSIT COMPLETE'), "id=$id");
} elseif(i($QUERY, 'status_action') == 'disapprove') {
	$id = $QUERY['select_row'][0];
	$sql->update('external_donations', array('donation_status' => 'TO_BE_APPROVED_BY_POC'), "id=$id");
}

// Initialize all necessany things. 
$crud = new Crud("external_donations");
$page_title = $crud->title = "External Donations Approval";
$crud->allow['add'] = false;
$crud->allow['edit'] = false;
$crud->allow['sorting'] = false;

$html = new HTML;
$html->options['output'] = 'return';

$all_donation_types = array(
		'ecs' 			=> 'ECS',
		'globalgiving'	=> 'Global Giving',
		'online'		=> 'Online',
		'other'			=> "Other",
		'any'			=> 'Any'
	);
$all_donation_status = array(
		'TO_BE_APPROVED_BY_POC'	=> 'Not Deposited',
		'DEPOSIT COMPLETE'		=> 'Deposited',
		'any'					=> 'Any'
	);
$all_cities = $sql->getById("SELECT id,name FROM cities ORDER BY name");
$all_cities[0] = 'Any';

// Filtering code - goes on the top.
$crud->code['before_content'] = '<form action="" method="post" class="form-area">'
	. $html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities))
	. '<div id="select-date-area">'
	. $html->buildInput("donation_type", 'Type', 'select', $donation_type, array('options' => $all_donation_types))
	. $html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => $all_donation_status))
	. $html->buildInput('from', 'From', 'text', $from, array('class' => 'date-picker'))
	. $html->buildInput('to', 'To', 'text', $to, array('class' => 'date-picker'))
	. '</div><a href="#" id="select-date-toggle">More Options</a><br />'
	. $html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'))
	. '</form><br /><br />';
$html->options['output'] = 'print';

// The SQL for the listing 
$crud->setListingQuery("SELECT D.* FROM external_donations D 
	INNER JOIN users U ON U.id=D.fundraiser_id
	WHERE " . implode(" AND ", $checks));

// Fields customization.
$crud->addField("donation_type", 'Type', 'enum', array(), $all_donation_types, 'select');
$crud->addListDataField("donor_id", "donours", "Donor", "", array('fields' => 'id,first_name'));
$crud->addListDataField("fundraiser_id", "users", "Fundraiser", "", array('fields' => 'id,CONCAT(first_name, " ", last_name) AS name'));
$crud->addListingField('Status', array('html'=>'($row["donation_status"] == "DEPOSIT COMPLETE")'
 	. ' ? "<span class=\"with-icon success\">Deposited - <a href=\'?status_action=disapprove&select_row[]=$row[id]\'>Undo Approval?</a></span>"'
 	. ' : "<span class=\"with-icon error\">Not Deposited Yet - <a href=\'?status_action=approve&select_row[]=$row[id]\'>Approve?</a></span>"'));

// Show only the listing 
$crud->setListingFields("donation_type", "amount", "donor_id", "fundraiser_id", "created_at", 'status');
$crud->setSearchFields('amount', 'donor_id', 'fundraiser_id');

// The other includes
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);

render();