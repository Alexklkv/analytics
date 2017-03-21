<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Тест корректности данных, отдаваемых сервером клиенту для всех видов отчётов
 */

	define('URL',			"http://bigstat.net/?module=analytics&action=load_one&report=");
	define('SETTINGS_FILE',		"an_settings");
	define('OBJECTS_DIR',		"objects");

/**
 * Класс с основной логикой теста корректности данных
 *
 * @todo Доработать для новых типов отчётов.
 * @todo Cделать регулярный автозапуск, логирование и уведомление об ошибках.
 */
class Checker
{
	private $services = array();
	private $errors = array(
		'cant_load'	=> array(),
		'empty_data'	=> array(),
		'wrong_date'	=> array()
	);
	private $skip = array("candles", "round", "nodate", "table");

	private $settings;

	private $check_dates;
	private $current_time;
	private $errors_shown = false;

	private $service;
	private $report;
	private $queue = array();

	private $timers;
	private $counters;

	public function __construct()
	{
		if (file_exists(SETTINGS_FILE))
			$this->settings = json_decode(file_get_contents(SETTINGS_FILE), true);
		else
		{
			$this->settings = array('session_key' => "", 'reports' => array());
			$this->save_settings();
		}

		$this->current_time = gmmktime(0, 0, 0, date("n"), date("d"), date("Y"));
	}

	public function __destruct()
	{
		if ($this->errors_shown === true)
			return;

		$this->show_errors();
		exit();
	}

	public function add_service($service)
	{
		$this->services[] = $service;
	}

	public function run()
	{
		echo "\nStarting checking reports at ".date("Y-m-d H:i")."\n";

		while (list(, $service) = each($this->services))
			$this->check_reports($service);

		$this->check_queue();
		$this->check_errors();
		$this->save_settings();

		echo "\nDone at ".date("Y-m-d H:i")."\n";
	}

	private function check_reports($service)
	{
		$this->service = $service;

		if (!file_exists(OBJECTS_DIR."/".$this->service."/".$this->service.".inc.php"))
			exit("Service: {$this->service} doesn't exists, can't parse");

		$this->event("service_start");

		$file = file_get_contents(OBJECTS_DIR."/".$this->service."/".$this->service.".inc.php");
		$file = mb_substr($file, mb_stripos($file, "public function get_reports()"));
		$file = mb_substr($file, 0, mb_stripos($file, "}"));
		$file = mb_substr($file, mb_stripos($file, "array("));
		$file = preg_replace("/\\\$this->\\w+\\['\\w+'\\]|\\\$this->\\w+/", "1", $file);
		$file = preg_replace("/self::\\w+/", "1", $file);
		$file = str_replace("\$id++", "1", $file);
		$file = str_replace("\"-\",", "", $file);

		$data = array();

		eval('$data = '. $file);
		while (list(, $reports) = each($data))
			$this->counters[$this->service]['total'] += count($reports);

		reset($data);
		while (list($category, $reports) = each($data))
		{
			while (list($name) = each($reports))
			{
				$this->report = $category."_".$name;
				$this->check_report();
				$this->event("report_done");
			}
		}

		$this->event("service_done");
	}

	private function check_queue()
	{
		while (list($service, $reports) = each($this->queue))
		{
			$this->service = $service;

			while (list($report, $response) = each($reports))
			{
				$this->report = $report;

				if (empty($response['json']['data']))
				{
					if ($this->get_answer("empty") !== false)
						$this->add_error("empty_data");

					continue;
				}

				while (list($graph, $data) = each($response['json']['data']))
				{
					$time = strtotime($data[count($data) - 1]['date']);
					$days = ($this->current_time - $time) / 86400;

					if ($days == 0)
						continue;

					$answer = $this->get_answer($days);

					if ($answer === false)
						continue;
					if ($days <= $answer)
						continue;

					$this->add_error("wrong_date", array('time' => $time, 'graph' => $graph));
				}
			}
		}
	}

	private function check_errors()
	{
		$counts = array();
		$found = false;

		while (list($type, $errors) = each($this->errors))
		{
			$counts[$type] = count($errors);

			if ($counts[$type] == 0)
				continue;

			$found = true;
		}

		if ($found === false)
			return;

		$i = 0;
		echo "\nFound errors: ";
		while (list($type, $count) = each($counts))
		{
			echo "({$i}){$type}::{$count} ";
			$i++;
		}

		$this->show_errors();
		$this->errors_shown = true;
	}

