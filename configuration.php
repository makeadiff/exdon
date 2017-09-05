<?php
//Configuration file for iFrame
$config = array(
	'site_title'	=> 'Strategy Dashboard',
	'db_database'	=> (isset($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] == 'makeadiff.in') ? 'makeadiff_cfrapp' : 'Project_Donut',
) + $config_data;
$config['site_home'] = $config_data['site_home'] . 'apps/exdon/';
