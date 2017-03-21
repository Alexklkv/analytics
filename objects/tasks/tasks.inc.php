<?php

/**
 * Предоставляет функции управления задачами
 *
 * @uses DatabaseInterface
 *
 * @version 1.0.3
 */
class ObjectTasks extends Object implements DatabaseInterface
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
			'get' => "SELECT * FROM @ptasks",
		);
	}

	/**
	 * Возвращает все задачи
	 * @retval Array Задачи
	 */
	public function get_list()
	{
		$modules = array();

		$result = $this->DB->get();

		while (($row = $result->fetch()))
			array_push($modules, $row);

		return $modules;
	}
}

?>