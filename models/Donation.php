<?php
class Donation extends DBTable {
	public $error;

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
	 * 				'donation_type'	=> 'ecs', // IF this is present - and the value is 'globalgiving' or 'ecs' or 'online', it becomes an external donation. 
	 * ));
	 */
	function add($data) {
		global $sql;
		$eighty_g_required = 0;
		$donor_address = '';
		extract($data);

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone, $donor_address);

		if(!$donor_id) return $this->_error("Can't find a valid Donor ID for this donation.");
		if(!$fundraiser_id) return $this->_error("Can't find a valid Fundraiser ID for this donation. Try logging out of the app and logging back in again.");

		if(isset($donation_type) and ($donation_type == 'globalgiving' or $donation_type == 'ecs' or $donation_type == 'online')) {
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
				'donation_status'	=> 'TO_BE_APPROVED_BY_POC',
				'source_id'			=> 1,

				// Legacy stuff
				'donation_type'		=> 'GEN',
				'version'			=> 1,
				'product_id'		=> 1,
			));
		
		return $donation_id;
	}

	/** Add an external donation. This is for ECS, Global Giving and Online donations only. 
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

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone);

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
				'source_id'			=> 1,
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

	/// Returns a list of donations that should be approved - and made by the people under the volunteer who's ID is given as the argument.
	function getDonationsForApproval($poc_id = 0, $donation_status = 'TO_BE_APPROVED_BY_POC') {
		global $sql;

		$user = new User($poc_id);

		if(!$user->hasRole($user->role_ids['CFR POC'])) return $this->_error("User '{$user->user['name']}' is not a POC. Only POCs have approval option.");

		$volunteers = $user->getSubordinates();

		if(!$volunteers) return $this->_error("This user don't have any volunteers under them.");

		$donations = $sql->getById("SELECT D.id, D.donation_status, D.eighty_g_required, D.created_at, D.updated_at, D.updated_by, D.donation_amount AS amount,
				U.id AS user_id, CONCAT(U.first_name,' ',U.last_name) AS user_name, DON.id AS donor_id, CONCAT(DON.first_name, ' ', DON.last_name) AS donor_name
			FROM donations D 
			INNER JOIN users U ON D.fundraiser_id=U.id
			INNER JOIN donours DON ON DON.id=D.donour_id
			WHERE donation_status='$donation_status' AND D.fundraiser_id IN (".implode(",", array_keys($volunteers)).")");

		return $donations;
	}

	/// Set the given donation as approved - with the user id(second argument) as the approver.
	function approveDonation($donation_id, $approver_id) {
		$donatinos_for_approval = $this->getDonationsForApproval($approver_id);
		$donation_ids_for_approval = array_keys($donatinos_for_approval);

		if(!in_array($donation_id, $donation_ids_for_approval)) $this->_error("User $approver_id can't approve the donation $donation_id");

		$this->find($donation_id);
		$this->field['donation_status'] = 'HAND_OVER_TO_FC_PENDING';
		$this->field['updated_at'] = 'NOW()';
		$this->field['updated_by'] = $approver_id;
		$this->save();
	}

	/// Use this to handle errors.
	private function _error($message) {
		$this->error = $message;
		return false;
	}
}