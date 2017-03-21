<?php

	// Engine
	define('ENGINE_VERSION',		"1.0.2");
	define('ENGINE_MODULES',		"main,analytics,bigscreen,labels,pages,users,files,logout");

	// Locations
	define('MAIN_LOCATION',			"/home/bigstat.net/www/");

	// Site
	define('SITE_CHARSET',			"utf-8");
	define('SITE_DATETIME_FORMAT',		"d.m.Y H:i");
	define('SITE_DOMAIN_NAME',		"bigstat.net");

	// Database
	define('DATABASE_HOST',			"localhost");
	define('DATABASE_NAME',			"analytics");
	define('DATABASE_USER',			"analytics");
	define('DATABASE_PASSWORD',		"");
	define('DATABASE_PREFIX',		"an_");

	// Session
	define('SESSION_NAME',			"ANALYTICS_SESSID");
	define('SESSION_DOMAIN_NAME',		SITE_DOMAIN_NAME);
	define('SESSION_LIFE_TIME',		365 * 24 * 60 * 60);
	define('SESSION_MAX_LOGIN_LEN',		32);
	define('SESSION_MAX_PASSWORD_LEN',	128);
	define('SESSION_INTERNAL_IPS',		"87.229.238.138,62.33.103.11");

	// PagesVersions
	define('PAGES_VERSIONS_PER_PAGE',	50);

	// Admin
	define('ADMIN_SESSION_PREFIX',		"ADMIN");
	define('ADMIN_PASSWORD_SALT',		"");

	// Cache
	define('CACHE_HOST',			"/tmp/memcached.socket");
	define('CACHE_PORT',			0);
	define('CACHE_HASH_PREFIX',		SESSION_DOMAIN_NAME);

	// Images
	define('IMAGES_QUALITY',		80);

	// Files
	define('FILES_MAX_SIZE',		10485760);
	define('FILES_MAX_NAME_LENGTH',		128);

	define('UPLOAD_DIR',			MAIN_LOCATION."uploads");

?>