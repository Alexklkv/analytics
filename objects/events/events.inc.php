<?php

/**
 * Предоставляет функции работы с клиентскими событиями
 *
 * @version 1.0.0
 */
class ObjectEvents extends Object implements DatabaseInterface
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
			'get_summary'			=> "SELECT p.`id`, p.`path`, ROUND(SUM(h.`visitors`) / COUNT(*)) as `visitors_avg`, SUM(h.`visitors`) as `visitors_sum`, ROUND(SUM(h.`hits`) / COUNT(*)) as `hits_avg`, SUM(h.`hits`) as `hits_sum` FROM `analytics_@t`.`@ppaths` as p INNER JOIN `analytics_@t`.`@phits_merged` as h ON p.`id` = h.`group_id` WHERE p.`path` NOT LIKE '%/%' AND h.`group_by` = 'path' AND h.`date` >= @s AND h.`date` <= @s GROUP BY `path` ORDER BY `@t` @t LIMIT @i, @i",
			'get_summary_filter'		=> "SELECT p.`id`, p.`path`, ROUND(SUM(h.`visitors`) / COUNT(*)) as `visitors_avg`, SUM(h.`visitors`) as `visitors_sum`, ROUND(SUM(h.`hits`) / COUNT(*)) as `hits_avg`, SUM(h.`hits`) as `hits_sum` FROM `analytics_@t`.`@ppaths` as p INNER JOIN `analytics_@t`.`@phits_merged` as h ON p.`id` = h.`group_id` WHERE p.`path` LIKE '@t%' AND h.`group_by` = 'path' AND h.`date` >= @s AND h.`date` <= @s GROUP BY `path` ORDER BY `@t` @t LIMIT @i, @i",

			'get_count'			=> "SELECT COUNT(DISTINCT p.`path`) as `cnt` FROM `analytics_@t`.`@ppaths` as p INNER JOIN `analytics_@t`.`@phits_merged` as h ON p.`id` = h.`group_id` WHERE p.`path` NOT LIKE '%/%' AND h.`group_by` = 'path' AND h.`date` >= @s AND h.`date` <= @s",
			'get_count_filter'		=> "SELECT COUNT(DISTINCT p.`path`) as `cnt` FROM `analytics_@t`.`@ppaths` as p INNER JOIN `analytics_@t`.`@phits_merged` as h ON p.`id` = h.`group_id` WHERE p.`path` LIKE '@t%' AND h.`group_by` = 'path' AND h.`date` >= @s AND h.`date` <= @s",

			'get_visitors'			=> "SELECT `date`, `group_id` as `data`, `visitors` as `value` FROM `analytics_@t`.`@phits_merged` WHERE `date` >= @s AND `group_id` IN(@l) AND `group_by` = 'path'",
			'get_mixed'			=> "SELECT `date`, `group_id` as `data`, `hits`, `visitors` FROM `analytics_@t`.`@phits_merged` WHERE `date` >= @s AND `group_id` IN(@l) AND `group_by` = 'path'",

			'get_all'			=> "SELECT `path`, `id` FROM `analytics_@t`.`@ppaths`",
			'get_filter'			=> "SELECT `path` FROM `analytics_@t`.`@ppaths` WHERE `path` LIKE '@t%' ORDER BY `path` LIMIT 0, 10"
		);
	}

	/**
	 * Формирует данные для динамических событийных отчётов (раздел "Отчёты по событиям")
	 *
	 * @param string $service_id ID сервиса
	 * @param string $date_begin Дата начала выборки в формате Y-m-d
	 * @param string $date_end Дата окончания выборки в формате Y-m-d
	 * @param string $filter Ключевое слово, по которому делать выборку для поля path в таблице paths, в запросе будет path LIKE 'текст фильтра%'
	 * @param string $limit Лимит выборки (для постраничного вывода), например "0-10" будет в запросе LIMIT 0, 10
	 * @param string $order Порядок выборки, например "visitors-desc" будет в запросе ORDER BY `visitors` desc
	 * @return array Данные сформированного событийного отчёта
	 */
	public function get_summary($service_id, $date_begin, $date_end, $filter = "", $limit = "0-10", $order = "visitors_avg-desc")
	{
		$data = array();

		list($field, $direction) = explode("-", $order);
		list($start, $end) = explode("-", $limit);

		if (!$filter)
			$result = $this->DB->get_summary($service_id, $service_id, $date_begin, $date_end, $field, $direction, (int) $start, (int) $end);
		else
			$result = $this->DB->get_summary_filter($service_id, $service_id, $filter, $date_begin, $date_end, $field, $direction, (int) $start, (int) $end);

		while ($row = $result->fetch())
			$data[] = $row;

		return $data;
	}

	public function get_count($service_id, $date_begin, $date_end, $filter = "")
	{
		if (!$filter)
			$data = $this->DB->get_count($service_id, $service_id, $date_begin, $date_end)->fetch();
		else
			$data = $this->DB->get_count_filter($service_id, $service_id, $filter, $date_begin, $date_end)->fetch();

		return isset($data['cnt']) ? $data['cnt'] : 0;
	}

	public function get_filter($service_id, $filter)
	{
		$data = array();

		$result = $this->DB->get_filter($service_id, $filter);
		while ($row = $result->fetch())
			$data[] = $row['path'];

		return $data;
	}

	public function get_visitors($service_id, $cache_date, &$paths)
	{
		$paths = $this->load_paths($service_id, $paths);
		if (empty($paths))
			return false;

		return $this->DB->get_visitors($service_id, $cache_date, array_keys($paths));
	}

	public function get_mixed($service_id, $cache_date, &$paths)
	{
		$paths = $this->load_paths($service_id, $paths);
		if (empty($paths))
			return false;

		return $this->DB->get_mixed($service_id, $cache_date, array_keys($paths));
	}

	private function load_paths($service_id, $groups)
	{
		static $paths = array();

		if (isset($paths[$service_id]))
			return $this->group_paths($paths[$service_id], $groups);

		$paths[$service_id] = array();

		$result = $this->DB->get_all($service_id);
		while ($row = $result->fetch())
			$paths[$service_id][$row['id']] = $row['path'];

		return $this->group_paths($paths[$service_id], $groups);
	}

	private function group_paths(&$paths, $groups)
	{
		$data = array();
		$length = count($groups);

		reset($paths);
		while (list($id, $path) = each($paths))
		{
			$path = explode("/", $path, $length);
			$options = array();
			$count = 0;

			reset($groups);
			while (list($key, $group) = each($groups))
			{
				if (!isset($path[$key]))
					break;

				$value = &$path[$key];

				if ($group === true)
				{
					$options[] = $value;
					$count++;
					continue;
				}
				if ($group === false)
				{
					$count++;
					continue;
				}

				reset($group);
				while (list($type, $name) = each($group))
				{
					if ($name !== $value)
						continue;

					$options[] = $type;
					$count++;
					break;
				}
			}

			if (!empty($options) && $count === $length)
				$data[$id] = $options;
		}

		return $data;
	}
}

?>