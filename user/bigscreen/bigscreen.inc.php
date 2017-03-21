<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Просмотр аналитики на большом экране
 *
 * @uses ComponentAdmin
 * @uses ObjectAdmin
 * @uses ObjectAnalytics
 * @uses ObjectEasyForms
 * @uses ObjectTemplates
 * @uses SimpleXMLElement
 */
class AdminBigscreen extends ComponentAdmin
{
	public function initialize()
	{
		parent::initialize();

		$this->Templates->set_base_path("Аналитика на большом экране");
		$this->Templates->set_template("Аналитика на большом экране/Шаблон");
	}

	public function get_name()
	{
		return "Большой экран";
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
		while (list($name) = each($services))
			$accesses[$name] = strtoupper($name);

		$accesses['big_screen'] = "INDEX";
		$accesses['big_screen_list'] = "INDEX";
		$accesses['big_screen_data'] = "INDEX";

		return $accesses;
	}

	public function __call($method, $args)
	{
		$this->Templates->set_page("Главная");
	}

	public function on_big_screen_list()
	{
		$fields = array(
			'service' => array('type' => INPUT_GET, 'require' => true)
		);

		$fields = $this->EasyForms->fields($fields);
		if ($fields === false)
			exit;
		if (!$this->Admin->check_access("bigscreen_".$fields['service']))
			exit;

		$xmlStr = file_get_contents(__DIR__.'/config.xml');
		$xmlList = new SimpleXMLElement($xmlStr);
		$list = $xmlList->xpath("/lists/list[@name='".$fields['service']."']/line");

		if (empty($list))
			return;

		$data = array();

		while (list(, $item) = each($list))
		{
			$attrs = array();
			$attributes = iterator_to_array($item->attributes());
			while (list($key, $val) = each($attributes))
			{
				$attrs[$key] = (string) $val;
			}

			$data[] = $attrs;
		}

		echo json_encode($data);
		exit;
	}

	public function on_big_screen_data()
	{
		$fields = array(
			'service_name' => array(),
			'service' => array(),
			'report' => array(),
			'chart' => array(),
			'type' => array()
		);

		$fields = $this->EasyForms->fields($fields, array('type' => INPUT_POST, 'require' => true));
		if ($fields === false)
			exit;

		if (!$this->Admin->check_access("bigscreen_".$fields['service_name']))
			exit;

		echo json_encode($this->Analytics->get_big_screen_data($fields['service'], $fields['report'], $fields['chart'], $fields['type'], 30));
		exit;
	}
}

?>