<?php
require 'common.php';
use PHPUnit\Framework\TestCase;

class UserApiTest extends TestCase {
	private $base_url = '';
	private $only_priority_tests = false;

	public function __construct() {
		global $base_url;
		$this->base_url = $base_url;
	}

	public function testGetUndeposited() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$undeposited_url = $this->base_url . 'donation/undeposited/16634';

		$return = load($undeposited_url); $data = json_decode($return);
		$this->assertEquals($data->donations->{8789}->user_name, "Unit Test ", "Returned data: $return\n" . var_export($data, true) . "\n$undeposited_url" );
		$this->assertEquals($data->donations->{8790}->amount, 13, "Returned data: $return\n" . var_export($data, true) . "\n$undeposited_url" );
	}

	public function testGetDeposited() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$undeposited_url = $this->base_url . 'donation/deposited/16634';

		$return = load($undeposited_url); $data = json_decode($return);
		$this->assertEquals($data->donations->{8792}->user_name, "Unit Test ", "Returned data: $return\n" . var_export($data, true) . "\n$undeposited_url" );
	}

	public function testReviewList() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$undeposited_url = $this->base_url . 'donation/for_review_by/151';

		$return = load($undeposited_url); $data = json_decode($return);
		$this->assertEquals($data->donations->{8791}->user_name, "Unit Test ", "Returned data: $return\n" . var_export($data, true) . "\n$undeposited_url" );
	}

} 
