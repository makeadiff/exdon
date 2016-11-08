<?php
require('common.php');

// $opts = getOptions($QUERY);
// extract($opts);

$types = array('nach', 'global_giving', 'mad_website', 'give_india');

$data = $sql->getById("SELECT donation_type, SUM(amount) AS amount FROM external_donations WHERE donation_status='DEPOSIT COMPLETE' GROUP BY donation_type");
$total = 0;
foreach ($types as $t) {
	if(isset($data[$t]))
		$total += $data[$t];
}

$percentage = array();
foreach ($types as $t) {
	if($data[$t])
		$percentage[$t] = round($data[$t] / $total * 100, 2);
}
// dump($percentage); exit;

$page_title = 'Revenue Source Breakdown';
$weekly_graph_data = array();
$annual_graph_data = array(
		array('Total', 'Sources'),
		array('NACH',	$percentage['nach']),
		array('Global Giving',	$percentage['global_giving']),
		array('MAD Website',		$percentage['mad_website']),
		array('Give India',		$percentage['give_india']),
	);

render('../reports/template/graph.php');
