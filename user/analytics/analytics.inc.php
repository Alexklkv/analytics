<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Модуль просмотра аналитики
 *
 * @uses ComponentAdmin
 * @uses ObjectAdmin
 * @uses ObjectAnalytics
 * @uses ObjectEasyForms
 * @uses ObjectLog
 * @uses ObjectTemplates
 *
 * @version 1.0.0
 */
class AdminAnalytics extends ComponentAdmin
{
	private $report;
	private $service;

	private $templates;

	public function initialize()
	{
		parent::initialize();

		$this->Templates->set_base_path("Аналитика");
		$this->Templates->set_template("Аналитика/Шаблоны/Страница");
	}

	public function get_name()
	{
		return "Аналитика";
	}

	public function get_services()
	{
		$services = $this->Analytics->get_services();

		$result = array();

		reset($services);
		while (list($name, $data) = each($services))
			$result[$name] = $data['title'];

		return $result;
	}

	/**
	 * Возвращает пользовательский массив прав доступа для каждого метода-обработчика,
	 * а также общие права на доступ к конкретному модулю
	 *
	 * @return array Массив прав доступа
	 */
	public function get_access_overrides()
	{
		$services = $this->Analytics->get_services();

		$accesses = array();

		reset($services);
		while (list(, $service) = each($services))
		{
			$accesses[$service['name']] = array(strtoupper($service['name']));
			$accesses[$service['name']][] = strtoupper($service['name']."_all");
			reset($service['reports']);
			while (list(, $reports) = each($service['reports']))
			{
				reset($reports);
				while (list(, $report) = each($reports))
				{
					if (!isset($report['path']))
						continue;

					list($category, $report_name) = explode('_', $report['path'], 2);
					$path = strtoupper($service['name']."_".$category."_".str_replace("_", "&#x005f;", $report_name));
					$accesses[$service['name']][] = $path;
				}
			}
		}

		$accesses['save_date'] = "ACCESS_PARAMS";
		$accesses['save_params'] = "INDEX";
		$accesses['load_one'] = "INDEX";
		$accesses['load_indicators'] = "ACCESS_PARAMS";
		$accesses['load_simple_indicators'] = "INDEX";
		$accesses['export'] = "ACCESS_EXPORT";
		$accesses['api_reports'] = "ACCESS_EVENTS";
		$accesses['api_reports_filter'] = "ACCESS_EVENTS";
		$accesses['edit_live_stream'] = "ACCESS_STREAM";
		$accesses['save_live_stream'] = "ACCESS_STREAM";
		$accesses['remove_live_stream'] = "ACCESS_STREAM";
		$accesses['live_stream_data_by_id'] = "ACCESS_STREAM";
		$accesses['get_live_stream'] = "INDEX";
		$accesses['col_edit'] = "ACCESS_ADVERT";
		$accesses['col_save'] = "ACCESS_ADVERT";
		$accesses['col_image'] = "INDEX";

		return $accesses;
	}

