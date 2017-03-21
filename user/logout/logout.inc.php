<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Модуль выхода из панели администрирования
 *
 * @uses ComponentAdmin
 * @uses ObjectAdmin
 *
 * @version 1.0.1
 */
class AdminLogout extends ComponentAdmin
{
	public function get_name()
	{
		return "Выход";
	}

	public function get_services()
	{
		return array('index' => "Выход");
	}

	/**
	 * Возвращает пользовательский массив прав доступа для каждого метода-обработчика,
	 * а также общие права на доступ к конкретному модулю
	 *
	 * @return array Массив прав доступа
	 */
	public function get_access_overrides()
	{
		return array('index' => false);
	}

	/**
	 * Отображает титульную страницу модуля
	 */
	public function on_index()
	{
		$this->Admin->logout();
		Component::redirect("");
	}
}

?>