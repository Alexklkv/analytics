<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Модуль главной страницы панели администратора
 *
 * @uses ComponentAdmin
 * @uses ObjectTemplates
 *
 * @version 1.0.1
 */
class AdminMain extends ComponentAdmin
{
	public function initialize()
	{
		parent::initialize();

		$this->Templates->set_base_path("Панель администрирования");
		$this->Templates->set_template("Панель администрирования/Шаблоны/Страница");
	}

	public function get_name()
	{
		return "";
	}

	public function get_services()
	{
		return array();
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
		$this->Templates->set_page("Главная");
	}
}

?>