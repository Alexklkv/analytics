<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Скрипт авторазвёртывания проекта на сервере аналитики и на игровых серверах,
 * обращается к репозиторию и в зависимости от наличия изменений делает обновление
 * на продакшн-серверах
 */

	define('HGPATH', "/usr/bin/hg");
	define('HGWORKDIR', "/usr/hg/analytics");
	define('HGEXEC', HGPATH." -R ".HGWORKDIR);
	define('LOGFILE', "autodeploy.rev");

	require_once "servers.inc.php";
	require_once "passwords.inc.php";

	$files = array(
		"objects/analytics/analytics.inc.php"	=> false
	);

	reset($servers);
	while (list($server_name, $data) = each($servers))
	{
		while (list(, $file) = each($data['files']))
		{
			if (isset($files[$file]))
				continue;

			$files[$file] = $server_name;
		}
	}

	exec(HGEXEC." identify -i", $hgcurrent_revision);
	if (empty($hgcurrent_revision))
		die("hg error\n");

	$hgcurrent_revision = $hgcurrent_revision[0];
	$hgcurrent_revision = str_replace("+", "", $hgcurrent_revision);

	$last_revision = false;
	if (file_exists(LOGFILE))
		$last_revision = file_get_contents(LOGFILE);
	if ($last_revision === false || empty($last_revision))
		$last_revision = $hgcurrent_revision;

	file_put_contents(LOGFILE, $hgcurrent_revision);

	exec(HGEXEC." status -m --rev ".$last_revision, $hgfiles);
	if (empty($hgfiles))
		die("No changes found\n");

	$servers_to_update = array();
	$files_to_update = array();
	$update_all = false;

	while (list(, $file) = each($hgfiles))
	{
		$exploded = explode(" ", $file);
		if (!isset($exploded[1]))
			continue;

		$filename = $exploded[1];
		if (!isset($files[$filename]))
			continue;

		$files_to_update[] = $filename;

		if ($files[$filename] === false || $update_all === true)
		{
			$update_all = true;
			continue;
		}

		if (!in_array($files[$filename], $servers_to_update))
			$servers_to_update[] = $files[$filename];
	}

	if (empty($servers_to_update) && $update_all === false)
		die("No changes found\n");

	reset($servers);
	while (list($name, $server) = each($servers))
	{
		if ($update_all === false && $name != "cdn")
		{
			if (!in_array($name, $servers_to_update))
				continue;
		}

		$connection = ftp_connect($server['host'], $server['port']);
		if ($connection === false)
			die("Failed to connect to server: {$server['host']}\n");

		$login = ftp_login($connection, $server['login'], $server['password']);
		if ($login === false)
			die("Failed to login to server: {$server['host']}\n");

		ftp_pasv($connection, true);

		while (list(, $file) = each($server['files']))
		{
			if (!in_array($file, $files_to_update))
				continue;
			$upload = ftp_put($connection, $server['basedir'].$file, HGWORKDIR."/".$file, FTP_BINARY);
		}

		ftp_close($connection);
	}

?>