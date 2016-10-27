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

		if(!$donor_id) return $this->_error("Can't find a valid Donor. Try logging out of the app and logging back in again.");
		if(!$fundraiser_id) return $this->_error("Can't find a valid Fundraiser. Try logging out of the app and logging back in again.");


		if(isset($created_at) and $created_at) {
			if($created_at == '1970-01-01') $created_at = date("Y-m-d H:i:s");
			else $created_at = date("Y-m-d H:i:s", strtotime($created_at));
		} else {
			$created_at = date('Y-m-d H:i:s');
		}

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
				'comment'		=> $comment,
				'donation_status'	=> 'TO_BE_APPROVED_BY_POC',
				'source_id'			=> 1,

				// Legacy stuff
				'donation_type'		=> 'GEN',
				'version'			=> 1,
				'product_id'		=> 1,
			));

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
		$email->html = '<html>
						<head>
						<title>Acknowledgement Email</title>
						</head>
						<body>
						<table style="width: 960px;margin:0 auto;height: auto;border: 2px solid #f1f1f1;font-family:arial;font-size:20px;">
						<tr><td style="vertical-align: top;"><img style="float:left;margin: 0px;" src="' . $base_url .  'assets/mad-letterhead-left.png' . '"/><img style="margin-left: -70px;" src="' . $base_url . 'assets/mad-letterhead-logo.png' . '"/><img style="float:right;margin:0px;" src="' . $base_url . 'assets/mad-letterhead-right.png' . '"/></td></tr>
						<tr><td style="color:#cc2028;float:right;margin:10px 20px;"> ' . date("d/m/Y") . ' </td></tr>
						<tr><td style="padding:10px 20px;"><strong>Dear ' . $donor_name . ',</strong></td></tr>
						<tr><td style="padding:10px 20px;">Thanks a lot for your contribution of Rs.<strong style="color:#cc2028;">' . $amount . '/-</strong> towards Make A Difference.</td></tr>
						<tr><td style="padding:10px 20px;">This is not a donation receipt. But only an acknowledgement. We will be sending you the e-receipt for the donation within the next 30 days once the amount reaches us.</td></tr>
						<tr><td style="padding:10px 20px;">Please feel free to contact us on <a href="mailto:info@makeadiff.in">info@makeadiff.in</a> for any clarifications.</td></tr>
						<tr><td style="padding:10px 20px;"><i>Little bit about Make A Difference: We are a youth run volunteer organization that  mobilizes young leaders to provide better outcomes to children living in shelter homes across India.</i></td></tr>
						<tr><td style="padding:20px 20px;"><i>You can read more about us @ <a href="http://www.makeadiff.in"> www.makeadiff.in </a> | <a href="http://www.facebook.com/makeadiff"> www.facebook.com/makeadiff </a> | <a href="http://www.twitter.com/makeadiff">www.twitter.com/makeadiff</a></i></td></tr>
						<tr><td style="color:#333231;font-size:16px;padding:0 20px;">First Floor, House no. 16C, MCHS colony, 1st B Main, 14th C Cross,</td></tr>
						<tr><td style="color:#333231;font-size:16px;padding:0 20px;">HSR Layout, Sector 6, Bangalore - 560102.</td></tr>
						<tr><td style="color:#333231;float:right;font-size:16px;margin:0 20px 20px;">http://www.makeadiff.in</td></tr>
						</table>
						</body>
						</html>';
		$email->to = $donor_email;
		$email->from = "noreply <noreply@makeadiff.in>";
		$email->subject = "Donation Acknowledgment";
		$email->images = $images;

		$email->send();



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


	// Used to validate the donation
	function validate($data) {

		global $sql;
		$donor_address = '';
		extract($data);

		$donor_id = $this->findDonor($donor_name, $donor_email, $donor_phone, $donor_address);

		if(!$donor_id) return $this->_error("Can't find a valid Donor ID for this donation. Try logging out of the app and logging back in again.");
		if(!$fundraiser_id) return $this->_error("Can't find a valid Fundraiser ID for this donation. Try logging out of the app and logging back in again.");

		if($this->checkIfDonorDetailsSameAsVolunteerBelowXAmount($donor_email,$donor_phone,$fundraiser_id)) {
			return $this->_error("You seem to have entered your own details in place of the donor. If you continue, the donor won't receive the acknowledgement or receipt. Are you sure you want to continue?");
		}elseif ($created_date = $this->checkIfRepeatDonation($donor_id,$fundraiser_id,$amount)) {
			return $this->_error("Donation of Rs. $amount from $donor_name has already been added on $created_date. Are you sure you want to add the same amount again?");
		}elseif($data = $this->checkIfRepeatDonationWithDifferentAmount($donor_id,$fundraiser_id)) {
			return $this->_error("Donation of Rs. $data[amount] from $donor_name has already been added on $data[created_date]. Are you sure you want to add another amount again?");
		}

		return true;


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