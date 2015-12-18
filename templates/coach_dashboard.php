<h3>Donations raised by people coached by <?php echo $coach_name ?></h3>

<table class="table table-striped">
<tr><th>&nbsp;</th><th>Volunter</th><th colspan="2" class="amounts">100+</th><th colspan="2" class="amounts">12000+</th><th colspan="2" class="amounts">100000+</th></tr>
<tr><th></th><th></th><th>Count</th><th>Total Amount</th><th>Count</th><th>Total Amount</th><th>Count</th><th>Total Amount</th></tr>
<tr>
	<td>All</td>
	<td><?php echo count($all_volunteers) ?></td>
	<td><?php echo $donations['total']['100'] ?></td>
	<td><?php echo $donations['total']['100_amount'] ?></td>
	<td><?php echo $donations['total']['12K'] ?></td>
	<td><?php echo $donations['total']['12K_amount'] ?></td>
	<td><?php echo $donations['total']['1L'] ?></td>
	<td><?php echo $donations['total']['1L_amount'] ?></td>
</tr>
<?php foreach ($all_volunteers as $volunteer_id => $volunteer_name) { ?>
	<tr>
		<td></td>
		<td><a href="donation_dashboard.php?fundraiser_id=<?php echo $volunteer_id ?>"><?php echo $volunteer_name ?></a></td>
		<td><?php echo $donations[$volunteer_id]['100'] ?></td>
		<td><?php echo $donations[$volunteer_id]['100_amount'] ?></td>
		<td><?php echo $donations[$volunteer_id]['12K'] ?></td>
		<td><?php echo $donations[$volunteer_id]['12K_amount'] ?></td>
		<td><?php echo $donations[$volunteer_id]['1L'] ?></td>
		<td><?php echo $donations[$volunteer_id]['1L_amount'] ?></td>
	</tr>
<?php } ?>
</table>