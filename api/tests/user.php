<?php
require 'iframe.php';
use PHPUnit\Framework\TestCase;

class UserApiTest extends TestCase {
	private $base_url = '';
	private $only_priority_tests = false;

	public function __construct() {
		global $base_url;
		$this->base_url = $base_url;
	}

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

} 
