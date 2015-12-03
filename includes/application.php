<?php

function param($name, $default_value = false) {
	global $QUERY;
	$return = i($QUERY, $name);

	if(!$return and $default_value === false) die("Neccessary Parameter Missing: $name");

	return $return;
}
