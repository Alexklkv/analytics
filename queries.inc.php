<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Группирует данные для запроса на вставку в базу данных и делает запрос на вставку
 */
class QueriesBuffer
{
	private $MaxBufferLength;

	private $sql;
	private $table;

	private $buffer = array();
	private $buffer_length = 0;

	public function __construct($sql, $table, $max = 100)
	{
		$this->MaxBufferLength = $max;
		$this->sql = $sql;
		$this->table = $table;
	}

	public function add($data)
	{
		array_walk($data, array($this, "protect"));
		array_push($this->buffer, "('".implode("', '", $data)."')");

		$this->buffer_length++;
		if ($this->buffer_length != $this->MaxBufferLength)
			return;

		$this->finish();
	}

	public function finish()
	{
		if ($this->buffer_length == 0)
			return;

		$this->sql->query("INSERT INTO ".$this->table." VALUES ".implode(", ", $this->buffer).";");
		if ($this->sql->errno != 0)
		{
			echo $this->sql->error."\n";
			//exit;
		}

		$this->buffer = array();
		$this->buffer_length = 0;
	}

	private function protect(&$value, $key)
	{
		$value = trim($value);
		$value = $this->sql->real_escape_string($value);
	}
}

?>