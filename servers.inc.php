<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Скрипт конфигурации авторазвёртывания проекта на продакшн-серверы
 */

	$servers = array(
		'bottle' => array(
			'host'		=> "88.212.207.116",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/bottle/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/bottle/bottle.inc.php",
				"objects/bottle/bottle_counters.inc.php"
			)
		),
		'bottle_eng' => array(
			'host'		=> "198.204.242.42",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/bottle_eng/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/bottlee/bottlee.inc.php",
				"objects/bottlee/bottlee_counters.inc.php"
			)
		),
		'squirrels' => array(
			'host'		=> "88.212.207.58",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/squirrels/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/squirrels/squirrels.inc.php"
			)
		),
		'squirrels_eng' => array(
			'host'		=> "198.204.242.42",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/squirrelse/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/squirrelse/squirrelse.inc.php"
			)
		),
		'squirrels_unity_rus' => array(
			'host'		=> "88.212.207.58",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/squirrels_unity/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/squirrelsur/squirrelsur.inc.php"
			)
		),
		'gods' => array(
			'host'		=> "88.212.206.113",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/gods/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/gods/gods.inc.php"
			)
		),
		'footwars' => array(
			'host'		=> "88.212.206.113",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/footwars/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/footwars/footwars.inc.php"
			)
		),
		'legends' => array(
			'host'		=> "88.212.206.113",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/localhost/www/legends/analytics/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/legends/legends.inc.php",
				"objects/legends/legends_counters.inc.php"
			)
		),
		'cdn' => array(
			'host'		=> "88.212.207.130",
			'port'		=> 21,
			'login'		=> "",
			'password'	=> "",
			'basedir'	=> "/bigstat.net/www/",
			'files'		=> array(
				"objects/analytics/analytics.inc.php",
				"objects/aninterpreter/aninterpreter.inc.php",
				"objects/bottle/bottle.inc.php",
				"objects/bottle/bottle_counters.inc.php",
				"objects/bottlee/bottlee.inc.php",
				"objects/bottlee/bottlee_counters.inc.php",
				"objects/footwars/footwars.inc.php",
				"objects/gods/gods.inc.php",
				"objects/legends/legends.inc.php",
				"objects/legends/legends_counters.inc.php",
				"objects/squirrels/squirrels.inc.php",
				"objects/squirrelse/squirrelse.inc.php",
				"objects/squirrelsue/squirrelsue.inc.php",
				"objects/squirrelsur/squirrelsur.inc.php",
				"objects/ureports/ureports.inc.php",
				"user/analytics/analytics.css",
				"user/analytics/analytics.js",
				"user/analytics/analytics.inc.php",
				"user/bigscreen/bigscreen.css",
				"user/bigscreen/bigscreen.js",
				"user/bigscreen/bigscreen.inc.php",
				"user/bigscreen/config.xml",
				"user/files/files.inc.php",
				"user/ureports/ureports.inc.php"
			)
		)
	);

?>