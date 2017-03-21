<?php

/**
 * Предоставляет функции внешнего api
 *
 * @uses DatabaseInterface
 *
 * @version 1.0.1
 */
class ObjectApi extends Object implements DatabaseInterface
{
	const HitsCacheDays = 30;

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
			'get_services_id'	=> "SELECT `id` FROM `@pservices` WHERE `account` != 0",
			'clear_hits'		=> "DELETE FROM `analytics_@i`.`@phits` WHERE `time` < DATE_SUB(NOW(), INTERVAL ".self::HitsCacheDays." DAY)"
		);
	}

	public function clear()
	{
		echo "\n\nStarting clearing hits cache at ".date("Y-m-d")."\n";

		$start = microtime(true);

		$result = $this->DB->get_services_id();
		while ($row = $result->fetch())
			$this->DB->clear_hits($row['id']);

		echo "done in ".round(microtime(true) - $start, 2)." seconds\n";
	}
}

?>