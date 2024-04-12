<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'hostname' => 'localhost',
	'username' => 'catalog-test',
	'password' => 'catalog-test',
	'database' => 'librivox_catalog_test',
	'dbdriver' => 'mysqli',
);

$db['librivox_forum'] = array(
	'hostname' => 'localhost',
	'username' => 'catalog-test',
	'password' => 'catalog-test',
	'database' => 'librivox_forum_test',
	'dbdriver' => 'mysqli',
);

$db['catalog'] = array(
	'hostname' => 'localhost',
	'username' => 'catalog-test',
	'password' => 'catalog-test',
	'database' => 'librivox_catalog_test',
	'dbdriver' => 'mysqli',
);

/* End of file database.php */
/* Location: ./application/config/database.php */
