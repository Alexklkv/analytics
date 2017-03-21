<?php

/**
 * Предоставляет общие функции работы со аналитикой
 *
 * @uses DatabaseInterface
 * @uses DateTime
 * @uses ObjectCache
 * @uses ObjectCommon
 * @uses ObjectExport
 * @uses ObjectLog
 *
 * @version 1.0.1
 */
class ObjectAnalytics extends Object implements DatabaseInterface
{
	const COMPARE_SIMPLE = 1;
	const COMPARE_AVG = 2;
	const COMPARE_SUM = 3;

	private $api_paths = false;

	/**
	 * Возвращает список запросов к базе днных, известных объектам
	 * данного класса. Ключ массива - имя метода объекта
	 * соединения с базой данных, в который должны передаваться значения
	 * для подставновки в запрос. Реализует интерфейс DatabaseInterface.
	 * Пример вызова запроса:
	 * <code>
	 * static public function get_queries()
	 * {
	 *	return array(
	 *		'some_query' => "SELECT * FROM `table` WHERE `date` = @s",
	 *		'another_query' => "SELECT * FROM `table2` WHERE `date` = @s"
	 *	);
	 * }
	 *
	 * public function someMethod()
	 * {
	 *	$date = date("Y-m-d");
	 *	$this->DB->some_query($date);
	 * }
	 * </code>
	 *
	 * @see DatabaseInterface::get_queries()
	 *
	 * @return array Список запросов к базе данных
	 */
	static public function get_queries()
	{
		return array(
			'get_services'			=> "SELECT * FROM `@pservices` ORDER BY `id` ASC",
			'get_labels'			=> "SELECT * FROM `@plabels` WHERE `service` = @i ORDER BY `date` ASC",
			'get_filtered_paths'		=> "SELECT * FROM `analytics_@t`.`@ppaths` WHERE `id` IN(@t)",

			'get_cache'			=> "SELECT *, UNIX_TIMESTAMP(`date`) as `time` FROM `@pcache` WHERE `service` = @i AND `report` IN(@l) AND `date` >= FROM_UNIXTIME(@i) AND `date` <= FROM_UNIXTIME(@i) ORDER BY `chart` ASC, `date` ASC",
			'get_cache_date'		=> "SELECT MAX(`date`) as `date` FROM `@pcache` WHERE `service` = @i and `report` = @s",
			'get_filtered_cache'		=> "SELECT `value`, UNIX_TIMESTAMP(`date`) as `time` FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `chart` = @i AND `type` = @i AND `date` >= DATE(FROM_UNIXTIME(@i)) AND `date` <= DATE(FROM_UNIXTIME(@i)) ORDER BY `date` ASC",
			'get_big_screen_data'		=> "SELECT * FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `chart` = @i AND `type` = @i AND `date` >= @s ORDER BY `date` ASC",
			'get_hits_merged'		=> "SELECT @t AS `service`, 'apipath_common' AS `report`, `date`, 0 AS `chart`, CAST(`group_id` AS SIGNED) AS `type`, `visitors` AS `value`, UNIX_TIMESTAMP(`date`) as `time` FROM `analytics_@t`.`@phits_merged` WHERE `group_by` = 'path' AND `group_id` IN(@t) AND `date` >= FROM_UNIXTIME(@i) AND `date` <= FROM_UNIXTIME(@i) UNION SELECT @t AS `service`, 'apipath_common' AS `report`, `date`, 0 AS `chart`, -CAST(`group_id` AS SIGNED) AS `type`, `hits` AS `value`, UNIX_TIMESTAMP(`date`) as `time` FROM `analytics_@t`.`@phits_merged` WHERE `group_by` = 'path' AND `group_id` IN(@t) AND `date` >= FROM_UNIXTIME(@i) AND `date` <= FROM_UNIXTIME(@i) ORDER BY `chart` ASC, `date` ASC",
			'get_live_stream_events'	=> "SELECT lse.`time_1`, lse.`value_1`, lse.`time_2`, lse.`value_2`, lsd.`name`, lsd.`report` FROM `@pstream_events` AS lse INNER JOIN `@pstream_data` AS lsd ON lsd.`id` = lse.`data_id` WHERE lsd.`service_id` = @i ORDER BY lse.`time_2` DESC LIMIT 0, @i",
			'get_live_stream_data'		=> "SELECT * FROM `@pstream_data` WHERE `service_id` = @i",
			'get_live_stream_data_by_id'	=> "SELECT `chart`, `compare_range`, `compare_type` + 0 AS `compare_type`, `direction` + 0 AS `direction`, `id`, `name`, `period`, `report`, `service_id`, `time`, `type`, `value` FROM `@pstream_data` WHERE `id` = @i AND `service_id` = @i",
			'get_stream_avg'		=> "SELECT AVG(`value`) FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `chart` = @i AND `type` = @i AND `date` >= @s AND `date` <= @s",
			'get_stream_sum'		=> "SELECT SUM(`value`) FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `chart` = @i AND `type` = @i AND `date` >= @s AND `date` <= @s",
			'check_live_stream'		=> "SELECT * FROM `@pstream_data` WHERE DATE_ADD(`time`, INTERVAL `period` SECOND) <= NOW()",
			'live_stream_insert_data'	=> "INSERT INTO `@pstream_data` SET @t",
			'live_stream_update_data'	=> "UPDATE `@pstream_data` SET @t WHERE `id` = @i",
			'remove_live_stream_data'	=> "DELETE FROM `@pstream_data` WHERE `id` = @i",
			'remove_live_stream_events'	=> "DELETE FROM `@pstream_events` WHERE `data_id` = @i",
			'add_live_stream_event'		=> "INSERT INTO `@pstream_events` SET `data_id` = @i, `time_1` = @s, `value_1` = @f, `time_2` = @s, `value_2` = @f",

			'get_job'			=> "SELECT `last_id` FROM `@pjobs` WHERE `service` = @i",
			'update_job'			=> "REPLACE INTO `@pjobs` SET `service` = @i, `last_id` = @i",

			'clear_cache'			=> "DELETE FROM `@pcache` WHERE `service` = @i AND `class` = @s AND `report` = @s AND `date` >= @s",
			'flush_cache'			=> "DELETE FROM `@pcache` WHERE `service` = @i AND `report` IN (@l)",
			'add_cache'			=> "INSERT INTO `@pcache` VALUES @t"
		);
	}

	public function get_services()
	{
		static $services = null;

		if (!is_null($services))
			return $services;

		$services = array();

		$result = $this->DB->get_services();
		while ($row = $result->fetch())
			$services[$row['name']] = $row;

		while (list($name) = each($services))
			$this->load_service($services[$name]);

		return $services;
	}

	public function get_data($report)
	{
		$offset = $report['date_begin'];
		if ($report['type'] !== "round" && $report['type'] !== "candles" && $report['type'] !== "table" && $report['type'] !== "monthly")
			$offset = $report['date_begin'] - ($report['date_end'] - $report['date_begin'] + 86400);
		if ($report['type'] === "nodate")
		{
			$offset = mktime(0, 0, 0, 1, 1, 2000);
			$report['date_end'] = time();
		}
		else if ($report['type'] === "monthly")
		{
			$offset = mktime(0, 0, 0, 1, 1, 2000);
			$report['date_end'] = strtotime(date("Y-m-t"));
		}
		else if ($report['type'] === "weekly_yb")
		{
			$date_obj = new DateTime(date("Y", $report['date_end'])."-01-01");
			$date_obj->add(new DateInterval("P".(ceil((date("z", $report['date_end']) + 1) / 7) * 7 - 1)."D"));
			$report['date_end'] = $date_obj->getTimestamp();
		}

		list($category, ) = explode("_", $report['path']);
		if ($category === "apipath")
			$result = $this->DB->get_hits_merged($report['service_id'], $report['service_id'], $report['api_path_id'], $offset, $report['date_end'], $report['service_id'], $report['service_id'], $report['api_path_id'], $offset, $report['date_end']);
		else if ($report['type'] === "special")
			$result = $this->get_special($report);
		else
		{
			$report_cond = isset($report['join_report']) ? array($report['path'], $report['join_report']) : array($report['path']);
			$result = $this->DB->get_cache($report['service_id'], $report_cond, $offset, $report['date_end']);
		}

		switch ($report['type'])
		{
			case "table":
				return $this->format_table($report, $result);
			case "candles":
				return $this->format_candles($report, $result);
			case "nodate":
				return $this->format_nodate($report, $result);
			case "special":
				return $this->format_special($report, $result);
			case "stacked":
			case "filled":
			case "monthly":
			case "weekly":
			case "weekly_yb":
			case "single":
				return $this->format_single($report, $result);
			case "round":
				return $this->format_round($report, $result);
		}
	}

