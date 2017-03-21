<?php

/**
 * Предоставляет функции общие для всей системы
 *
 * @version 1.0.4
 */
class ObjectCommon extends Object
{
	private $protect_symbols = array("&", "'", "\"", "\\");
	private $protected_symbols = array("&amp;", "&#039;", "&#034;", "&#092;");

	/**
	 * Генерирует пароль заданной длинны
	 * @param $count Integer: Требуемая длинна пароля
	 * @param $use_nums Boolean: Определяет, использовать ли цифры при генерации
	 * @retval String Сгенерированный пароль
	 */
	public function gen_password($count, $use_nums = true)
	{
		$symbols	= array("q", "w", "e", "r", "t", "y", "u", "i", "o", "p", "a", "s", "d", "f", "g", "h", "j", "k", "l", "z", "x", "c", "v", "b", "n", "m");
		$nums		= array("1", "2", "3", "4", "5", "6", "7", "8", "9");

		if ($use_nums)
			$symbols = array_merge($symbols, $nums);

		$symbols_count = count($symbols);

		$result = "";
		while ($count != 0)
		{
			$symbol = $symbols[mt_rand(0, $symbols_count - 1)];
			if (mt_rand(0, 3) == 1)
				$symbol = ucfirst($symbol);

			if (strpos($result, $symbol) !== false)
				continue;

			$result = $result.$symbol;
			$count--;
		}

		return $result;
	}

	/**
	 * Генерирует код подверждения
	 * @retval String Код подтверждения
	 */
	public function gen_confirm_code()
	{
		return strtoupper(sha1(mt_rand()));
	}

	/**
	 * Собирает параметры GET запроса в URL
	 * @param $params Array: Массив параметров
	 * @retval String Собранный URL
	 */
	public function format_params($params)
	{
		$result = "";

		reset($params);
		while (list($key, $value) = each($params))
		{
			if ($result != "")
				$result .= "&amp;";

			$result .= $key."=".$value;
		}

		return $result;
	}

	/**
	 * Копирует непустые элементы из массива согласно списка
	 * @param $data Array: Исходный массив
	 * @param $fields Array: Список копируемых элементов
	 * @retval Array Собранный URL
	 */
	public function copy_fields($data, $fields)
	{
		$result = array();

		while (list(, $field) = each($fields))
		{
			if (!isset($data[$field]))
				continue;
			$result[$field] = $data[$field];
		}

		return $result;
	}

	/**
	 * Перемещает элементы из исходного массива согласно списка
	 * @param[in,out] $data Array: Исходный массив
	 * @param $fields Array: Список перемещаемых элементов
	 * @retval Array Перемещённые элементы
	 */
	public function move_fields(&$data, $fields)
	{
		$result = array();

		while (list(, $field) = each($fields))
		{
			$result[$field] = $data[$field];
			unset($data[$field]);
		}

		return $result;
	}

	/**
	 * Удаляет элементы из исходного массива согласно списка
	 * @param[in,out] $data Array: Исходный массив
	 * @param $fields Array: Список удаляемых элементов
	 */
	public function remove_fields(&$data, $fields)
	{
		while (list(, $field) = each($fields))
			unset($data[$field]);
	}

	/**
	 * Удаляет из массива пустые элементы
	 * @param[in,out] $fields Array: Массив для обработки
	 */
	public function remove_empty(&$fields)
	{
		while (list($key, $value) = each($fields))
		{
			if (!empty($value))
				continue;
			unset($fields[$key]);
		}
	}

	/**
	 * Перемещает элементы из исходного массива и возвращает сериализованный результат
	 * @param[in,out] $data Array: Исходный массив
	 * @param $fields Array: Список перемещаемых элементов
	 * @retval String Сериализованный результат
	 */
	public function serialize_fields(&$data, $fields)
	{
		$result = $this->move_fields($data, $fields);
		$result = serialize($result);

		return $result;
	}

	/**
	 * Выполняет обрезание начальных и конечных пробелов всех элементов массива
	 * @param $data Array: Исходный массив
	 * @retval Array Результирующий массив
	 */
	public function trim($data)
	{
		if (!is_array($data))
			return trim($data);

		while (list($key, $value) = each($data))
			$data[$key] = $this->trim($value);

		return $data;
	}

	/**
	 * Возвращает строку, обрезанную до заданной длинны
	 * @param $text String: Строка
	 * @param $max_len Integer: Максимальная длинна
	 * @retval String Обрезанная строка
	 */
	public function cut($text, $max_len)
	{
		$text = strip_tags($text);

		if (mb_strlen($text) <= $max_len)
			return $text;

		return mb_substr($text, 0, $max_len)."...";
	}

	/**
	 * Возвращает текущий IP пользователя в виде строки
	 * @retval String Текущий IP пользователя
	 * @retval "" Если IP адрес определить не удалось
	 */
	public function get_ip_string()
	{
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		if ($ip !== false)
			return $_SERVER['REMOTE_ADDR'];

		$ip = ip2long($_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip !== false)
			return $_SERVER['HTTP_X_FORWARDED_FOR'];

		return "";
	}

	/**
	 * Возвращает текущий IP пользователя в виде числа
	 * @retval Integer Текущий IP пользователя
	 * @retval false Если IP адрес определить не удалось
	 */
	public function get_ip_long()
	{
		return ip2long($this->get_ip_string());
	}

