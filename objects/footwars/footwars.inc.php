<?php

/**
 * Реализует отчёты Footwars: Футбольные Войны
 *
 * @uses DatabaseInterface
 *
 * @version 1.0.0
 */
class ObjectFootwars extends Object implements DatabaseInterface
{
	/**
	 * Идентификатор проекта в системе аналитики
	 */
	private static $service_id;

	/**
	 * @var array Массив социальных сетей, на площадках которых работает проект,
	 * для обозначения кривых на графиках. Ключи массива соответствуют
	 * полю type в таблицах payments и players в базе данных игрового сервера.
	 */
	private $networks = array(0 => "ВКонтакте", 1 => "МойМир", 3 => "МирТесен", 4 => "Одноклассники", 5 => "Facebook", 6 => "Мамба", 30 => "ФотоСтрана", 31 => "NextGame", 32 => "StandAlone");

	/**
	 * @var array Массив пола игроков для обозначения кривых на графиках
	 */
	private $sex = array(0 => "Не задан", 1 => "Мужской", 2 => "Женский");

	/**
	 * @var array Массив групп возрастов игроков для обозначения кривых на графиках
	 */
	private $ages = array(0 => "0-13", 1 => "14-17", 2 => "18-21", 3 => "22-25", 4 => "26-30", 5 => "31-35", 6 => "36-40", 7 => "41+", 99 => "Не задан");

	/**
	 * @var array Массив обозначений групп игроков по уровням,
	 * для обозначения кривых на графиках
	 */
	private $players_levels = array(0 => "111+", 6 => "1-5", 11 => "6-10", 21 => "11-20", 36 => "21-35", 56 => "36-55", 81 => "56-80", 111 => "81-110");

	/**
	 * @var array Массив обозначений кривых для отчёта Выходы из матчей,
	 * кривые на графиках времени выхода игрока из матча
	 */
	private $matches_leave_time = array(0 => "До 10 секунды", 1 => "До 20 секунды", 2 => "До 30 секунды", 3 => "До 45 секунды", 4 => "После 45 секунды");

	/**
	 * @var array Массив обозначений кривых для отчёта Выходы из матчей,
	 * кривые на графике, каким по счёту игрок покинул матч
	 */
	private $matches_leave_size = array(1 => "1-й", 2 => "2-й", 3 => "3-й", 4 => "4-й", 5 => "5-й", 6 => "6-й", 7 => "7-й", 8 => "8-й", 9 => "9-й", 10 => "10-й", 11 => "11-й", 12 => "12-й", 13 => "13-й", 14 => "14-й", 15 => "15-й", 16 => "16-й", 17 => "17-й", 18 => "18-й", 19 => "19-й", 20 => "20-й");

	/**
	 * @var array Массив обозначений кривых для отчёта Статистика матчей,
	 * группы разниц в счёте в конце матча
	 */
	private $matches_score_diff_groups = array(0 => "21+", 3 => "0-2", 6 => "3-5", 11 => "6-10", 16 => "11-15", 21 => "16-20");

	/**
	 * @var array Массив коэффициентов, на которые умножается жесткая игровая валюта
	 * (кристаллы), чтобы получить сумму в рублях. Используется
	 * в фининасовых отчётах. Ключи массива соответствуют полю type
	 * в таблицах payments и players в базе данных игрового сервера.
	 */
	private $revenue_hard = array(0 => 0.6);

	/**
	 * @var array Массив коэффициентов, на которые умножается мягкая игровая валюта
	 * (баксы), чтобы получить сумму в рублях. Используется
	 * в фининасовых отчётах. Ключи массива соответствуют полю type
	 * в таблицах payments и players в базе данных игрового сервера.
	 */
	private $revenue_soft = array(0 => 0.0012);

	/**
	 * @var string Обозначение валюты (рубли) для финансовых отчетов,
	 * выводится на подписях к кривым и в других местах,
	 * где требуется указание реальной валюты.
	 */
	private $currency = " р.";

