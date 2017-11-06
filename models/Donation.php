<?php
class Donation extends DBTable {
	public $error;
	public $start_date = '2016-10-01';
	public $current_mode = 'live';

	private $status_order = array('TO_BE_APPROVED_BY_POC', 'HAND_OVER_TO_FC_PENDING', 'DEPOSIT_PENDING', 'DEPOSIT COMPLETE');

	function __construct() {
	   parent::__construct('donations');
	}

	/** Add a donation. Takes one array as argument...
	 * Example - $donation->add(array(
	 * 				'donor_name'	=> 'Binny V A',
	 * 				'donor_email'	=> 'binnyvx@gmail.com',
	 * 				'donor_phone'	=> '9746068563',
	 * 				'fundraiser_id'	=> 4, // The ID from the 'users' table. This donation was raised by this user
	 * 				'amount'		=> 13,
	 * 				'created_at'	=> date('Y-m-d H:i:s'),
	 * 				'donation_type'	=> 'nach', // IF this is present - and the value is 'global_giving' or 'mad_website' or 'give_india' or 'nach', it becomes an external donation.
	 * ));
	 */
	function add($data) {
		global $sql;
		$eighty_g_required = 0;
		$donor_address = '';
		extract($data);

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone, $donor_address);

		if(!$donor_id) return $this->_error("Can't find a valid Donor. Try logging out of the app and logging back in again.");
		if(!$fundraiser_id) return $this->_error("Can't find a valid Fundraiser. Try logging out of the app and logging back in again.");

		if(isset($created_at) and $created_at) {
			if($created_at == '1970-01-01') $created_at = date("Y-m-d H:i:s");
			else $created_at = date("Y-m-d H:i:s", strtotime($created_at));
		} else {
			$created_at = date('Y-m-d H:i:s');
		}
		if(empty($comment)) $comment = '';

		if(isset($donation_type) and ($donation_type == 'global_giving' or $donation_type == 'mad_website' or $donation_type == 'give_india' or $donation_type == 'nach')) {
			return $this->addExternal($donation_type, $data);
		}

		// Insert the donation
		$donation_id = $sql->insert("donations", array(
				'donour_id'			=> $donor_id,
				'fundraiser_id'		=> $fundraiser_id,
				'updated_by'		=> $fundraiser_id,
				'donation_amount'	=> $amount,
				'created_at'		=> $created_at,
				'updated_at'		=> 'NOW()',
				'eighty_g_required'	=> ($eighty_g_required) ? 1 : 0,
				'comment'			=> $comment,
				'donation_status'	=> 'TO_BE_APPROVED_BY_POC',
				'source_id'			=> 1,

				// Legacy stuff
				'donation_type'		=> 'GEN',
				'version'			=> 1,
				'product_id'		=> 1,
			));

		if($this->current_mode != 'testing') {
			//Send acknowledgment SMS
			$sms = new SMS();
			$sms->message = "Dear $donor_name, Thanks a lot for your contribution of Rs. $amount towards Make a Difference. This is only an acknowledgement. A confirmation and e-receipt would be sent once the amount reaches us.";
			$sms->number = $donor_phone;
			$sms->send();

			//Send acknowledgement Email
			$base_url = '../';
			$images[] = $base_url . 'assets/mad-letterhead-left.png';
			$images[] = $base_url . 'assets/mad-letterhead-logo.png';
			$images[] = $base_url . 'assets/mad-letterhead-right.png';

			$email = new Email();
			$email_html = file_get_contents($base_url . 'templates/email/donation_acknowledgement.html');
			$email->html = str_replace(	array('%BASE_URL%', '%AMOUNT%', '%DONOR_NAME%', '%DATE%'), 
										array($base_url, 	$amount, 	$donor_name, 	date('d/m/Y')), $email_html);
			$email->to = $donor_email;
			$email->from = "noreply <noreply@makeadiff.in>";
			$email->subject = "Donation Acknowledgment";
			$email->images = $images;
			$email->send();
		}

