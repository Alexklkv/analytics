<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Модуль управления тегами графиков
 *
 * @uses ComponentAdmin
 * @uses ObjectAnalytics
 * @uses ObjectEasyForms
 * @uses ObjectLabels
 * @uses ObjectTemplates
 * @uses ObjectXML
 *
 * @version 1.0.0
 */

class AdminLabels extends ComponentAdmin
{
	private $owner;

	public function __construct(&$copy = null)
	{
		parent::__construct($copy);

		$this->owner = &$copy;
	}

	public function initialize()
	{
		parent::initialize();

		$this->Templates->set_base_path("Панель администрирования/Теги");
		$this->Templates->set_template("Панель администрирования/Шаблоны/Страница");
		$this->Templates->title = "Теги";
	}

	public function get_name()
	{
		return "Теги";
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

		$accesses = array(
			'get'		=> array(),
			'add'		=> array(),
			'edit'		=> array(),
			'delete'	=> array()
		);

		reset($services);
		while (list($name) = each($services))
		{
			$access = strtoupper($name);

			$accesses[$name] = $access."_GET";
			$accesses['get'][] = $access."_GET";
			$accesses['add'][] = $access."_ADD";
			$accesses['edit'][] = $access."_EDIT";
			$accesses['delete'][] = $access."_DELETE";
		}

		return $accesses;
	}

	public function __call($method, $args)
	{
		$this->Templates->set_page("");
		$this->Templates->service = self::get_action();
	}

	public function on_get()
	{
		$fields = array(
			'service' => array('type' => INPUT_GET),
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			return;

		if (!$this->get_service($fields, "GET"))
			return;

		$this->Labels->get($fields['service']);
	}

	public function on_add()
	{
		$this->XML->start_answer();

		$fields = array(
			'service'	=> array('id'	=> "add_service"),
			'date'		=> array('id'	=> "add_date"),
			'value'		=> array('id'	=> "add_value")
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			return;

		if (!$this->get_service($fields, "ADD"))
			return;

		$this->Labels->add($fields);
	}

	public function on_edit()
	{
		$this->XML->start_answer();

		$fields = array(
			'service'	=> array(),
			'old_date'	=> array(),
			'date'		=> array('id'	=> "add_date"),
			'value'		=> array('id'	=> "add_value")
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			return;

		if (!$this->get_service($fields, "EDIT"))
			return;

		$this->Labels->edit($fields);
	}

	public function on_delete()
	{
		$this->XML->start_answer();

		$fields = array(
			'service'	=> array(),
			'date'		=> array()
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			return;

		if (!$this->get_service($fields, "DELETE"))
			return;

		$this->Labels->delete($fields);
	}

	private function get_service(&$fields, $action)
	{
		$service = $fields['service'];

		$services = $this->Analytics->get_services();

		if (!isset($services[$service]))
			return false;

		$access = strtoupper($service);
		if (!$this->owner->access_check($access."_".$action))
			return false;

		$fields['service'] = $services[$service]['id'];
		return true;
	}
}

?>