<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

class ObjectUreports extends Object implements DatabaseInterface
{
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
			'get_list'			=> "SELECT `id`, `init_date`, `name`, `description` FROM `@pureport_config` WHERE `users_id` = @i",
			'get_data'			=> "SELECT uc.`init_date`, uc.`name` AS `uc_name`, uf.`id`, uf.`formula`, uf.`name`, ud.`period_init`, ud.`value` FROM `@pureport_formula` AS uf INNER JOIN `@pureport_rel` AS ur ON ur.`urf_id` = uf.`id` INNER JOIN `@pureport_config` AS uc ON uc.`id` = ur.`urc_id` LEFT JOIN `@pureport_data` AS ud ON ud.`urf_id` = uf.`id` WHERE ur.`urc_id` = @i ORDER BY uf.`id`, ud.`period_init` ASC",
			'save_data'			=> "REPLACE INTO `@pureport_data` SET `urf_id` = @i, `period_init` = @s, `value` = @f",
			'get_raw_metric'		=> "SELECT `key` FROM `@pureport_metrics` WHERE `id` = @i",
			'get_avg'			=> "SELECT AVG(`value`) FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `date` >= @s AND `date` <= @s AND `chart` = @i AND `type` = @i",
			'get_sum'			=> "SELECT SUM(`value`) FROM `@pcache` WHERE `service` = @i AND `report` = @s AND `date` >= @s AND `date` <= @s AND `chart` = @i AND `type` = @i",
			'get_config'			=> "SELECT `init_date`, `name` FROM `@pureport_config` WHERE `id` = @i AND `users_id` = @i",
			'add_report_conf'		=> "INSERT INTO `@pureport_config` SET @t",
			'update_report_conf'		=> "UPDATE `@pureport_config` SET @t WHERE `id` = @i",
			'get_service_id'		=> "SELECT `id` FROM `@pservices` WHERE `name` = @s",
			'get_raw_metric_id'		=> "SELECT `id` FROM `@pureport_metrics` WHERE `key` = @s",
			'add_raw_metric'		=> "INSERT INTO `@pureport_metrics` SET `key` = @s, `name` = @s",
			'get_umetric_by_formula'	=> "SELECT * FROM `@pureport_formula` WHERE `formula` = @s",
			'add_umetric'			=> "INSERT INTO `@pureport_formula` SET `formula` = @s, `name` = @s, `description` = @s",
			'add_formula_rel'		=> "REPLACE INTO `@pureport_rel` SET `urc_id` = @i, `urf_id` = @i",
			'delete_conf_by_id'		=> "DELETE FROM `@pureport_config` WHERE `id` = @i",
			'delete_data_by_report'		=> "DELETE FROM `@pureport_data` WHERE `urf_id` IN(SELECT `urf_id` FROM `@pureport_rel` WHERE `urc_id` = @i)",
			'delete_rel_by_report'		=> "DELETE FROM `@pureport_rel` WHERE `urc_id` = @i",
			'get_matched_formulas'		=> "SELECT `id`, `name`, `description` FROM `@pureport_formula` WHERE `name` LIKE '@t' OR `description` LIKE '@t' ORDER BY `name` LIMIT 0, 10"
		);
	}

	public function get_list()
	{
		$ret = array();
		$result = $this->DB->get_list(intval($_SESSION['id']));
		while ($row = $result->fetch())
			$ret[] = $row;

		return $ret;
	}

	public function save($data)
	{
		if ($data['id'] == 0)
			$this->add_report ($data);
		else if ($data['id'] > 0)
			$this->update_report($data);
	}

	public function get_xls($id)
	{
		$this->update_metrics($id);
		$export = $this->Export->set_defaults("Тест");
		$export->has_formulas = false;
		$cols = $this->get_cols($id);
		if ($cols === false)
			$this->Log->error("Users report not found");

		reset($cols);
		while (list($i, $col) = each($cols))
		{
			if ($i == 0)
				$export->add_column(0, $col, $col, 'text');
			else
				$export->add_column(0, $col, $col);
		}

		$result = $this->DB->get_data($id);
		while ($row = $result->fetch())
		{
			if (!isset($data[$row['id']]))
				$data[$row['id']] = array();

			if (!isset($data[$row['id']][$row['uc_name']]))
				$data[$row['id']][$row['uc_name']] = $row['name'];

			$date_label = $this->get_date_label($row['period_init']);

			$data[$row['id']][$date_label] = $row['value'];
		}

		while (list(, $row) = each($data))
		{
			$export->add_data_row(0, $row);
		}

		return $export->get_xls();
	}

	public function get_sum($metric_id, $init_date)
	{
		$data = $this->DB->get_raw_metric($metric_id)->fetch_row();
		$metric_fields = json_decode($data[0]);
		$date_tmp = new DateTime($init_date);
		$date_tmp->add(new DateInterval("P7D"));
		$end_date = $date_tmp->format("Y-m-d");
		$result = $this->DB->get_sum($metric_fields->service, $metric_fields->report, $init_date, $end_date, $metric_fields->chart, $metric_fields->type);

		if ($result->num_rows() == 0)
			return 0;

		$data = $result->fetch_row();

		return $data[0];
	}

	public function get_avg($metric_id, $init_date)
	{
		$data = $this->DB->get_raw_metric($metric_id)->fetch_row();
		$metric_fields = json_decode($data[0]);
		$date_tmp = new DateTime($init_date);
		$date_tmp->add(new DateInterval("P7D"));
		$end_date = $date_tmp->format("Y-m-d");
		$result = $this->DB->get_avg($metric_fields->service, $metric_fields->report, $init_date, $end_date, $metric_fields->chart, $metric_fields->type);

		if ($result->num_rows() == 0)
			return 0;

		$data = $result->fetch_row();

		return $data[0];
	}

	public function get_last_period_date()
	{
		$date = new DateTime();
		$days = $date->format("N") + 6;
		$date->sub(new DateInterval("P".$days."D"));

		return $date->format("Y-m-d");
	}

	public function get_service_id($name)
	{
		$result = $this->DB->get_service_id($name);
		if ($result->num_rows() == 0)
			return 0;

		$data = $result->fetch_row();

		return $data[0];
	}

	public function get_raw_metric_id($metric)
	{
		$key = json_encode($metric);

		$result = $this->DB->get_raw_metric_id($key);
		if ($result->num_rows() > 0)
			return "m".$result->fetch_row()[0];

		$id = $this->add_raw_metric($metric);

		return "m".$id;
	}

	public function add_user_metric($data)
	{
		$result = $this->DB->get_umetric_by_formula($data['formula']);

		if ($result->num_rows() > 0)
		{
			$ret = $result->fetch();

			return $ret['id'];
		}

		$this->DB->add_umetric($data['formula'], $data['name'], $data['description']);

		return $this->DB->insert_id;
	}

	public function remove_report($id)
	{
		$this->DB->delete_conf_by_id($id);
		$this->DB->delete_data_by_report($id);
		$this->DB->delete_rel_by_report($id);
	}

	public function get_umetrics_matched($query)
	{
		$data = array(
			'options' => array()
		);

		$result = $this->DB->get_matched_formulas("%{$query}%", "%{$query}%");
		while ($row = $result->fetch())
		{
			$data['options'][] = $row['name'];
		}

		return $data;
	}

	private function add_report($data)
	{
		$users_id = intval($_SESSION['id']);
		$list = "`users_id` = {$users_id}, `init_date` = '{$data['init_date']}', `name` = '{$data['name']}', `description` = '{$data['description']}'";
		$this->DB->add_report_conf($list);
		$urc_id = intval($this->DB->insert_id);
		reset($data['umetrics']);
		while (list(, $urf_id) = each($data['umetrics']))
		{
			$this->DB->add_formula_rel($urc_id, intval($urf_id));
		}
	}

	private function update_report($data)
	{
		$users_id = intval($_SESSION['id']);
		$list = "`users_id` = {$users_id}, `init_date` = '{$data['init_date']}', `name` = '{$data['name']}', `description` = '{$data['description']}'";
		$this->DB->update_report_conf($list, intval($data['id']));
	}

	private function update_metrics($id)
	{
		$current = $this->get_last_period_date();
		$result = $this->DB->get_data($id);
		$last = false;
		while ($row = $result->fetch())
		{
			if (is_null($row['period_init']))
				$this->update_user_metric($id, $row['id'], $row['formula'], $row['init_date']);

			$last = $row;
		}

		if ($last !== false && $current > $last['period_init'])
				$this->update_user_metric($id, $last['id'], $last['formula'], $last['period_init']);
	}

	private function update_user_metric($urc_id, $urf_id, $formula, $date)
	{
		$current = $this->get_last_period_date();
		while ($date <= $current)
		{
			$result = $this->Aninterpreter->evaluate($formula, $date);
			$this->DB->save_data($urf_id, $date, $result);
			$tmp_date = new DateTime($date);
			$tmp_date->add(new DateInterval("P7D"));
			$date = $tmp_date->format("Y-m-d");
		}
	}

	private function get_cols($id)
	{
		$result = $this->DB->get_config($id, $_SESSION['id']);

		if ($result->num_rows() == 0)
			return false;

		list($date, $name) = $result->fetch_row();
		$date_labels = $this->prepare_date_labels($date);
		$ret = array_merge(array($name), $date_labels);

		return $ret;
	}

	private function prepare_date_labels($init_date)
	{
		$date_labels = array();
		$current = $this->get_last_period_date();
		while ($init_date <= $current)
		{
			$label = $this->get_date_label($init_date);
			$tmp_date = new DateTime($init_date);
			$date_labels[] = $label;
			$tmp_date->add(new DateInterval("P7D"));
			$init_date = $tmp_date->format("Y-m-d");
		}

		return $date_labels;
	}

	private function get_date_label($init_date)
	{
		$tmp_date = new DateTime($init_date);
		$label = $tmp_date->format("d.m.Y")." - ";
		$tmp_date->add(new DateInterval("P6D"));
		$label .= $tmp_date->format("d.m.Y");

		return $label;
	}

	private function add_raw_metric($metric)
	{
		$s_name = "";
		$r_name = "";
		$g_name = "";
		$l_name = "";

		$data = $this->Analytics->get_services();
		reset($data);
		while (list(, $serice) = each($data))
		{
			if ($serice['id'] != $metric->service)
				continue;

			$s_name = $serice['title'];
			list($m_category, $m_report) = explode("_", $metric->report, 2);

			while (list($category, $reports) = each($serice['reports']))
			{
				if ($m_category != $category)
					continue;

				while (list($key, $report) = each($reports))
				{
					if (!is_array($report))
						continue;
					if ($m_report != $key)
						continue;

					$r_name = $report['title'];
					$g_name = $report['graphs'][$metric->chart]['title'];
					$l_name = $report['graphs'][$metric->chart]['legend'][$metric->type];
				}
			}
		}

		$metric_key = json_encode($metric);
		$metric_name = $s_name.", ".$r_name.", ".$g_name.", ".$l_name;

		$result = $this->DB->add_raw_metric($metric_key, $metric_name);

		return $this->DB->insert_id;
	}
}

?>