	private function show_errors()
	{
		echo "\nShow errors (0, 1, 2 or y/n)? ";
		$type = $this->get_input();
		if ($type === false)
			return;

		switch (1)
		{
			case $type === true:
			case $type === 0:
				reset($this->errors['cant_load']);
				while (list(, $error) = each($this->errors['cant_load']))
					echo "Load error in {$error['service']}::{$error['report']}\n".mb_substr(implode("", $error['data']), 0, 200)."\n";

				if ($type === 0)
					break;
			case $type === true:
			case $type === 1:
				reset($this->errors['empty_data']);
				while (list(, $error) = each($this->errors['empty_data']))
					echo "Empty data in {$error['service']}::{$error['report']}\n";

				if ($type === 1)
					break;
			case $type === true:
			case $type === 2:
				reset($this->errors['wrong_date']);
				while (list(, $error) = each($this->errors['wrong_date']))
				{
					$graphs = array();
					$min_time = false;

					while (list(, $data) = each($error['data']))
					{
						if ($min_time === false)
							$min_time = $data['time'];

						$min_time = min($data['time'], $min_time);
						$graphs[] = $data['graph'];
					}

					echo "Wrong date in {$error['service']}::{$error['report']}, [".implode(", ", $graphs)."], ".date("d.m.Y", $min_time)."\n";
				}

				break;
		}

		if ($type === true)
			return;

		$this->show_errors();
	}

	private function save_settings()
	{
		file_put_contents(SETTINGS_FILE, json_encode($this->settings));
	}

	private function check_report()
	{
		$response = $this->call_report();

		if ($response['json'] === null)
			return $this->add_error("cant_load", $response['text']);
		if (in_array($response['json']['type'], $this->skip))
			return;
		if (empty($response['json']['data']))
		{
			if ($this->get_answer("empty", false) === false)
			{
				$this->add_queue($response);
				return;
			}

			$this->add_error("empty_data");
			return;
		}
		if ($this->check_dates === false)
			return;

		$this->add_queue($response);
	}

	private function event($type)
	{
		echo "\rChecking {$this->service} ...";

		switch ($type)
		{
			case "service_start":
				$this->counters[$this->service] = array('total' => 0, 'now' => 0);
				$this->timers[$this->service] = microtime(true);
				break;
			case "service_done":
				$time = microtime(true) - $this->timers[$this->service];
				$each = round($time / $this->counters[$this->service]['now'], 3);

				echo " {$this->counters[$this->service]['now']}/{$this->counters[$this->service]['total']} reports in ".round($time, 2)."s (".$each."s per report)\n";
				break;
			case "report_done":
				$this->counters[$this->service]['now'] += 1;

				echo " {$this->counters[$this->service]['now']}/{$this->counters[$this->service]['total']}";
				break;
		}
	}

	private function call_report()
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, URL.$this->report."&service=".$this->service);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_COOKIE, "ADMIN_ANALYTICS_SESSID=".$this->settings['session_key']);

		$result = curl_exec($curl);
		curl_close($curl);

		if (mb_stripos($result, "Авторизация") !== false && mb_stripos($result, "form-signin-heading") !== false)
		{
			$this->errors_shown = true;
			exit("Edit session_key in settings file! Analytics asks auth");
		}

		return array('json' => json_decode($result, true), 'text' => $result);
	}

	private function get_answer($days, $ask_user = true)
	{
		if (!isset($this->settings['reports'][$this->service][$this->report]))
		{
			if ($ask_user === false)
				return false;

			echo "\r{$this->service}::{$this->report} ignored days (now: {$days}), 'n'/'no' to skip: ";
			$this->settings['reports'][$this->service][$this->report] = $this->get_input();
		}

		return $this->settings['reports'][$this->service][$this->report];
	}

	private function add_error($type, $data = false)
	{
		if (!isset($this->errors[$type]))
			exit("Wrong error type: {$type}");

		if (!isset($this->errors[$type][$this->service."_".$this->report]))
			$this->errors[$type][$this->service."_".$this->report] = array('service' => $this->service, 'report' => $this->report, 'data' => array());
		$this->errors[$type][$this->service."_".$this->report]['data'][] = $data;
	}

	private function add_queue($response)
	{
		$this->queue[$this->service][$this->report] = $response;
	}

	private function get_input()
	{
		$fp = fopen("php://stdin", "r");
		$input = trim(strtolower(fgets($fp)));

		fclose($fp);

		if ($input == "y" || $input == "yes")
			return true;
		if ($input == "n" || $input == "" || $input == "no")
			return false;
		return intval($input);
	}
}

	$checker = new Checker();

	$checker->add_service("azorium");
	$checker->add_service("bottle");
	$checker->add_service("gods");
	$checker->add_service("olymp");
	$checker->add_service("squirrels");
	$checker->add_service("squirrelse");
	$checker->add_service("megaball");

	$checker->run();

?>