<h3>Donations raised by <?php echo $fundraiser_name ?></h3>

<table class="table table-striped">
<tr><th>&nbsp;</th><th>Donor</th><th>Donuted Amount</th><th>Deposited</th><th>Late</th></tr>
<?php 
$total_deposited = 0;
$total_donated = 0;
$total_late = 0;
foreach ($all_donations as $donation_id => $donation) { ?>
	<tr>
		<td></td>
		<td><?php echo $donation['donor'] ?></td>
		<td><?php if(($donation['donation_status'] != 'DEPOSIT COMPLETE' or $donation['donation_status'] != 'RECEIPT SENT') and $donation['created_at'] > date("Y-m-d 00:00:00", strtotime("-3 weeks"))) {
			echo $donation['amount'];
			$total_donated += $donation['amount'];
		} ?></td>
		<td><?php if($donation['donation_status'] == 'DEPOSIT COMPLETE' or $donation['donation_status'] == 'RECEIPT SENT') {
			echo $donation['amount'];
			$total_deposited += $donation['amount'];
		} ?></td>
		<td><?php if(($donation['donation_status'] != 'DEPOSIT COMPLETE' or $donation['donation_status'] != 'RECEIPT SENT') and $donation['created_at'] <= date("Y-m-d 00:00:00", strtotime("-3 weeks"))) {
			echo $donation['amount'];
			$total_late += $donation['amount'];
		} ?></td>
	</tr>
<?php } ?>
<tr>
	<td>All</td>
	<td><?php echo count($all_donations) ?></td>
	<td><?php echo $total_donated ?></td>
	<td><?php echo $total_deposited ?></td>
	<td><?php echo $total_late ?></td>
</tr>

</table>