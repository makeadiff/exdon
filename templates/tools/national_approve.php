<h3>Donations for National Approval</h3>

<table class="table">
<tr><th>ID</th><th>Status</th><th>Created At</th><th>Updated At</th><th>Collected From</th><th>Amount</th><th>Fundraiser</th><th>Donor</th></tr>
<?php
$total = 0;
foreach($donations_for_approval as $don ) {
	$total += $don['amount'];
?>
<tr>
	<td><?php echo $don['id'] ?></td>
	<td><?php echo $don['donation_status'] ?></td>
	<td><?php echo $don['created_at'] ?></td>
	<td><?php echo $don['updated_at'] ?></td>
	<td><?php echo $don['updated_by'] ?></td>
	<td><?php echo $don['amount'] ?></td>
	<td><?php echo $don['user_name'] . '/' . $don['user_id'] ?></td>
	<td><?php echo $don['donor_name'] . '/' . $don['donor_id'] ?></td>
</tr>
<?php } ?>
<tr><td colspan="5">&nbsp;</td><td><?php echo $total ?></td><td colspan="2">&nbsp;</td></tr>
</table>