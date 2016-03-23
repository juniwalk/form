<?php

/**
 * @author    Martin Procházka <juniwalk@outlook.cz>
 * @package   Dispatcher
 * @link      https://github.com/juniwalk/Dispatcher
 * @copyright Martin Procházka (c) 2015
 * @license   MIT License
 */

if (!@include __DIR__.'/../vendor/autoload.php') {
	echo 'Please install required components using "composer install".';
	exit(1);
}


Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

define('TEMP_DIR', __DIR__.'/tmp/'.getmypid());
@mkdir(TEMP_DIR, 0777, TRUE);

register_shutdown_function(function () {
	Tester\Helpers::purge(TEMP_DIR);
	rmdir(TEMP_DIR);
});


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


/**
 * @param  string|NULL  $&onfig
 * @return Nette\DI\Container
 */
function createContainer($config = NULL)
{
	$builder = new Nette\Configurator();
	$builder->addConfig(__DIR__.'/config.neon');
	$builder->setTempDirectory(TEMP_DIR);

	if (!is_null($config)) {
		$builder->addConfig($config);
	}

	$builder->addParameters([
		'container' => ['class' => 'SystemContainer_'.md5($config)],
		'appDir' => dirname(__DIR__),
		'wwwDir' => __DIR__,
	]);

	return $builder->createContainer();
}