	static public function init($service_id)
	{
		self::$service_id = $service_id;
	}

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
			'payments_all_hard'		=> "SELECT DATE(`time`) as `date`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `time` >= @s AND `offer` != 5 GROUP BY `date`",
			'payments_all_soft'		=> "SELECT DATE(`time`) as `date`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `time` >= @s AND `offer` = 5 GROUP BY `date`",
			'payments_first_hard'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 FORCE INDEX (`time`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` = p1.`time` WHERE p2.`time` >= @s AND p2.`offer` != 5 GROUP BY `date`",
			'payments_first_soft'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 FORCE INDEX (`time`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` = p1.`time` WHERE p2.`time` >= @s AND p2.`offer` = 5 GROUP BY `date`",
			'payments_repeated_hard'	=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 FORCE INDEX (`type`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` != p1.`time` WHERE p2.`time` >= @s AND p2.`offer` != 5 GROUP BY `date`",
			'payments_repeated_soft'	=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 FORCE INDEX (`type`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` != p1.`time` WHERE p2.`time` >= @s AND p2.`offer` = 5 GROUP BY `date`",
			'payments_newbies_hard'		=> "SELECT DATE(pm.`time`) as `date`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` = DATE(pm.`time`) AND pm.`time` >= @s AND pm.`offer` != 5 GROUP BY `date`",
			'payments_newbies_soft'		=> "SELECT DATE(pm.`time`) as `date`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` = DATE(pm.`time`) AND pm.`time` >= @s AND pm.`offer` = 5 GROUP BY `date`",
			'payments_net'			=> "SELECT DATE(`time`) as `date`, `type` as `data`, SUM(`balance`) as `sum`, COUNT(*) as `count`, `offer` FROM `payments` WHERE `time` >= @s GROUP BY `date`, `data`, `offer`",

			'finance_arpu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, `offer`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `time` >= @s GROUP BY `date`, `data`, `offer`",
			'finance_arpu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), pm.`time`)) as `data`, pm.`type` as `net`, pm.`offer`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= @s GROUP BY `date`, `data`, `net`, `offer`",
			'finance_arpu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, pm.`offer`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= @s GROUP BY `date`, `data`, `net`, `offer`",
			'finance_arppu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, `offer`, SUM(`balance`) as `sum`, COUNT(DISTINCT `net_id`) as `count` FROM `payments` WHERE `time` >= @s GROUP BY `date`, `data`, `offer`",
			'finance_arppu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), pm.`time`)) as `data`, pm.`type` as `net`, pm.`offer`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= @s GROUP BY `date`, `data`, `net`, `offer`",
			'finance_arppu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, pm.`offer`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= @s GROUP BY `date`, `data`, `net`, `offer`",

			'counters_online_all'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 0 AND `time` >= @s GROUP BY `date`",
			'counters_online_net'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 1 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_age'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 2 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_sex'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 3 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_dau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 5 AND `date` >= @s",
			'counters_dau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 8 AND `date` >= @s",
			'counters_dau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 11 AND `date` >= @s",
			'counters_dau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 14 AND `date` >= @s",
			'counters_wau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 6 AND `date` >= @s",
			'counters_mau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 7 AND `date` >= @s",
			'counters_mau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 10 AND `date` >= @s",
			'counters_mau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 13 AND `date` >= @s",
			'counters_mau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 16 AND `date` >= @s",
			'counters_sessions_count'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 21 AND `date` >= @s",
			'counters_sessions_time'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 17 AND `date` >= @s",

			'players_new_all'		=> "SELECT `register_time` as `date`, 0 as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`",
			'players_retention_all'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`",
			'players_retention_1d'		=> "SELECT DATE_ADD('1970-01-01', INTERVAL `data` DAY) as `registered`, DATEDIFF(`date`, '1970-01-01') - `data` as `days`, `value` FROM `counters_daily` WHERE `type` = 25",
			'players_paying_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `net_id`, COUNT(*) as `count` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), pm.`time`)) as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_loading'		=> "SELECT `date`, `data` & 0xFFFF AS `data`, `value` FROM `counters_daily` WHERE `type` = 32 AND `date` >= @s",
			'players_matches'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 33 AND `date` >= @s",
			'players_matches_score_diff'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 36 AND `date` >= @s",
			'players_matches_leave_time'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 34 AND `date` >= @s",
			'players_matches_leave_size'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 35 AND `date` >= @s",
			'players_levels'		=> "SELECT `register_time` as `date`, `level`, COUNT(*) as `value` FROM `players` GROUP BY `date`, `level`",

			'hidden_counters_mau_net'	=> "SELECT LAST_DAY(CURDATE()) as `date`, `type` as `data`, COUNT(*) as `value` FROM `players` WHERE `logout_time` >= @i GROUP BY `data`",
			'hidden_counters_mau_age'	=> "SELECT LAST_DAY(CURDATE()) as `date`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL `bday` SECOND), NOW())) as `data`, COUNT(*) as `value` FROM `players` WHERE `logout_time` >= @i GROUP BY `data`",
			'hidden_counters_mau_sex'	=> "SELECT LAST_DAY(CURDATE()) as `date`, `sex` as `data`, COUNT(*) as `value` FROM `players` WHERE `logout_time` >= @i GROUP BY `data`",
			'hidden_paying_month_net'	=> "SELECT LAST_DAY(`time`) as `date`, `type` as `data`, COUNT(DISTINCT `net_id`, `type`) as `value` FROM `payments` FORCE INDEX(`time`) WHERE `time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_age'	=> "SELECT LAST_DAY(pm.`time`) as `date`, IF(p.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL p.`bday` SECOND), pm.`time`)) as `data`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_sex'	=> "SELECT LAST_DAY(pm.`time`) as `date`, p.`sex` as `data`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`"
		);
	}

	public function get_jobs()
	{
		return array(18 => "players_matches");
	}

	public function get_categories()
	{
		return array(
			'payments'	=> "Платежи",
			'finance'	=> "Финансы",
			'counters'	=> "Счётчики",
			'players'	=> "Игроки",
			'hidden'	=> "Скрытая категория",
			'apipath'	=> "Скрытая для путей"
		);
	}