	public function get_export_single($report, $params)
	{
		$export = $this->Export->set_defaults($report['title']);
		$legends = &$params['active_legend'];

		$graphs = $report['graphs'];
		$last = array();
		$inserts = array();

		$result = $this->DB->get_cache($report['service_id'], array($report['path']), $report['date_begin'], $report['date_end']);
		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];
			$date = strtotime($cache['date']);

			if (!isset($legends[$chart]))
				continue;

			if (!isset($graphs[$chart]))
			{
				$this->Log->warning("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				exit;
			}

			if (!isset($graphs[$chart]['legend'][$type]))
				continue;
			if (!isset($last[$chart]))
				$last[$chart] = $date;
			if (!isset($inserts[$chart]))
				$inserts[$chart] = array();
			if ($last[$chart] !== $date)
			{
				if (!empty($inserts))
				{
					$inserts[$chart]['date'] = $last[$chart];
					$export->add_data_row($chart, $inserts[$chart]);
				}

				$inserts[$chart] = array();
				$last[$chart] = $date;
			}

			if (!isset($legends[$chart][$type]))
				continue;

			$inserts[$chart][$type] = $cache['value'];
		}

		while (list($chart, $legend) = each($legends))
		{
			if (!isset($graphs[$chart]))
				continue;

			$export->add_title($chart, $graphs[$chart]['title']);
			$export->add_column($chart, "Дата", "date", "date");

			if (!empty($inserts[$chart]))
			{
				$inserts[$chart]['date'] = $last[$chart];
				$export->add_data_row($chart, $inserts[$chart]);
			}

			while (list($type, $name) = each($legend))
				$export->add_column($chart, $name, $type);
		}