	public function __call($method, $args)
	{
		$this->load_report();

		if (!is_null($this->report) && !$this->Analytics->check_report_access($this->report['service_name'], $this->report['path']))
		{
			$this->Templates->set("/Панель администрирования/Шаблоны/Доступ запрещён");
			$this->Templates->accesses = strtoupper("analytics_".$this->report['service_name']."_all".", analytics_".$this->report['service_name']."_".$this->report['path']);
			return false;
		}
		$this->Templates->set_page("Главная");
		$this->Templates->title = $this->service['title'];
		$this->Templates->categories = $this->make_categories();
		$this->Templates->service = json_encode($this->service['name']);

		list($services, $reports) = $this->Analytics->make_lists();

		$this->Templates->services_list = json_encode($services);
		$this->Templates->reports_list = json_encode($reports);

		if ($this->report === null)
		{
			$this->Templates->add("Шаблоны/Общий отчёт");
			$date_begin = date("d.m.Y", mktime(0, 0, 0, date("m") - 2, 1, date("Y")));
			$date_end = date("d.m.Y", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
			$indicators = json_encode($this->Analytics->get_indicators($date_begin, $date_end, $this->service, "service"));
			$this->Templates->date_begin = $date_begin;
			$this->Templates->date_end = $date_end;
			$this->Templates->service_indicators = $indicators;
			$this->Templates->indicators_toolbar = "";
			if ($this->Admin->check_access("analytics_access_params"))
				$this->Templates->indicators_toolbar = $this->Templates->get_param("indicators_toolbar_param");
			$this->Templates->service_name = $this->service['name'];
			$this->Templates->stream = "";
			if ($this->Admin->check_access("analytics_access_stream"))
				$this->Templates->stream = $this->Templates->get_param("stream_block");

			return;
		}

		$this->Templates->add("Шаблоны/Отчёт");

		$this->Templates->date_begin = date("d.m.Y", $this->report['date_begin']);
		$this->Templates->date_end = date("d.m.Y", $this->report['date_end']);
		$this->Templates->report_title = $this->report['title'];
		$this->Templates->report_description = $this->report['description'];
		if ($this->Admin->check_access("analytics_access_export"))
			$this->Templates->export_item = $this->Templates->get_param("module_export_item");
		else
			$this->Templates->export_item = "";

		if ($this->Admin->check_access("analytics_access_params"))
			$this->Templates->calendar = $this->Templates->get_param("module_calendar");
		else
			$this->Templates->calendar = "";

		$this->Templates->report = json_encode($this->report['path']);
		$this->Templates->options = json_encode($this->load_options());
	}

	public function on_export()
	{
		$fields = array(
			'service'	=> array(),
			'filename'	=> array(),
			'type'		=> array(),
			'active_legend'	=> array('require' => false, 'array' => true),
			'reports'	=> array('require' => false, 'array' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		if ($fields['type'] == 0)
		{
			$this->load_report($fields['service']);
			$export = $this->Analytics->get_export_single($this->report, $fields);
		}
		else
		{
			if (isset($_SESSION['date_begin']))
				$date_begin = $_SESSION['date_begin'];
			else
				$date_begin = mktime(0, 0, 0, date("n") - 1);

			if (isset($_SESSION['date_end']))
				$date_end = $_SESSION['date_end'];
			else
				$date_end = time();

			$export = $this->Analytics->get_export_multiple($fields['reports'], array('service' => $fields['service'], 'date_begin' => $date_begin, 'date_end' => $date_end));
		}

		$this->print_headers("application/x-msexcel");
		header("Content-transfer-encoding: binary");
		header("Content-Disposition: attachment; filename=".$fields['filename'].".xls");
		echo $export->get_xls();
		exit;
	}

	public function on_load_one()
	{
		$fields = array(
			'service'	=> array('type' => INPUT_GET),
			'report'	=> array('type' => INPUT_GET),
			'graph'		=> array('type' => INPUT_GET, 'require' => false),
			'legend'	=> array('type' => INPUT_GET, 'require' => false),
			'connect'	=> array('type' => INPUT_GET, 'require' => false),
			'no_indicators'	=> array('type' => INPUT_GET, 'require' => false),
			'api_path_id'	=> array('type' => INPUT_GET, 'require' => false)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;
		if (!$this->Analytics->check_report_access($fields['service'], $fields['report']))
			exit;

		$this->load_report($fields['service']);

		if ($fields['api_path_id'])
		{
			$this->report['api_path_id'] = $fields['api_path_id'];
			$api_paths_ids = explode(",", $fields['api_path_id']);
			$legend = array();
			$negative = array();
			$show_sums = array();
			$value_append = "";
			$paths = $this->Analytics->get_filtered_api_paths($this->service['id'], $fields['api_path_id']);
			while (list(, $val) = each($api_paths_ids))
			{
				$legend[$val] = "Посетители (".$paths[$val].")";
				$legend[-$val] = "События(".$paths[$val].")";
				$negative[$val] = false;
				$negative[-$val] = false;
				$show_sums[$val] = false;
				$show_sums[-$val] = false;
				$value_append[$val] = "";
				$value_append[-$val] = "";
			}
			$this->report['graphs'][0]['legend'] = $legend;
			$this->report['graphs'][0]['negative'] = $negative;
			$this->report['graphs'][0]['show_sums'] = $show_sums;
			$this->report['graphs'][0]['value_append'] = $value_append;
		}

		$data = $this->Analytics->get_data($this->report);

		if ($this->report['type'] === "table")
		{
			$result = array(
				'report'	=> array(
					'title'		=> $this->report['title'],
					'path'		=> $this->report['path'],
					'description'	=> $this->report['description']
				),
				'type'		=> $this->report['type'],
				'data'		=> $data['data'],
				'rows'		=> $this->report['rows'],
				'legend'	=> $data['legend'],
				'show_legend'	=> $this->report['show_legend'],
				'legend_groups'	=> $data['legend_groups'],
				'connect'	=> array('target' => false, 'graph' => false),
				'filter'	=> $data['filter'],
				'editable'	=> $data['editable'],
				'show_image'	=> $data['show_image']
			);

			echo json_encode($result);
			exit;
		}

		$indicators = false;
		if ($fields['no_indicators'] === "0" && $this->report['type'] !== "monthly")
		{
			$date_begin = date("d.m.Y", $this->report['date_begin']);
			$date_end = date("d.m.Y", $this->report['date_end']);
			$indicators = $this->Analytics->get_indicators($date_begin, $date_end, $this->service, "report", $this->report['path']);
		}

		$result = array(
			'report'	=> array(
				'title'		=> $this->report['title'],
				'path'		=> $this->report['path'],
				'description'	=> $this->report['description']
			),
			'type'		=> $this->report['type'],
			'data'		=> array(),
			'graphs'	=> array(),
			'connect'	=> array('target' => $fields['connect'], 'graph' => $fields['graph']),
			'indicators'	=> $indicators
		);

		if ($fields['graph'] === false || $fields['graph'] === "" || $this->report['type'] == "candles")
		{
			$result['data'] = $data['data'];
			$result['graphs'] = $data['graphs'];
			$result['indicators'] = $indicators;

			echo json_encode($result);
			exit;
		}

		if (!isset($data['graphs'][$fields['graph']]) || !isset($data['data'][$fields['graph']]))
		{
			echo "No chart (chart data) {$fields['graph']} in {$this->report['path']}";
			exit;
		}

		$result['data'][$fields['graph']] = $data['data'][$fields['graph']];
		$result['graphs'][$fields['graph']] = $data['graphs'][$fields['graph']];
		$result['indicators'] = $indicators;

		echo json_encode($result);
		exit;
	}

	public function on_load_indicators()
	{
		$fields = array(
			'service'		=> array('type' => INPUT_GET),
			'date_begin'		=> array('type' => INPUT_GET),
			'date_end'		=> array('type' => INPUT_GET),
			'report'		=> array('type' => INPUT_GET, 'require' => false),
			'skip_report'	=> array('type' => INPUT_GET, 'require' => false),
			'type'			=> array('type' => INPUT_GET, 'require' => false)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$this->set_service($fields['service']);

		$type = $fields['type'] ? $fields['type'] : "service";

		echo json_encode($this->Analytics->get_indicators($fields['date_begin'], $fields['date_end'], $this->service, $type, $fields['skip_report']));
		exit;
	}

	public function on_save_date()
	{
		if (!$this->Admin->check_access("analytics_access_params"))
			exit;

		$fields = array(
			'date_begin'	=> array(),
			'date_end'	=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		$date_begin = explode(".", $fields['date_begin']);
		if (count($date_begin) == 3)
			$_SESSION['date_begin'] = mktime(0, 0, 0, $date_begin[1], $date_begin[0], $date_begin[2]);
		else
			unset($_SESSION['date_begin']);

		$date_end = explode(".", $fields['date_end']);
		if (count($date_end) == 3)
		{
			$date_end = mktime(0, 0, 0, $date_end[1], $date_end[0], $date_end[2]);
			if ($date_end != mktime(0, 0, 0))
			{
				$_SESSION['date_end'] = $date_end;
				exit;
			}
		}

		unset($_SESSION['date_end']);
		exit;
	}

	public function on_save_params()
	{
		$fields = array(
			'report'	=> array('require' => false),
			'value'		=> array('require' => false, 'array' => true),
			'type'		=> array(),
			'push'		=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;

		switch ($fields['type'])
		{
			case "show_average":
				$_SESSION['analytics']['global']['average'] = ($fields['push'] ? true : false);
				break;
			case "show_percent":
				$_SESSION['analytics']['global']['percent'] = ($fields['push'] ? true : false);
				break;
			case "show_difference":
				$_SESSION['analytics']['global']['difference'] = ($fields['push'] ? true : false);
				break;
			case "line_graphs":
				$_SESSION['analytics']['global']['line_graphs'] = ($fields['push'] ? true : false);
				break;
			case "simple_indicator_handler":
			case "indicator_handler":
				list($service, $category, $report, $graph, $legend) = $this->save_params_helper($fields['report'], $fields['value']);
				$type = ($fields['type'] == "simple_indicator_handler") ? "service" : "report";
				$indicators_set = $this->Analytics->get_indicators_set($type, $service);
				$order = isset($fields['value'][2]) ? $fields['value'][2] : 0;
				if ($fields['push'] == 1)
					$indicators_set->save($category."_".$report, $graph, $legend, $order);
				else
					$indicators_set->remove($category."_".$report, $graph, $legend);
				break;
			case "legend_hide":
				list($service, $category, $report, $graph, $type) = $this->save_params_helper($fields['report'], $fields['value']);

				if (!isset($_SESSION['analytics']['graphs'][$fields['report']]['legend_hide'][$graph]))
					$_SESSION['analytics']['graphs'][$fields['report']]['legend_hide'][$graph] = array();
				$legend_hide = &$_SESSION['analytics']['graphs'][$fields['report']]['legend_hide'][$graph];

				if ($fields['push'] == 1)
					$legend_hide[] = $type;
				else
				{
					$key = array_search($type, $legend_hide);
					if ($key !== false)
						unset($legend_hide[$key]);
				}

				$legend_hide = array_unique($legend_hide);
				break;
		}

		echo json_encode(array("success"));
		exit;
	}

	public function on_api_reports()
	{
		$fields = array(
			'service'	=> array('type' => INPUT_GET, 'require' => true),
			'filter'	=> array('require' => false),
			'pos_quantity'	=> array('require' => false),
			'current_page'	=> array('require' => false),
			'order'		=> array('require' => false)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$this->set_service($fields['service']);

		$date_begin = isset($_SESSION['date_begin']) ? $_SESSION['date_begin'] : mktime(0, 0, 0, date("n") - 1);
		$date_end = isset($_SESSION['date_end']) ? $_SESSION['date_end'] : time();

		$current_page = !$fields['current_page'] ? 1 : $fields['current_page'];

		$limit_end = $fields['pos_quantity']? $fields['pos_quantity'] : 100;
		$limit_start = ($current_page - 1) * $limit_end;
		$limit = $limit_start."-".$limit_end;

		if ($fields['order'])
			$order = $fields['order'];
		else
			$order = "visitors_avg-desc";

		$data = $this->Events->get_summary($this->service['id'], date("Y-m-d", $date_begin), date("Y-m-d", $date_end), $fields['filter'], $limit, $order);
		$pos_count = $this->Events->get_count($this->service['id'], date("Y-m-d", $date_begin), date("Y-m-d", $date_end), $fields['filter']);

		if ($fields['filter'] || $fields['pos_quantity'])
		{
			echo json_encode(array('data' => $data, 'pos_count' => $pos_count, 'current_page' => $current_page));
			exit;
		}

		$this->Templates->set_page("Главная");
		$this->Templates->title = $this->service['title'].": Отчет по событиям";
		$this->Templates->categories = $this->make_categories();
		$this->Templates->service = json_encode($this->service['name']);

		list($services, $reports) = $this->Analytics->make_lists();

		$this->Templates->services_list = json_encode($services);
		$this->Templates->reports_list = json_encode($reports);
		$this->Templates->pos_count = $pos_count;
		$this->Templates->current_page = $current_page;

		$this->Templates->add("Шаблоны/Отчёт по событиям");

		$this->Templates->date_begin = date("d.m.Y", $date_begin);
		$this->Templates->date_end = date("d.m.Y", $date_end);

		$api_row = $this->Templates->get_param("api_row");

		while (list( , $val) = each($data))
		{
			$api_row->path_id = $val['id'];
			$api_row->path = $val['path'];
			$api_row->visitors_avg = number_format($val['visitors_avg'], 0, "", " ");
			$api_row->visitors_sum = number_format($val['visitors_sum'], 0, "", " ");
			$api_row->hits_avg = number_format($val['hits_avg'], 0, "", " ");
			$api_row->hits_sum = number_format($val['hits_sum'], 0, "", " ");
			$this->Templates->api_rows .= (string) $api_row;
		}

		$this->Templates->options = json_encode($this->load_options());
	}

	public function on_api_reports_filter()
	{
		$fields = array(
			'filter'	=> array('require' => true),
			'service'	=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$this->set_service($fields['service']);

		echo json_encode(array('paths' => $this->Events->get_api_paths_filter($this->service['id'], $fields['filter'])));
		exit;
	}

	public function on_get_live_stream()
	{
		$fields = array(
			'service'	=> array('type' => INPUT_GET, 'require' => true),
			'end'		=> array('type' => INPUT_GET, 'require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$this->set_service($fields['service']);

		echo json_encode($this->Analytics->get_live_stream_events($this->service['id'], intval($fields['end'])));
		exit;
	}

	public function on_edit_live_stream()
	{
		$fields = array(
			'service'	=> array('type' => INPUT_GET, 'require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$this->set_service($fields['service']);
		$this->Templates->set_page("Главная");
		$this->Templates->add("Шаблоны/Управление лентой событий");
		$this->Templates->title = $this->service['title'] . ": Управление лентой событий";
		$this->Templates->categories = $this->make_categories();
		$this->Templates->service = json_encode($this->service['name']);
		$this->Templates->service_name = $this->service['name'];
		$this->Templates->service_id = $this->service['id'];

		list($services, $reports) = $this->Analytics->make_lists();

		$this->Templates->services_list = json_encode($services);
		$this->Templates->reports_list = json_encode($reports);

		$options = new stdClass();
		$options->is_subservient_section = true;
		$options->subservient_handler = "setEditLiveStreamEvents";

		$this->Templates->global_options = json_encode($options);

		$events = $this->Analytics->get_live_stream_data($this->service['id']);

		if (count($events) === 0)
		{
			$this->Templates->data_area = (string) $this->Templates->get_param("no_data");
			return;
		}

		$data_row = $this->Templates->get_param("data_row");

		while (list( , $val) = each($events))
		{
			$data_row->data_id = $val['id'];
			$data_row->name = $val['name'];
			$this->Templates->data_rows .= (string) $data_row;
		}

		$this->Templates->data_area = (string) $this->Templates->get_param("data_table");
	}

	public function on_save_live_stream()
	{
		$fields = array(
			'id'		=> array('require' => true),
			'service_id'	=> array('require' => true),
			'report'	=> array('require' => true),
			'chart'		=> array('require' => true),
			'type'		=> array('require' => true),
			'compare_type'	=> array('require' => true),
			'period'	=> array('require' => true),
			'direction'	=> array('require' => true),
			'compare_range'	=> array('require' => true),
			'name'		=> array('require' => true),
			'service_name'	=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
		{
			echo json_encode(array('error' => "1"));
			exit;
		}
		if (!$this->Admin->check_access("analytics_".$fields['service_name']))
		{
			echo json_encode(array('error' => "2"));
			exit;
		}

		unset($fields['service_name']);

		if ($fields['id'] > 0)
		{
			unset($fields['report']);
			unset($fields['chart']);
			unset($fields['type']);
			unset($fields['compare_type']);
			unset($fields['period']);
		}

		$id = $this->Analytics->save_live_stream_data($fields);

		echo json_encode(array('id' => $id));
		exit;
	}

	public function on_remove_live_stream()
	{
		$fields = array(
			'id'		=> array('require' => true),
			'service_name'	=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("analytics_".$fields['service_name']))
			exit;

		$this->Analytics->remove_live_stream_data($fields['id']);
		$this->Analytics->remove_live_stream_events($fields['id']);

		echo json_encode(array('success' => true));
		exit;
	}

	public function on_live_stream_data_by_id()
	{
		$fields = array(
			'id'		=> array('require' => true),
			'service_name'	=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);

		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("analytics_".$fields['service_name']))
			exit;

		$this->set_service($fields['service_name']);
		$data = $this->Analytics->get_live_stream_data($this->service['id'], $fields['id']);

		echo json_encode(isset($data[0]) ? $data[0] : null);
		exit;
	}

	public function on_col_edit()
	{
		$fields = array(
			'service'	=> array('require' => true),
			'report'	=> array('require' => true),
			'type'		=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);

		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$class = ucfirst($fields['service']);
		$method_name = $fields['report']."_editform";

		if (!method_exists($this->$class, $method_name))
			$this->Log->error("Method {$method_name} doesn't exist in {$class}");

		echo json_encode($this->$class->$method_name($fields['report'], $fields['type']));
		exit;
	}

	public function on_col_save()
	{
		$fields = array(
			'service'	=> array('require' => true),
			'report'	=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);

		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$class = ucfirst($fields['service']);
		$method_name = $fields['report']."_saveform";

		if (!method_exists($this->$class, $method_name))
			$this->Log->error("Method {$method_name} doesn't exist in {$class}");

		echo json_encode($this->$class->$method_name());
		exit;
	}

	public function on_col_image()
	{
		$fields = array(
			'service'	=> array('require' => true),
			'report'	=> array('require' => true),
			'type'		=> array('require' => true)
		);

		$fields = $this->EasyForms->fields($fields);

		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("analytics_".$fields['service']))
			exit;

		$class = ucfirst($fields['service']);
		$method_name = $fields['report']."_colimage";

		if (!method_exists($this->$class, $method_name))
			$this->Log->error("Method {$method_name} doesn't exist in {$class}");

		echo json_encode($this->$class->$method_name($fields['report'], $fields['type']));
		exit;
	}

	private function set_service($service_name)
	{
		$services = $this->Analytics->get_services();

		if (!isset($services[$service_name]))
			$this->Log->error("Service {$service_name} doesn't exist");
		$this->service = &$services[$service_name];
	}

	private function load_options()
	{
		$data = array(
			'show_average'		=> false,
			'show_percent'		=> false,
			'show_difference'	=> true,
			'line_graphs'		=> true
		);

		if (isset($_SESSION['analytics']['global']['average']))
			$data['show_average'] = $_SESSION['analytics']['global']['average'];
		if (isset($_SESSION['analytics']['global']['percent']))
			$data['show_percent'] = $_SESSION['analytics']['global']['percent'];
		if (isset($_SESSION['analytics']['global']['difference']))
			$data['show_difference'] = $_SESSION['analytics']['global']['difference'];
		if (isset($_SESSION['analytics']['global']['line_graphs']))
			$data['line_graphs'] = $_SESSION['analytics']['global']['line_graphs'];

		return $data;
	}

	private function make_categories()
	{
		$this->templates = $this->Templates->get("Шаблоны/Меню")->get_params(array("category", "report", "divider", "api_reports"));

		$category = $this->templates['category'];
		$report = $this->templates['report'];

		$report->module = self::get_module();
		$report->action = $this->service['name'];

		$categories = "";
		while (list($name, $title) = each($this->service['categories']))
		{
			if (!isset($this->service['reports'][$name]))
				$this->Log->error("Category {$name} has no reports");

			$reports = $this->make_reports($name, $this->service['reports'][$name]);
			if ($reports === "")
				continue;

			$category->title = $title;
			$category->reports = $reports;

			$categories .= (string) $category;
		}

		if ($this->Admin->check_access("analytics_access_events"))
		{
			$this->templates['api_reports']->service = $this->service['name'];
			$categories .= (string) $this->templates['api_reports'];
		}

		return $categories;
	}

	private function make_reports($category, $reports)
	{
		$report = $this->templates['report'];
		$divider = $this->templates['divider'];

		$list = "";

		reset($reports);
		while (list($name, $data) = each($reports))
		{
			if ($data === "-" && $list !== "")
			{
				$list .= (string) $divider;
				continue;
			}

			if ($data === "-" || $data['hidden'] === true)
				continue;
			if (!$this->Analytics->check_report_access($this->service['name'], $data['path']))
				continue;
			if (!isset($data['title']))
				$data['title'] = "";
			if (!isset($data['description']))
				$data['description'] = "";

			$name = $category."_".$name;

			$report->name = $name;
			$report->title = $data['title'];
			$report->description = $data['description'];
			$report->active = ($this->report !== null && $name === $this->report['path']);

			$list .= (string) $report;
		}

		return preg_replace('/('. preg_quote($divider, '/') . ')+$/', "", $list);
	}

	private function load_report($action = false)
	{
		if ($action === false)
			$action = self::get_action();

		$this->set_service($action);

		$path = $this->EasyForms->field("report", INPUT_GET);
		if ($path === false)
			return;

		list($category, $name) = explode("_", $path, 2);

		if (!isset($this->service['reports'][$category][$name]))
		{
			$this->Log->warning("Report {$this->service['name']}::{$path} doesn't exist");
			exit;
		}
		if ($this->service['reports'][$category][$name] === "-")
		{
			$this->Log->warning("Report {$this->service['name']}::{$path} doesn't exist");
			exit;
		}

		$this->report = &$this->service['reports'][$category][$name];

		if (isset($_SESSION['date_begin']))
			$this->report['date_begin'] = $_SESSION['date_begin'];
		else
			$this->report['date_begin'] = mktime(0, 0, 0, date("n") - 1);

		if (isset($_SESSION['date_end']))
			$this->report['date_end'] = $_SESSION['date_end'];
		else
			$this->report['date_end'] = time();
	}

	private function save_params_helper($report_path, $value)
	{
		list($service, $category, $report) = explode("_", $report_path, 3);

		$this->set_service($service);

		$reports = $this->service['reports'];
		if (!isset($reports[$category][$report]))
		{
			echo "No such report {$service}::{$category}::{$report}";
			exit;
		}

		list($graph, $type) = $value;
		if (!isset($reports[$category][$report]['graphs'][$graph]))
		{
			echo "No such graph {$service}::{$category}::{$report}::{$graph}";
			exit;
		}
		if ($category == "apipath")
		{
			$paths = $this->Analytics->get_filtered_api_paths($this->service['id'], abs($type));
			$reports[$category][$report]['graphs'][$graph]['legend'][$type] = $type > 0 ? "Посетители (".$paths[abs($type)].")" : "События(".$paths[abs($type)].")";
		}
		if (!isset($reports[$category][$report]['graphs'][$graph]['legend'][$type]) && $type != "sumline")
		{
			echo "No such type (legend) {$service}::{$category}::{$report}::{$graph}::{$type}";
			exit;
		}

		return array($service, $category, $report, $graph, $type);
	}
}

?>