		return $donation_id;
	}

	/** Add an external donation. This is for NACH, Global Giving, Give India and MAD Website donations only.
	 * Example - $donation->add($donation_type, array(
	 * 				'donor_name'	=> 'Binny V A',
	 * 				'donor_email'	=> 'binnyvx@gmail.com',
	 * 				'donor_phone'	=> '9746068563',
	 * 				'fundraiser_id'	=> 4, // The ID from the 'users' table. This donation was raised by this user
	 * 				'amount'		=> 13,
	 * 				'created_at'	=> date('Y-m-d H:i:s')
	 * ));
	 */
	function addExternal($donation_type, $data) {
		global $sql;
		extract($data);

		//Convert monthly amount of nach to yearly amount
		if($donation_type == "nach") {
			$amount = $amount * 12;
		}

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone);

		if(isset($created_at) and $created_at) {
			$created_at = date("Y-m-d H:i:s", strtotime($created_at));
		} else {
			$created_at = date('Y-m-d H:i:s');
		}

		// Insert the donation
		$donation_id = $sql->insert("external_donations", array(
				'donation_type'		=> $donation_type,
				'donor_id'			=> $donor_id,
				'fundraiser_id'		=> $fundraiser_id,
				'updated_by'		=> $fundraiser_id,
				'amount'			=> $amount,
				'created_at'		=> $created_at,
				'updated_at'		=> 'NOW()',
				'donation_status'	=> 'TO_BE_APPROVED_BY_POC',
			));
		
		return $donation_id;
	}

	function findDonor($donor_name, $donor_email, $donor_phone) {
		global $sql;
		
		// Find the donor - both email and phone must be same
		$donor_id = $sql->getOne("SELECT id FROM donours WHERE email_id='$donor_email' AND phone_no='$donor_phone'");

		// If we can't find the donor, add a new one.
		if(!$donor_id) {
			$donor_id = $sql->insert("donours", array(
					'first_name'	=> $donor_name,
					'email_id'		=> $donor_email,
					'phone_no'		=> $donor_phone,
					'created_at'	=> 'NOW()',
					'updated_at'	=> 'NOW()',
				));
		}

		return $donor_id;
	}

	/// Get all the donations donuted by the given user
	function getDonationsByUser($user_id) {
		return $this->search(array('fundraiser_id' => $user_id, 'include_external_donations' => true, 'include_deposit_info' => true));
	}

	/**
	 * Search for donations with any of the following parameters...
	 *   	- amount 	Amount of donation. 
	 *   	- donor_id 	All the donations from this donor.
	 *   	- status 	All donations with this status.
	 *   	- deposited 		true/false 	Return only deposited or Not deposited donations.
	 *   	- fundraiser_id 	Donations that was raised by this fundraiser.
	 *   	- fundraiser_ids 	Donations that was raised by any of these fundraisers.
	 *   	- reviewer_id		Donations that this approver has to review.
	 * More to be added later.
	 */
	function search($params) {
		global $sql;
		$sql_checks = array();
		$sql_joins = array();

		if(isset($params['amount']) and $params['amount'])
			$sql_checks['donation_amount'] = "D.donation_amount = " . $params['amount'];
		if(isset($params['city_id']) and $params['city_id'])
			$sql_checks['city_id'] = "U.city_id = " . $params['city_id'];
		if(isset($params['donor_id']) and $params['donor_id'])
			$sql_checks['donor_id'] = "DON.id = " . $params['donor_id'];
		if(isset($params['donor_name']) and $params['donor_name']) 
			$sql_checks['donor_name'] = "DON.first_name LIKE '" . $params['donor_name'] . "%'";
		if(isset($params['status']) and $params['status'])
			$sql_checks['status'] = "D.donation_status = '" . $params['status'] . "'";
		if(isset($params['status_in']) and $params['status_in'])
			$sql_checks['status_in'] = "D.donation_status IN ('" . implode("','",$params['status_in']) . "')";
		if(isset($params['fundraiser_name']) and $params['fundraiser_name']) 
			$sql_checks['fundraiser_name'] = "U.first_name LIKE '" . $params['fundraiser_name'] . "%'";
		if(isset($params['fundraiser_id']) and $params['fundraiser_id'])
			$sql_checks['fundraiser_id'] = "D.fundraiser_id = " . $params['fundraiser_id'];
		if(isset($params['fundraiser_ids']) and $params['fundraiser_ids'])
			$sql_checks['fundraiser_ids'] = "D.fundraiser_id IN (" . implode($params['fundraiser_ids'], ',') . ')';
		if(isset($params['not_fundraiser_id']) and $params['not_fundraiser_id'])
			$sql_checks['fundraiser_id'] = "D.fundraiser_id != " . $params['not_fundraiser_id'];
		if(isset($params['updated_by']) and $params['updated_by'])
			$sql_checks['updated_by'] = "D.updated_by = " . $params['updated_by'];
		if(isset($params['reviewer_id']) and $params['reviewer_id']) {
			$sql_joins['deposits_donations'] = 'INNER JOIN deposits_donations DD ON DD.donation_id=D.id INNER JOIN deposits DP ON DP.id=DD.deposit_id';
			$sql_checks['reviewer_id'] = "DP.given_to_user_id={$params['reviewer_id']}";
			if(!isset($sql_checks['deposit_status'])) $sql_checks['deposit_status'] = "DP.status='pending'";
		}
		if(isset($params['approver_id'])) {
			// Let the key be 'reviewer_id' itself - because exact same thing. Don't want it to be joined twice.
			$sql_joins['deposits_donations'] = 'INNER JOIN deposits_donations DD ON DD.donation_id=D.id INNER JOIN deposits DP ON DP.id=DD.deposit_id'; 
			$sql_checks['approver_id'] = "DP.given_to_user_id={$params['approver_id']}";
			if(!isset($sql_checks['deposit_status'])) $sql_checks['deposit_status'] = "DP.status='approved'";
		}
		if(isset($params['deposit_status'])) {
			$sql_joins['deposits_donations'] = 'INNER JOIN deposits_donations DD ON DD.donation_id=D.id INNER JOIN deposits DP ON DP.id=DD.deposit_id';
			$sql_checks['deposit_status'] = "DP.status='$params[deposit_status]'";
		}
		if(isset($params['deposit_status_in'])) {
			$sql_joins['deposits_donations'] = 'INNER JOIN deposits_donations DD ON DD.donation_id=D.id INNER JOIN deposits DP ON DP.id=DD.deposit_id';
			$deposit_status = array();
			foreach ($params['deposit_status_in'] as $value) $deposit_status[] = "DP.status = '$value'";
			$sql_checks['deposit_status'] = "(" . implode(" OR ", $deposit_status) . ")";
		}

		// Only get donations after a preset date
		include('../../donutleaderboard/_city_filter.php');
		$from_date = $city_date_filter['25']['from']; // National start date
		$sql_checks['from_date'] = "D.created_at >= '$from_date 00:00:00'";

		$donations = $sql->getById("SELECT D.id, D.donation_status, D.eighty_g_required, D.created_at, D.updated_at, D.updated_by, D.donation_amount AS amount,
				U.id AS user_id, TRIM(CONCAT(U.first_name,' ',U.last_name)) AS user_name, DON.id AS donor_id, TRIM(CONCAT(DON.first_name, ' ', DON.last_name)) AS donor_name,
				D.donation_type
			FROM donations D 
			INNER JOIN users U ON D.fundraiser_id=U.id
			INNER JOIN donours DON ON DON.id=D.donour_id
			" . implode("\n", $sql_joins) . "
			WHERE " . implode(' AND ', $sql_checks) . "
			GROUP BY D.id
			ORDER BY D.created_at DESC");
		// print $sql->_query . "\n";

		// Find only deposited or undeposited donations - also used to include deposit info.
		if(isset($params['deposited']) or (isset($params['include_deposit_info']) and $params['include_deposit_info'])) {
			$person_id = i($params, 'fundraiser_id');
			if(!$person_id) $person_id = i($params, 'updated_by');
			if($person_id) {
				foreach ($donations as $donation_id => $donation_info) {
					$all_deposit_info = $sql->getAll("SELECT DP.*,TRIM(CONCAT(GU.first_name,' ',GU.last_name)) AS given_to_user_name,
								TRIM(CONCAT(CU.first_name,' ',CU.last_name)) AS collected_from_user_name FROM deposits DP 
							INNER JOIN deposits_donations DD ON DD.deposit_id=DP.id 
							INNER JOIN users GU ON GU.id=DP.given_to_user_id
							INNER JOIN users CU ON CU.id=DP.collected_from_user_id
							WHERE DD.donation_id=$donation_id AND DP.collected_from_user_id=$person_id AND DP.status IN ('approved', 'pending')
							ORDER BY DP.added_on DESC");
					$deposit_info = reset($all_deposit_info);

					if(isset($params['include_deposit_info']) and $params['include_deposit_info']) {
						$donations[$donation_id]['deposit'] = $all_deposit_info;
					}

					if(isset($params['deposited'])) {
						// Find donations which had are in the deposits table with status of pending or approved
						if(!$deposit_info and $params['deposited'])  unset($donations[$donation_id]); // Deposit info not present - undeposited.
						if($deposit_info and ($deposit_info['status'] == 'approved' or $deposit_info['status'] == 'pending')) {// Approved or pending deposit
							if($params['deposited'] == false) unset($donations[$donation_id]); // If they want only undeposited donations, unset
						} else if($params['deposited'] == true) unset($donations[$donation_id]); // Only deposited donations go thru.
					}
				}
			}
		}

		// Include external donation details in the return.
		if(isset($params['include_external_donations']) and $params['include_external_donations']) {
			if(isset($params['amount'])) $sql_checks['donation_amount'] = "D.amount = " . $params['amount']; // Different field name for amount.

			$external_donations = $sql->getById("SELECT CONCAT('Ex:',D.id) AS id, D.donation_status, '0' AS eighty_g_required, D.created_at, D.updated_at, D.updated_by, D.amount, D.donation_type,
				U.id AS user_id, TRIM(CONCAT(U.first_name,' ',U.last_name)) AS user_name, DON.id AS donor_id, TRIM(CONCAT(DON.first_name, ' ', DON.last_name)) AS donor_name
			FROM external_donations D 
			INNER JOIN users U ON D.fundraiser_id=U.id
			INNER JOIN donours DON ON DON.id=D.donor_id
			WHERE " . implode($sql_checks, ' AND ') . "
			GROUP BY D.id
			ORDER BY D.created_at DESC");
			// print $sql->_query;

			$donations = array_merge($donations, $external_donations);
		}

		return $donations;
	}

	/// Update donation with the given $changes.
	function updateDonation($donation_id, $updater_user_id, $changes) {
		$this->find($donation_id);
		$this->field['updated_at'] = 'NOW()';
		$this->field['updated_by'] = $updater_user_id;
		foreach ($changes as $key => $value) {
			$this->field[$key] = $value;
		}
		return $this->save();
	}

	/// Set the given donation as approved - with the user id(second argument) as the approver.
	function pocApprove($donation_id, $approver_id) {
		$donations_for_approval = $this->search(array('poc_id' => $approver_id, 'status' => 'TO_BE_APPROVED_BY_POC'));
		if(!count($donations_for_approval)) return $this->_error("Can't find any donations that can be approved by current user($approver_id)");
		$donation_ids_for_approval = array_keys($donations_for_approval);

		if(!in_array($donation_id, $donation_ids_for_approval)) return $this->_error("User $approver_id can't approve the donation $donation_id");

		$this->updateDonation($donation_id, $approver_id, array('donation_status' => 'HAND_OVER_TO_FC_PENDING'));
		return true;
	}

	/// Set the given donation as NOT approved - with the user id(second argument) as the rejecter.
	function pocReject($donation_id, $rejecter_id) {
		$donations_for_rejection = $this->search(array('poc_id' => $rejecter_id, 'status' => 'HAND_OVER_TO_FC_PENDING'));
		if(!count($donations_for_rejection)) return $this->_error("Can't find any donations that can be recected by current user($rejecter_id)");
		$donation_ids_for_rejection = array_keys($donations_for_rejection);

		if(!in_array($donation_id, $donation_ids_for_rejection)) return $this->_error("User $rejecter_id can't approve the donation $donation_id");

		$this->updateDonation($donation_id, $rejecter_id, array('donation_status' => 'TO_BE_APPROVED_BY_POC'));
		return true;
	}

	/// Approve the given donation using the FC account.
	function fcApprove($donation_id, $approver_id) {
		$donations_for_approval = $this->search(array('fc_id' => $approver_id, 'status' => 'HAND_OVER_TO_FC_PENDING'));
		if(!count($donations_for_approval)) return $this->_error("Can't find any donations that can be approved by current user($approver_id)");
		$donation_ids_for_approval = array_keys($donations_for_approval);

		if(!in_array($donation_id, $donation_ids_for_approval)) return $this->_error("User $approver_id can't approve the donation $donation_id");

		$this->updateDonation($donation_id, $approver_id, array('donation_status' => 'DEPOSIT_PENDING'));
		return true;
	}

	/// Set the given donation as NOT approved - with the user id(second argument) as the rejecter.
	function fcReject($donation_id, $rejecter_id) {
		$donations_for_rejection = $this->search(array('fc_id' => $rejecter_id, 'status' => 'DEPOSIT_PENDING'));
		if(!count($donations_for_rejection)) return $this->_error("Can't find any donations that can be recected by current user($rejecter_id)");
		$donation_ids_for_rejection = array_keys($donations_for_rejection);

		if(!in_array($donation_id, $donation_ids_for_rejection)) return $this->_error("User $rejecter_id can't approve the donation $donation_id");

		$this->updateDonation($donation_id, $rejecter_id, array('donation_status' => 'HAND_OVER_TO_FC_PENDING'));
		return true;
	}

	/// Delete the external donation of which id is given.
	function removeExternal($donation_id, $deleter_id, $fc_poc = 'poc') {
		global $sql;

		$donations_for_deletion = $this->search(array('fundraiser_id' => $deleter_id, 'include_external_donations' => true));
		if(!count($donations_for_deletion) or !$donations_for_deletion) return $this->_error("Can't find any donations that can be deleted by '$deleter_id'");

		$donation_ids_for_deletion = array_keys($donations_for_deletion); 

		if(!in_array('Ex:'.$donation_id, $donation_ids_for_deletion)) return $this->_error("User $deleter_id can't delete the donation $donation_id");

		return $sql->execQuery("DELETE FROM external_donations WHERE id=$donation_id"); // Delete the donation.
	}

	/// Delete the donation of which id is given.
	function remove($donation_id, $deleter_id, $fc_poc = 'poc') {
		global $sql;

		if($fc_poc == 'self') {
			$donations_for_deletion = $this->search(array('fundraiser_id' => $deleter_id));
		} else {
			$donations_for_deletion = $this->search(array($fc_poc . '_id' => $deleter_id));
		}
		if(!count($donations_for_deletion) or !$donations_for_deletion) return $this->_error("Can't find any donations that can be deleted by '$deleter_id'");

		$donation_ids_for_deletion = array_keys($donations_for_deletion); 

		if(!in_array($donation_id, $donation_ids_for_deletion)) return $this->_error("User $deleter_id can't delete the donation $donation_id");

		$sql->execQuery("INSERT INTO deleted_donations 
				(id,donation_type,version,fundraiser_id,donour_id,donation_status,eighty_g_required,product_id,donation_amount,created_at,updated_at,updated_by,source_id)
		SELECT 	 id,donation_type,version,fundraiser_id,donour_id,donation_status,eighty_g_required,product_id,donation_amount,created_at,updated_at,$deleter_id,source_id
			FROM donations WHERE id=$donation_id"); // Get a copy of the donation as backup
		return $sql->execQuery("DELETE FROM donations WHERE id=$donation_id"); // Delete the donation.
	}

	/// Use this to handle errors.
	private function _error($message) {
		$this->error = $message;
		// print json_encode(array('error' => $message, 'success' => false));
		return false;
	}

	// Used to validate the donation
	function validate($data) {
		global $sql;
		$donor_address = '';
		extract($data);

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone, $donor_address);

		if(!$donor_id) return $this->_error("Can't find a valid Donor ID for this donation. Try logging out of the app and logging back in again.");
		if(!$fundraiser_id) return $this->_error("Can't find a valid Fundraiser ID for this donation. Try logging out of the app and logging back in again.");

		if($this->checkIfDonorDetailsSameAsVolunteerBelowXAmount($donor_email,$donor_phone,$fundraiser_id)) {
			return $this->_error("You seem to have entered your own details in place of the donor. If you continue, the donor won't receive the acknowledgement or receipt. You can only make two donations under your own details. You sure you want to continue?");

		} elseif ($created_date = $this->checkIfRepeatDonation($donor_id,$fundraiser_id,$amount)) { // = is used for assignment. It should NOT be ==
			return $this->_error("Donation of Rs. $amount from $donor_name has already been added on $created_date. Are you sure you want to add the same amount again?");

		} elseif($data = $this->checkIfRepeatDonationWithDifferentAmount($donor_id,$fundraiser_id)) {
			return $this->_error("Donation of Rs. $data[amount] from $donor_name has already been added on $data[created_date]. Are you sure you want to add another amount again?");
		}

		return true;
	}

	function getTotalDonations($user_data) {
		global $sql;
		$user_id = 0;
		$total = 0;

		if(isset($user_data['id'])) $user_id = $user_data['id'];
		elseif(isset($user_data['user_id'])) $user_id = $user_data['user_id'];
		elseif(isset($user_data['email'])) {
			$user_id = $sql->getOne("SELECT id FROM users WHERE email='{$user_data['email']}' AND is_deleted='0'");
		} elseif(isset($user_data['phone'])) {
			$user_id = $sql->getOne("SELECT id FROM users WHERE phone_no='{$user_data['phone']}' AND is_deleted='0'");
		}

		if(!$user_id) return "Cannot find user";
		
		$donuted_amount = $sql->getOne("SELECT SUM(donation_amount) FROM donations WHERE fundraiser_id=$user_id AND created_at>'$this->start_date 00:00:00'");
		$exdon_amount = $sql->getOne("SELECT SUM(amount) FROM external_donations WHERE fundraiser_id=$user_id AND created_at>'$this->start_date 00:00:00'");

		return $donuted_amount + $exdon_amount;
	}

	function checkIfDonorDetailsSameAsVolunteerBelowXAmount($donor_email,$donor_phone,$fundraiser_id) {
		global $sql;

		$fundraiser = $sql->getAssoc("SELECT phone_no,email FROM users WHERE id = $fundraiser_id");

		if(empty($fundraiser)) {
			return $this->_error("Can't find a valid Fundraiser ID for this donation. Try logging out of the app and logging back in again.");
		}

		if(($fundraiser['phone_no'] == $donor_phone) || ($fundraiser['email'] == $donor_email)) {
			return true;
		} else {
			return false;
		}

	}


	function checkIfRepeatDonation($donor_id,$fundraiser_id,$amount) {
		global $sql;

		$donation_date = $sql->getOne("SELECT created_at FROM donations WHERE donour_id = $donor_id AND fundraiser_id = $fundraiser_id AND donation_amount = $amount");

		if(empty($donation_date)) {
			return false;
		} else {
			$formatted_date = date('j-M-Y',strtotime($donation_date));
			return $formatted_date;
		}
	}

	function checkIfRepeatDonationWithDifferentAmount($donor_id,$fundraiser_id) {
		global $sql;

		$donation = $sql->getAssoc("SELECT created_at,donation_amount FROM donations WHERE donour_id = $donor_id AND fundraiser_id = $fundraiser_id");

		if(empty($donation)) {
			return false;
		} else {
			$created_date = date('j-M-Y',strtotime($donation['created_at']));
			$amount = $donation['donation_amount'];
			$return = compact("created_date", "amount");
			return $return;
		}
	}

}