		return $export;
	}

	public function get_export_multiple($reports, $params)
	{
		$services = $this->get_services();

		if (!isset($services[$params['service']]))
			return false;
		$service = &$services[$params['service']];

		$dates = $this->get_date_periods(date("d.m.Y", $params['date_begin']), date("d.m.Y", $params['date_end']));
		if ($dates === false)
			return false;

		$periods = &$dates['periods'];

		$export = $this->Export->set_defaults("Общий отчет");
		$export->has_formulas = false;
		$export->add_column(0, "", "title", "text");

		while (list(, $path) = each($reports))
		{
			list($category, $name) = explode("_", $path, 2);
			if (!isset($service['reports'][$category][$name]))
				continue;

			$report = &$service['reports'][$category][$name];
			$graphs = &$report['graphs'];
			$simple_name = true;
			if (count($graphs) > 1)
				$simple_name = false;

			$inserts = array();

			$result = $this->DB->get_cache($report['service_id'], array($report['path']), $dates['date_begin'], $dates['date_end']);
			while ($cache = $result->fetch())
			{
				$chart = $cache['chart'];
				$type = $cache['type'];
				$date = strtotime($cache['date']);

				if (!isset($graphs[$chart]))
					$this->Log->error("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				if (!isset($graphs[$chart]['legend'][$type]))
					continue;

				$current_period = false;

				reset($periods);
				while (list($key, $period) = each($periods))
				{
					if (!($date >= $period['min'] && $date < $period['max']))
						continue;

					$current_period = "period-".$key;
					break;
				}

				if ($current_period === false)
					continue;
				if (!isset($inserts[$chart]))
					$inserts[$chart] = array();
				if (!isset($inserts[$chart][$type]))
					$inserts[$chart][$type] = array();
				if (!isset($inserts[$chart][$type][$current_period]))
					$inserts[$chart][$type][$current_period] = 0;
				$inserts[$chart][$type][$current_period] += $cache['value'];
 			}

 			while (list($chart, $types) = each($inserts))
 			{
 				while (list($type, $values) = each($types))
 				{
 					$graph = &$graphs[$chart];
	 				$values['title'] = $report['title']." ".$graph['legend'][$type];
	 				if ($simple_name === false)
	 					$values['title'] .= " (".$graph['title'].")";

	 				if ($graph['show_sums'][$type] === true)
	 				{
	 					$export->add_data_row(0, $values);
	 					continue;
	 				}

	 				while (list($key, $value) = each($values))
	 				{
	 					if ($key === "title")
	 						continue;

						$period_key = intval(str_replace("period-", "", $key));
						$days = ($periods[$period_key]['max'] + 1 - $periods[$period_key]['min']) / 86400;
						if ($days == 0)
							continue;

						$values[$key] = round($value / $days, 2);
	 				}

	 				$export->add_data_row(0, $values);
	 			}
 			}
		}

		reset($periods);
		while (list($key, $period) = each($periods))
			$export->add_column(0, $this->get_date_string($period['min'], $period['max']), "period-".$key, "big_numbers");

		return $export;
	}

	public function update($full, $class = "common", $report_only = false)
	{
		echo "\n\nStarting export at ".date("Y-m-d H:i")."\n";

		$this->Cache->disable();

		$start = microtime(true);
		$current_date = date("Y-m-d");

		$services = $this->get_services();

		if ($class === "common")
			$this->run_jobs($services);

		reset($services);
		while (list(, $service) = each($services))
		{
			reset($service['reports']);
			while (list($category, $reports) = each($service['reports']))
			{
				reset($reports);
				while (list(, $report) = each($reports))
				{
					if (!is_array($report))
						continue;
					if ($report_only !== false && $report['path'] != $report_only)
						continue;
					if ($report['class'] !== $class)
						continue;

					$result = $this->DB->get_cache_date($report['service_id'], $report['path']);
					$cache_date = $result->fetch("date");

					$skip = !($report['cache'] || $full);
					if ($cache_date === false)
						$skip = false;

					if ($cache_date !== $current_date && $report['end_date'] !== false && strtotime($cache_date) >= strtotime($report['end_date']))
						continue;

					if (!$report['cache'] || $cache_date === false)
						$cache_date = $report['start_date'];

					if ($skip)
						continue;

					$this->update_cache($report, $cache_date);
				}
			}
		}

		$start = microtime(true) - $start;
		$start = round($start, 2);

		echo "All done in {$start} seconds\n";
	}

	public function get_date_periods($begin, $end, $full_month = false, $exclude_current = false)
	{
		$exploded_begin = explode(".", $begin);
		if (count($exploded_begin) != 3)
			return false;

		$exploded_end = explode(".", $end);
		if (count($exploded_end) != 3)
			return false;

		$max_time = mktime(0, 0, 0, date("n"), date("d") - 1, date("Y"));
		$begin_timestamp = mktime(0, 0, 0, $exploded_begin[1], $exploded_begin[0], $exploded_begin[2]);
		$end_timestamp = mktime(0, 0, 0, $exploded_end[1], $exploded_end[0], $exploded_end[2]);

		if ($end_timestamp > $max_time && $exclude_current === true)
		{
			$exploded_end = array(date("d") - 1, date("n"), date("Y"));
			$end_timestamp = $max_time;
		}

		if ($full_month === true)
		{
			$exploded_begin[0] = 1;
			$begin_timestamp = mktime(0, 0, 0, $exploded_begin[1], $exploded_begin[0], $exploded_begin[2]);

			$exploded_end[0] = date("t", $end_timestamp);
			$end_timestamp = mktime(0, 0, 0, $exploded_end[1], $exploded_end[0], $exploded_end[2]);
		}

		if ($begin_timestamp > $end_timestamp)
			return false;

		$month_offset = ((date("Y", $end_timestamp) - date("Y", $begin_timestamp)) * 12) + (date("m", $end_timestamp) - date("m", $begin_timestamp));
		if ($month_offset == 0)
		{
			$periods = array(
				array(
					'min'		=> $begin_timestamp,
					'max'		=> $end_timestamp + 86399,
					'month_days'	=> date("t", $begin_timestamp)
				),
				array(
					'min'		=> mktime(0, 0, 0, $exploded_begin[1] - 1, 1, $exploded_begin[2]),
					'max'		=> mktime(0, 0, 0, $exploded_begin[1] - 1, 1, $exploded_end[2]),
					'month_days'	=> date("t", mktime(0, 0, 0, $exploded_begin[1] - 1, 1, $exploded_begin[2]))
				)
			);

			$periods[1]['max'] += 86400 * date("t", $periods[1]['max']) - 1;

			while (list($key) = each($periods))
			{
				$point = &$periods[$key];

				$point['umin'] = date("Y-m-d\T00:00:00+00:00", $point['min']);
				$point['umax'] = date("Y-m-d\T00:00:00+00:00", $point['max']);
			}

			return array('date_begin' => $periods[1]['min'], 'date_end' => $end_timestamp, 'periods' => $periods);
		}

		$periods = array(
			array(
				'min'		=> mktime(0, 0, 0, $exploded_end[1], 1, $exploded_end[2]),
				'max'		=> mktime(0, 0, 0, $exploded_end[1], $exploded_end[0], $exploded_end[2]) + 86399,
				'month_days'	=> date("t", mktime(0, 0, 0, $exploded_end[1], 1, $exploded_end[2]))
			)
		);

		for ($i = 1; $i <= $month_offset; $i++)
		{
			$periods[$i] = array(
				'min' => mktime(0, 0, 0, $exploded_end[1] - $i, 1, $exploded_end[2]),
				'max' => mktime(0, 0, 0, $exploded_end[1] - $i, 1, $exploded_end[2])
			);

			$periods[$i]['month_days'] = date("t", $periods[$i]['max']);
			$periods[$i]['max'] += 86400 * $periods[$i]['month_days'] - 1;
		}

		while (list($key) = each($periods))
		{
			$point = &$periods[$key];

			$point['umin'] = date("Y-m-d\T00:00:00+00:00", $point['min']);
			$point['umax'] = date("Y-m-d\T00:00:00+00:00", $point['max']);
		}

		return array('date_begin' => $begin_timestamp, 'date_end' => $end_timestamp, 'periods' => $periods);
	}

	public function get_indicators_set($type, $service)
	{
		return IndicatorsSet::getSets($type, $service, $this->Admin);
	}

	public function get_indicators($date_begin, $date_end, $service, $type, $skip = "")
	{
		$indicators_set = $this->get_indicators_set($type, $service['name']);

		$dates = $this->get_date_periods($date_begin, $date_end, true, true, true);
		if ($dates === false)
			return "";

		$report_data = array('service' => $service['id'], 'date_begin' => $dates['date_begin'], 'date_end' => $dates['date_end']);

		$result = array();
		while ($indicators_set->valid())
		{
			$current = $indicators_set->current();

			if ($skip !== "" && $current->get_report() === $skip)
			{
				$indicators_set->next();
				continue;
			}

			$data = $this->get_indicator($current, $service, $report_data, $dates['periods']);
			if ($data === false)
			{
				$indicators_set->next();
				continue;
			}

			$result[$current->get_report()] = $data;

			$indicators_set->next();
		}

		return array('dates' => $dates, 'reports' => $result);
	}

	public function get_filtered_api_paths($service_id, $paths)
	{
		$data = array();

		$result = $this->DB->get_filtered_paths($service_id, $paths);
		while ($row = $result->fetch())
			$data[$row['id']] = $row['path'];

		return $data;
	}

	public function get_big_screen_data($service, $report, $chart, $type, $period)
	{
		$date = new DateTime();
		$date->sub(new DateInterval("P".$period."D"));
		$data = array();

		$result = $this->DB->get_big_screen_data($service, $report, $chart, $type, $date->format('Y-m-d'));
		while ($row = $result->fetch())
			$data[] = $row;

		return $data;
	}

	public function update_live_stream()
	{
		$result = $this->DB->check_live_stream();
		while ($row = $result->fetch())
		{
			$method = "live_stream_".strtolower($row['compare_type']);

			if (!method_exists($this, $method))
				continue;

			$this->$method($row);
		}
	}

	public function get_live_stream_events($service_id, $end = 20)
	{
		$data = array();

		$result = $this->DB->get_live_stream_events($service_id, $end);
		while ($row = $result->fetch())
			$data[] = $row;

		return $data;
	}

	public function get_live_stream_data($service_id, $id = 0)
	{
		$data = array();

		if ($id === 0)
			$result = $this->DB->get_live_stream_data($service_id);
		else
			$result = $this->DB->get_live_stream_data_by_id($id, $service_id);

		while ($row = $result->fetch())
			$data[] = $row;

		return $data;
	}

	public function save_live_stream_data($fields)
	{
		$data_arr = array();

		while (list($key, $val) = each($fields))
			$data_arr[] = "`$key` = '$val'";

		if ($fields['id'] == 0)
		{
			$time = time();
			if ($fields['compare_type'] == self::COMPARE_SIMPLE)
				$str_time = date("Y-m-d H:i:s", $time);
			else
			{
				$init_date = new DateTime(date("Y-m-d 23:59:59", $time));
				$str_time = $init_date->sub(new DateInterval("P1D"))->format("Y-m-d H:i:s");
				$end_date = $init_date->format("Y-m-d");
				$days = $fields['period'] / 86400;
				$init_date->sub(new DateInterval("P".$days."D"));
				$start_date = $init_date->format("Y-m-d");
			}

			$data_arr[] = "`time` = '$str_time'";

			switch ($fields['compare_type'])
			{
				case self::COMPARE_SIMPLE:
					$result = $this->DB->get_filtered_cache(intval($fields['service_id']), $fields['report'], $fields['chart'], $fields['type'], $time, $time);
					break;
				case self::COMPARE_AVG:
					$result = $this->DB->get_stream_avg(intval($fields['service_id']), $fields['report'], $fields['chart'], $fields['type'], $start_date, $end_date);
					break;
				case self::COMPARE_SUM:
					$result = $this->DB->get_stream_sum(intval($fields['service_id']), $fields['report'], $fields['chart'], $fields['type'], $start_date, $end_date);
					break;
			}

			$row = $result->fetch_row();
			$value = isset($row[0]) ? $row[0] : 0;
			$data_arr[] = "`value` = '$value'";
			$this->DB->live_stream_insert_data(implode(", ", $data_arr));
		}
		else
			$this->DB->live_stream_update_data(implode(", ", $data_arr), intval($fields['id']));

		return ($fields['id'] == 0) ? $this->DB->insert_id : $fields['id'];
	}

	public function remove_live_stream_data($id)
	{
		$this->DB->remove_live_stream_data($id);
	}

	public function remove_live_stream_events($id)
	{
		$this->DB->remove_live_stream_events($id);
	}

	public function update_ad_params()
	{
		echo "\n\nStarting update advertising campaign parameters at ".date("Y-m-d H:i")."\n";

		$this->Cache->disable();

		$start = microtime(true);
		$current_date = date("Y-m-d");

		$services = $this->get_services();

		reset($services);
		while (list(, $service) = each($services))
		{
			$class = ucfirst($service['name']);
			if (!method_exists($this->$class, "update_ad_params"))
				continue;

			$this->$class->update_ad_params();
		}

		$start = microtime(true) - $start;
		$start = round($start, 2);

		echo "All done in {$start} seconds\n";
	}

	public function make_lists()
	{
		$an_services = $this->get_services();

		$services = array();
		$reports = array();

		reset($an_services);
		while (list($service, $data) = each($an_services))
		{
			if (!$this->Admin->check_access("analytics_".$service))
				continue;

			$reports[$service] = array();

			while (list($name, $title) = each($data['categories']))
			{
				if (!isset($data['reports'][$name]))
				{
					$this->Log->warning("Category {$name} has no reports");
					exit;
				}

				if (empty($data['reports'][$name]))
					continue;

				if (!isset($reports[$service]))
					$reports[$service] = array();
				$point = &$reports[$service];

				while (list(, $report) = each($data['reports'][$name]))
				{
					if ($report === "-")
						continue;
					if ($report['connectable'] === false)
						continue;
					if ($report['hidden'] === true)
						continue;
					if (!$this->check_report_access($service, $report['path']))
						continue;

					$point[] = array('title' => $report['title'], 'graphs' => $report['graphs'], 'path' => $report['path'], 'type' => $report['type']);
				}
			}

			$services[$service] = $data['title'];
		}

		while (list($key) = each($reports))
			sort($reports[$key]);

		return array($services, $reports);
	}

	/**
	 * Проверка прав доступа к конткретному отчёту
	 *
	 * @param string $service Имя проекта
	 * @param string $report Имя отчёта
	 * @return bool Есть ли права доступа к отчёту
	 */
	public function check_report_access($service, $report)
	{
		list($category, $report_name) = explode('_', $report, 2);

		return $this->Admin->check_access("analytics_".$service."_".$category."_".str_replace("_", "&#x005f;", $report_name)) || $this->Admin->check_access("analytics_".$service."_all");
	}

	private function get_special($report)
	{
		$class = ucfirst($report['service_name']);
		$method = "special_".$report['path'];

		if (!method_exists($this->$class, $method))
			$this->Log->error("Wrong method name {$method} in {$report['service_name']}::{$report['path']}");

		return $this->$class->$method();
	}

	private function format_candles($report, $result)
	{
		if (!isset($report['legend']))
		{
			$this->Log->warning("Legend for report {$report['service_name']}:{$report['path']} not defined");
			exit;
		}

		$graphs = $report['graphs'];
		$graphs['sum'] = 0;
		$graphs['count'] = 0;
		$graphs['legend'] = $report['legend'];

		$data = array();
		$last = false;

		while ($cache = $result->fetch())
		{
			$type = $cache['type'];
			$date = $cache['date'];

			$this->add_gap($data, $last, $date);

			if (!isset($data[$date]))
				$data[$date] = array();
			$point = &$data[$date];

			$point['date'] = $date."T00:00:00+00:00";
			$point['value'.$type] = $cache['value'];

			// Close
			if ($type != 1)
				continue;

			$graphs['sum'] += $cache['value'];
			$graphs['count'] += 1;
		}

		$this->add_labels($report['service_labels'], $data);

		$data = array_values($data);

		return array('data' => $data, 'graphs' => $graphs);
	}

	private function format_nodate($report, $result)
	{
		$graphs = $report['graphs'];
		$full_path = $report['service_name']."_".$report['path'];
		$full_key = $report['service_id']."_".$report['id'];

		$data = array();
		$last = array();

		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];
			$date = $cache['time'];

			if (!isset($graphs[$chart]))
			{
				$this->Log->warning("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				exit;
			}

			if (!isset($graphs[$chart]['legend'][$type]))
				$graphs[$chart]['legend'][$type] = "Not defined ({$type})";

			if (!isset($graphs[$chart]['report_name']))
				$graphs[$chart]['report_name'] = $full_path;
			if (!isset($graphs[$chart]['report_key']))
				$graphs[$chart]['report_key'] = $full_key;

			$graph = &$graphs[$chart];

			if (!isset($graph['sums']))
				$graph['sums'] = array();
			if (!isset($graph['sums'][$type]))
				$graph['sums'][$type] = 0;
			if (!isset($graph['counts']))
				$graph['counts'] = array();
			if (!isset($graph['counts'][$type]))
				$graph['counts'][$type] = 0;
			if (!isset($last[$chart]))
				$last[$chart] = array();
			if (!isset($last[$chart][$type]))
				$last[$chart][$type] = array('date' => $date, 'position' => 0);
			if ($last[$chart][$type]['date'] !== $date)
			{
				$last[$chart][$type]['position'] += round(($date - $last[$chart][$type]['date']) / 86400);
				$last[$chart][$type]['date'] = $date;
			}

			$position = &$last[$chart][$type]['position'];

			if (!isset($data[$chart][$position]))
				$data[$chart][$position] = array('x' => $position);
			$point = &$data[$chart][$position];

			$point['value-'.$full_key.$chart.$type] = $cache['value'];
			$graph['sums'][$type] += $cache['value'];
			$graph['counts'][$type] += 1;
		}

		while (list($chart) = each($data))
		{
			$fill = 0;

			while (list($key, $values) = each($data[$chart]))
			{
				while ($key > $fill)
				{
					$data[$chart][$fill] = array('x' => $fill);
					$fill += 1;
				}

				$fill += 1;
			}

			ksort($data[$chart]);
		}

		return array('data' => $data, 'graphs' => $graphs);
	}

	public function format_special($report, $result)
	{
		$graphs = $report['graphs'];
		$full_path = $report['service_name']."_".$report['path'];
		$full_key = $report['service_id']."_".$report['id'];

		$data = array();
		$last = array();
		$current_date = strtotime(date("Y-m-d 00:00:00"));

		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];
			$date = $current_date + $cache['hour'] * 3600;

			if (!isset($graphs[$chart]))
			{
				$this->Log->warning("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				exit;
			}

			if (!isset($graphs[$chart]['legend'][$type]))
				$graphs[$chart]['legend'][$type] = "Not defined ({$type})";

			if (!isset($graphs[$chart]['report_name']))
				$graphs[$chart]['report_name'] = $full_path;
			if (!isset($graphs[$chart]['report_key']))
				$graphs[$chart]['report_key'] = $full_key;

			$graph = &$graphs[$chart];

			if (!isset($graph['sums']))
				$graph['sums'] = array();
			if (!isset($graph['sums'][$type]))
				$graph['sums'][$type] = 0;
			if (!isset($graph['counts']))
				$graph['counts'] = array();
			if (!isset($graph['counts'][$type]))
				$graph['counts'][$type] = 0;
			if (!isset($last[$chart]))
				$last[$chart] = array();
			if (!isset($last[$chart][$type]))
				$last[$chart][$type] = array('date' => $date, 'position' => 0);
			if ($last[$chart][$type]['date'] !== $date)
			{
				$last[$chart][$type]['position'] += round(($date - $last[$chart][$type]['date']) / 3600);
				$last[$chart][$type]['date'] = $date;
			}

			$position = &$last[$chart][$type]['position'];

			if (!isset($data[$chart][$position]))
				$data[$chart][$position] = array('x' => $position);
			$point = &$data[$chart][$position];

			$point['value-'.$full_key.$chart.$type] = $cache['value'];
			$graph['sums'][$type] += $cache['value'];
			$graph['counts'][$type] += 1;
		}

		while (list($chart) = each($data))
		{
			$fill = 0;

			while (list($key, $values) = each($data[$chart]))
			{
				while ($key > $fill)
				{
					$data[$chart][$fill] = array('x' => $fill);
					$fill += 1;
				}

				$fill += 1;
			}

			ksort($data[$chart]);
		}

		return array('data' => $data, 'graphs' => $graphs);
	}

	private function format_table($report, $result)
	{
		$data = array();
		$legend = $report['legend'];
		$legend_groups = $report['legend_groups'];
		$filter = false;
		$build_legend = empty($legend);

		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];

			if ($build_legend === false && !isset($legend[$type]))
				continue;

			if (isset($legend[$type]))
			{
				$data[$chart][$type] = $cache['value'];
				continue;
			}

			$legend_label = $this->get_legend_label($type, $report);
			$group_index = $this->get_legend_group_index($type, $report);

			if ($legend_label === false)
				continue;
			if ($group_index === false)
				continue;

			if (!isset($legend_groups[$group_index]['groups']))
				$legend_groups[$group_index]['groups'] = array();

			$legend[$type] = $legend_label;
			$legend_groups[$group_index]['groups'][] = intval($type);

			$data[$chart][$type] = $cache['value'];
		}

		while ($build_legend === true && (list($key) = each($legend_groups)))
		{
			if (isset($legend_groups[$key]['groups']))
				sort($legend_groups[$key]['groups'], SORT_STRING);
		}

		if ($report['filter'] === true)
			$filter = $this->get_table_filter($legend, $report);

		return array('data' => $data, 'legend' => $legend, 'legend_groups' => $legend_groups, 'filter' => $filter, 'editable' => $report['editable'], 'show_image' => $report['show_image']);
	}

	private function format_single($report, $result)
	{
		$graphs = $report['graphs'];
		$full_path = $report['service_name']."_".$report['path'];
		$full_key = $report['service_id']."_".$report['id'];

		$data = array();
		$last = array();

		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];
			$date = $cache['date'];

			if (!isset($graphs[$chart]))
			{
				$this->Log->warning("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				exit;
			}

			if (!isset($graphs[$chart]['legend'][$type]))
				$graphs[$chart]['legend'][$type] = "Not defined ({$type})";

			if (!isset($graphs[$chart]['report_name']))
				$graphs[$chart]['report_name'] = $full_path;
			if (!isset($graphs[$chart]['report_key']))
				$graphs[$chart]['report_key'] = $full_key;

			$graph = &$graphs[$chart];

			if (!isset($graph['old_period']))
				$graph['old_period'] = array('sums' => array(), 'counts' => array());
			if (!isset($graph['old_period']['sums'][$type]))
				$graph['old_period']['sums'][$type] = 0;
			if (!isset($graph['old_period']['counts'][$type]))
				$graph['old_period']['counts'][$type] = 0;

			if ($cache['time'] < $report['date_begin'] && $report['type'] !== "monthly")
			{
				$graph['old_period']['sums'][$type] += $cache['value'];
				$graph['old_period']['counts'][$type] += 1;

				continue;
			}

			if (!isset($graph['sums']))
				$graph['sums'] = array();
			if (!isset($graph['sums'][$type]))
				$graph['sums'][$type] = 0;
			if (!isset($graph['counts']))
				$graph['counts'] = array();
			if (!isset($graph['counts'][$type]))
				$graph['counts'][$type] = 0;

			if (!isset($data[$chart]))
				$data[$chart] = array();
			if (!isset($last[$chart]))
				$last[$chart] = false;
			if ($report['type'] !== "monthly" && $report['type'] !== "weekly" && $report['type'] !== "weekly_yb")
				$this->add_gap($data[$chart], $last[$chart], $date);

			if (!isset($data[$chart][$date]))
				$data[$chart][$date] = array();
			$point = &$data[$chart][$date];

			$point['date'] = $date."T00:00:00+00:00";
			$point['value-'.$full_key.$chart.$type] = $cache['value'];

			$graph['sums'][$type] += $cache['value'];
			$graph['counts'][$type] += 1;
		}

		while (list($chart) = each($data))
		{
			$this->add_labels($report['service_labels'], $data[$chart]);
			$data[$chart] = array_values($data[$chart]);
		}

		return array('data' => $data, 'graphs' => $graphs);
	}

	private function format_round($report, $result)
	{
		$graphs = $report['graphs'];
		$data = array();

		while ($cache = $result->fetch())
		{
			$chart = $cache['chart'];
			$type = $cache['type'];

			if (!isset($graphs[$chart]))
			{
				$this->Log->warning("Chart {$chart} for report {$report['service_name']}:{$report['path']}:{$chart} not defined");
				exit;
			}

			if (!isset($graphs[$chart]['legend'][$type]))
				$graphs[$chart]['legend'][$type] = "Not defined ({$type})";

			if (!isset($data[$chart]))
				$data[$chart] = array();

			if (!isset($data[$chart][$type]))
				$data[$chart][$type] = 0;
			$data[$chart][$type] += $cache['value'];
		}

		$order = array();

		while (list($chart) = each($data))
		{
			if ($graphs[$chart]['inherit_order'] !== false)
				$chart_inh = $graphs[$chart]['inherit_order'];
			else
				$chart_inh = $chart;

			arsort($data[$chart_inh]);
			while (list($type) = each($data[$chart_inh]))
				$order[$chart][$type] = array('legend' => $graphs[$chart]['legend'][$type], 'value' => $data[$chart][$type]);

			$order[$chart] = array_values($order[$chart]);
		}

		return array('data' => $order, 'graphs' => $graphs);
	}

	private function format_indicator(&$report, &$periods, $graph, $type)
	{
		$current_day = mktime(0, 0, 0, date("n"), date("d"), date("Y"));

		if ($report['type'] === "monthly")
			$current_day = mktime(23, 59, 59, date("n"), date("t"), date("Y"));

		$indicator = &$report['graphs'][$graph]['indicator'];
		$image = new Trend(580, 100);
		$values = array();
		$data = array();

		reset($periods);
		while (list($key, $offsets) = each($periods))
			$values[$key] = array('value' => 0, 'month_days' => $offsets['month_days'], 'count' => 0, 'max_time' => 0);

		$result = $this->DB->get_filtered_cache($report['service'], $report['path'], $graph, $type, $report['date_begin'], $report['date_end']);
		while ($row = $result->fetch())
		{
			$time = $row['time'];
			$data[date("Y-m-d\T00:00:00+00:00", $row['time'])] = $row['value'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']) || $time >= $current_day)
					continue;

				if ($indicator['type'] == "fixed")
					$values[$key]['value'] = 0;

				$values[$key]['value'] += $row['value'];
				$values[$key]['count'] += 1;
				$values[$key]['max_time'] = max($values[$key]['max_time'], $time);

				$image->add_row($row['value']);
			}
		}

		if ($indicator['type'] == "function")
		{
			$class = "Object".ucfirst($report['service_name']);
			if (!method_exists($class, $indicator['function']))
				$this->Log->error("Wrong indicator function name {$indicator['function']} in {$report['service_name']}::{$report['path']}");

			$values = forward_static_call(array($class, $indicator['function']), $this, $report, $periods, $graph, $type);

			return array($values, $data, $image->draw());
		}

		if ($indicator['type'] == "fixed")
			return array($values, $data, $image->draw());

		while (list($key, $value) = each($values))
		{
			if ($indicator['type'] == "sum")
			{
				$days = ($value['max_time'] - $periods[$key]['min']) / 86400 + 1;
				if ($value['month_days'] == $days)
					continue;

				$values[$key]['diff'] = $value['value'] / $days * $value['month_days'];
				continue;
			}

			if ($value['count'] == 0)
			{
				$values[$key]['value'] = 0;
				continue;
			}

			$values[$key]['value'] = round($value['value'] / $value['count'], 2);
		}

		return array($values, $data, $image->draw());
	}

	private function get_indicator(Indicator $item, $service, $data, $periods)
	{
		list($category, $name) = explode("_", $item->get_report(), 2);
		if (!isset($service['reports'][$category][$name]))
			return false;

		$report = array_merge($service['reports'][$category][$name], $data);

		if (!isset($report['graphs'][$item->get_graph()]['legend'][$item->get_legend()]))
			return false;

		$indicators = array();

		$point = &$report['graphs'][$item->get_graph()];
		list($common, $vertices, $trend) = $this->format_indicator($report, $periods, $item->get_graph(), $item->get_legend());

		$indicators[$item->get_graph()."-".$item->get_legend()] = array(
			'chart'		=> $item->get_graph(),
			'type'		=> $item->get_legend(),
			'order'		=> $item->get_order(),
			'title'		=> $point['title'],
			'sub_title'	=> $point['legend'][$item->get_legend()],
			'value_append'	=> $point['value_append'][$item->get_legend()],
			'negative'	=> $point['negative'][$item->get_legend()],
			'values'	=> $common,
			'data'		=> $vertices,
			'trend'		=> $trend
		);

		return array('title' => $report['title'], 'charts' => $indicators);
	}

	private function add_labels($service_id, &$data)
	{
		$labels = $this->get_labels($service_id);
		if ($labels === false)
			return;

		while (list($date, $label) = each($labels))
		{
			if (!isset($data[$date]))
				continue;

			$data[$date]['description'] = $label['value'];
			$data[$date]['label'] = 0;
		}
	}

	private function get_legend_label($type, $report)
	{
		$class = ucfirst($report['service_name']);

		$method_name = $report['path']."_legend";

		if (!method_exists($this->$class, $method_name))
			return false;

		return $this->$class->$method_name($type);
	}

	private function get_table_filter($legend, $report)
	{
		$class = ucfirst($report['service_name']);

		$method_name = $report['path']."_table_filter";

		if (!method_exists($this->$class, $method_name))
			return false;

		return $this->$class->$method_name($legend);
	}

	private function get_legend_group_index($type, $report)
	{
		$class = ucfirst($report['service_name']);

		$method_name = $report['path']."_legend_index";

		if (!method_exists($this->$class, $method_name))
			return false;

		return $this->$class->$method_name($type);
	}

	private function update_cache($report, $cache_date)
	{
		$class = ucfirst($report['service_name']);

		if (!method_exists($this->$class, $report['path']))
		{
			$this->Log->warning("Method {$report['service_name']}::{$report['path']} don't exists");
			return;
		}

		echo "Counting {$report['service_name']}::{$report['path']} from {$cache_date} ... ";
		$start = microtime(true);

		$data = $this->$class->{$report['path']}($cache_date);

		$clear_date = $this->get_clear_date($data);
		$this->DB->clear_cache($report['service_id'], $report['class'], $report['path'], $clear_date);
		$removed = $this->DB->affected_rows;
		$inserted = 0;

		reset($data);
		while (list($i) = each($data))
		{
			$values = "";
			$pieces = 0;

			reset($data[$i]);
			while (list(, $point) = each($data[$i]))
			{
				if ($values != "")
					$values .= ",";

				$values .= "('".$report['service_id']."', '".$report['class']."', '".$report['path']."', '".$point['date']."', ".$i.", ".$point['type'].", ".$point['value'].")";
				$pieces++;

				if ($pieces != 1000)
					continue;

				$this->DB->add_cache($values);
				$inserted += $this->DB->affected_rows;

				$pieces = 0;
				$values = "";
			}

			if ($values == "")
				continue;

			$this->DB->add_cache($values);
			$inserted += $this->DB->affected_rows;
		}

		$total = $inserted - $removed;

		$start = microtime(true) - $start;
		$start = round($start, 2);

		echo "done in {$start} seconds ({$total} rows changed +{$inserted} -{$removed})\n";
	}

	private function load_service(&$service)
	{
		$class = ucfirst($service['name']);

		$this->$class::init($service['id']);

		$service['categories'] = $this->$class->get_categories();
		$service['reports'] = $this->$class->get_reports();
		$service['jobs'] = $this->$class->get_jobs();

		reset($service['reports']);
		while (list($category) = each($service['reports']))
		{
			$reports = &$service['reports'][$category];

			reset($reports);
			while (list($name) = each($reports))
			{
				$report = &$reports[$name];

				if (!is_array($report))
					continue;

				$report['name'] = $name;
				$report['path'] = $category."_".$name;
				$report['service_id'] = $service['id'];
				$report['service_name'] = $service['name'];
				$report['service_labels'] = $service['id'];

				$this->set_defaults($report);
			}
		}
	}

	private function run_jobs($services)
	{
		reset($services);
		while (list(, $service) = each($services))
		{
			$result = $this->DB->get_job($service['id']);

			$last_id = $result->fetch("last_id");
			if ($last_id === false)
				$last_id = 0;

			reset($service['jobs']);
			while (list($id, $reports) = each($service['jobs']))
			{
				if ($id <= $last_id)
					continue;

				echo "Clearing {$service['name']}::{$reports} ... ";

				$reports = explode(",", $reports);
				$reports = array_map("trim", $reports);

				$this->DB->flush_cache($service['id'], $reports);
				$last_id = $id;

				echo "done\n";
			}

			$this->DB->update_job($service['id'], $last_id);
		}
	}

	private function get_labels($service_id)
	{
		$labels = array();

		$result = $this->DB->get_labels($service_id);
		while ($label = $result->fetch())
			$labels[$label['date']] = $label;

		return $labels;
	}

	private function get_date_string($begin, $end = false, $format = false)
	{
		setlocale(LC_TIME, "ru_RU.UTF-8");

		$begin_day = date("d", $begin);
		$begin_month = date("m", $begin);
		$begin_year = date("Y", $begin);

		if ($end === false)
		{
			if ($format !== false && isset($format['begin']))
				return strftime($format['begin'], $begin);
			if ($begin_day == 1)
				return $begin_month." ".$begin_year;
			return $begin_day.".".$begin_month.".".$begin_year;
		}

		$end_day = date("d", $end);
		$end_month = date("m", $end);
		$end_year = date("Y", $end);
		$end_max = date("t", $end);

		if ($begin_month === $end_month && $begin_year === $end_year)
		{
			if ($end_day === $end_max)
				return strftime("%B %Y", $begin);
			return $begin_day."-".strftime("%d %B %Y", $end);
		}
		if ($format !== false)
		{
			$date = date($format['begin'], $begin);
			if (isset($format['end']))
				$date .= strftime($format['end'], $end);

			return $date;
		}

		return date("d.m.Y", $begin)." - ".date("d.m.Y", $end);
	}

	private function set_defaults(&$report)
	{
		$defaults = array(
			'description'	=> "",
			'type'		=> "single",
			'graphs'	=> array(),
			'params'	=> array(),
			'start_date'	=> "2000-01-01",
			'end_date'	=> false,
			'connectable'	=> true,
			'hidden'	=> false,
			'cache'		=> true,
			'class'		=> "common"
		);

		$report = $this->Common->array_merge($defaults, $report);

		switch ($report['type'])
		{
			case "table":
				$this->set_table_defaults($report);
				return;
			case "candles":
				$graph_params = array(
					'value_append'	=> "",
					'show_sums'	=> true
				);

				$report['graphs'] = $this->Common->array_merge($graph_params, $report['graphs']);
				return;
			case "round":
				$graph_params = array(
					'value_append'	=> "",
					'show_legend'	=> true,
					'inherit_order'	=> false
				);
				$legend_params = array();
				break;
			case "stacked":
				$graph_params = array(
					'legend_hide'	=> array(),
					'show_legend'	=> true,
					'indicator'	=> array('type' => "sum")
				);
				$legend_params = array(
					'value_append'	=> "",
					'show_sums'	=> true,
					'negative'	=> false
				);
				break;
			case "nodate":
			case "filled":
			case "single":
			case "weekly":
			case "weekly_yb":
			case "monthly":
			case "special":
				$graph_params = array(
					'legend_hide'	=> array(),
					'legend_menu'	=> false,
					'split_axis'	=> "",
					'show_sumline'	=> false,
					'sort_graphs'	=> false,
					'indicator'	=> array('type' => "sum")
				);
				$legend_params = array(
					'value_append'	=> "",
					'show_sums'	=> true,
					'negative'	=> false
				);
				break;
		}

		$global_params = $report['params'];
		unset($report['params']);

		while (list($id) = each($report['graphs']))
		{
			$graph = &$report['graphs'][$id];

			reset($legend_params);
			while (list($param, $value) = each($legend_params))
			{
				if (isset($global_params[$param]))
					$value = $global_params[$param];
				if (isset($graph[$param]) && !is_array($graph[$param]))
				{
					$value = $graph[$param];
					$graph[$param] = array();
				}

				reset($graph['legend']);
				while (list($key) = each($graph['legend']))
				{
					if (!isset($graph[$param]))
						$graph[$param] = array();
					if (!isset($graph[$param][$key]))
						$graph[$param][$key] = $value;
				}
			}

			reset($graph_params);
			while (list($param, $value) = each($graph_params))
			{
				if (isset($global_params[$param]))
					$value = $global_params[$param];

				if (!isset($graph[$param]))
					$graph[$param] = $value;
			}
		}

		$this->set_session_params($report);
	}

	private function set_table_defaults(&$report)
	{
		unset($report['graphs']);

		$defaults = array(
			'rows'		=> array(),
			'legend_groups'	=> array(),
			'show_legend'	=> false,
			'build_legend'	=> false,
			'filter'	=> false,
			'editable'	=> false,
			'show_image'	=> false
		);

		$report = $this->Common->array_merge($defaults, $report);
		$report['connectable'] = false;

		$rows_defaults = array(
			'title'		=> "",
			'data'		=> false,
			'sub_data'	=> 0,
			'hover_append'	=> "",
			'value_append'	=> "",
			'value'		=> "default",
			'tooltip'	=> "deviation",
			'show_diff'	=> true,
			'negative'	=> false
		);

		while (list($id) = each($report['rows']))
		{
			$row = &$report['rows'][$id];

			reset($rows_defaults);
			while (list($name, $value) = each($rows_defaults))
			{
				if (isset($row[$name]))
					continue;
				if (isset($report['params'][$name]))
					$value = $report['params'][$name];

				$row[$name] = $value;
			}
		}
	}

	private function set_session_params(&$report)
	{
		if ($report['type'] == "candles" || $report['type'] == "round")
			return;

		$full_path = $report['service_name']."_".$report['path'];
		$session_params = &$_SESSION['analytics']['graphs'];

		if (!isset($session_params[$full_path]))
			return;

		reset($report['graphs']);
		while (list($id) = each($report['graphs']))
		{
			$graph = &$report['graphs'][$id];

			if (!isset($session_params[$full_path]['legend_hide'][$id]))
				continue;

			$legend_hide = $session_params[$full_path]['legend_hide'][$id];

			array_splice($graph['legend_hide'], -1, false, $legend_hide);
			$graph['legend_hide'] = array_unique($graph['legend_hide']);
		}
	}

	private function add_gap(&$data, &$last, $cur)
	{
		if ($last === false)
		{
			$last = $cur;
			return;
		}
		if ($last === $cur)
			return;

		$date = new DateTime($last);

		while ($last != $cur)
		{
			$date->modify("+1 day");
			$last = $date->format("Y-m-d");

			$data[$last] = array('date' => $date->format("Y-m-d\T00:00:00+00:00"));
		}
	}

	private function get_clear_date($data)
	{
		$date = date("Y-m-t");
		$time = strtotime($date);

		while (list(, $chart) = each($data))
		{
			while (list(, $values) = each($chart))
			{
				$current = strtotime($values['date']);

				if ($time > $current)
				{
					$time = $current;
					$date = $values['date'];
				}
			}
		}

		return $date;
	}

	private function live_stream_simple($data)
	{
		$time = time();
		$result = $this->DB->get_filtered_cache($data['service_id'], $data['report'], $data['chart'], $data['type'], $time, $time);
		$row = $result->fetch_row();

		if (!isset($row[0]))
			return;

		$this->live_stream_event($data, $row[0]);
	}

	private function live_stream_avg($data)
	{
		$start = strtotime($data['time']);
		$end = $start + $data['period'];
		$value = 0;

		$result = $this->DB->get_filtered_cache($data['service_id'], $data['report'], $data['chart'], $data['type'], $start, $end);

		if ($result->num_rows() == 0)
			return;

		while ($row = $result->fetch())
		{
			$value += $row['value'];
		}

		$value /= $result->num_rows();

		$this->live_stream_event($data, $value);
	}

	private function live_stream_sum($data)
	{
		$start = strtotime($data['time']);
		$end = $start + $data['period'];
		$value = 0;

		$result = $this->DB->get_filtered_cache($data['service_id'], $data['report'], $data['chart'], $data['type'], $start, $end);

		if ($result->num_rows() == 0)
			return;

		while ($row = $result->fetch())
		{
			$value += $row['value'];
		}

		$this->live_stream_event($data, $value);
	}

	private function live_stream_event($data, $current_check_value)
	{
		$current_check_data = array();
		$current_check_time = date("Y-m-d H:i:s", strtotime($data['time']) + $data['period']);
		$current_check_data[] = "`time` = '$current_check_time'";
		$current_check_data[] = "`value` = '$current_check_value'";
		$this->DB->live_stream_update_data(implode(", ", $current_check_data), $data['id']);
		$check_diff = false;
		$diff = $current_check_value - $data['value'];

		switch ($data['direction'])
		{
			case 'UP':
				$check_diff = $diff > 0 ? ((($diff / $data['value'] * 100) >= $data['compare_range']) ? true : false) : false;
				break;
			case 'DOWN':
				$check_diff = $diff < 0 ? ((abs($diff / $data['value'] * 100) >= $data['compare_range']) ? true : false) : false;
				break;
			case 'UP_AND_DOWN':
				$check_diff = $diff != 0 ? ((abs($diff / $data['value'] * 100) >= $data['compare_range']) ? true : false) : false;
				break;
		}

		if ($check_diff)
			$this->DB->add_live_stream_event($data['id'], $data['time'], $data['value'], $current_check_time, $current_check_value);
	}
}

