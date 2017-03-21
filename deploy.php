<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Скрипт развёртывания проекта на продакшн-серверах, запускается вручную
 */

	require_once "servers.inc.php";
	require_once "passwords.inc.php";

	while (list($name, $server) = each($servers))
	{
		$connection = ftp_connect($server['host'], $server['port']);
		if ($connection === false)
			die("Failed to connect to server: {$server['host']}\n");

		$login = ftp_login($connection, $server['login'], $server['password']);
		if ($login === false)
			die("Failed to login to server: {$server['host']}\n");

		ftp_pasv($connection, true);

		while (list(, $file) = each($server['files']))
		{
			echo "Uploading {$name}/{$file} ... ";

			$upload = ftp_put($connection, $server['basedir'].$file, $file, FTP_BINARY);
			if ($upload === false)
				echo "failed\n";
			else
				echo "done\n";
		}

		ftp_close($connection);
	}

?>