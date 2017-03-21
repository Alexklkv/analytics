<?php

/**
 * Реализует отчёты Трагедии Белок Unity Russian
 *
 * @uses DatabaseInterface
 * @uses ObjectLog
 *
 * @version 1.0.0
 */
class ObjectSquirrelsur extends Object implements DatabaseInterface
{
	/**
	 * Идентификатор проекта в системе аналитики
	 */
	private static $service_id;

	const Offers = "2";
	const OfferNone = 0;
	const OfferBox = 1;

	private $networks = array(0 => "Вконтакте", 1 => "МойМир", 4 => "Одноклассники", 5 => "Facebook", 32 => "StandAlone");
	private $ages = array(3 => "19-24", 4 => "25-35", 5 => "36+", 0 => "1-10", 1 => "11-14", 2 => "15-18", 99 => "Не задан");
	private $sex = array(2 => "Мужской", 1 => "Женский", 0 => "Не задан");
	private $devices = array(0 => "Неизвестно", 1 => "Web", 2 => "Apple", 3 => "Android");
	private $goods = array(-1 => "Сумма", 1 => "Одежда", 2 => "Энергия мал.", 3 => "Энергия бол.", 4 => "Мана мал.", 5 => "Мана бол.", 6 => "Шаман", 7 => "Орехи", 8 => "Локация", 10 => "Заяц", 11 => "Монетки", 14 => "Предметы шамана", 15 => "Набор вещей", 16 => "Подписка", 17 => "Клановая комната", 18 => "Дракон", 19 => "Быстрая покупка", 22 => "Воскрешение");
	private $payments = array(20 => "20", 30 => "30", 100 => "100", 300 => "300", 0 => "Другие", -1 => "Сундук", -2 => "Офферы");
	private $periods = array(0 => "0-1d", 2 => "2-7d", 8 => "8-14d", 15 => "15-30d", 31 => "31d+");
	private $locations = array(0 => "Летающие острова", 1 => "Снежные хребты", 2 => "Топи", 4 => "Аномальная зона", 5 => "Дикие земли", 7 => "Обучение", 9 => "Испытания", 10 => "Битва", 13 => "Шторм");

	private $levels = array(
		1, 5, 10, 30, 80, 135, 202, 281, 372, 475,
		590, 717, 856, 1007, 1178, 1374, 1600, 1861, 2162, 2508,
		2904, 3355, 3866, 4442, 5088, 5809, 6610, 7501, 8487, 9573,
		10764, 12065, 13481, 15393, 17973, 21457, 26160, 32510, 41081, 52653,
		68275, 89365, 117836, 156271, 208159, 278208, 372775, 500439, 672786, 905454
	);
	private $packages = array(
		138 => "Комплект Стиляги", 86 => "Комплект Женщины Кошки", 142 => "Комплект Дарта Вейдера", 150 => "Комплект джентельмена",
		146 => "Свадебный комплект", 148 => "Комплект Гопника", 149 => "Гламурный комплект", 137 => "Женский комплект Полицейского",
		140 => "Комплект Легионера", 143 => "Комплект Тора", 136 => "Мужской комплект Полицейского", 139 => "Комплект Росомахи",
		147 => "Советский комплект", 119 => "Комплект Самурая", 141 => "Комплект Человека Паука", 91 => "Комплект Железного Человека",
		110 => "Комплект Викинга", 113 => "Комплект Ниндзя", 111 => "Комплект Скелета", 135 => "Комплект Зайца НеСудьбы",
		126 => "Комплект Деда Мороза", 126 => "Комплект Снегурочки", 130 => "Комплект Ангела", 133 => "Комплект Архангела",
		156 => "Комплект Истинной Леди", 164 => "Комплект Наполеона", 169 => "Эльфийские одеяния", 174 => "Костюм Локи"
	);
	private $shaman_items = array(-1 => "Сумма", 0 => "Балка", 2 => "Ящик", 8 => "Гиря", 9 => "Батут", 11 => "Ядро", 12 => "Синий портал", 13 => "Красный портал", 16 => "Удалятор", 17 => "Шар");