/**
 * Набор виджетов с данными за период на главной странице отчёта
 * и на страницах конкретных отчётов
 *
 * @uses Iterator
 */
class IndicatorsSet implements Iterator
{
	/**
	 * @var array Список отчётов для набора виджетов по умолчанию
	 */
	private $defaults = array(
		'service' => array(
			"counters_dau",
			"counters_mau",
			"finance_arpu",
			"finance_arppu",
			"payments_all",
			"finance_revenue"
		),
		'report' => array(
			"counters_dau",
			"counters_mau",
			"finance_arpu",
			"finance_arppu"
		)
	);

	/**
	 * @var string Тип виджета: для главной страницы проекта - service,
	 * для страницы конкретного отчёта проекта - report
	 */
	private $type;

	/**
	 * @var string Имя проекта для виджета
	 */
	private $service;

	/**
	 * @var int Порядковый номер объекта в Iterator
	 */
	private $position = 0;

	/**
	 * @var ObjectAdmin Объект панели администратора системы,
	 * нужен для проверки прав доступа к отчётам для виджетов
	 */
	private $admin;

	/**
	 * Конструктор
	 *
	 * @param string $type Тип виджета: для главной страницы проекта - service,
	 * для страницы конкретного отчёта проекта - report
	 * @param string $service Имя проекта для виджета
	 * @param ObjectAdmin $admin Объект панели администратора системы,
	 * нужен для проверки прав доступа к отчётам для виджетов
	 */
	private function __construct($type, $service, ObjectAdmin $admin)
	{
		$this->type = $type;
		$this->service = $service;
		$this->admin = $admin;
	}

