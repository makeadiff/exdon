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

	public function testSeach() {
		if($this->only_priority_tests) $this->markTestSkipped("Running only priority tests.");

		$deposit_add_url = $this->base_url . 'donation/search/';
	}

} 
