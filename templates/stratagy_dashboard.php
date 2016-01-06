<form action="" method="get" class="form-area">
<?php 
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => array('any' => 'Any', 'deposited' => 'Deposited', 'not_deposited' => 'Not Deposited')));
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />

<table class="table table-striped">
<tr><th>Coach</th><th class="line">Volunter Count</th><th colspan="3" class="amounts line">100+</th><th colspan="3" class="amounts line">12000+</th><th colspan="3" class="amounts">100000+</th></tr>
<tr><th></th><th class="line"></th><th>Count</th><th>Percentage</th><th class="line">Total Amount</th><th>Count</th><th>Percentage</th><th class="line">Total Amount</th><th>Count</th><th>Percentage</th><th>Total Amount</th></tr>
<tr>
	<td>All</td>
	<td class="line"><?php echo $total_volunteers ?></td>
	<td><?php echo $donations['total']['100'] ?></td>
	<td><?php echo $donations['total']['100_percent'] ?>%</td>
	<td class="line"><?php echo $donations['total']['100_amount'] ?></td>
	<td><?php echo $donations['total']['12K'] ?></td>
	<td><?php echo $donations['total']['12K_percent'] ?>%</td>
	<td class="line"><?php echo $donations['total']['12K_amount'] ?></td>
	<td><?php echo $donations['total']['1L'] ?></td>
	<td><?php echo $donations['total']['1L_percent'] ?>%</td>
	<td><?php echo $donations['total']['1L_amount'] ?></td>
</tr>
<?php foreach ($all_coaches as $coach_id => $coach_name) { ?>
	<tr>
		<td><a href="coach_dashboard.php?coach_id=<?php echo $coach_id ?>&amp;donation_status=<?php echo $donation_status ?>"><?php echo $coach_name ?></a></td>
		<td class="line"><?php echo $couch_volunteers_count[$coach_id] ?></td>
		<td><?php echo $donations[$coach_id]['100'] ?></td>
		<td><?php echo $donations[$coach_id]['100_percent'] ?>%</td>
		<td class="line"><?php echo $donations[$coach_id]['100_amount'] ?></td>
		<td><?php echo $donations[$coach_id]['12K'] ?></td>
		<td><?php echo $donations[$coach_id]['12K_percent'] ?>%</td>
		<td class="line"><?php echo $donations[$coach_id]['12K_amount'] ?></td>
		<td><?php echo $donations[$coach_id]['1L'] ?></td>
		<td><?php echo $donations[$coach_id]['1L_percent'] ?>%</td>
		<td><?php echo $donations[$coach_id]['1L_amount'] ?></td>
	</tr>
<?php } ?>
</table>