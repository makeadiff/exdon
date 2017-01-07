<h1 class="title">Deposit Aggregator</h1>

<form action="" method="get" class="form-area">
<?php
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("vertical_id", 'Vertical', 'select', $vertical_id, array('options' => $all_verticals));
$html->buildInput("group_type", 'Volunteer Type', 'select', $group_type, array('options' => $all_group_types));
$html->buildInput("coach_id", 'Coach', 'select', $coach_id, array('options' => $coaches[$city_id]));
$html->buildInput("donation_type", 'Type', 'select', $donation_type, array('options' => $all_donation_types));
// $html->buildInput("donation_status", 'Status', 'select', $donation_status, array('options' => $all_donation_status));
?>
<label for="donation_status">Status</label>
<select name="donation_status" id="donation_status">
	<optgroup label="Aggregated">
		<option value="any" <?php if($donation_status == 'any') echo 'selected'; ?>>Any</option>
		<option value="DEPOSITED" <?php if($donation_status == 'DEPOSITED') echo 'selected'; ?>>Not Deposited</option>
		<option value="NOT_DEPOSITED" <?php if($donation_status == 'NOT_DEPOSITED') echo 'selected'; ?>>Deposited</option>
	</optgroup>
	<optgroup label="Seperate">
		<option value="TO_BE_APPROVED_BY_POC" <?php if($donation_status == 'TO_BE_APPROVED_BY_POC') echo 'selected'; ?>>With Volunteer</option>
		<option value="HAND_OVER_TO_FC_PENDING" <?php if($donation_status == 'HAND_OVER_TO_FC_PENDING') echo 'selected'; ?>>With Coach</option>
		<option value="DEPOSIT_PENDING" <?php if($donation_status == 'DEPOSIT_PENDING') echo 'selected'; ?>>In National Account(Unapproved)</option>
		<option value="DEPOSIT COMPLETE" <?php if($donation_status == 'DEPOSIT COMPLETE') echo 'selected'; ?>>In National Account(Approved)</option>
	</optgroup>
</select><br />
<?php
$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />
<script type="text/javascript">
var coaches = <?php echo json_encode($coaches); ?>;
</script>

<table class="table table-striped">
<tr><th>Fundraiser</th<th>Donor</th>><th>Type</th><th>Donuted Amount</th><th>Status</th><th>Donuted On</th><th>Deposited</th>
	<th>1 Week Late</th><th>2 Week Late</th><th>3 Week Late</th><th>4+ Week Late</th>
	</tr>
<tr>
	<td><strong>Total</strong></td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td><?php echo money_format("%.0n", $total_amount) ?></td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td><?php echo money_format("%.0n", $total_deposited) ?></td>
	<td><?php echo money_format("%.0n", $total_late_1_weeks) ?></td>
	<td><?php echo money_format("%.0n", $total_late_2_weeks) ?></td>
	<td><?php echo money_format("%.0n", $total_late_3_weeks) ?></td>
	<td><?php echo money_format("%.0n", $total_late_4_or_more_weeks) ?></td>




</tr>
<?php foreach ($all_donations as $don) { ?>
<tr>
	<td><?php echo $don['fundraiser_name'] ?></td>
	<td><?php echo $don['donor_name'] ?></td>

	<td><?php echo i($all_donation_types, $don['donation_type'], $don['donation_type']) ?></td>
	<td><?php echo money_format("%.0n", $don['donation_amount']) ?></td>
	<td><?php echo $all_donation_status[$don['donation_status']] ?></td>
	<td><?php echo date($config['date_format_php'], strtotime($don['created_at'])); ?></td>

	<td><?php echo money_format("%.0n", $don['amount_deposited']) ?></td>
	<td><?php echo money_format("%.0n", $don['amount_late_1_weeks']) ?></td>
	<td><?php echo money_format("%.0n", $don['amount_late_2_weeks']) ?></td>
	<td><?php echo money_format("%.0n", $don['amount_late_3_weeks']) ?></td>
	<td><?php echo money_format("%.0n", $don['amount_late_4_or_more_weeks']) ?></td>




</tr>
<?php } ?>
</table>