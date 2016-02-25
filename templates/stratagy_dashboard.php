<br>
<form action="" method="get" class="form-area">
<?php 
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => array('any' => 'Any', 'deposited' => 'Deposited', 'not_deposited' => 'Not Deposited')));
$html->buildInput("amount", 'Amount', 'select', $donation_status, array('options' => array('donuted' => 'Donuted', '4k' => '4K+', '6k' => '6K+', '8k' => '8K+', '12k' => '12K+', '1l' => '1L+')));
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />

<table class="table table-striped">
<tr><th>Group</th><th>Coach</th><th>Volunter Count</th><th class="line">Funds Raised</th>
	<th colspan="2" class="amounts line donuted">Donuted</th>
	<th colspan="2" class="amounts line 4k">4K+</th>
	<th colspan="2" class="amounts line 6k">6K+</th>
	<th colspan="2" class="amounts line 8k">8K+</th>
	<th colspan="2" class="amounts line 12k">12K+</th>
	<th colspan="2" class="amounts line 1l">1L+</th></tr>
<tr><th></th><th></th><th></th><th class="line"></th><th class="donuted">Count</th><th class="line donuted">Percentage</th><th class="4k">Count</th><th class="line 4k">Percentage</th>
	<th class="6k">Count</th><th class="line 6k">Percentage</th><th class="8k">Count</th><th class="line 8k">Percentage</th><th class="12k">Count</th><th class="line 12k">Percentage</th>
	<th class="1l">Count</th><th class="line 1l">Percentage</th></tr>
<tr>
	<td>All</td>
	<td>-</td>
	<td ><?php echo $total_volunteers ?></td>
	<td class="line"><?php echo money_format("%.0n",$donations['total']['total']) ?></td>
	<td class="donuted"><?php echo $donations['total']['donuted'] ?></td>
	<td class="line donuted"><?php echo $donations['total']['donuted_percent'] ?>%</td>
	<td class="4k"><?php echo $donations['total']['4K'] ?></td>
	<td class="line 4k"><?php echo $donations['total']['4K_percent'] ?>%</td>
	<td class="6k"><?php echo $donations['total']['6K'] ?></td>
	<td class="line 6k"><?php echo $donations['total']['6K_percent'] ?>%</td>
	<td class="8k"><?php echo $donations['total']['8K'] ?></td>
	<td class="line 8k"><?php echo $donations['total']['8K_percent'] ?>%</td>
	<td class="12k"><?php echo $donations['total']['12K'] ?></td>
	<td class="line 12k"><?php echo $donations['total']['12K_percent'] ?>%</td>
	<td class="1l"><?php echo $donations['total']['1L'] ?></td>
	<td class="line 1l"><?php echo $donations['total']['1L_percent'] ?>%</td>
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
		<td class="line"><?php echo money_format("%.0n",$donations[$coach_id]['total']) ?></td>
		<td class="donuted"><?php echo $donations[$coach_id]['donuted'] ?></td>
		<td class="line donuted"><?php echo $donations[$coach_id]['donuted_percent'] ?>%</td>
		<td class="4k"><?php echo $donations[$coach_id]['4K'] ?></td>
		<td class="line 4k"><?php echo $donations[$coach_id]['4K_percent'] ?>%</td>
		<td class="6k"><?php echo $donations[$coach_id]['6K'] ?></td>
		<td class="line 6k"><?php echo $donations[$coach_id]['6K_percent'] ?>%</td>
		<td class="8k"><?php echo $donations[$coach_id]['8K'] ?></td>
		<td class="line 8k"><?php echo $donations[$coach_id]['8K_percent'] ?>%</td>
		<td class="12k"><?php echo $donations[$coach_id]['12K'] ?></td>
		<td class="line 12k"><?php echo $donations[$coach_id]['12K_percent'] ?>%</td>
		<td class="1l"><?php echo $donations[$coach_id]['1L'] ?></td>
		<td class="line 1l"><?php echo $donations[$coach_id]['1L_percent'] ?>%</td>
	</tr>
<?php } ?>
</table>