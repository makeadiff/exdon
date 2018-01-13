 
<h1>NACH Approvals</h1>

<table class="table table-stripped">
<tr><th>ID</th><th>Donor</th><th>Donor Phone</th><th>Donor Email</th><th>Fundraiser</th><th>Created At</th><th>Status</th><th>Amount</th><th>Action</th></tr>

<?php foreach($page as $row) { ?>
<tr>
	<form action="" method="post">
	<td><?php echo $row['id'] ?></td>
	<td><?php echo $row['donor_name'] ?></td>
	<td><?php echo $row['donor_phone'] ?></td>
	<td><?php echo $row['donor_email'] ?></td>
	<td><?php echo $row['fundraiser'] ?></td>
	<td><?php echo date($config['date_format_php'], strtotime($row['created_at'])) ?></td>
	<td><?php echo $status[$row['donation_status']]; ?></td>
	<td><input type="text" value="<?php echo $row['amount'] ?>" name="amount" size="6" class="amount" />
		<input type="hidden" value="<?php echo $row['id'] ?>" name="donation_id" /></td>
	<td nowrap="1"><input type="submit" name="action" value="Approve" class="btn btn-primary" />
	<?php if(i($QUERY, 'donation_id') == $row['id']) echo '<span class="icon done">Done</span>'; ?>
	</td>
	</form>
</tr>
<?php } ?>
</table>

<?php
$nach->link_template = '<a href="%%PAGE_LINK%%" class="%%CLASS%% with-icon">%%TEXT%%</a>';
$nach->text['previous'] = $nach->text['next'] = $nach->text['first'] = $nach->text['last'] = '&nbsp;';

print $nach->getLink("first") . ' | ' . $nach->getLink("previous");
$nach->printPager();
print $nach->getLink("next") . ' | ' . $nach->getLink("last");
