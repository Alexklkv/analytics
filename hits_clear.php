<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Удаляет данные по событиям (неагрегированные хиты) из an_hits.
 * Дублирует аналогичную задачу в модуле cron (см. файл objects/api/api.inc.php,
 * метод clear() класса ObjectApi). Нужен для ручного запуска в тех случаях,
 * когда cron не сработал по каким-то причинам. Удаляет по 1000 записей
 * за одну итерацию цикла.
 */

	require_once "config.inc.php";

	$sql = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

	$result = $sql->query("SELECT `id` FROM `an_services` WHERE `account` != 0");
	while ($row = $result->fetch_assoc())
		clear($row['id']);

	function clear($id)
	{
		$sql = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

		$total = 0;
		echo "Deleted {$total} rows from service {$id}";

		while (true)
		{
			$sql->query("DELETE FROM `analytics_{$id}`.`an_hits` WHERE `time` < DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 1000");
			if ($sql->affected_rows == 0)
				break;

			$total += $sql->affected_rows;

			echo "\rDeleted {$total} rows from service {$id}";
		}

		echo " DONE\n";
	}

?>