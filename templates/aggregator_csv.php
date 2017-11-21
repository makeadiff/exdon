<?php 
header("Content-type: text/plain; charset=utf-8");
// header('Content-Disposition: attachment; filename="Aggregate.csv"');
?>Type,Donuted Amount,Deposited/External,1 Week Late,2 Week Late,3 Week Late,4+ Week Late,Donor,Donor Email,Fundraiser,Email,Phone,City,Donuted On,Status
Total,<?php echo money_format("%.0n", $total_amount) ?>,<?php echo money_format("%.0n", $total_deposited) . ' + ' . money_format("%.0n", $total_external) ?>,<?php echo money_format("%.0n", $total_late_1_weeks) ?>,<?php echo money_format("%.0n", $total_late_2_weeks) ?>,<?php echo money_format("%.0n", $total_late_3_weeks) ?>,<?php echo money_format("%.0n", $total_late_4_or_more_weeks) ?>,,,,,,
<?php foreach ($all_donations as $don) { ?>
<?php echo i($all_donation_types, $don['donation_type'], $don['donation_type']) ?>,<?php echo money_format("%.0n", $don['donation_amount']) ?>,<?php echo money_format("%.0n", $don['amount_deposited']) ?>,<?php echo money_format("%.0n", $don['amount_late_1_weeks']) ?>,<?php echo money_format("%.0n", $don['amount_late_2_weeks']) ?>,<?php echo money_format("%.0n", $don['amount_late_3_weeks']) ?>,<?php echo money_format("%.0n", $don['amount_late_4_or_more_weeks']) ?>,<?php echo $don['donor_name'] ?>, <?php echo $don['donor_email'] ?>, <?php echo $don['fundraiser_name'] ?>,<?php echo $don['fundraiser_email'] ?>,<?php echo $don['fundraiser_phone'] ?>,<?php echo $don['fundraiser_city'] ?>,<?php echo date('Y-m-d', strtotime($don['created_at'])); ?>,<?php echo $all_donation_status[$don['donation_status']] ?>

<?php } ?>