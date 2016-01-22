<form action="" method="get" class="form-area">
<?php
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("coach_id", 'Coach', 'select', $coach_id, array('options' => $coaches[$city_id]));
$html->buildInput("donation_type", 'Type', 'select', $donation_type, array('options' => $all_donation_types));
$html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => $all_donation_status));
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />
<script type="text/javascript">
var coaches = <?php echo json_encode($coaches); ?>;
</script>

<table class="table table-striped">
<tr><th>Type</th><th>Amount</th><th>Deposited</th><!-- <th>3 Week Late</th> --><th>Donor</th><th>Fundraiser</th><th>Status</th></tr>
<tr>
	<td>Total</td>
	<td><?php echo money_format("%n", $total_amount) ?></td>
	<td><?php echo money_format("%n", $total_deposited) ?></td>
	<!-- <td><?php echo money_format("%n", $total_late) ?></td> -->
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<?php foreach ($all_donations as $don) { ?>
<tr>
	<td><?php echo i($all_donation_types, $don['donation_type'], $don['donation_type']) ?></td>
	<td><?php echo money_format("%n", $don['donation_amount']) ?></td>
	<td><?php echo money_format("%n", $don['amount_deposited']) ?></td>
	<!-- <td><?php echo money_format("%n", $don['amount_late']) ?></td> -->
	<td><?php echo $don['donor_name'] ?></td>
	<td><?php echo $don['fundraiser_name'] ?></td>
	<td><?php echo $don['donation_status'] ?></td>
</tr>
<?php } ?>
</table>