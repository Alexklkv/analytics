<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

class AdminUreports extends ComponentAdmin
{
	public function initialize()
	{
		parent::initialize();

		$this->Templates->set_base_path("Мои отчёты");
		$this->Templates->set_template("Мои отчёты/Шаблоны/Страница");
	}

	public function get_name()
	{
		return "Мои отчёты";
	}

	public function get_services()
	{
		return array(
			'create'	=> "Создать",
			'list'		=> "Список отчётов"
		);
	}

	/**
	 * Возвращает пользовательский массив прав доступа для каждого метода-обработчика,
	 * а также общие права на доступ к конкретному модулю
	 *
	 * @return array Массив прав доступа
	 */
	public function get_access_overrides()
	{
		return array(
			'create'		=> "CREATE",
			'get_raw_metric_id'	=> "CREATE",
			'add_user_metric'	=> "CREATE",
			'save'			=> "CREATE",
			'get_umetrics_matched'	=> "CREATE",
			'remove_report'		=> "LIST",
			'list'			=> "LIST",
			'download'		=> "LIST",

		);
	}

	public function on_create()
	{
		$this->Templates->set_page("");
		$this->Templates->title = "Создать пользовательский отчёт";
		$module = $this->Templates->get_param("create");
		$tmp_date = new DateTime($this->Ureports->get_last_period_date());
		$module->init_date = $tmp_date->format("d.m.Y");
		$module->id = 0;
		list($services, $reports) = $this->Analytics->make_lists();
		$module->services_list = json_encode($services);
		$module->reports_list = json_encode($reports);
		$this->Templates->module = $module;
	}

	public function on_list()
	{
		$this->Templates->set_page("");
		$this->Templates->title = "Просмотр и редактирование пользовательских отчётов";
		$ureports = $this->Ureports->get_list();
		if (count($ureports) == 0)
			$this->Templates->module = $this->Templates->get_param("empty");
		else
		{
			$rows = "";
			$row = $this->Templates->get_param("ureports_table_row");
			reset($ureports);
			while (list(, $report) = each($ureports))
			{
				$row->init_date = $report['init_date'];
				$row->name = $report['name'];
				$row->id = $report['id'];
				$rows .= (string) $row;
			}
			$ureports_table = $this->Templates->get_param("ureports_table");
			$ureports_table->rows = $rows;
			$this->Templates->module = $ureports_table;
		}
	}

	public function on_download()
	{
		$fields = array(
			'id'	=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$this->print_headers("application/x-msexcel");
		header("Content-transfer-encoding: binary");
		header("Content-Disposition: attachment; filename=report.xls");
		echo $this->Ureports->get_xls(intval($fields['id']));
		exit;
	}

	public function on_save()
	{
		$fields = array(
			'id'		=> array(),
			'init_date'	=> array(),
			'name'		=> array(),
			'description'	=> array(),
			'umetrics'	=> array('array' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$fields['init_date'] = implode("-", array_reverse(explode(".", $fields['init_date'])));

		$this->Ureports->save($fields);

		echo json_encode(array('success' => 1));
		exit;
	}

	public function on_get_raw_metric_id()
	{
		$fields = array(
			'service'	=> array(),
			'report'	=> array(),
			'chart'		=> array(),
			'type'		=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$fields['service'] = $this->Ureports->get_service_id($fields['service']);
		$metric = new stdClass();
		reset($fields);
		while (list($key, $val) = each($fields))
			$metric->$key = $val;

		$metric_id = $this->Ureports->get_raw_metric_id($metric);

		echo json_encode(array('metric_id' => $metric_id));
		exit;
	}

	public function on_add_user_metric()
	{
		$fields = array(
			'name'		=> array(),
			'formula'	=> array(),
			'description'	=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$umetric_id = $this->Ureports->add_user_metric($fields);

		echo json_encode(array('umetric_id' => $umetric_id));
		exit;
	}

	public function on_remove_report()
	{
		$fields = array(
			'id' => array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$this->Ureports->remove_report(intval($fields['id']));

		echo json_encode(array('success' => 1));
		exit;
	}

	public function on_get_umetrics_matched()
	{
		$fields = array(
			'query' => array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$data = $this->Ureports->get_umetrics_matched($fields['query']);

		echo json_encode($data);
		exit;
	}
}

?>