	public function get_reports()
	{
		$id = 0;

		return array(
			'payments' => array(
				'all' => array(
					'id'		=> $id++,
					'title'		=> "Все платежи",
					'description'	=> "Количество и сумма платежей в кристаллах и баксах",
					'graphs'	=> array(
						array(
							'title'		=> "Кристаллы",
							'legend'	=> array(0 => "Сумма в кристаллах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Баксы",
							'legend'	=> array(0 => "Сумма в баксах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				),
				"-",
				'first' => array(
					'id'		=> $id++,
					'title'		=> "Первые платежи",
					'description'	=> "Количество и сумма платежей, сделанных игроками в первый раз",
					'graphs'	=> array(
						array(
							'title'		=> "Кристаллы",
							'legend'	=> array(0 => "Сумма в кристаллах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Баксы",
							'legend'	=> array(0 => "Сумма в баксах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				),
				'repeated' => array(
					'id'		=> $id++,
					'title'		=> "Повторные платежи",
					'description'	=> "Количество и сумма платежей, сделанных игроками повторно",
					'graphs'	=> array(
						array(
							'title'		=> "Кристаллы",
							'legend'	=> array(0 => "Сумма в кристаллах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Баксы",
							'legend'	=> array(0 => "Сумма в баксах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				),
				'newbies' => array(
					'id'		=> $id++,
					'title'		=> "Платежи новичков",
					'description'	=> "Количество и сумма платежей, сделанных игроками в течение суток с момента регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Кристаллы",
							'legend'	=> array(0 => "Сумма в кристаллах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Баксы",
							'legend'	=> array(0 => "Сумма в баксах", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				)
			),
			'finance' => array(
				'revenue' => array(
					'id'		=> $id++,
					'title'		=> "Доход",
					'description'	=> "Сумма дохода",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Сумма")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency
					)
				),
				"-",
				'arpu' => array(
					'id'		=> $id++,
					'title'		=> "ARPU",
					'description'	=> "Средний доход от игрока за день в рублях (без учёта комиссии социальной сети)",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Общее")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency,
						'show_sums'	=> false,
						'indicator'	=> array('type' => "function", 'function' => "arpu_indicator")
					)
				),
				'arppu' => array(
					'id'		=> $id++,
					'title'		=> "ARPPU",
					'description'	=> "Средний доход от платящего игрока за день в рублях (без учёта комиссии социальной сети)",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Общее")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency,
						'show_sums'	=> false,
						'indicator'	=> array('type' => "function", 'function' => "arppu_indicator")
					)
				)
			),
			'counters' => array(
				'online' => array(
					'id'		=> $id++,
					'title'		=> "Онлайн",
					'description'	=> "Онлайн игроков",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Максимальный", 1 => "Минимальный")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По полу",
							'legend'	=> array(0 => "Не задан", 1 => "Женский", 2 => "Мужской")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				"-",
				'dau' => array(
					'id'		=> $id++,
					'title'		=> "DAU",
					'description'	=> "Количество уникальных игроков за день",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array("Игроки")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'wau' => array(
					'id'		=> $id++,
					'title'		=> "WAU",
					'description'	=> "Количество уникальных игроков за неделю",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Игроки")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'mau' => array(
					'id'		=> $id++,
					'title'		=> "MAU",
					'description'	=> "Количество уникальных игроков за месяц",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Игроки")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "fixed")
					)
				),
				"-",
				'mau_percent' => array(
					'id'		=> $id++,
					'title'		=> "DAU/MAU",
					'description'	=> "Отношение DAU к MAU",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Общий")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "fixed")
					)
				),
				'wau_percent' => array(
					'id'		=> $id++,
					'title'		=> "DAU/WAU",
					'description'	=> "Отношение DAU к WAU",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Общий")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "fixed")
					)
				),
				"-",
				'sessions' => array(
					'id'		=> $id++,
					'title'		=> "Сессии",
					'description'	=> "Среднее количество игровых сессий на одного игрока (DAU) и среднее время в минутах игровых сессий в день",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Количество")
						),
						array(
							'title'		=> "Время",
							'legend'	=> array(0 => "Время"),
							'value_append'	=> " м."
						)
					)
				)
			),
			'players' => array(
				'new' => array(
					'id'		=> $id++,
					'title'		=> "Новые игроки",
					'description'	=> "Количество игроков, только что установивших приложение",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки",
							'legend'	=> array(0 => "Игроки")
						)
					)
				),
				'retention' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения",
					'description'	=> "Возвращение игроков через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "%",
							'legend'	=> array(0 => "1d", 1 => "3d", 2 => "7d", 3 => "1d+")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				"-",
				'paying_day' => array(
					'id'		=> $id++,
					'title'		=> "Платящие за день",
					'description'	=> "Процент пользователей, которые платили хотя бы раз в день",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Процент", 1 => "Количество"),
							'value_append'	=> array(0 => "%", 1 => ""),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'value_append'	=> "%",
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'paying_month' => array(
					'id'		=> $id++,
					'title'		=> "Платящие за месяц",
					'description'	=> "Процент пользователей, которые платили хотя бы раз в месяц",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Процент", 1 => "Количество"),
							'value_append'	=> array(1 => ""),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "fixed")
					)
				),
				"-",
				'loading' => array(
					'id'		=> $id++,
					'title'		=> "Первая загрузка",
					'description'	=> "Количество событий первой загрузки для игрока поэтапно",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Приложение установлено", 1 => "Клиент запущен", 2 => "Клиент загружен", 3 => "Обучение с тренером завершено", 4 => "Обучение с ботами завершено", 5 => "3-й этап обучения: устаревший", 6 => "4-й этап обучения: устаревший", 7 => "5-й этап обучения: устаревший")
						)
					)
				),
				"-",
				'matches' => array(
					'id'		=> $id++,
					'title'		=> "Статистика матчей",
					'description'	=> "Количество матчей. Количество игроков начавших матч и закончивших или покинувших его. Разница в счёте в конце матча",
					'graphs'	=> array(
						array(
							'title'		=> "Статистика новичков",
							'legend'	=> array(9 => "Классика: Новичков начало", 10 => "Классика: Новичков доиграло", 11 => "Эксперимент: Новичков начало", 12 => "Эксперимент: Новичков доиграло")
						),
						array(
							'title'		=> "Общая статистика",
							'legend'	=> array(0 => "Классика: Матч начат", 1 => "Классика: Игроков начало", 2 => "Классика: Игроков доиграло", 4 => "Тренировок начато", 6 => "Эксперимент: Матч начат", 7 => "Эксперимент: Игроков начало", 8 => "Эксперимент: Игроков доиграло")
						),
						array(
							'title'		=> "Разница в счёте",
							'legend'	=> $this->matches_score_diff_groups
						)
					)
				),
				'matches_leave' => array(
					'id'		=> $id++,
					'title'		=> "Выходы из матчей",
					'description'	=> "Время выхода первого игрока, время выхода второго игрока; игроки, покинувшие матч N-ми",
					'graphs'	=> array(
						array(
							'title'		=> "Первый игрок",
							'legend'	=> $this->matches_leave_time
						),
						array(
							'title'		=> "Второй игрок",
							'legend'	=> $this->matches_leave_time
						),
						array(
							'title'		=> "Покинули N-ми",
							'legend'	=> $this->matches_leave_size
						)
					)
				),
				'levels' => array(
					'id'		=> $id++,
					'type'		=> "filled",
					'title'		=> "Уровни игроков",
					'description'	=> "Разделение новых игроков по уровням",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> $this->players_levels
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
			),
			'hidden' => array(
				'payments_all' => array(
					'id'		=> $id++,
					'title'		=> "Все платежи в рублях",
					'description'	=> "Сумма платежей в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма")
						)
					),
					'hidden'	=> true
				),
				'payments_net' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по сетям в рублях",
					'description'	=> "Сумма платежей для каждой соц. сети в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->networks
						)
					),
					'hidden'	=> true
				),
				'payments_age' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по возрасту в рублях",
					'description'	=> "Сумма платежей для каждой возрастной группы в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->ages
						)
					),
					'hidden'	=> true
				),
				'payments_sex' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по полу в рублях",
					'description'	=> "Сумма платежей для каждого пола в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->sex
						)
					),
					'hidden'	=> true
				),
				'counters_mau' => array(
					'id'		=> $id++,
					'title'		=> "MAU Последнего дня",
					'description'	=> "MAU на последний день месяца",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'hidden'	=> true
				),
				'paying_month' => array(
					'id'		=> $id++,
					'title'		=> "Платящие за месяц",
					'description'	=> "Количество платящих на каждый последний день месяца",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					),
					'hidden'	=> true
				)
			),
			'apipath' => array(
				'common' => array(
					'id'		=> $id++,
					'title'		=> "Уникальные посетители и события",
					'description'	=> "Количество уникальных посетителей и событий по определенным путям событий",
					'graphs'	=> array(
						array(
							'title'		=> "Посетители и события",
							'legend'	=> array()
						)
					),
					'hidden'	=> true,
					'params'	=> array(
						'show_sums'	=> false
					)
				)
			)
		);
	}

	/**
	 * Сбор общих данных по ARPU для виджета на странице общего отчёта
	 *
	 * @param ObjectAnalytics $Analytics Объект с общими функциями аналитики
	 * @param array $report Массив с данными отчёта
	 * @param array $periods Временные ограничения для выборки данных
	 * @param string $graph Номер графика в отчёте
	 * @param int $type Номер кривой в отчёте
	 * @return array Данные для представления
	 */
	public static function arpu_indicator($Analytics, $report, $periods, $graph, $type)
	{
		$data = array();

		while (list($key, $offsets) = each($periods))
			$data[$key] = array('value' => 0, 'month_days' => $offsets['month_days']);

		switch ($graph)
		{
			case 0:
				$path = "hidden_payments_all";
				break;
			case 1:
				$path = "hidden_payments_net";
				break;
			case 2:
				$path = "hidden_payments_age";
				break;
			case 3:
				$path = "hidden_payments_sex";
				break;
		}

		$result = $Analytics->DB->get_filtered_cache($report['service'], $path, 0, $type, $report['date_begin'], $report['date_end']);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$data[$key]['value'] += $row['value'];
			}
		}

		$mau = array();

		$result = $Analytics->DB->get_filtered_cache($report['service'], "hidden_counters_mau", $graph, $type, $report['date_begin'], $report['date_end']);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$mau[$key] = $row['value'];
			}
		}

		while (list($key, $values) = each($data))
		{
			if (!isset($mau[$key]))
			{
				$data[$key]['value'] = 0;
				continue;
			}
			if ($mau[$key] == 0)
			{
				$data[$key]['value'] = 0;
				continue;
			}

			$data[$key]['value'] = round($values['value'] / $mau[$key], 2);
		}

		return $data;
	}

	public static function arppu_indicator($Analytics, $report, $periods, $graph, $type)
	{
		$data = array();

		while (list($key, $offsets) = each($periods))
			$data[$key] = array('value' => 0, 'month_days' => $offsets['month_days']);

		switch ($graph)
		{
			case 0:
				$path = "hidden_payments_all";
				break;
			case 1:
				$path = "hidden_payments_net";
				break;
			case 2:
				$path = "hidden_payments_age";
				break;
			case 3:
				$path = "hidden_payments_sex";
				break;
		}

		$result = $Analytics->DB->get_filtered_cache($report['service'], $path, 0, $type, $report['date_begin'], $report['date_end']);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$data[$key]['value'] += $row['value'];
			}
		}

		$paying = array();

		$result = $Analytics->DB->get_filtered_cache($report['service'], "hidden_paying_month", $graph, $type, $report['date_begin'], $report['date_end']);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$paying[$key] = $row['value'];
			}
		}

		if (date("Y.m") === date("Y.m", $report['date_end']))
		{
			$last_day = mktime(0, 0, 0, date("n"), date("d") - 1);

			$result = $Analytics->DB->get_filtered_cache($report['service'], "players_paying_month", $graph, ($graph == 0 ? 1 : $type), $last_day, $last_day + 86399);
			if ($row = $result->fetch())
			{
				if ($graph != 0)
				{
					$result = $Analytics->DB->get_filtered_cache($report['service'], "counters_mau", $graph, $type, $last_day, $last_day + 86399);
					if ($mau = $result->fetch())
						$row['value'] = $mau['value'] / 100 * $row['value'];
				}

				$data[0]['diff'] = $data[0]['value'] / date("d", time() - 86400) * date("t") / $row['value'];
			}
		}

		while (list($key, $values) = each($data))
		{
			if (!isset($paying[$key]))
			{
				$data[$key]['value'] = 0;
				continue;
			}
			if ($paying[$key] == 0)
			{
				$data[$key]['value'] = 0;
				continue;
			}

			$data[$key]['value'] = round($values['value'] / $paying[$key], 2);
		}

		return $data;
	}

	/**
	 * Платежи
	 */
	public function payments_all($cache_date)
	{
		$result = $this->DB->payments_all_hard($cache_date);
		$hard = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		$result = $this->DB->payments_all_soft($cache_date);
		$soft = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($hard, $soft);
	}

	public function payments_first($cache_date)
	{
		$result = $this->DB->payments_first_hard($cache_date, $cache_date);
		$hard = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		$result = $this->DB->payments_first_soft($cache_date, $cache_date);
		$soft = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($hard, $soft);
	}

	public function payments_repeated($cache_date)
	{
		$result = $this->DB->payments_repeated_hard($cache_date, $cache_date);
		$hard = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		$result = $this->DB->payments_repeated_soft($cache_date, $cache_date);
		$soft = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($hard, $soft);
	}

	public function payments_newbies($cache_date)
	{
		$result = $this->DB->payments_newbies_hard($cache_date);
		$hard = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		$result = $this->DB->payments_newbies_soft($cache_date);
		$soft = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($hard, $soft);
	}

	/**
	 * Финансы
	 */
	public function finance_revenue($cache_date)
	{
		$data = array();
		$data_all = array();

		$result = $this->DB->finance_arpu_net($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$net = $row['net'];
			$hard = $row['offer'] === "5" ? false : true;
			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;
			$row['sum'] = $revenue;

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$data[$date."-".$type]['value'] += $row['sum'];

			if (!isset($data_all[$date]))
				$data_all[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$data_all[$date]['value'] += $row['sum'];
		}

		$data = array_values($data);
		$data_all = array_values($data_all);

		return array($data_all, $data);
	}

	public function finance_arpu($cache_date)
	{
		list($all, $net) = $this->finance_arpu_type($cache_date, "net");
		$age = $this->finance_arpu_type($cache_date, "age");
		$sex = $this->finance_arpu_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	public function finance_arppu($cache_date)
	{
		list($all, $net) = $this->finance_arppu_type($cache_date, "net");
		$age = $this->finance_arppu_type($cache_date, "age");
		$sex = $this->finance_arppu_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	/**
	 * Счётчики
	 */
	public function counters_online($cache_date)
	{
		$all = array();

		$result = $this->DB->counters_online_all($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];

			$all[] = array('date' => $date, 'type' => 0, 'value' => $row['max']);
			$all[] = array('date' => $date, 'type' => 1, 'value' => $row['min']);
		}

		$result = $this->DB->counters_online_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_online_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $sex);
	}

	public function counters_dau($cache_date)
	{
		$result = $this->DB->counters_dau_all($cache_date);
		$all = $this->type_data($result);

		return array($all);
	}

	public function counters_wau($cache_date)
	{
		$result = $this->DB->counters_wau_all($cache_date);
		$all = $this->type_data($result);

		return array($all);
	}

	public function counters_mau($cache_date)
	{
		$result = $this->DB->counters_mau_all($cache_date);
		$all = $this->type_data($result);

		return array($all);
	}

	public function counters_mau_percent($cache_date)
	{
		$all = $this->counters_mau_percent_type($cache_date, "all");

		return array($all);
	}

	public function counters_wau_percent($cache_date)
	{
		$all = $this->counters_wau_percent_type($cache_date, "all");

		return array($all);
	}

	public function counters_sessions($cache_date)
	{
		$count = array();
		$time = array();

		$result = $this->DB->counters_sessions_count($cache_date);
		while ($row = $result->fetch())
			$count[$row['date']] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);

		$result = $this->DB->counters_sessions_time($cache_date);
		while ($row = $result->fetch())
		{
			if (!isset($count[$row['date']]))
				continue;

			$time[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => round($row['value'] / $count[$row['date']]['value'] / 60, 2));
		}

		$result = $this->DB->counters_dau_all($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			if (!isset($count[$date]))
				continue;

			$point = &$count[$date];

			$point['value'] = round($point['value'] / $value, 2);
			$point['full'] = true;
		}

		return array(array_values($count), $time);
	}

	/**
	 * Игроки
	 */
	public function players_new($cache_date)
	{
		$result = $this->DB->players_new_all($cache_date);
		$all = $this->type_data($result);

		return array($all);
	}

	public function players_retention($cache_date)
	{
		$returned = array();

		$result = $this->DB->players_retention_all();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];
			$days = $row['days'];

			if (!isset($returned[$date]))
				$returned[$date] = array('registered' => 0, '1d' => 0, '3d' => 0, '7d' => 0, '1d+' => 0);
			$point = &$returned[$date];

			$point['registered'] += $value;

			if ($days == 0)
				continue;
			if ($days >= 1)
				$point['1d+'] += $value;
		}

		$result = $this->DB->players_retention_1d();
		while ($row = $result->fetch())
		{
			$date = $row['registered'];
			$value = $row['value'];
			$days = $row['days'];

			if (!isset($returned[$date]))
				continue;
			$point = &$returned[$date];

			if ($days == 1)
				$point['1d'] = $value;
			else if ($days == 3)
				$point['3d'] = $value;
			else if ($days == 7)
				$point['7d'] = $value;
		}

		$data = array();
		while (list($date, $values) = each($returned))
		{
			$registered = &$values['registered'];

			$data[] = array('date' => $date, 'type' => 0, 'value' => round(($values['1d'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 1, 'value' => round(($values['3d'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 2, 'value' => round(($values['7d'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 3, 'value' => round(($values['1d+'] * 100) / $registered, 2));
		}

		return array($data);
	}

	public function players_paying_day($cache_date)
	{
		list($all, $net) = $this->players_paying_day_type($cache_date, "net");
		$age = $this->players_paying_day_type($cache_date, "age");
		$sex = $this->players_paying_day_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	public function players_paying_month($cache_date)
	{
		list($all, $net) = $this->players_paying_month_type($cache_date, "net");
		$age = $this->players_paying_month_type($cache_date, "age");
		$sex = $this->players_paying_month_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	public function players_loading($cache_date)
	{
		$result = $this->DB->players_loading($cache_date);

		return array($this->type_data($result));
	}

	public function players_matches($cache_date)
	{
		$data = array();

		$result = $this->DB->players_matches($cache_date);
		while ($row = $result->fetch())
		{
			if ($row['data'] < 9)
				$data[1][] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
			else
				$data[0][] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
		}

		$data[2] = array();
		$result = $this->DB->players_matches_score_diff($cache_date);
		while ($row = $result->fetch())
		{
			$type = $this->score_diff_groups($row['data']);
			$key = $row['date']."-".$type;

			if (!isset($data[2][$key]))
				$data[2][$key] = array('date' => $row['date'], 'type' => $type, 'value' => 0);

			$data[2][$key]['value'] += $row['value'];
		}

		$data[2] = array_values($data[2]);

		return $data;
	}

	public function players_matches_leave($cache_date)
	{
		$data = array();

		$data[0] = array();
		$data[1] = array();
		$result = $this->DB->players_matches_leave_time($cache_date);
		while ($row = $result->fetch())
		{
			$order = $row['data'] & 0xFFFF;
			$time = $row['data'] >> 16;

			if ($order > 2)
				continue;

			$data[$order - 1][] = array('date' => $row['date'], 'type' => $time, 'value' => $row['value']);
		}

		$data[2] = array();
		$count = count($this->matches_leave_size);
		$result = $this->DB->players_matches_leave_size($cache_date);
		while ($row = $result->fetch())
		{
			if ($row['data'] > $count)
				continue;

			$data[2][] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
		}

		return $data;
	}

	public function players_levels($cache_date)
	{
		$data = array();

		$result = $this->DB->players_levels();
		while ($row = $result->fetch())
		{
			$type = $this->players_levels_groups($row['level']);
			$key = $row['date']."-".$type;

			if (!isset($data[$key]))
				$data[$key] = array('date' => $row['date'], 'type' => $type, 'value' => 0);

			$data[$key]['value'] += $row['value'];
		}

		return array(array_values($data));
	}


	/**
	 * Скрытый отчёт, не выводится (все платежи). Собираются данные для виджетов ARPU и ARPPU на странице общего отчёта
	 *
	 * @param string $cache_date Дата Y-m-d, с которой начинать сбор данных
	 * @return array Готовый массив данных для записи в базу
	 */
	public function hidden_payments_all($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_net($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$net = $row['data'];
			$hard = $row['offer'] === "5" ? false : true;

			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;

			if (!isset($data[$date]))
				$data[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$data[$date]['value'] += $revenue;
		}

		$data = array_values($data);

		return array($data);
	}

	/**
	 * Скрытый отчёт, не выводится (платежи по сетям). Собираются данные для виджетов ARPU и ARPPU на странице общего отчёта
	 *
	 * @param string $cache_date Дата Y-m-d, с которой начинать сбор данных
	 * @return array Готовый массив данных для записи в базу
	 */
	public function hidden_payments_net($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_net($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$hard = $row['offer'] === "5" ? false : true;
			$key = $date."-".$type;
			$revenue = $this->get_revenue($row['sum'], $type, $hard);

			if ($revenue === false)
				continue;

			if (!isset($data[$key]))
				$data[$key] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$key]['value'] += $revenue;
		}

		return array($data);
	}

	/**
	 * Скрытый отчёт, не выводится (платежи по возрасту). Собираются данные для виджетов ARPU и ARPPU на странице общего отчёта
	 *
	 * @param string $cache_date Дата Y-m-d, с которой начинать сбор данных
	 * @return array Готовый массив данных для записи в базу
	 */
	public function hidden_payments_age($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_age($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$net = $row['net'];
			$type = $this->get_age_index($row['data']);
			$hard = $row['offer'] === "5" ? false : true;

			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$data[$date."-".$type]['value'] += $revenue;
		}

		$data = array_values($data);

		return array($data);
	}

	/**
	 * Скрытый отчёт, не выводится (платежи по полу). Собираются данные для виджетов ARPU и ARPPU на странице общего отчёта
	 *
	 * @param string $cache_date Дата Y-m-d, с которой начинать сбор данных
	 * @return array Готовый массив данных для записи в базу
	 */
	public function hidden_payments_sex($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_sex($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$net = $row['net'];
			$type = $row['data'];
			$hard = $row['offer'] === "5" ? false : true;

			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$data[$date."-".$type]['value'] += $revenue;
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_counters_mau($cache_date)
	{
		$all = array();
		$net = array();

		$cache_time = mktime(0, 0, 0, date("m"), 1, date("Y"));

		$result = $this->DB->hidden_counters_mau_net($cache_time);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$net[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);

			if (!isset($all[$date]))
				$all[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$all[$date]['value'] += $row['value'];
		}

		$age = array();

		$result = $this->DB->hidden_counters_mau_age($cache_time);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_age_index($row['data']);

			if (!isset($age[$date."-".$type]))
				$age[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$age[$date."-".$type]['value'] += $row['value'];
		}

		$result = $this->DB->hidden_counters_mau_sex($cache_time);
		$sex = $this->type_data($result);

		$all = array_values($all);
		$age = array_values($age);

		return array($all, $net, $age, $sex);
	}

	public function hidden_paying_month($cache_date)
	{
		list($all, $net) = $this->hidden_paying_month_type($cache_date, "net");
		$age = $this->hidden_paying_month_type($cache_date, "age");
		$sex = $this->hidden_paying_month_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	public function apipath_common($cache_date)
	{
		return array();
	}

	/**
	 * Вспомогательные методы
	 */
	private function simple_data($result, $legend)
	{
		$data = array();
		while ($row = $result->fetch())
		{
			reset($legend);
			while (list($type, $field) = each($legend))
				$data[] = array('date' => $row['date'], 'type' => $type, 'value' => $row[$field]);
		}

		return $data;
	}

	private function type_data($result, $monthly = false)
	{
		$data = array();

		while ($row = $result->fetch())
		{
			if ($monthly === true)
				$date = date("Y-m-t", strtotime($row['year']."-".sprintf("%'.02d", $row['month'])."-01"));
			else
				$date = $row['date'];

			$data[] = array('date' => $date, 'type' => $row['data'], 'value' => $row['value']);
		}

		return $data;
	}

	private function finance_arpu_type($cache_date, $key)
	{
		$data = array();
		$data_all = array();

		$result = $this->DB->{"finance_arpu_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$net = $row['net'];
			$hard = $row['offer'] === "5" ? false : true;

			if ($key == "age")
				$type = $this->get_age_index($type);

			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;

			$row['sum'] = $revenue;

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0, 'full' => false);
			$point = &$data[$date."-".$type];

			$point['value'] += $row['sum'];

			if (!isset($data_all[$date]))
				$data_all[$date] = array('date' => $date, 'type' => 0, 'value' => 0, 'full' => false);
			$data_all[$date]['value'] += $row['sum'];
		}

		$result = $this->DB->{"counters_dau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			if (!isset($data[$date."-".$type]))
				continue;
			$point = &$data[$date."-".$type];

			$point['value'] = round($point['value'] / $value, 2);
			$point['full'] = true;
		}

		$net = array();
		while (list(, $values) = each($data))
		{
			if (!$values['full'])
				continue;

			$net[] = $values;
		}

		if ($key != "net")
			return $net;

		$result = $this->DB->counters_dau_all($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			if (!isset($data_all[$date]))
				continue;
			$point = &$data_all[$date];

			$point['value'] = round($point['value'] / $value, 2);
			$point['full'] = true;
		}

		$all = array();
		while (list(, $values) = each($data_all))
		{
			if (!$values['full'])
				continue;

			$all[] = $values;
		}

		return array($all, $net);
	}

	private function finance_arppu_type($cache_date, $key)
	{
		$data = array();

		$result = $this->DB->{"finance_arppu_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$net = $row['net'];
			$hard = $row['offer'] === "5" ? false : true;

			if ($key == "age")
				$type = $this->get_age_index($type);

			$revenue = $this->get_revenue($row['sum'], $net, $hard);

			if ($revenue === false)
				continue;
			$row['sum'] = $revenue;

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'sum' => 0, 'count' => 0);
			$point = &$data[$date."-".$type];

			$point['sum'] += $row['sum'];
			$point['count'] += $row['count'];
		}

		$data = array_values($data);

		while (list($i, $values) = each($data))
			$data[$i]['value'] = round($values['sum'] / $values['count'], 2);

		if ($key != "net")
			return $data;

		$all = array();

		reset($data);
		while (list(, $values) = each($data))
		{
			$date = $values['date'];

			if (!isset($all[$date]))
				$all[$date] = array('date' => $date, 'type' => 0, 'sum' => 0, 'count' => 0);

			$all[$date]['sum'] += $values['sum'];
			$all[$date]['count'] += $values['count'];
		}

		$all = array_values($all);

		while (list($i, $values) = each($all))
			$all[$i]['value'] = round($values['sum'] / $values['count'], 2);

		return array($all, $data);
	}

	private function get_age_index($age)
	{
		if ($age >= 41)
			return 7;
		if ($age >= 36)
			return 6;
		if ($age >= 31)
			return 5;
		if ($age >= 26)
			return 4;
		if ($age >= 22)
			return 3;
		if ($age >= 18)
			return 2;
		if ($age >= 14)
			return 1;
		if ($age > 0)
			return 0;
		return 99;
	}

	private function players_paying_month_type($cache_date, $key)
	{
		$data = array();

		$result = $this->DB->{"players_paying_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			if (!isset($data[$date][$type]))
				$data[$date][$type] = array();
			$point = &$data[$date][$type];

			$point[] = $row['net_id'];
		}

		$last_date = false;

		$values = array();
		$inserts = array();
		$counts = array();
		$counts_net = array();
		$merged = array();

		while (list($date, $types) = each($data))
		{
			if (!isset($counts[$date]))
				$counts[$date] = 0;

			if ($last_date === false)
				$last_date = $date;

			$days = $this->date_diff($date, $last_date);

			$last_date = $date;

			while (list($type, $ids) = each($types))
			{
				if (!isset($values[$type]))
					$values[$type] = array();
				if (!isset($inserts[$type]))
					$inserts[$type] = array();
				if (!isset($merged[$type]))
					$merged[$type] = array();

				for ($i = 1; $i < $days; $i++)
				{
					$values[$type][] = array();
					$inserts[$type][] = 0;
				}

				$values[$type][] = $ids;
				$inserts[$type][] = count($ids);
				array_splice($merged[$type], -1, 0, $ids);

				$count = count($values[$type]);
				while ($count > 30)
				{
					$inserted = $inserts[$type][0];

					array_shift($values[$type]);
					array_shift($inserts[$type]);
					array_splice($merged[$type], 0, $inserted);

					$count--;
				}

				$unique = array_unique($merged[$type]);
				$count = count($unique);

				$counts[$date] += $count;
				$counts_net[$date."-".$type] = $count;
			}
		}

		$net = array();

		$result = $this->DB->{"counters_mau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if (!isset($counts_net[$date."-".$type]) || $value == 0)
				continue;
			$count = &$counts_net[$date."-".$type];

			$net[] = array('date' => $date, 'type' => $type, 'value' => round(($count * 100) / $value, 2));
		}


		if ($key != "net")
			return $net;

		$all = array();

		$result = $this->DB->counters_mau_all($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if (!isset($counts[$date]) || $value == 0)
				continue;
			$count = &$counts[$date];

			$all[] = array('date' => $date, 'type' => 0, 'value' => round(($count * 100) / $value, 2));
			$all[] = array('date' => $date, 'type' => 1, 'value' => $count);
		}

		return array($all, $net);
	}

	private function date_diff($date1, $date2)
	{
		$date1 = date_parse($date1);
		$date2 = date_parse($date2);

		$time1 = gmmktime(0, 0, 0, $date1['month'], $date1['day'], $date1['year']);
		$time2 = gmmktime(0, 0, 0, $date2['month'], $date2['day'], $date2['year']);

		return (($time1 - $time2) / 86400);
	}

	private function hidden_paying_month_type($cache_date, $key)
	{
		$counts = array();
		$counts_net = array();

		$result = $this->DB->{"hidden_paying_month_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			if (!isset($counts_net[$date."-".$type]))
				$counts_net[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($counts[$date]))
				$counts[$date] = array('date' => $date, 'type' => 0, 'value' => 0);

			$counts[$date]['value'] += $row['value'];
			$counts_net[$date."-".$type]['value'] += $row['value'];
		}

		$counts_net = array_values($counts_net);

		if ($key != "net")
			return $counts_net;

		$counts = array_values($counts);

		return array($counts, $counts_net);
	}

	private function counters_mau_percent_type($cache_date, $key)
	{
		$dau = array();

		$result = $this->DB->{"counters_dau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($dau[$date]))
				$dau[$date] = array();

			$dau[$date][$type] = $row['value'];
		}

		$data = array();

		$result = $this->DB->{"counters_mau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if (!isset($dau[$date][$type]) || $value == 0)
				continue;
			$day = &$dau[$date][$type];

			$data[] = array('date' => $date, 'type' => $type, 'value' => round(($day * 100) / $value, 2));
		}

		return $data;
	}

	private function counters_wau_percent_type($cache_date, $key)
	{
		$dau = array();

		$result = $this->DB->{"counters_dau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($dau[$date]))
				$dau[$date] = array();

			$dau[$date][$type] = $row['value'];
		}

		$data = array();

		$result = $this->DB->{"counters_wau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if (!isset($dau[$date][$type]) || $value == 0)
				continue;
			$day = &$dau[$date][$type];

			$data[] = array('date' => $date, 'type' => $type, 'value' => round(($day * 100) / $value, 2));
		}

		return $data;
	}

	private function players_paying_day_type($cache_date, $key)
	{
		$data = array();
		$data_all = array();

		$result = $this->DB->{"finance_arppu_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = 0;

			$data[$date."-".$type] += $row['count'];

			if (!isset($data_all[$date]))
				$data_all[$date] = 0;

			$data_all[$date] += $row['count'];
		}

		$net = array();

		$result = $this->DB->{"counters_dau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if (!isset($data[$date."-".$type]) || $value == 0)
				continue;
			$payments = &$data[$date."-".$type];

			$net[] = array('date' => $date, 'type' => $type, 'value' => round(($payments * 100) / $value, 2));
		}

		if ($key != "net")
			return $net;

		$all = array();

		$result = $this->DB->counters_dau_all($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if (!isset($data_all[$date]) || $value == 0)
				continue;
			$payments = &$data_all[$date];

			$all[] = array('date' => $date, 'type' => 0, 'value' => round(($payments * 100) / $value, 2));
			$all[] = array('date' => $date, 'type' => 1, 'value' => $payments);
		}

		return array($all, $net);
	}

	private function players_levels_groups($data)
	{
		reset($this->players_levels);
		while (list($key, $val) = each($this->players_levels))
		{
			if ($data < $key)
				return $key;
		}

		return 0;
	}

	private function score_diff_groups($data)
	{
		reset($this->matches_score_diff_groups);
		while (list($key, $val) = each($this->matches_score_diff_groups))
		{
			if ($data < $key)
				return $key;
		}

		return 0;
	}

	/**
	 * Перевод игровых валют в доход в рублях
	 *
	 * @param string|int $sum Сумма платежей в игровой валюте
	 * @param string|int $net Идентификатор источника платежа (ВКонтакте, например)
	 * @param bool $hard Флаг - жесткая (Кристаллы) или мягкая (Баксы) валюта
	 * @return bool|float Возвращает false, если нет соответствующего идентификатора источника, либо сумму платежей в рублях
	 */
	private function get_revenue($sum, $net, $hard = true)
	{
		if ($hard)
			$revenue = $this->revenue_hard;
		else
			$revenue = $this->revenue_soft;

		if (!isset($revenue[$net]))
			return false;

		return round($sum * $revenue[$net], 2);
	}
}

?>