<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'database' => '/librivox/www/librivox.org/catalog/application/tests/librivox_catalog_test.sqlite3',
	'dbdriver' => 'sqlite3',
);

$db['librivox_forum'] = array(
	'database' => '/librivox/www/librivox.org/catalog/application/tests/librivox_catalog_test.sqlite3',
	'dbdriver' => 'sqlite3',
);

$db['catalog'] = array(
	'database' => '/librivox/www/librivox.org/catalog/application/tests/librivox_catalog_test.sqlite3',
	'dbdriver' => 'sqlite3',
);

/* End of file database.php */
/* Location: ./application/config/database.php */