	/**
	 * Получить набор виджетов для главной страницы проекта
	 * или для страницы конкретного отчёта
	 *
	 * @staticvar IndicatorsSet[] $sets Массив наборов виджетов
	 * @param string $type Тип виджета: для главной страницы проекта - service,
	 * для страницы конкретного отчёта проекта - report
	 * @param string $service Имя проекта для виджета
	 * @param ObjectAdmin $admin
	 * @return IndicatorsSet Набор виджетов с данными за месяц на главной странице отчёта
	 * и на страницах конкретных отчётов
	 */
	public static function getSets($type, $service, ObjectAdmin $admin)
	{
		static $sets = array();

		if (empty($sets) || !isset($sets[$type.$service]))
			$sets[$type.$service] = new self($type, $service, $admin);

		return $sets[$type.$service];
	}

	/**
	 * Сохраняет объект виджета в хранилище виджетов IndicatorsStorage,
	 * если виджет уже есть в хранилище, сохраняется его состояние
	 *
	 * @param string $report Имя отчёта для виджета
	 * @param int $graph Идентификатор графика отчёта для виджета
	 * @param int $legend Идентификатор кривой отчёта для виджета
	 * @param int $order Номер места (порядок), на котором выводится виджет
	 */
	public function save($report, $graph, $legend, $order = 0)
	{
		$item = new Indicator($this->type, $this->service, $report, $graph, $legend, $order);
		$index = $this->get_storage()->get_index($this->type.$this->service, $item);
		if ($index !== false)
			$this->change_order($item, $index);
		else
			$this->get_storage()->save($this->type.$this->service, $item);
	}