	private $cost = 0.125;
	private $currency = "$";

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
			'payments_all'			=> "SELECT DATE(`time`) as `date`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`",
			'payments_offer'		=> "SELECT DATE(`time`) as `date`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` >0 AND `time` >= @s GROUP BY `date`",
			'payments_candles'		=> "SELECT DATE(`time`) as `date`, HOUR(`time`) as `hour`, SUM(`balance`) as `sum` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= DATE_SUB(@s, INTERVAL 1 DAY) GROUP BY `date`, `hour`",
			'payments_specific'		=> "SELECT DATE(`time`) as `date`, `offer`, `balance`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `time` >= @s GROUP BY `date`, `offer`, `balance`",
			'payments_net'			=> "SELECT DATE(`time`) as `date`, `type` as `data`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'payments_device'		=> "SELECT DATE(`time`) as `date`, `device` as `data`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'payments_age'			=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`",
			'payments_sex'			=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`",
			'payments_first'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 ON p2.`type` = p1.`type` AND p2.`net_id` = p1.`net_id` AND p2.`time` = p1.`time` WHERE p2.`offer` NOT IN(".self::Offers.") GROUP BY `date`",
			'payments_repeated'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 ON p2.`type` = p1.`type` AND p2.`net_id` = p1.`net_id` AND p2.`time` != p1.`time` WHERE p2.`offer` NOT IN(".self::Offers.") GROUP BY `date`",
			'payments_day_first'		=> "SELECT pl.`register_time` as `date`, pm.`net_id`, DATEDIFF(pm.`time`, pl.`register_time`) as `days`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` > '2000-01-01' GROUP BY `date`, `days` ORDER BY `date` ASC",
			'payments_day_next'		=> "SELECT DATE(`time`) as `date`, `net_id`, `type`, `balance`, `bonus` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") ORDER BY `time` ASC",
			'payments_newbies'		=> "SELECT DATE(pm.`time`) as `date`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND TIMESTAMPDIFF(HOUR, pl.`register_time`, pm.`time`) < 24 AND pm.`time` >= @s GROUP BY `date`",

			'finance_arpu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'finance_arpu_device'		=> "SELECT DATE(`time`) as `date`, `device` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arpu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arpu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",

			'finance_arppu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(DISTINCT `net_id`) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'finance_arppu_device'		=> "SELECT DATE(`time`) as `date`, `device` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(DISTINCT `net_id`) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arppu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arppu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",

			'buyings_common'		=> "SELECT `time` as `date`, `good_id` as `data`, SUM(`coins`) as `coins`, SUM(`nuts`) as `nuts`, COUNT(*) as `count` FROM `buyings` WHERE `time` >= @s GROUP BY `date`, `good_id`",
			'buyings_packages'		=> "SELECT `time` as `date`, `data`, SUM(`coins`) as `coins`, COUNT(*) as `count` FROM `buyings` WHERE `time` >= @s AND `good_id` = 1 GROUP BY `date`, `data`",
			'buyings_shaman'		=> "SELECT `time` as `date`, `data`, SUM(`nuts`) as `nuts`, COUNT(*) as `count` FROM `buyings` WHERE `time` >= @s AND `good_id` = 14 GROUP BY `date`, `data`",

			'counters_dau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 18 AND `date` >= @s",
			'counters_dau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 35 AND `date` >= @s",
			'counters_dau_device'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 1002 AND `date` >= @s",
			'counters_dau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 45 AND `date` >= @s",
			'counters_dau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 54 AND `date` >= @s",
			'counters_dau_locations'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 139 AND `date` >= @s",

			'counters_wau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 19 AND `date` >= @s",
			'counters_wau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 98 AND `date` >= @s",
			'counters_wau_device'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 1004 AND `date` >= @s",
			'counters_wau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 99 AND `date` >= @s",
			'counters_wau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 100 AND `date` >= @s",

			'counters_mau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 20 AND `date` >= @s",
			'counters_mau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 52 AND `date` >= @s",
			'counters_mau_device'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 1003 AND `date` >= @s",
			'counters_mau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 53 AND `date` >= @s",
			'counters_mau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 55 AND `date` >= @s",

			'counters_online_all'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 0 AND `time` >= @s GROUP BY `date`",
			'counters_online_net'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 1 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_device'	=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 1000 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_age'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 2 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_sex'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 109 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_location'	=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 41 AND `time` >= @s GROUP BY `date`, `data`",

			'counters_coins'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 31 AND `date` >= @s",
			'counters_nuts'			=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 40 AND `date` >= @s",
			'counters_magic'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 12 AND `data` < 10 AND `date` >= @s",

			'counters_active_all'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 105 AND c2.`type` = 101 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_net'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 106 AND c2.`type` = 102 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_device'	=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 1006 AND c2.`type` = 1005 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_age'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 107 AND c2.`type` = 103 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_sex'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 108 AND c2.`type` = 104 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",

			'players_new_all'		=> "SELECT `register_time` as `date`, 0 as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`",
			'players_new_net'		=> "SELECT `register_time` as `date`, `type` as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_device'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 1007 AND `date` >= @s",
			'players_new_age'		=> "SELECT `register_time` as `date`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL `bday` SECOND), NOW())) as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_sex'		=> "SELECT `register_time` as `date`, `sex` as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",

			'players_retention_all'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= '2000-01-01' AND `logout_time` != 0 GROUP BY `date`, `days`",
			'players_retention_net'		=> "SELECT `register_time` as `date`, `type` as `data`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= '2000-01-01' AND `logout_time` != 0 GROUP BY `date`, `data`, `days`",

			'players_paying_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `net_id` FROM `payments` FORCE INDEX(`time`) WHERE `offer` NOT IN(".self::Offers.") AND `time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",

			'players_levels'		=> "SELECT `register_time` as `date`, `experience` as `data` FROM `players` WHERE `register_time` > '2000-01-01'"
		);
	}

	public function get_jobs()
	{
		return array(
			3 => "counters_online"
		);
	}

	public function get_categories()
	{
		return array(
			'payments'	=> "Платежи",
			'finance'	=> "Финансы",
			'buyings'	=> "Покупки",
			'counters'	=> "Счётчики",
			'players'	=> "Игроки"
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
					'description'	=> "Количество и сумма платежей",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Платежи по офферам",
							'legend'	=> array(0 => "Сумма", 1 => "По офферам, количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				),
				'candles' => array(
					'id'		=> $id++,
					'type'		=> "candles",
					'title'		=> "Все платежи (свечи)",
					'description'	=> "Сумма платежей с минимальным и максимальным значениями в час",
					'legend'	=> "Сумма"
				),
				'specific' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по суммам",
					'description'	=> "Количество и сумма платежей, с разделением по суммам",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->payments
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->payments
						)
					)
				),
				"-",
				'net' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по сетям",
					'description'	=> "Количество и сумма платежей для каждой соц. сети",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->networks
						)
					),
					'params'	=> array(
						'legend_skip'	=> true
					)
				),
				'device' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по платформам",
					'description'	=> "Количество и сумма платежей для каждой платформы",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->devices
						)
					)
				),
				'age' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по возрасту",
					'description'	=> "Количество и сумма платежей для каждой возрастной группы",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->ages
						)
					)
				),
				'sex' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по полу",
					'description'	=> "Количество и сумма платежей для каждого пола",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->sex
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
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					),
					'cache'		=> false
				),
				'repeated' => array(
					'id'		=> $id++,
					'title'		=> "Повторные платежи",
					'description'	=> "Количество и сумма платежей, сделанных игроками повторно",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					),
					'cache'		=> false
				),
				'newbies' => array(
					'id'		=> $id++,
					'title'		=> "Платежи новичков",
					'description'	=> "Количество и сумма платежей, сделанных игроками в течении суток после регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1")
						)
					)
				),
				"-",
				'day_first' => array(
					'id'		=> $id++,
					'type'		=> "filled",
					'title'		=> "Время первого платежа",
					'description'	=> "Разеделение первых платежей по дням, прошедших после регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> $this->periods
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%"
					),
					'cache'		=> false
				),
				'day_next' => array(
					'id'		=> $id++,
					'title'		=> "Время между платежами",
					'description'	=> "Количество и сумма платежей, совершенных через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->periods
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->periods
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					),
					'cache'		=> false
				)
			),
			'finance' => array(
				'arpu' => array(
					'id'		=> $id++,
					'title'		=> "ARPU",
					'description'	=> "Средняя сумма, потраченная всеми игроками за день",
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
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'show_sums'	=> false
					)
				),
				'arppu' => array(
					'id'		=> $id++,
					'title'		=> "ARPPU",
					'description'	=> "Средняя сумма, потраченная всеми игроками за день",
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
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'show_sums'	=> false
					)
				)
			),
			'buyings' => array(
				'coins' => array(
					'id'		=> $id++,
					'title'		=> "За монеты",
					'description'	=> "Количество и сумма покупок за монеты",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->goods
						)
					),
					'params'	=> array(
						'legend_skip'	=> true,
						'hide_empty'	=> true
					)
				),
				'nuts' => array(
					'id'		=> $id++,
					'title'		=> "За орехи",
					'description'	=> "Количество и сумма покупок за орехи",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->goods
						)
					),
					'params'	=> array(
						'legend_skip'	=> true,
						'hide_empty'	=> true
					)
				),
				"-",
				'shaman' => array(
					'id'		=> $id++,
					'title'		=> "Предметы шамана",
					'description'	=> "Количество и сумма покупок предметов шамана за орешки",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->shaman_items
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->shaman_items
						)
					),
					'params'	=> array(
						'legend_skip'	=> true,
						'hide_empty'	=> true
					)
				),
				'packages' => array(
					'id'		=> $id++,
					'type'		=> "round",
					'title'		=> "Комплекты",
					'description'	=> "Количество и сумма покупок комплектов за монеты",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->packages
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->packages,
							'inherit_order'	=> 0
						)
					),
					'params'	=> array(
						'show_legend'	=> false
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
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'show_sums'	=> false
					)
				),
				'online_location' => array(
					'id'		=> $id++,
					'title'		=> "Онлайн по локациям",
					'description'	=> "Максимальный и минимальный онлайн по локациям",
					'graphs'	=> array(
						array(
							'title'		=> "Максимальный",
							'legend'	=> $this->locations
						),
						array(
							'title'		=> "Минимальный",
							'legend'	=> $this->locations
						)
					),
					'params'	=> array(
						'legend_skip'	=> true,
						'show_sums'	=> false
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
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "По локациям",
							'legend'	=> $this->locations
						)
					),
					'params'	=> array(
						'show_sums'	=> false
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
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'show_sums'	=> false
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
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'show_sums'	=> false
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
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
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
						'value_append'	=> "%"
					)
				),
				"-",
				'coins' => array(
					'id'		=> $id++,
					'title'		=> "Баланс монеток",
					'description'	=> "Количество полученных и потраченных игроками монеток",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				'nuts' => array(
					'id'		=> $id++,
					'title'		=> "Баланс орехов",
					'description'	=> "Количество полученных и потраченных игроками орехов",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				"-",
				'magic' => array(
					'id'		=> $id++,
					'title'		=> "Использование магии",
					'description'	=> "Количество использований магии за сутки",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(-1 => "Сумма", 0 => "Невидимка", 1 => "Белка-молния", 2 => "Высокий прыжок", 3 => "Двойной прыжок", 4 => "Белка-летяга", 5 => "Цепкие лапки", 6 => "Белка-варвар", 7 => "Реинкарнация", 8 => "Телепортация", 9 => "Малыш")
						)
					)
				),
				"-",
				'active' => array(
					'id'		=> $id++,
					'title'		=> "Активность",
					'description'	=> "Среднее время и количество игровых сессий",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> array(0 => "Время, минут")
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> array(0 => "Сессии")
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					)
				),
				'active_net' => array(
					'id'		=> $id++,
					'title'		=> "Активность по сетям",
					'description'	=> "Среднее время и количество игровых сессий по соц. сетям",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					)
				),
				'active_device' => array(
					'id'		=> $id++,
					'title'		=> "Активность по платформам",
					'description'	=> "Среднее время и количество игровых сессий по платформам",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> $this->devices
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					)
				),
				'active_age' => array(
					'id'		=> $id++,
					'title'		=> "Активность по возрасту",
					'description'	=> "Среднее время и количество игровых сессий по возрасту",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> $this->ages
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					)
				),
				'active_sex' => array(
					'id'		=> $id++,
					'title'		=> "Активность по полу",
					'description'	=> "Среднее время и количество игровых сессий по полу",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'show_sums'	=> false
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
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks,
							'legend_skip'	=> true
						),
						array(
							'title'		=> "По платформам",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "По возрасту",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						)
					)
				),
				"-",
				'retention' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения",
					'description'	=> "Возвращение игроков через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "%",
							'legend'	=> array(1 => "1d+", 2 => "2d+", 7 => "7d+", 30 => "30d+")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%"
					),
					'cache'		=> false
				),
				'retention_net' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по сетям",
					'description'	=> "Возвращение игроков через N дней по соц. сетям",
					'graphs'	=> array(
						array(
							'title'		=> "1d+",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->networks
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'legend_skip'	=> true
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
							'legend'	=> $this->networks,
							'legend_skip'	=> true
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
						'show_sums'	=> false
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
							'legend'	=> $this->networks,
							'legend_skip'	=> true
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
						'value_append'	=> "%"
					)
				),
				'levels' => array(
					'id'		=> $id++,
					'type'		=> "filled",
					'title'		=> "Уровни игроков",
					'description'	=> "Разделение игроков по уровням",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "0", 1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5", 6 => "6", 7 => "7", 8 => "8", 9 => "9", 10 => "10+")
						)
					),
					'params'	=> array(
						'show_sums'	=> false
					),
					'cache'		=> false
				)
			)
		);
	}

	/**
	 * Платежи
	 */
	public function payments_all($cache_date)
	{
		$result = $this->DB->payments_all($cache_date);
		$data = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		$result = $this->DB->payments_offer($cache_date);
		$offer = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($data, $offer);
	}

	public function payments_candles($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_candles($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$hour = $row['hour'];

			if (!isset($data[$date]))
				$data[$date] = array();
			$data[$date][$hour] = $row;
		}

		$open = 0;
		$first = true;

		$candles = array();
		while (list($date, $hours) = each($data))
		{
			$close = 0;

			$sums = array();
			while (list($hour, $values) = each($hours))
			{
				$close += $values['sum'];
				$sums[] = $values['sum'];
			}

			sort($sums);

			$count = count($sums);
			if ($count > 4)
			{
				$sums = array_slice($sums, 2, $count - 4);
				$count -= 4;
			}

			$low = 0;
			$high = 0;

			$average_count = $count * 1 / 2;

			if ($average_count != 0)
			{
				for ($i = 0; $i < $average_count; $i++)
					$low += $sums[$i];
				$low = intval($low * 24 / $average_count);

				for ($i = $count - 1; $i >= $average_count; $i--)
					$high += $sums[$i];
				$high = intval($high * 24 / $average_count);
			}
			else
			{
				$low = $close;
				$high = $close;
			}

			if ($first)
				$open = $close;

			$days = $this->date_diff($date, $cache_date);
			if ($days >= 0)
			{
				$candles[] = array('date' => $date, 'type' => 0, 'value' => $open);
				$candles[] = array('date' => $date, 'type' => 1, 'value' => $close);
				$candles[] = array('date' => $date, 'type' => 2, 'value' => $low);
				$candles[] = array('date' => $date, 'type' => 3, 'value' => $high);
			}

			$open = $close;
			$first = false;
		}

		return array($candles);
	}

	public function payments_specific($cache_date)
	{
		$data = array('sum' => array(), 'count' => array());
		$others = array();
		$offers = array();

		$result = $this->DB->payments_specific($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$offer = $row['offer'];
			$balance = $row['balance'];

			switch ($offer)
			{
				case self::OfferBox:
					$data['sum'][] = array('date' => $date, 'type' => -1, 'value' => $row['sum']);
					$data['count'][] = array('date' => $date, 'type' => -1, 'value' => $row['count']);
					break;
				case self::OfferNone:
					if (isset($this->payments[$balance]))
					{
						$data['sum'][] = array('date' => $date, 'type' => $balance, 'value' => $row['sum']);
						$data['count'][] = array('date' => $date, 'type' => $balance, 'value' => $row['count']);
						break;
					}

					if (!isset($others[$date]))
						$others[$date] = array('sum' => 0, 'count' => 0);

					$others[$date]['sum'] += $row['sum'];
					$others[$date]['count'] += $row['count'];
					break;
				default:
					if (!isset($offers[$date]))
						$offers[$date] = array('sum' => 0, 'count' => 0);

					$offers[$date]['sum'] += $row['sum'];
					$offers[$date]['count'] += $row['count'];
					break;
			}
		}

		while (list($date, $values) = each($others))
		{
			$data['sum'][] = array('date' => $date, 'type' => 0, 'value' => $values['sum']);
			$data['count'][] = array('date' => $date, 'type' => 0, 'value' => $values['count']);
		}

		while (list($date, $values) = each($offers))
		{
			$data['sum'][] = array('date' => $date, 'type' => -2, 'value' => $values['sum']);
			$data['count'][] = array('date' => $date, 'type' => -2, 'value' => $values['count']);
		}

		return array($data['sum'], $data['count']);
	}

	public function payments_net($cache_date)
	{
		return $this->payments_type($cache_date, "net");
	}

	public function payments_device($cache_date)
	{
		return $this->payments_type($cache_date, "device");
	}

	public function payments_age($cache_date)
	{
		return $this->payments_type($cache_date, "age");
	}

	public function payments_sex($cache_date)
	{
		return $this->payments_type($cache_date, "sex");
	}

	public function payments_first($cache_date)
	{
		$result = $this->DB->payments_first();
		$data = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($data);
	}

	public function payments_repeated($cache_date)
	{
		$result = $this->DB->payments_repeated();
		$data = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($data);
	}

	public function payments_newbies($cache_date)
	{
		$result = $this->DB->payments_newbies($cache_date);
		$data = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($data);
	}

	public function payments_day_first($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_day_first();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_period_index($row['days']);

			if (!isset($data[$date]))
				$data[$date] = array('0d' => 0, '2d' => 0, '8d' => 0, '15d' => 0, '31d' => 0, 'count' => 0);
			$point = &$data[$date];

			$point[$type.'d'] += $row['count'];
			$point['count'] += $row['count'];
		}

		$all = array();

		while (list($date, $values) = each($data))
		{
			$day = &$values['count'];

			$all[] = array('date' => $date, 'type' => 0, 'value' => round($values['0d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 2, 'value' => round($values['2d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 8, 'value' => round($values['8d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 15, 'value' => round($values['15d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 31, 'value' => round($values['31d'] * 100 / $day, 2));
		}

		return array($all);
	}

	public function payments_day_next($cache_date)
	{
		$sum = array();
		$count = array();
		$dates = array();

		$result = $this->DB->payments_day_next();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$uid = $row['type']."-".$row['net_id'];

			if (!isset($dates[$uid]))
			{
				$dates[$uid] = $date;
				continue;
			}

			$days = $this->date_diff($date, $dates[$uid]);
			$type = $this->get_period_index($days);

			if (!isset($sum[$date."-".$type]))
				$sum[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$point1 = &$sum[$date."-".$type];

			if (!isset($count[$date."-".$type]))
				$count[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$point2 = &$count[$date."-".$type];

			$point1['value'] += $row['balance'];
			$point2['value'] += 1;

			$dates[$uid] = $date;
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	/**
	 * Финансы
	 */
	public function finance_arpu($cache_date)
	{
		list($all, $net) = $this->finance_arpu_type($cache_date, "net");
		$device = $this->finance_arpu_type($cache_date, "device");
		$age = $this->finance_arpu_type($cache_date, "age");
		$sex = $this->finance_arpu_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}

	public function finance_arppu($cache_date)
	{
		list($all, $net) = $this->finance_arppu_type($cache_date, "net");
		$device = $this->finance_arppu_type($cache_date, "device");
		$age = $this->finance_arppu_type($cache_date, "age");
		$sex = $this->finance_arppu_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}

	/**
	 * Покупки
	 */
	public function buyings_coins($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_common($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['coins'] == 0)
				continue;

			if (!isset($sum[$date."-".$type]) || !isset($count[$date."-".$type]))
			{
				$sum[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
				$count[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			}

			$sum[$date."-".$type]['value'] += $row['coins'];
			$count[$date."-".$type]['value'] += $row['count'];

			if (!isset($sum[$date."--1"]) || !isset($count[$date."--1"]))
			{
				$sum[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
				$count[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
			}

			$sum[$date."--1"]['value'] += $row['coins'];
			$count[$date."--1"]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_nuts($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_common($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['nuts'] == 0)
				continue;

			if (!isset($sum[$date."-".$type]) || !isset($count[$date."-".$type]))
			{
				$sum[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
				$count[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			}

			$sum[$date."-".$type]['value'] += $row['nuts'];
			$count[$date."-".$type]['value'] += $row['count'];

			if (!isset($sum[$date."--1"]) || !isset($count[$date."--1"]))
			{
				$sum[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
				$count[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
			}

			$sum[$date."--1"]['value'] += $row['nuts'];
			$count[$date."--1"]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_packages($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_packages($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($this->packages[$type]) || $row['coins'] == 0)
				continue;

			$sum[] = array('date' => $date, 'type' => $type, 'value' => $row['coins']);
			$count[] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	public function buyings_shaman($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_shaman($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($this->shaman_items[$type]) || $row['nuts'] == 0)
				continue;

			if (!isset($sum[$date."-".$type]) || !isset($count[$date."-".$type]))
			{
				$sum[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
				$count[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			}

			$sum[$date."-".$type]['value'] += $row['nuts'];
			$count[$date."-".$type]['value'] += $row['count'];

			if (!isset($sum[$date."--1"]) || !isset($count[$date."--1"]))
			{
				$sum[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
				$count[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
			}

			$sum[$date."--1"]['value'] += $row['nuts'];
			$count[$date."--1"]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	/**
	 * Счётчики
	 */
	public function counters_dau($cache_date)
	{
		$result = $this->DB->counters_dau_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->counters_dau_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_dau_device($cache_date);
		$device = $this->type_data($result);

		$result = $this->DB->counters_dau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_dau_sex($cache_date);
		$sex = $this->type_data($result);

		$result = $this->DB->counters_dau_locations($cache_date);
		$locations = $this->type_data($result);

		return array($all, $net, $device, $age, $sex, $locations);
	}

	public function counters_wau($cache_date)
	{
		$result = $this->DB->counters_wau_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->counters_wau_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_wau_device($cache_date);
		$device = $this->type_data($result);

		$result = $this->DB->counters_wau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_wau_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $device, $age, $sex);
	}

	public function counters_mau($cache_date)
	{
		$result = $this->DB->counters_mau_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->counters_mau_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_mau_device($cache_date);
		$device = $this->type_data($result);

		$result = $this->DB->counters_mau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_mau_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $device, $age, $sex);
	}

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

		$result = $this->DB->counters_online_device($cache_date);
		$device = $this->type_data($result);

		$result = $this->DB->counters_online_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_online_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $device, $age, $sex);
	}

	public function counters_online_location($cache_date)
	{
		$max = array();
		$min = array();

		$result = $this->DB->counters_online_location($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$max[] = array('date' => $date, 'type' => $type, 'value' => $row['max']);
			$min[] = array('date' => $date, 'type' => $type, 'value' => $row['min']);
		}

		return array($max, $min);
	}

	public function counters_mau_percent($cache_date)
	{
		$all = $this->counters_mau_percent_type($cache_date, "all");
		$net = $this->counters_mau_percent_type($cache_date, "net");
		$device = $this->counters_mau_percent_type($cache_date, "device");
		$age = $this->counters_mau_percent_type($cache_date, "age");
		$sex = $this->counters_mau_percent_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}

	public function counters_coins($cache_date)
	{
		$coins = array();

		$result = $this->DB->counters_coins($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($type != 0)
			{
				$value *= $type;
				$type = 1;
			}

			if (!isset($coins[$date."_".$type]))
				$coins[$date."_".$type] = array('date' => $row['date'], 'type' => $type, 'value' => 0);
			if (!isset($coins[$date."_2"]))
				$coins[$date."_2"] = array('date' => $row['date'], 'type' => 2, 'value' => 0);

			$coins[$date."_".$type]['value'] += $value;
			$coins[$date."_2"]['value'] += $value;
		}

		$coins = array_values($coins);

		return array($coins);
	}

	public function counters_nuts($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_nuts($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($type != 0)
			{
				$value *= $type;
				$type = 1;
			}

			if (!isset($data[$date."_".$type]))
				$data[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($data[$date."_2"]))
				$data[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$data[$date."_".$type]['value'] += $value;
			$data[$date."_2"]['value'] += $value;
		}

		$data = array_values($data);

		return array($data);
	}

	public function counters_magic($cache_date)
	{
		$result = $this->DB->counters_magic($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($magic[$date."-".$type]))
				$magic[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$magic[$date."-".$type]['value'] += $row['value'];

			if (!isset($magic[$date."--1"]))
				$magic[$date."--1"] = array('date' => $date, 'type' => -1, 'value' => 0);
			$magic[$date."--1"]['value'] += $row['value'];
		}

		$magic = array_values($magic);

		return array($magic);
	}

	public function counters_active($cache_date)
	{
		return $this->counters_active_type($cache_date, "all");
	}

	public function counters_active_net($cache_date)
	{
		return $this->counters_active_type($cache_date, "net");
	}

	public function counters_active_device($cache_date)
	{
		return $this->counters_active_type($cache_date, "device");
	}

	public function counters_active_age($cache_date)
	{
		return $this->counters_active_type($cache_date, "age");
	}

	public function counters_active_sex($cache_date)
	{
		return $this->counters_active_type($cache_date, "sex");
	}

	/**
	 * Игроки
	 */
	public function players_new($cache_date)
	{
		$result = $this->DB->players_new_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->players_new_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->players_new_device($cache_date);
		$device = $this->type_data($result);

		$ages = array();

		$result = $this->DB->players_new_age($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_age_index($row['data']);

			if (!isset($ages[$date."-".$type]))
				$ages[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);

			$ages[$date."-".$type]['value'] += $row['value'];
		}

		$ages = array_values($ages);

		$result = $this->DB->players_new_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $device, $ages, $sex);
	}

	public function players_retention($cache_date)
	{
		$players = array();

		$result = $this->DB->players_new_all($cache_date);
		while ($row = $result->fetch())
			$players[$row['date']] = $row['value'];

		$returned = array();

		$result = $this->DB->players_retention_all();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];
			$days = $row['days'];

			if ($days == 0)
				continue;

			if (!isset($returned[$date]))
				$returned[$date] = array('1d' => 0, '2d' => 0, '7d' => 0, '30d' => 0);
			$point = &$returned[$date];

			if ($days >= 1)
				$point['1d'] += $value;
			if ($days >= 2)
				$point['2d'] += $value;
			if ($days >= 7)
				$point['7d'] += $value;
			if ($days >= 30)
				$point['30d'] += $value;
		}

		$data = array();
		while (list($date, $values) = each($returned))
		{
			if (empty($players[$date]))
			{
				$this->Log->warning("No players registered at {$date}");
				continue;
			}

			$data[] = array('date' => $date, 'type' => 1, 'value' => round(($values['1d'] * 100) / $players[$date], 2));
			$data[] = array('date' => $date, 'type' => 2, 'value' => round(($values['2d'] * 100) / $players[$date], 2));
			$data[] = array('date' => $date, 'type' => 7, 'value' => round(($values['7d'] * 100) / $players[$date], 2));
			$data[] = array('date' => $date, 'type' => 30, 'value' => round(($values['30d'] * 100) / $players[$date], 2));
		}

		return array($data);
	}

	public function players_retention_net($cache_date)
	{
		$players = array();

		$result = $this->DB->players_new_net($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$players[$date."-".$type] = $row['value'];
		}

		$returned = array();

		$result = $this->DB->players_retention_net();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$days = $row['days'];
			$value = $row['value'];

			if ($type == 255 || $days == 0)
				continue;

			if (!isset($returned[$date]))
				$returned[$date] = array();

			if (!isset($returned[$date][$type]))
				$returned[$date][$type] = array('1d' => 0, '2d' => 0, '7d' => 0, '30d' => 0);
			$point = &$returned[$date][$type];

			if ($days >= 1)
				$point['1d'] += $value;
			if ($days >= 2)
				$point['2d'] += $value;
			if ($days >= 7)
				$point['7d'] += $value;
			if ($days >= 30)
				$point['30d'] += $value;
		}

		$data1 = array();
		$data2 = array();
		$data7 = array();
		$data30 = array();

		while (list($date, $types) = each($returned))
		{
			while (list($type, $values) = each($types))
			{
				if (empty($players[$date."-".$type]))
				{
					$this->Log->warning("No players registered at {$date} for type {$type}");
					continue;
				}
				$registered = &$players[$date."-".$type];

				$data1[] = array('date' => $date, 'type' => $type, 'value' => round(($values['1d'] * 100) / $registered, 2));
				$data2[] = array('date' => $date, 'type' => $type, 'value' => round(($values['2d'] * 100) / $registered, 2));
				$data7[] = array('date' => $date, 'type' => $type, 'value' => round(($values['7d'] * 100) / $registered, 2));
				$data30[] = array('date' => $date, 'type' => $type, 'value' => round(($values['30d'] * 100) / $registered, 2));
			}
		}

		return array($data1, $data2, $data7, $data30);
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

	public function players_levels($cache_date)
	{
		$all = array();

		$result = $this->DB->players_levels();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_level($row['data']);

			if ($type > 10)
				$type = 10;

			if (!isset($all[$date."-".$type]))
				$all[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$point = &$all[$date."-".$type];

			$point['value']++;
		}

		$all = array_values($all);

		return array($all);
	}

	/**
	 * Helper functions
	 */
	private function payments_type($cache_date, $key)
	{
		$sums = array();
		$counts = array();

		$result = $this->DB->{"payments_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			if (!isset($sums[$date."-".$type]))
				$sums[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$sums[$date."-".$type]['value'] += $row['sum'];

			if (!isset($counts[$date."-".$type]))
				$counts[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			$counts[$date."-".$type]['value'] += $row['count'];
		}

		$sums = array_values($sums);
		$counts = array_values($counts);

		return array($sums, $counts);
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

			if ($key == "age")
				$type = $this->get_age_index($type);

			$row['sum'] *= $this->cost;

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

			if ($key == "age")
				$type = $this->get_age_index($type);

			$row['sum'] *= $this->cost;

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

	private function counters_active_type($cache_date, $key)
	{
		$dau = array();

		$result = $this->DB->{"counters_dau_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$dau[$date."-".$type] = $row['value'];
		}

		$sum = array();
		$count = array();

		$result = $this->DB->{"counters_active_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (empty($dau[$date."-".$type]))
				continue;
			$day = &$dau[$date."-".$type];

			$sum[] = array('date' => $date, 'type' => $type, 'value' => round($row['sum'] / $day / 60, 2));
			$count[] = array('date' => $date, 'type' => $type, 'value' => round($row['count'] / $day, 2));
		}

		return array($sum, $count);
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

	/**
	 * Common functions
	 */
	private function get_age_index($age)
	{
		if ($age >= 36)
			return 5;
		if ($age >= 25)
			return 4;
		if ($age >= 19)
			return 3;
		if ($age >= 15)
			return 2;
		if ($age >= 11)
			return 1;
		if ($age >= 0)
			return 0;
		return 99;
	}

	private function get_period_index($days)
	{
		if ($days >= 31)
			return 31;
		if ($days >= 15)
			return 15;
		if ($days >= 8)
			return 8;
		if ($days >= 2)
			return 2;
		return 0;
	}

	private function get_level($experience)
	{
		$max_level = count($this->levels);
		for ($level = $max_level - 1; $level >= 0; $level--)
		{
			if ($experience < $this->levels[$level])
				continue;
			return $level + 1;
		}
		return 0;
	}

	private function date_diff($date1, $date2)
	{
		$date1 = date_parse($date1);
		$date2 = date_parse($date2);

		$time1 = gmmktime(0, 0, 0, $date1['month'], $date1['day'], $date1['year']);
		$time2 = gmmktime(0, 0, 0, $date2['month'], $date2['day'], $date2['year']);

		return (($time1 - $time2) / 86400);
	}

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

	private function type_data($result)
	{
		$data = array();
		while ($row = $result->fetch())
			$data[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);

		return $data;
	}
}

?>