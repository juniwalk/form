<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Form
 * @link      https://github.com/juniwalk/form
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

if (!@include __DIR__.'/../vendor/autoload.php') {
	echo 'Please install required components using "composer install".';
	exit(1);
}


Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


$_ENV = array_intersect_key($_ENV, ['TRAVIS' => TRUE]);
$_SERVER['REQUEST_TIME'] = 1234567890;
$_SERVER['REQUEST_ID'] = getmypid();
$_GET = $_POST = [];

if (isset($_SERVER['argv'])) {
	$_SERVER['REQUEST_ID'] = md5(serialize($_SERVER['argv']));
}

$_SERVER = array_intersect_key($_SERVER, array_flip([
	'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv',
	'REQUEST_TIME', 'REQUEST_ID', 'SCRIPT_NAME',
	'PHP_SELF', 'SERVER_ADDR','SERVER_SOFTWARE',
]));