	/**
	 * Удалить виджет из хранилища виджетов IndicatorsStorage
	 *
	 * @param string $report Имя отчёта для виджета
	 * @param int $graph Идентификатор графика отчёта для виджета
	 * @param int $legend Идентификатор кривой отчёта для виджета
	 * @return null Возврат, если виджета нет в наборе
	 */
	public function remove($report, $graph, $legend)
	{
		$item = new Indicator($this->type, $this->service, $report, $graph, $legend);
		$index = $this->get_storage()->get_index($this->type.$this->service, $item);
		if ($index === false)
			return;

		$this->get_storage()->remove($this->type.$this->service, $index);
	}

	/**
	 * Получить объект хранилища виджетов
	 *
	 * @return IndicatorsStorage Объект хранилища виджетов
	 */
	public function get_storage()
	{
		return IndicatorsStorage::instance();
	}

	/**
	 * Реализация метода из Iterator
	 */
	public function next()
	{
		++$this->position;
	}

	/**
	 * Реализация метода из Iterator
	 */
	public function valid()
	{
		if ($this->get_storage()->is_empty($this->type.$this->service))
			$this->set_defaults();

		return $this->get_storage()->key_exists($this->type.$this->service, $this->position);
	}

	/**
	 * Реализация метода из Iterator
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this->get_storage()->get_item($this->type.$this->service, $this->position);
	}

	/**
	 * Реализация метода из Iterator
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * Устанавливает набор виджетов по умолчанию
	 *
	 * @return null Возврат, если нет набора отчётов данного типа
	 */
	private function set_defaults()
	{
		if (!isset($this->defaults[$this->type]))
			return;

		while (list($key, $val) = each($this->defaults[$this->type]))
		{
			list($category, $report_name) = explode("_", $val, 2);
			if (!$this->admin->check_access("analytics_".$this->service."_".$category."_".str_replace("_", "&#x005f;", $report_name)) && !$this->admin->check_access("analytics_".$this->service."_all"))
				continue;

			$this->save($val, 0, 0, $key);
		}
	}

