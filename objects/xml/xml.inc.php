<?php

/**
 * Реализует оборачивание данных в CDATA секции при работе с XML
 *
 * @uses SimpleXMLElement
 *
 * @version 1.0.3
 */
class SimpleXMLExtended extends SimpleXMLElement
{
	/**
	 * Добавляет дочерний элемент в XML контейнер
	 * @param $name String: Имя элемента
	 * @param $value String: Значение элемента
	 * @param $namespace String: Пространство имён
	 * @retval SimpleXMLElement Добавленный элемент
	 */
	public function addChild($name, $value = null, $namespace = null)
	{
		$child = parent::addChild($name, null, $namespace);
		if ($value === null)
			return $child;

		$node = dom_import_simplexml($child);
		$owner = $node->ownerDocument;
		$node->appendChild($owner->createCDATASection($value));

		return $child;
	}

	/**
	 * Записывает данные массива в виде аттрибутов XML объекта
	 * @param $data Array: Данные для записи
	 */
	public function write_attributes($data)
	{
		while ((list($key, $value) = each($data)))
			$this->addAttribute($key, $value);
	}

	/**
	 * Записывает данные массива в виде дочерних элементов XML объекта
	 * @param $data Array: Данные для записи
	 * @param $node_name String: Имя дочернего элемента
	 */
	public function write_nodes($data, $node_name)
	{
		while ((list(, $value) = each($data)))
			$this->addChild($node_name, $value);
	}
}

/**
 * Предоставляет функции генерации XML данных
 *
 * @uses ObjectErrors
 * @uses SimpleXMLExtended
 *
 * @version 1.0.3
 */
class ObjectXML extends Object
{
	/**
	 * Создаёт XML объект для отправки ответа клиенту
	 * @param $send_errors Boolean: Отправлять ошибки в отдельном контейнере?
	 * @retval SimpleXMLExtended XML объект
	 */
	public function start_answer($send_errors = true)
	{
		$xml = new SimpleXMLExtended("<?xml version='1.0' encoding='".SITE_CHARSET."'?><answer />");

		ob_start();

		register_shutdown_function(array($this, "send_xml"), $xml, $send_errors);

		return $xml;
	}

	/**
	 * Отправляет XML данные клиенту
	 * @param $xml SimpleXMLExtended: XML объект
	 */
	public function send_xml($xml, $send_errors)
	{
		$buffer = ob_get_clean();

		if ($buffer !== "")
		{
			if ($send_errors && !$this->Errors->is_empty())
				$container = "errors";
			else
				$container = "content";

			$xml->addChild($container, $buffer);
		}

		Component::print_headers("application/xml");
		echo $xml->asXML();
		exit;
	}
}

?>