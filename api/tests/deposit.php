<?php
require 'iframe.php';
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase {
	private $base_url = 'http://localhost/Sites/community/makeadiff/makeadiff.in/apps/exdon/api/';

	private $collected_from_user_id = 8902;
	private $given_to_user_id = 151;
	private $only_priority_tests = false;

	public function testLogin() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$user_name = '9746068565';
		$password = 'pass';

		$login_url = $this->base_url . 'user/login';

		$return = load($login_url, array(
					'method' => 'post', 
					'post_data' => array(
						'phone'		=> $user_name,
						'password'	=> $password
			)));

		$data = json_decode($return);
		$this->assertFalse($data->error); // Failure is false
		$this->assertEquals($data->success, "Login successful");
		$this->assertEquals(trim($data->user->name), "Binny V A");
		$this->assertEquals($data->user->madapp_user_id, "1");
	}

	public function testDepositAddValidation() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$deposit_add_url = $this->base_url . 'deposit/add/';

		$invalid_collected_from_user_id = 2; // Invalid User ID
		$invalid_given_to_user_id = 3; // Invalid User 
		$invalid_donation_ids = [1,2,3];

		$collected_from_user_id = $this->collected_from_user_id; // Binny
		$given_to_user_id = $this->given_to_user_id; // Sushma
		$deposited_donation_ids = [6373, 6437]; // Deposited donations. Will cause failue.

		$post_data = array(
				'collected_from_user_id'=> $invalid_collected_from_user_id,
				'given_to_user_id'		=> $invalid_given_to_user_id,
				'donation_ids'			=> implode(',', $invalid_donation_ids)
			);
		$data = json_decode(load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)));
		$this->assertEquals("Invalid User ID of depositer.", $data->error, "Returned data: " . var_export($data, true));

		$post_data['collected_from_user_id'] = $collected_from_user_id;
		$data = json_decode(load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)));
		$this->assertEquals("Invalid User ID of collector.", $data->error, "Returned data: " . var_export($data, true));

		$post_data['given_to_user_id'] = $collected_from_user_id;
		$data = json_decode(load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)));
		$this->assertEquals("Depositer and collector can't be the same person.", $data->error, "Returned data: " . var_export($data, true));

		$post_data['given_to_user_id'] = $given_to_user_id;
		$data = json_decode(load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)));
		$this->assertEquals("Dontation 1 does not exist.", $data->error, "Returned data: " . var_export($data, true));

		$post_data['donation_ids'] = implode(',', $deposited_donation_ids);
		$data = json_decode(load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)));
		$this->assertEquals("Dontation 6373 is already deposited. You cannot deposit it again.", $data->error, "Returned data: " . var_export($data, true));
	}

	public function testDepositAdd() {
		// if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$deposit_add_url = $this->base_url . 'deposit/add/';

		$collected_from_user_id = $this->collected_from_user_id; // Binny
		$given_to_user_id = $this->given_to_user_id; // Sushma
		$donation_ids = [6713, 4938];

		$post_data = array(
			'collected_from_user_id'=> $collected_from_user_id,
			'given_to_user_id'		=> $given_to_user_id,
			'donation_ids'			=> implode(',', $donation_ids)
		);

		$return = load($deposit_add_url, array('method' => 'post', 'post_data' => $post_data)); $data = json_decode($return);
		$this->assertEquals($data->success, "Deposit made", "Returned data: $return\n" . var_export($data, true) . "\n$deposit_add_url" );
		return $data->deposit_id;
	}

	/**
     * @depends testDepositAdd
     */
	public function testDepositApprove($deposit_id) {
		$deposit_approve_url = $this->base_url . 'deposit/approve/' . $deposit_id;

		$return = load($deposit_approve_url); $data = json_decode($return);
		$this->assertEquals($data->success, "Deposit Approved", "Returned data: $return\n" . var_export($data, true) . "\n$deposit_approve_url");
	}

	/**
     * @depends testDepositAdd
     */
	public function testDepositReject($deposit_id) {
		$deposit_reject_url = $this->base_url . 'deposit/reject/' . $deposit_id;

		$return = load($deposit_reject_url); $data = json_decode($return);
		$this->assertEquals($data->success, "Deposit Rejected", "Returned data: $return\n" . var_export($data, true) . "\n$deposit_reject_url");
	}
} 