	/**
	 * Меняет порядок вывода виджетов
	 *
	 * @param Indicator $new Объект виджета с новым порядковым номером
	 * @param int $index Индекс массива набора виджетов в хранилище виджетов IndicatorsStorage
	 * @return null Возврат, если виджет отсутствует в наборе
	 * или если виджет остаётся на том же месте
	 */
	private function change_order(Indicator $new, $index)
	{
		$old = $this->get_storage()->get_item($this->type.$this->service, $index);
		if ($old === false)
			return;

		if ($old->get_order() == $new->get_order())
			return;

		$data = $this->get_storage()->get_all($this->type.$this->service);
		$count = count($data);
		unset($data[$old->get_order()]);
		$data = array_values($data);
		$result = array();
		$j = 0;
		for ($i = 0; $i < $count; $i++)
		{
			if ($i == $new->get_order())
			{
				$result[$i] = $new;
				continue;
			}

			$data[$j]->set_order($i);
			$result[$i] = $data[$j];
			$j++;
		}

		$this->get_storage()->save_all($this->type.$this->service, $result);
	}

	/**
	 * Запрет на прямое создание объекта
	 */
	private function __clone() { }

	/**
	 * Запрет на прямое создание объекта
	 */
	private function __wakeup() { }
}

class Indicator
{
	private $type;
	private $service;
	private $report;
	private $graph;
	private $legend;
	private $order;

