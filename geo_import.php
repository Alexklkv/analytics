<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Скрипт геолокации, определение местоположения по IP-адресу пользователя.
 * Используется в аналитике для определения стран и регионов игроков.
 */

	define('TABLES_PREFIX', "an_");

	require_once "config.inc.php";
	require_once "reader.inc.php";
	require_once "queries.inc.php";

	$renames = array(
		'Подмосковье'			=> "Московская область",
		'Пермская область'		=> "Пермский край",
		'Сахалин'			=> "Сахалинская область",
		'Ставрополье'			=> "Ставропольский край",
		'Тюмень'			=> "Тюменская область",
		'Brestskaya Oblast’'		=> "Брестская область",

		'Гомельская Область'		=> "Гомельская область",
		'Гродненская Область'		=> "Гродненская область",
		'Минская Область'		=> "Минская область",
		'Витебская Oбласть'		=> "Витебская область",

		'Mangistauskaya Oblast’'	=> "Мангистауская область",
		'Kostanayskaya Oblast’'		=> "Костанайская область",
		'Kyzylordinskaya Oblast’'	=> "Кызылординская область",
		'Pavlodarskaya Oblast’'		=> "Павлодарская область",
		'Dzhalal-Abadskaya Oblast’'	=> "Джалал-Абадская область",
		'Tsentral’nyy Aymag'		=> "Центральный Аймак",
		'Khubsugul’skiy Aymak'		=> "Хубсугульский Аймак"
	);

	$sql = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

	load_locations($sql);
	load_ips($sql);
	add_defaults($sql);
//	write_locations($sql);

	function write_locations($sql)
	{
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><locations />");

		$countries = $xml->addChild("countries");

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."countries`");
		while ($row = $result->fetch_assoc())
		{
			$record = $countries->addChild("record");
			$record->addAttribute("iso", $row['iso_code']);
			$record->addAttribute("name", $row['name']);
		}

		$subdivisions = $xml->addChild("subdivisions");

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."subdivisions`");
		while ($row = $result->fetch_assoc())
		{
			$record = $subdivisions->addChild("record");
			$record->addAttribute("country", $row['country_id']);
			$record->addAttribute("name", $row['name']);
		}

		$xml->asXML("locations.xml");