	/**
	 * Делает первую букву строки в многобайтовой кодировке Заглавной
	 * @param $text String: Строка
	 * @retval String Строка с Заглавной первой буквой
	 */
	public function mb_ucfirst($text)
	{
		$text_length = mb_strlen($text);
		$first_letter = mb_substr($text, 0, 1);
		$first_letter = mb_strtoupper($first_letter);
		$last_letters = mb_substr($text, 1, $text_length - 1);

		return $first_letter.$last_letters;
	}

	/**
	 * Вставляет в исходную строку разделитель через некоторые промежутки
	 * @param $str String: Исходная строка
	 * @param $width Integer: Максимальная длинна элемента
	 * @param $break String: Разделитель
	 * @retval String Результирующая строка
	 */
	public function mb_wordwrap($str, $width, $break = " ")
	{
		$pieces = explode(" ", $str);

		$result = array();
		foreach ($pieces as $piece)
		{
			$current = $piece;
			while (mb_strlen($current) > $width)
			{
				$result[] = mb_substr($current, 0, $width);
				$current = mb_substr($current, $width);
			}
			$result[] = $current;
		}

		return implode($break, $result);
	}

	/**
	 * Форматирует число в формат размера файла
	 * @param[in,out] $size Integer: Размер
	 */
	public function format_size(&$size)
	{
		if (!is_numeric($size))
			return;

		$base = 1000;
		$ext = "Байт";
		if ($size > $base)
		{
			$size /= 1024;
			$ext = "КБ";
		}

		if ($size > $base)
		{
			$size /= 1024;
			$ext = "МБ";
		}

		if ($size > $base)
		{
			$size /= 1024;
			$ext = "ГБ";
		}

		$size = round($size, 2);
		$size .= " ".$ext;
	}

	/**
	 * Проверяет значение на корректность и сбрасывает его на начальное, в случае некорректности
	 * @param[in,out] $value Mixed: Проверяемое значение
	 * @param $options Array: Массив корректных значений
	 */
	public function check_in(&$value, $options)
	{
		$key = array_search($value, $options);
		if ($key === false)
			$key = 0;

		$value = $options[$key];
	}

	/**
	 * Применяет к значениям массива шаблоны, находящиеся по тем же индексам в массиве шаблонов, что и сами элементы
	 * @param[in,out] $data Array: Индексированный массив для обработки
	 * @param $templates Array: Индексированный массив шаблонов
	 */
	public function apply_templates(&$data, $templates, $binds = false)
	{
		while (list($i, ) = each($data))
		{
			if (empty($templates[$i]))
				continue;

			if ($binds !== false)
				$templates[$i]->bind_params($binds);

			$templates[$i]->data = $data[$i];

			$data[$i] = (string) $templates[$i];
		}
	}

	/**
	 * Проверяет является ли источник, с которого перешли на данную страницу, текущим сайтом
	 * @param $redirect_url String: URL для редиректа в случае если не является
	 */
	public function check_referrer($redirect_url)
	{
		if (empty($_SERVER['HTTP_REFERER']))
			return;
		if (stripos($_SERVER['HTTP_REFERER'], SITE_DOMAIN_NAME) !== false)
			return;
		Component::redirect($redirect_url);
	}

	/**
	 * Заменяет спец. символы их безопасными аналогами
	 * @param $data Array: Данные для защиты
	 */
	public function protect(&$data)
	{
		$data['name']	= str_replace($this->protect_symbols, $this->protected_symbols, $data['name']);
		$data['descr']	= str_replace($this->protect_symbols, $this->protected_symbols, $data['descr']);
	}

	public function split_numbers(&$data)
	{
		if (!is_array($data))
		{
			$data = number_format($data, 2, ".", " ");
			return;
		}

		while (list($key, $value) = each($data))
		{
			if (strpos($value, ".") !== false)
				continue;
			$data[$key] = number_format($value, 0, ".", " ");
		}
	}

	public function clear(&$values, &$assigned = false)
	{
		$values = array_map("trim", $values);
		$values = array_filter($values);
		$values = array_unique($values);

		if ($assigned === false)
			return;

		$assigned_new = array();

		while (list($key,) = each($values))
		{
			if (!isset($assigned[$key]))
				$assigned[$key] = "";
			$assigned_new[$key] = trim($assigned[$key]);
		}

		$assigned = $assigned_new;
	}

	public function array_merge(array &$array1, array &$array2)
	{
		$merged = $array1;

		reset($array2);
		while (list($key, $value) = each($array2))
		{
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
				$merged[$key] = $this->array_merge($merged[$key], $value);
			else
				$merged[$key] = $value;
		}

		return $merged;
	}

	public function starts_with($haystack, $needle, $delimiter = null)
	{
		return preg_match("/^".preg_quote($needle, $delimiter)."/ui", $haystack) > 0;
	}

	public function contains($haystack, $needle, $delimiter = null)
	{
		return preg_match("/".preg_quote($needle, $delimiter)."/ui", $haystack) > 0;
	}

	public function ends_with($haystack, $needle, $delimiter = null)
	{
		return preg_match("/".preg_quote($needle, $delimiter)."$/ui", $haystack) > 0;
	}

	private function clean_to_bind(&$data, $save_tags = false)
	{
		reset($data);
		while (list($key, $value) = each($data))
		{
			if (is_array($value))
			{
				unset($data[$key]);
				continue;
			}

			if (!$save_tags)
			{
				$value = strip_tags($value);
				$value = str_replace($this->protected_symbols, $this->protect_symbols, $value);
				$value = str_replace($this->protect_symbols, $this->protected_symbols, $value);
			}


			$data[$key] = $value;
		}
	}
}

?>