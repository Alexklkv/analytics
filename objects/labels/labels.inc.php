<?php
/**
 * Модуль управления тегами
 *
 * @uses DatabaseInterface
 * @uses ObjectErrors
 * @uses ObjectTables
 *
 * @version 1.0.0
 */

class ObjectLabels extends Object implements DatabaseInterface
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
			'get'			=> "SELECT * FROM `@plabels` @W",

			'add'			=> "INSERT INTO `@plabels` SET @a",
			'update'		=> "UPDATE `@plabels` SET `date` = @s, `value` = @s WHERE `service` = @i AND `date` = @s",
			'delete'		=> "DELETE FROM `@plabels` WHERE `service` = @i AND `date` = @s",

			'get_data'		=> "SELECT `date`, `value` FROM `@plabels` WHERE `service` = @i @@A @O @L",
			'get_data_count'	=> "SELECT COUNT(*) as `total` FROM `@plabels` WHERE `service` = @i @@A"
		);
	}

	public function get($service)
	{
		$table = array(
			'fields'	=> array("date", "value"),
			'count'		=> array(&$this->DB, "get_data_count"),
			'data'		=> array(&$this->DB, "get_data"),
			'params'	=> array($service)
		);

		$this->Tables->send($table);
	}

	public function get_by($values)
	{
		$result = $this->DB->get($values);

		if ($result->is_empty())
			return false;

		return $result->fetch();
	}

	public function add($data)
	{
		$result = $this->DB->get(array('service' => $data['service'], 'date' => $data['date']));

		if ($result->num_rows() != 0)
		{
			$this->Errors->add(array("labels"), "Тег на данное число уже есть");
			return false;
		}

		$this->DB->add($data);

		if ($this->DB->affected_rows == 0)
		{
			$this->Errors->add(array("labels"), "При добавлении тега произошла ошибка");
			return false;
		}

		return true;
	}

	public function edit($data)
	{
		if ($data['date'] != $data['old_date'])
		{
			$result = $this->DB->get(array('service' => $data['service'], 'date' => $data['date']));

			if ($result->num_rows() != 0)
			{
				$this->Errors->add(array("labels"), "Тег на данное число уже есть");
				return false;
			}
		}

		$this->DB->update($data['date'], $data['value'], $data['service'], $data['old_date']);
		return true;
	}

	public function delete($data)
	{
		$this->DB->delete($data['service'], $data['date']);

		if ($this->DB->affected_rows == 0)
		{
			$this->Errors->add(array("labels"), "Данный тег уже был удалён");
			return false;
		}

		return true;
	}
}

?>