/*
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><locations />");

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."locations` WHERE `city_name` != ''");
		while ($row = $result->fetch_assoc())
		{
			$record = $locations->addChild("record");
			$record->addAttribute("id", $row['geoname_id']);
			$record->addAttribute("name", $row['city_name']);
		}
*/
	}

	function load_locations($sql)
	{
		$sql->query("TRUNCATE TABLE `".TABLES_PREFIX."locations`");

		$countries = array('' => 0);
		$subdivisions = array('' => 0);
		$cities = array('' => 0);

		load_data($countries, $subdivisions, $cities);

		$reader = new FileReader("GeoIP2-City-Locations-ru.csv");
		$buffer = new QueriesBuffer($sql, TABLES_PREFIX."locations");

		$lines = 0;
		while (!$reader->eof())
		{
			$line = $reader->get_line();
			$lines++;

			if ($lines == 1)
				continue;

			$line = trim($line);
			if ($line == "")
				continue;

			// geoname_id,locale_code,continent_code,continent_name,country_iso_code,country_name,subdivision_1_iso_code,subdivision_1_name,subdivision_2_iso_code,subdivision_2_name,city_name,metro_code,time_zone
			$data = str_getcsv($line);

			$country = get_country($data, $countries);
			$subdivision = get_subdivision($data, $country[0], $subdivisions);
			$city = get_city($data, $subdivision[0], $cities);

			$row = array();
			$row[] = $data[0];		// geoname_id

			$row[] = $country[0];		// country_id
			$row[] = $country[1];		// country_iso_code
			$row[] = $country[2];		// country_name

			$row[] = $subdivision[0];	// subdivision_id
			$row[] = $subdivision[1];	// subdivision_iso_code
			$row[] = $subdivision[2];	// subdivision_name

			$row[] = $city[0];		// city_id
			$row[] = $city[1];		// city_name
			$buffer->add($row);
		}

		echo "Readed ".($reader->pos())." of ".($reader->size())." bytes (".round($reader->pos() * 100 / $reader->size())."%) DONE\n";

		$buffer->finish();
		$reader->close();
	}

	function load_ips($sql)
	{
		$sql->query("TRUNCATE TABLE `".TABLES_PREFIX."ips`");

		$reader = new FileReader("GeoIP2-City-Blocks-IPv4.csv");
		$buffer = new QueriesBuffer($sql, TABLES_PREFIX."ips");

		$lines = 0;
		while (!$reader->eof())
		{
			$line = $reader->get_line();
			$lines++;

			if ($lines == 1)
				continue;

			$line = trim($line);
			if ($line == "")
				continue;

			// netwok,geoname_id,registered_country_geoname_id,represented_country_geoname_id,is_anonymous_proxy,is_satellite_provider,postal_code,latitude,longitude
			$data = str_getcsv($line);
			$network = explode("/", $data[0]);

			$row = array();
			$row[] = ip2long($network[0]);	// ip
			$row[] = $network[1];		// mask
			$row[] = $data[1];		// geoname_id
			$row[] = get_float($data[7]);	// latitude
			$row[] = get_float($data[8]);	// longitude
			$buffer->add($row);

			if ($lines % 10000 == 0)
				echo "\rReaded ".($reader->pos())." of ".($reader->size())." bytes (".round($reader->pos() * 100 / $reader->size())."%)";
		}

		echo "\rReaded ".($reader->pos())." of ".($reader->size())." bytes (".round($reader->pos() * 100 / $reader->size())."%) DONE\n";

		$buffer->finish();
		$reader->close();
	}

	function load_data(&$countries, &$subdivisions, &$cities)
	{
		global $sql;

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."countries`");
		while ($row = $result->fetch_assoc())
		{
			$key = $row['iso_code']."_".$row['name'];

			$countries[$key] = $row['id'];
		}

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."subdivisions`");
		while ($row = $result->fetch_assoc())
		{
			if ($row['iso_code'] != "" && $row['name'] != "")
				$key = $row['iso_code']."_".$row['name']."_".$row['country_id'];
			else
				$key = "_".$row['country_id'];		// Country default

			$subdivisions[$key] = $row['id'];
		}

		$result = $sql->query("SELECT * FROM `".TABLES_PREFIX."cities`");
		while ($row = $result->fetch_assoc())
		{
			$key = $row['name']."_".$row['subdivision_id'];

			$cities[$key] = $row['id'];
		}
	}

	function add_defaults($sql)
	{
		$result = $sql->query("SELECT MAX(`id`) as `id` FROM `".TABLES_PREFIX."countries`");
		$row = $result->fetch_assoc();

		for ($id = 0; $id <= $row['id']; $id++)
		{
			$sql->query("INSERT IGNORE INTO `".TABLES_PREFIX."subdivisions` SET `country_id` = {$id}, `iso_code` = '', `name` = ''");
			if ($sql->errno != 0)
				die($sql->error."\n");
		}

		$sql->query("UPDATE `".TABLES_PREFIX."locations` l LEFT JOIN `".TABLES_PREFIX."subdivisions` s ON s.`country_id` = l.`country_id` AND s.`iso_code` = '' SET l.`subdivision_id` = s.`id` WHERE l.`subdivision_id` = 0");
		$sql->query("UPDATE `".TABLES_PREFIX."cities` c LEFT JOIN `".TABLES_PREFIX."locations` l ON l.`city_id` = c.`id` SET c.`subdivision_id` = l.`subdivision_id` WHERE c.`subdivision_id` = 0");
	}

	function get_float($num)
	{
		if (empty($num))
			return 0;

		$num = explode(".", $num);
		if (!isset($num[1]))
			return $num[0] * 100000;

		if ($num[0] < 0)
			return $num[0] * 100000 - $num[1];

		return $num[0] * 100000 + $num[1];
	}

	function get_country($data, &$countries)
	{
		global $sql;

		$country_iso_code = trim($data[4], "\"");
		$country_name = trim($data[5], "\"");

		$key = "";
		if ($country_iso_code != "")
			$key = $country_iso_code."_".$country_name;

		if (isset($countries[$key]))
			return array($countries[$key], $country_iso_code, $country_name);

		$sql->query("INSERT INTO `".TABLES_PREFIX."countries` SET `iso_code` = '{$country_iso_code}', `name` = '".$sql->real_escape_string($country_name)."'");
		if ($sql->errno != 0)
			die($sql->error."\n");

		$country_id = $sql->insert_id;
		$countries[$key] = $country_id;

		echo "Country \"{$country_name}\", \"{$country_iso_code}\" added with id {$country_id}\n";

		return array($country_id, $country_iso_code, $country_name);
	}

	function get_subdivision($data, $country_id, &$subdivisions)
	{
		global $sql, $renames;

		if (!empty($data[8]) || !empty($data[9]))
		{
			$subdivision_iso_code = trim($data[8], "\"");	// subdivision_2_iso_code
			$subdivision_name = trim($data[9], "\"");	// subdivision_2_name
		}
		else
		{
			$subdivision_iso_code = trim($data[6], "\"");	// subdivision_1_iso_code
			$subdivision_name = trim($data[7], "\"");	// subdivision_1_name
		}

		if (isset($renames[$subdivision_name]))
			$subdivision_name = $renames[$subdivision_name];

		$key = "";
		if ($subdivision_iso_code != "" && $subdivision_name != "")
			$key = $subdivision_iso_code."_".$subdivision_name."_".$country_id;
		else
			$key = "_".$country_id;				// Country default

		if (isset($subdivisions[$key]))
			return array($subdivisions[$key], $subdivision_iso_code, $subdivision_name);

		$sql->query("INSERT INTO `".TABLES_PREFIX."subdivisions` SET `country_id` = {$country_id}, `iso_code` = '{$subdivision_iso_code}', `name` = '".$sql->real_escape_string($subdivision_name)."'");
		if ($sql->errno != 0)
			die($sql->error."\n");

		$subdivision_id = $sql->insert_id;
		$subdivisions[$key] = $subdivision_id;

		echo "Subdivision \"{$subdivision_name}\", \"{$subdivision_iso_code}\", {$country_id} added with id {$subdivision_id}\n";

		return array($subdivision_id, $subdivision_iso_code, $subdivision_name);
	}

	function get_city($data, $subdivision_id, &$cities)
	{
		global $sql;

		$city_name = trim($data[10], "\"");

		$key = "";
		if ($city_name != "")
			$key = $city_name."_".$subdivision_id;

		if (isset($cities[$key]))
			return array($cities[$key], $city_name);

		$sql->query("INSERT INTO `".TABLES_PREFIX."cities` SET `subdivision_id` = {$subdivision_id}, `name` = '".$sql->real_escape_string($city_name)."'");
		if ($sql->errno != 0)
			die($sql->error."\n");

		$city_id = $sql->insert_id;
		$cities[$key] = $city_id;

		echo "City \"{$city_name}\", {$subdivision_id} added with id {$city_id}\n";

		return array($city_id, $city_name);
	}

?>