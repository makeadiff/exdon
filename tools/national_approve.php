<?php
require('../common.php');

$action = i($QUERY, 'action');
$user_id = 13257; // Pooja

$donation = new Donation;
$donations_for_approval = $donation->search(array('reviewer_id' => $user_id));

if($action == 'change_status') {
	foreach($donations_for_approval as $don ) {
		if($don['donation_status'] != 'DEPOSIT_PENDING' && $don['donation_status'] != 'RECEIPT SENT') {
			$sql->update("donations", array(
						'donation_status'	=> 'DEPOSIT_PENDING',
						'updated_by'		=> 0,
						'updated_at'		=> 'NOW()'
					), "id=$don[id]");
		}
	}
}

render();