	public function __construct($type, $service, $report, $graph, $legend, $order = 0)
	{
		$this->type = $type;
		$this->service = $service;
		$this->report = $report;
		$this->graph = intval($graph);
		$this->legend = intval($legend);
		$this->order = intval($order);
	}

	public function get_type()
	{
		return $this->type;
	}

	public function get_service()
	{
		return $this->service;
	}

	public function get_report()
	{
		return $this->report;
	}

	public function get_graph()
	{
		return $this->graph;
	}

	public function get_legend()
	{
		return $this->legend;
	}

	public function get_order()
	{
		return $this->order;
	}

	public function set_order($order)
	{
		$this->order = $order;
	}

	public function is_equal(Indicator $item)
	{
		if ($this->get_type() !== $item->get_type())
			return false;
		if ($this->get_service() !== $item->get_service())
			return false;
		if ($this->get_report() !== $item->get_report())
			return false;
		if ($this->get_graph() !== $item->get_graph())
			return false;
		if ($this->get_legend() !== $item->get_legend())
			return false;

		return true;
	}
}

class IndicatorsStorage
{
	private $data = array();

	private function __construct()
	{
		if (isset($_SESSION['analytics']['indicators']))
			$this->data = unserialize($_SESSION['analytics']['indicators']);
	}

	public static function instance()
	{
		static $instance = null;
		if (null === $instance)
			$instance = new static();

		return $instance;
	}

	public function save($key, Indicator $val)
	{
		if (!isset($this->data[$key]))
			$this->data[$key] = array();

		while (list($index, $value) = each($this->data[$key]))
		{
			$item = $value;

			if ($item->is_equal($val))
			{
				$this->data[$key][$index] = $val;
				$this->save_to_session();

				return;
			}
		}

		$val->set_order(count($this->data[$key]));
		$this->data[$key][] = $val;
		$this->save_to_session();
	}

	public function remove($key, $index)
	{
		unset($this->data[$key][$index]);
		$this->data[$key] = array_values($this->data[$key]);
		$this->save_to_session();
	}

	public function get_item($key, $index)
	{
		if (!$this->key_exists($key, $index))
			return false;

		return $this->data[$key][$index];
	}

	public function get_all($key)
	{
		return $this->data[$key];
	}

	public function save_all($key, $items)
	{
		$this->data[$key] = $items;
		$this->save_to_session();
	}

	public function key_exists($key, $index)
	{
		return isset($this->data[$key][$index]);
	}

	public function is_empty($key)
	{
		return empty($this->data[$key]);
	}

	public function get_index($key, Indicator $item)
	{
		if ($this->is_empty($key))
			return false;

		while(list($index, $current) = each($this->data[$key]))
		{
			if ($current->is_equal($item))
				return $index;
		}

		return false;
	}

	private function save_to_session()
	{
		$_SESSION['analytics']['indicators'] = serialize($this->data);
	}

	private function __clone() { }

	private function __wakeup() { }
}

class Trend
{
	private $image;
	private $size;
	private $data = array();

	public function __construct($width, $height)
	{
		$this->image = imagecreatetruecolor($width, $height);
		$this->size = array('width' => $width, 'height' => $height - 2);

		$back = imagecolorallocate($this->image, 0, 0, 0);
		imagecolortransparent($this->image, $back);
	}

	public function add_row($value)
	{
		$this->data[] = $value;
	}

	public function draw($color = "DFEFF7")
	{
		if (count($this->data) < 2)
		{
			ob_start();
			imagepng($this->image);

			return base64_encode(ob_get_clean());
		}

		if (strlen($color) !== 6)
			exit("Wrong color length");

		$color = imagecolorallocate($this->image, hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));

		$min = min($this->data);
		$max = max($this->data);

		$spaces = $this->size['width'] / (count($this->data) - 1);
		$points = array();
		$vertical = $this->size['height'] / 100 * 70;
		$top_whitespace = $this->size['height'] - $vertical - 5;

		while (list($key, $value) = each($this->data))
			$points[] = array('x' => $key * $spaces, 'y' => $vertical - ($value - $min) / ($max - $min) * $vertical + $top_whitespace);

		$last_point = false;
		imagesetthickness($this->image, 5);

		while (list(, $point) = each($points))
		{
			if ($last_point !== false)
				imageline($this->image, $last_point['x'], $last_point['y'], $point['x'], $point['y'], $color);

			$last_point = $point;
		}

		$background = imagecolorallocate($this->image, 249, 252, 254);
		imagefilltoborder($this->image, 0, 100, $color, $background);

		ob_start();
		imagepng($this->image);

		return base64_encode(ob_get_clean());
	}
}

?>