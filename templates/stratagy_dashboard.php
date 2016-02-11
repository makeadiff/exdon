<form action="" method="get" class="form-area">
<?php 
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => array('any' => 'Any', 'deposited' => 'Deposited', 'not_deposited' => 'Not Deposited')));
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />

<table class="table table-striped">
<tr><th>Group</th><th>Coach</th><th>Volunter Count</th><th class="line">Funds Raised</th><th colspan="2" class="amounts line">Donuted</th><th colspan="2" class="amounts line">12000+</th><th colspan="2" class="amounts line">100000+</th></tr>
<tr><th></th><th></th><th></th><th class="line"></th><th>Count</th><th class="line">Percentage</th><th>Count</th><th class="line">Percentage</th><th>Count</th><th class="line">Percentage</th></tr>
<tr>
	<td>All</td>
	<td>-</td>
	<td ><?php echo $total_volunteers ?></td>
	<td class="line"><?php echo $donations['total']['total'] ?></td>
	<td><?php echo $donations['total']['donuted'] ?></td>
	<td class="line"><?php echo $donations['total']['donuted_percent'] ?>%</td>
	<td><?php echo $donations['total']['12K'] ?></td>
	<td class="line"><?php echo $donations['total']['12K_percent'] ?>%</td>
	<td><?php echo $donations['total']['1L'] ?></td>
	<td class="line"><?php echo $donations['total']['1L_percent'] ?>%</td>
</tr>
<?php foreach ($all_coaches as $coach_id => $coach_name) { ?>
	<tr>
		<td><?php echo $coach_name['group_name'] ?></td>
		<td><a href="coach_dashboard.php?coach_id=<?php echo $coach_id ?>&amp;donation_status=<?php echo $donation_status ?>"><?php echo $coach_name['name'] ?></a></td>
		<td><?php
					if(isset($couch_volunteers_count[$coach_id]))
						echo $couch_volunteers_count[$coach_id];
					else
						echo "0";
		?></td>
		<td class="line"><?php echo $donations[$coach_id]['total'] ?></td>
		<td><?php echo $donations[$coach_id]['donuted'] ?></td>
		<td class="line"><?php echo $donations[$coach_id]['donuted_percent'] ?>%</td>
		<td><?php echo $donations[$coach_id]['12K'] ?></td>
		<td class="line"><?php echo $donations[$coach_id]['12K_percent'] ?>%</td>
		<td><?php echo $donations[$coach_id]['1L'] ?></td>
		<td class="line"><?php echo $donations[$coach_id]['1L_percent'] ?>%</td>
	</tr>
<?php } ?>
</table>