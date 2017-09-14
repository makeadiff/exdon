<?php
class Deposit extends DBTable {
	public $error = '';
	private $sql;

	function __construct() {
		global $sql;
		$this->sql = $sql;
		parent::__construct('deposits');
	}

	function add($collected_from_user_id, $given_to_user_id, $donation_ids) {
		
		// Validations...
		if(!$collected_from_user_id or !$this->sql->getOne("SELECT id FROM users WHERE id=$collected_from_user_id AND is_deleted='0'")) 
			return $this->_error("Invalid User ID of depositer.");
		if(!$given_to_user_id or !$this->sql->getOne("SELECT id FROM users WHERE id=$given_to_user_id AND is_deleted='0'")) 
			return $this->_error("Invalid User ID of collector.");
		if($collected_from_user_id == $given_to_user_id) 
			return $this->_error("Depositer and collector can't be the same person.");

		// Check if any of the given donation has been part of an approved or pending deposit. Rejected deposits are ok.
		foreach ($donation_ids as $donation_id) {
			$existing_donation = $this->sql->getOne("SELECT id FROM donations WHERE id=$donation_id");
			if(!$existing_donation) return $this->_error("Dontation $donation_id does not exist.");

			$pre_existing_deposit = $this->sql->getOne("SELECT D.id FROM donations D
							INNER JOIN deposits_donations DD ON DD.donation_id=D.id
							INNER JOIN deposits DEP ON DEP.id=DD.deposit_id
							WHERE DEP.status IN ('pending', 'approved') AND D.id=$donation_id");
			if($pre_existing_deposit) return $this->_error("Dontation $donation_id is already deposited. You cannot deposit it again.");

			// :TODO: Check if this user has the ability to deposit this donation - must be a donation the user fundraised or approved at some point.
		}

		$amount = $this->sql->getOne("SELECT SUM(donation_amount) AS amount FROM donations WHERE id IN (" . implode(",", $donation_ids) . ")");

		// All good, do insert.
		$this->field['collected_from_user_id'] = $collected_from_user_id;
		$this->field['given_to_user_id'] = $given_to_user_id;
		$this->field['added_on'] = 'NOW()';
		$this->field['reviewed_on'] = '0000-00-00 00:00:00';
		$this->field['status'] = 'pending';
		$this->field['amount'] = $amount;
		$deposit_id = $this->save();

		foreach ($donation_ids as $donation_id) {
			$this->sql->insert("deposits_donations", array(
				'donation_id'	=> $donation_id,
				'deposit_id'	=> $deposit_id
			));
		}
		
		return $deposit_id;
	}

	function search($params) {
		$this->sql_checks = array();
		$this->sql_joins = array();

		if(isset($params['reviewer_id'])) {
			$this->sql_checks['reviewer_id'] = "DP.given_to_user_id={$params['reviewer_id']}";
			$this->sql_checks['status'] = "DP.status='pending'";
		}
		if(isset($params['status'])) $this->sql_checks['status'] = "DP.status={$params['status']}";
		if(isset($params['status_in'])) $this->sql_checks['status'] = "DP.status IN (" . implode(",", $params['status_in']) . ")";

		// Only get donations after a preset date
		include('../../donutleaderboard/_city_filter.php');
		$from_date = $city_date_filter['25']['from']; // National start date
		$this->sql_checks['from_date'] = "DP.added_on >= '$from_date 00:00:00'";

		$deposits = $this->sql->getById("SELECT DP.id, DP.amount,DP.added_on, DP.reviewed_on, DP.status,
				DP.collected_from_user_id, TRIM(CONCAT(CFU.first_name, ' ', CFU.last_name)) AS collected_from_user_name,
				DP.given_to_user_id, TRIM(CONCAT(GTU.first_name,' ', GTU.last_name)) AS given_to_user_name
			FROM deposits DP
			INNER JOIN users GTU ON DP.given_to_user_id=GTU.id
			INNER JOIN users CFU ON DP.collected_from_user_id=CFU.id
			" . implode("\n", $this->sql_joins) . "
			WHERE " . implode(' AND ', $this->sql_checks) . "
			ORDER BY DP.added_on DESC");

		foreach($deposits as $id => $dep) {
			$deposits[$id]['donations'] = $this->getDonationsIn($id);
		}

		return $deposits;
	}

	function approve($deposit_id, $current_user_id = 0) {
		$deposit = $this->getDeposit($deposit_id);
		$done = $this->changeStatus($deposit, 'approved', $current_user_id);

		$user = new User($current_user_id);
		$roles = $user->getRoles();

		// Change the status of each donation in this deposit.
		$donation = new Donation();
		foreach($deposit['donations'] as $don) {
			if($don['donation_status'] == 'TO_BE_APPROVED_BY_POC' and !empty($roles[$user->role_ids['CFR POC']])) {
				$donation->pocApprove($don['id'], $current_user_id); // Approved by POC/Coach.

			} elseif($don['donation_status'] == 'HAND_OVER_TO_FC_PENDING' and !empty($roles[$user->role_ids['FC']])) {
				$donation->fcApprove($don['id'], $current_user_id); // Approved by FC
			}
		}

		return $done;
	}
	function reject($deposit_id, $current_user_id = 0) {
		$deposit = $this->getDeposit($deposit_id);
		return $this->changeStatus($deposit, 'rejected', $current_user_id);
	}


	function changeStatus($deposit, $status, $current_user_id) {
		if(!$deposit) return false;
		// :TODO: Validation - see if the $current_user_id is the right person to approve/reject. Should be the given_to_user_id

		$affected = 0;
		if(!empty($deposit['amount'])) {
			$this->field['status'] = $status;
			$this->field['reviewed_on'] = 'NOW()';
			$affected = $this->save();
		}

		return $affected;
	}


	function getDeposit($deposit_id) {
		$deposit = $this->find($deposit_id);
		if(!$deposit) return $this->_error("Invalid deposit id");

		$deposit['donations'] = $this->getDonationsIn($deposit_id);

		return $deposit;
	}

	function getDonationsIn($deposit_id) {
		$donations = $this->sql->getById("SELECT D.id,D.donation_status,D.donation_amount AS amount,D.created_at,D.updated_by,
						D.fundraiser_id, U.id AS user_id, TRIM(CONCAT(U.first_name,' ',U.last_name)) AS user_name,
						D.donour_id, DON.id AS donor_id, TRIM(CONCAT(DON.first_name,' ', DON.last_name)) AS donor_name
			FROM donations D 
			INNER JOIN deposits_donations DD ON D.id=DD.donation_id
			INNER JOIN users U ON D.fundraiser_id=U.id
			INNER JOIN donours DON ON DON.id=D.donour_id
			WHERE DD.deposit_id=$deposit_id");

		return $donations;
	}


	/// Use this to handle errors.
	private function _error($message) {
		$this->error = $message;
		// print json_encode(array('error' => $message, 'success' => false));
		return false;
	}
}

