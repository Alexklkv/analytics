<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Класс для чтения файлов
 */
class FileReader
{
	const ReadBuffer = 64 * 1024;
	const LineEnding = "\n";

	private $handle = false;
	private $file_size = false;

	private $buffer = "";
	private $buffer_size = 0;
	private $readed_pos = 0;

	public function __construct($file_name)
	{
		$this->handle = fopen($file_name, "r");
		if ($this->handle === false)
			throw new Exception("Can't open file {$file_name}");

		$stat = fstat($this->handle);
		if ($stat === false)
		{
			$this->close();
			throw new Exception("Can't get file {$file_name} size");
		}

		$this->file_size = $stat['size'];
	}

	public function __destruct()
	{
		$this->close();
	}

	public function close()
	{
		if ($this->handle === false)
			return;

		fclose($this->handle);

		$this->handle = false;
		$this->file_size = false;
	}

	public function eof()
	{
		if ($this->handle === false)
			return true;

		return feof($this->handle);
	}

	public function pos()
	{
		if ($this->handle === false)
			return false;

		return ftell($this->handle);
	}

	public function size()
	{
		return $this->file_size;
	}

	public function get_line()
	{
		while (true)
		{
			$line_end = strpos($this->buffer, self::LineEnding, $this->readed_pos);
			if ($line_end !== false)
				break;

			if (!$this->read())
				return false;
		}

		$line_end++;
		$line = substr($this->buffer, $this->readed_pos, $line_end - $this->readed_pos);

		$this->readed_pos = $line_end;

		return $line;
	}

	private function read()
	{
		if ($this->handle === false)
			return false;

		$result = fread($this->handle, self::ReadBuffer);
		if ($result === false)
			return false;

		if ($this->buffer_size != 0)
		{
			$this->buffer = substr($this->buffer, $this->readed_pos, $this->buffer_size - $this->readed_pos);

			$this->buffer_size -= $this->readed_pos;
			$this->readed_pos = 0;
		}

		$this->buffer .= $result;
		$this->buffer_size += strlen($result);

		return true;
	}
}

?>