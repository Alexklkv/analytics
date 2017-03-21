<?php

/**
 * Реализует отчёты Игры богов
 *
 * @uses DatabaseInterface
 * @uses ObjectLog
 *
 * @version 1.0.1
 */
class ObjectGods extends Object implements DatabaseInterface
{
	/**
	 * Идентификатор проекта в системе аналитики
	 */
	private static $service_id;

	const Offers = "1,2,3";
	const OfferNone = 0;
	const OfferBox = 4;

	private $networks = array(0 => "ВКонтакте", 1 => "МойМир", 4 => "Одноклассники", 5 => "Facebook", 30 => "ФотоСтрана", 31 => "NextGame");
	private $ages = array(0 => "Не задан", 1 => "<16", 2 => "16-19", 3 => "20-24", 4 => "25-29", 5 => "30-34", 6 => "35-40", 7 => "41-50", 8 => ">50");
	private $sex = array(2 => "Мужской", 1 => "Женский", 0 => "Не задан");
	private $goods = array(0 => "Друг", 1 => "Артефакт", 2 => "Энергия", 3 => "Подписка", 4 => "Артефакт во время боя", 5 => "Эликсир", 6 => "Турниры");
	private $payments = array(2 => "2", 10 => "10", 20 => "20", 50 => "50", 100 => "100", 125 => "125", 0 => "Другие", -1 => "Сундук", -2 => "Офферы");
	private $periods = array(0 => "0-1d", 2 => "2-7d", 8 => "8-14d", 15 => "15-30d", 31 => "31d+");
	private $games = array(0 => "Классика", 1 => "Блиц", 2 => "Цепочки");
	private $energy = array(-1 => "Другие", 2 => "2", 4 => "4", 8 => "8", 10 => "10", 50 => "50", 150 => "150", 300 => "300");
	private $reasons = array(
		1 => "Предупреждение", 2 => "Флуд", 3 => "Мат в чате", 4 => "Обсуждение действий мордератора",
		5 => "Оскорбление личности и достоинства", 6 => "Реклама сторонних проектов", 7 => "Мат в профиле", 8 => "Мат на аватарке",
		9 => "Оскорбление модератора", 10 => "Разжигание межнациональной розни", 11 => "Порнография на аватарке", 12 => "Использование багов игры",
		13 => "Использование стороннего ПО", 14 => "Оскорбление администрации", 15 => "Злостный нарушитель", 16 => "Пустой вредноносный аккаунт"
	);
	private $retention_intervals = array(1, 2, 7, 30);

	private $levels = array(0 => "Горожанин", 5 => "Ученик", 15 => "Мастер", 50 => "Гладиатор", 110 => "Воин", 230 => "Герой", 530 => "Титан", 1500 => "Полубог", 4000 => "Бог");
	private $ranks = array(0 => "Горожанин", 1 => "Ученик", 2 => "Мастер", 3 => "Гладиатор", 4 => "Воин", 5 => "Герой", 6 => "Титан", 7 => "Полубог", 8 => "Бог");
	private $artefacts = array(
		2 => "Амброзия", 21 => "Амулет защиты", 30 => "Браслет Эриний", 25 => "Бумеранг", 10 => "Гнев Аида", 3 => "Жезл Гермеса",
		6 => "Золотая монета", 8 => "Клык Цербера", 26 => "Колесница Гелиоса", 5 => "Кристаллы жизни", 12 => "Крылатые сандалии",
		9 => "Лапа Василиска", 22 => "Лапа Грифона", 7 => "Меч Геракла", 23 => "Молния Зевса", 29 => "Молот Гефеста", 15 => "Око Мойры",
		17 => "Оковы Прометея", 11 => "Панцирь черепахи", 16 => "Перо феникса", 28 => "Песочные часы", 20 => "Рог изобилия",
		19 => "Сова Афины", 34 => "Солнечные часы", 0 => "Трезубец Посейдона", 18 => "Хвост Василиска", 14 => "Хвост Грифона",
		27 => "Храброе сердце", 1 => "Цветок Морфея", 32 => "Чаша Ириды", 13 => "Шлем Ареса", 4 => "Щит Афины", 33 => "Эгида",
		24 => "Эликсир жизни", 31 => "Яд Танатоса"
	);

	private $costs = array(0 => 0.7, 1 => 0.7, 4 => 0.7, 5 => 3, 30 => 1, 31 => 5);
	private $currency = " р.";

	private $free_crystals = array(0 => "За достижения", 1 => "Заработанные в игровых раундах", 2 => "За головоломки", 3 => "Ежедневные бонусы", 4 => "За победы в турнирах");
	private $free_energy = array(1 => "Достижения", 2 => "Подарки", 3 => " Ежедневный бонус", 4 => "Новогодний подарок", 5 => "Артефакт «Посейдон»");

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
			'payments_candles'		=> "SELECT DATE(`time`) as `date`, HOUR(`time`) as `hour`, SUM(`balance`) as `sum` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= DATE_SUB(@s, INTERVAL 1 DAY) GROUP BY `date`, `hour`",
			'payments_specific'		=> "SELECT DATE(`time`) as `date`, `offer`, `balance`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `time` >= @s GROUP BY `date`, `offer`, `balance`",
			'payments_net'			=> "SELECT DATE(`time`) as `date`, `type` as `data`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'payments_age'			=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`",
			'payments_sex'			=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`",
			'payments_first'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 ON p2.`type` = p1.`type` AND p2.`net_id` = p1.`net_id` AND p2.`time` = p1.`time` WHERE p2.`offer` NOT IN(".self::Offers.") GROUP BY `date`",
			'payments_repeated'		=> "SELECT DATE(p2.`time`) as `date`, SUM(p2.`balance`) as `sum`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) p1 INNER JOIN `payments` p2 ON p2.`type` = p1.`type` AND p2.`net_id` = p1.`net_id` AND p2.`time` != p1.`time` WHERE p2.`offer` NOT IN(".self::Offers.") GROUP BY `date`",
			'payments_day_first'		=> "SELECT pl.`register_time` as `date`, pm.`net_id`, DATEDIFF(pm.`time`, pl.`register_time`) as `days`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") GROUP BY `type`, `net_id`) pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` > '2000-01-01' GROUP BY `date`, `days` ORDER BY `date` ASC",
			'payments_day_next'		=> "SELECT DATE(`time`) as `date`, `net_id`, `type`, `balance`, `bonus` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") ORDER BY `time` ASC",
			'payments_newbies'		=> "SELECT DATE(pm.`time`) as `date`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND TIMESTAMPDIFF(HOUR, pl.`register_time`, pm.`time`) < 24 AND pm.`time` >= @s GROUP BY `date`",

			'finance_arpu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(*) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'finance_arpu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arpu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",

			'finance_arppu_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `type` as `net`, SUM(`balance`) as `sum`, COUNT(DISTINCT `net_id`) as `count` FROM `payments` WHERE `offer` NOT IN(".self::Offers.") AND `time` >= @s GROUP BY `date`, `data`",
			'finance_arppu_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",
			'finance_arppu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`type` as `net`, SUM(pm.`balance`) as `sum`, COUNT(DISTINCT pm.`net_id`) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= @s GROUP BY `date`, `data`, `net`",

			'buyings_common'		=> "SELECT DATE(`time`) as `date`, `good_id` as `data`, SUM(`price`) as `sum`, COUNT(*) as `count` FROM `buyings` WHERE `time` >= @s GROUP BY `date`, `good_id`",
			'buyings_artefact'		=> "SELECT DATE(`time`) as `date`, `data`, SUM(`price`) as `sum`, COUNT(*) as `count` FROM `buyings` WHERE `good_id` IN (1, 4) AND `time` >= @s GROUP BY `date`, `data`",
			'buyings_energy'		=> "SELECT DATE(`time`) as `date`, `data`, SUM(`price`) as `sum`, COUNT(*) as `count` FROM `buyings` WHERE `good_id` = 2 AND `time` >= @s GROUP BY `date`, `data`",
			'buyings_subscription'		=> "SELECT DATE(`time`) as `date`, `data`, SUM(`price`) as `sum`, COUNT(*) as `count` FROM `buyings` WHERE `good_id` = 3 AND `time` >= @s GROUP BY `date`, `data`",
			'buyings_elixir'		=> "SELECT DATE(`time`) as `date`, `data`, SUM(`price`) as `sum`, COUNT(*) as `count` FROM `buyings` WHERE `good_id` = 5 AND `time` >= @s GROUP BY `date`, `data`",

			'counters_dau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 4 AND `date` >= @s",
			'counters_dau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 30 AND `date` >= @s",
			'counters_dau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 24 AND `date` >= @s",
			'counters_dau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 17 AND `date` >= @s",
			'counters_dau_rank'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 27 AND `date` >= @s",

			'counters_wau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 5 AND `date` >= @s",
			'counters_wau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 32 AND `date` >= @s",
			'counters_wau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 26 AND `date` >= @s",
			'counters_wau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 18 AND `date` >= @s",
			'counters_wau_rank'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 29 AND `date` >= @s",

			'counters_mau_all'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 6 AND `date` >= @s",
			'counters_mau_net'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 31 AND `date` >= @s",
			'counters_mau_age'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 25 AND `date` >= @s",
			'counters_mau_sex'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 19 AND `date` >= @s",
			'counters_mau_rank'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 28 AND `date` >= @s",

			'counters_online_all'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 0 AND `time` >= @s GROUP BY `date`",
			'counters_online_net'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 1 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_age'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 14 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_sex'		=> "SELECT DATE(`time`) as `date`, `data`, MAX(`value`) as `value` FROM `counters` WHERE `type` = 15 AND `time` >= @s GROUP BY `date`, `data`",

			'counters_crystal'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 16 AND `date` >= @s",
			'counters_free_crystals'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 48 AND `date` >= @s",
			'counters_free_energy'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 49 AND `date` >= @s",

			'counters_play_random'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 10 AND `date` >= @s",
			'counters_play_bot'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 11 AND `date` >= @s",
			'counters_play_duel'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 12 AND `date` >= @s",
			'counters_play_champ'		=> "SELECT `date`, 0 as `data`, `value` FROM `counters_daily` WHERE `type` = 64 AND `data` = 0 AND `date` >= @s",
			'counters_play_champ_total'	=> "SELECT `date`, 0 as `data`, SUM(`value`) as `value` FROM `counters_daily` WHERE `type` = 64 AND `date` >= @s GROUP BY `date`",

			'counters_puzzle_easy'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 20 AND `date` >= @s",
			'counters_puzzle_normal'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 21 AND `date` >= @s",
			'counters_puzzle_hard'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 22 AND `date` >= @s",

			'counters_active_all'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 33 AND c2.`type` = 37 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_net'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 34 AND c2.`type` = 38 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_age'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 35 AND c2.`type` = 39 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_active_sex'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 36 AND c2.`type` = 40 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_payings'		=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 57 AND c2.`type` = 56 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",
			'counters_payings_dau'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 60 AND `date` >= @s",
			'counters_payings_rounds'	=> "SELECT c1.`date`, c1.`data`, c1.`value` as `sum`, c2.`value` as `count` FROM `counters_daily` c1, `counters_daily` c2 WHERE c1.`type` = 59 AND c2.`type` = 58 AND c2.`date` = c1.`date` AND c2.`data` = c1.`data` AND c1.`date` >= @s",

			'counters_referrals'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 45 AND `date` >= @s ORDER BY `date`, `data`",
			'counters_games'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 23 AND `date` >= @s ORDER BY `date`, `data`",

			'counters_rounds_total'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 50 AND `date` >= @s ORDER BY `date`, `data`",
			'counters_time_total'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 51 AND `date` >= @s ORDER BY `date`, `data`",
			'counters_sessions_total'	=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 37 AND `date` >= @s",

			'counters_max_energy'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 52 AND `date` >= @s ORDER BY `date`, `data`",

			'counters_bots'			=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 54 AND `date` >= @s",
			'counters_friends'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 55 AND `date` >= @s",

			'counters_paying_champ'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 61 AND `date` >= @s",
			'counters_paying_sum'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 62 AND `date` >= @s",
			'counters_paying_count'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 63 AND `date` >= @s",

			'players_new_all'		=> "SELECT `register_time` as `date`, 0 as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`",
			'players_new_net'		=> "SELECT `register_time` as `date`, `type` as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_age'		=> "SELECT `register_time` as `date`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL `bday` SECOND), NOW())) as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_sex'		=> "SELECT `register_time` as `date`, `sex` as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",

			'players_retention_all'		=> "SELECT `date`, `data`, SUM(`value`) as `value` FROM (SELECT `register_time` as `date`, `first_retention` as `data`, COUNT(`first_retention`) as `value` FROM `players` WHERE `first_retention` NOT IN (4294967295, 0) AND `register_time` >= @s GROUP BY `register_time`, `first_retention`) `qr` GROUP BY `date`, `data`",
			'players_lost'			=> "SELECT `register_time` as `date`, 0 as `data`, COUNT(`logout_time`) as `value` FROM `players` WHERE `register_time` >= @s AND `first_retention` IN (0, 4294967295) GROUP BY `register_time`",
			'players_retention_net'		=> "SELECT `register_time` as `date`, `type` as `data`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= '2000-01-01' AND `logout_time` != 0 GROUP BY `date`, `data`, `days`",

			'players_paying_net'		=> "SELECT DATE(`time`) as `date`, `type` as `data`, `net_id` FROM `payments` FORCE INDEX(`time`) WHERE `offer` NOT IN(".self::Offers.") AND `time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_age'		=> "SELECT DATE(pm.`time`) as `date`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL pl.`bday` SECOND), NOW())) as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",
			'players_paying_sex'		=> "SELECT DATE(pm.`time`) as `date`, pl.`sex` as `data`, pm.`net_id` as `net_id` FROM `payments` pm FORCE INDEX(`time`) INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`offer` NOT IN(".self::Offers.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `data`, `net_id` ORDER BY `date` ASC",

			'players_bans'			=> "SELECT DATE(FROM_UNIXTIME(`time`)) as `date`, `reason` as `data`, COUNT(*) as `value` FROM `blocks` WHERE `time` >= UNIX_TIMESTAMP(@s) GROUP BY `date`, `data`",
			'players_levels'		=> "SELECT `register_time` as `date`, `wins` as `data`, COUNT(*) as `value` FROM `players` WHERE `register_time` >= '2000-01-01' GROUP BY `register_time`, `wins`",
			'players_chat'			=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 53 AND `date` >= @s"
		);
	}

	public function get_jobs()
	{
		return array(
			36 => "buyings_common, buyings_common_paying, buyings_artefact, buyings_energy, buyings_subscription, buyings_elixir"
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
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
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
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
			),
			'finance' => array(
				'arpu' => array(
					'id'		=> $id++,
					'title'		=> "ARPU",
					'description'	=> "Средний доход от игрока за день (в копейках)",
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
						'value_append'	=> " коп.",
						'show_sums'	=> false,
						'indicator'	=> array('type' => "function", 'function' => "arpu_indicator")
					)
				),
				'arppu' => array(
					'id'		=> $id++,
					'title'		=> "ARPPU",
					'description'	=> "Средний доход от платящего игрока за день",
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
			'buyings' => array(
				'common' => array(
					'id'		=> $id++,
					'title'		=> "Общий",
					'description'	=> "Количество и сумма покупок за кристаллы",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->goods
						)
					)
				),
				'common_paying' => array(
					'id'		=> $id++,
					'title'		=> "Общий для платящих",
					'description'	=> "Количество и сумма покупок за кристаллы для платящих игроков",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->goods
						)
					)
				),
				'artefact' => array(
					'id'		=> $id++,
					'title'		=> "Артефакты",
					'description'	=> "Количество и сумма покупок Артефактов",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->artefacts
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->artefacts
						)
					)
				),
				'energy' => array(
					'id'		=> $id++,
					'title'		=> "Энергия",
					'description'	=> "Количество и сумма покупок Энергии",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->energy
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->energy
						)
					)
				),
				'subscription' => array(
					'id'		=> $id++,
					'title'		=> "Подписка",
					'description'	=> "Количество и сумма покупок Подписки",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> array(0 => "На 24 часа", 1 => "На 3 дня (было 7 дней)", 2 => "На 14 дней (было 30 дней)")
						),
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "На 24 часа", 1 => "На 3 дня (было 7 дней)", 2 => "На 14 дней (было 30 дней)")
						)
					)
				),
				'elixir' => array(
					'id'		=> $id++,
					'title'		=> "Эликсир",
					'description'	=> "Количество и сумма покупок Эликсира",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> array(0 => "Розовый", 1 => "Фиалковый")
						),
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Розовый", 1 => "Фиалковый")
						)
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
						),
						array(
							'title'		=> "По рангу",
							'legend'	=> $this->ranks
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
						),
						array(
							'title'		=> "По рангу",
							'legend'	=> $this->ranks
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
						),
						array(
							'title'		=> "По рангу",
							'legend'	=> $this->ranks
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
				'crystal' => array(
					'id'		=> $id++,
					'title'		=> "Баланс кристаллов",
					'description'	=> "Количество полученных и потраченных игроками кристаллов",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				'free_crystals' => array(
					'id'		=> $id++,
					'title'		=> "Бесплатные кристаллы",
					'description'	=> "Количество бесплатных кристаллов, по типу",
					'graphs'	=> array(
						array(
							'title'		=> "Кристаллы, количество",
							'legend'	=> $this->free_crystals
						)
					)
				),
				'free_energy' => array(
					'id'		=> $id++,
					'title'		=> "Бесплатная энергия по типу",
					'description'	=> "Количество бесплатной энергии",
					'graphs'	=> array(
						array(
							'title'		=> "Энергия, количество",
							'legend'	=> $this->free_energy
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
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'active_net' => array(
					'id'		=> $id++,
					'title'		=> "Активность по сетям",
					'description'	=> "Среднее время и количество игровых сессий по соц. сетям",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Сессии",
							'legend'	=> $this->networks
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
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
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
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
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'active_pay' => array(
					'id'		=> $id++,
					'title'		=> "Активность платящих",
					'description'	=> "Среднее время, количество игровых сессий и количество раундов для платящих игроков",
					'graphs'	=> array(
						array(
							'title'		=> "Время, минут",
							'legend'	=> array(0 => "Время, минут")
						),
						array(
							'title'		=> "Сессии, количество",
							'legend'	=> array(0 => "Сессии")
						),
						array(
							'title'		=> "Среднее время раунда, минут",
							'legend'	=> array(0 => "Время, минут")
						),
						array(
							'title'		=> "Раунды, количество",
							'legend'	=> array(0 => "Количество")
						)
					)
				),
				"-",
				'play' => array(
					'id'		=> $id++,
					'title'		=> "Обычные игры",
					'description'	=> "Количество игр по типам",
					'graphs'	=> array(
						array(
							'title'		=> "Случайные игры",
							'legend'	=> $this->games
						),
						array(
							'title'		=> "Игры с ботами",
							'legend'	=> $this->games
						),
						array(
							'title'		=> "Дуэли",
							'legend'	=> $this->games
						),
						array(
							'title'		=> "Турниры",
							'legend'	=> $this->games
						)
					)
				),
				'puzzle' => array(
					'id'		=> $id++,
					'title'		=> "Головомки",
					'description'	=> "Количество побед и проигрышей в головоломках разной сложности",
					'graphs'	=> array(
						array(
							'title'		=> "Легкие головоломки",
							'legend'	=> array(0 => "Проигрыши", 1 => "Победы")
						),
						array(
							'title'		=> "Средние головоломки",
							'legend'	=> array(0 => "Проигрыши", 1 => "Победы")
						),
						array(
							'title'		=> "Сложные головоломки",
							'legend'	=> array(0 => "Проигрыши", 1 => "Победы")
						)
					)
				),
				"-",
				'referrals' => array(
					'id'		=> $id++,
					'title'		=> "Рефералы и мастера",
					'description'	=> "Количество пользователей пришедших в игру по рефералам и количество пользователей ставших мастерами из тех, что пришли по рефералам",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки, количество",
							'legend'	=> array(0 => "По рефералам", 1 => "Мастера")
						)
					)
				),
				'games' => array(
					'id'		=> $id++,
					'title'		=> "Результаты игр",
					'description'	=> "Количество игроков, которые вышли или сдались по типу режима игры",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки, количество",
							'legend'	=> $this->games
						)
					)
				),
				'rounds' => array(
					'id'		=> $id++,
					'title'		=> "Раунды",
					'description'	=> "Среднее количество раундов за игровую сессию по типу игры и средняя продолжительность раунда",
					'graphs'	=> array(
						array(
							'title'		=> "Раундов за игровую сессию, количество",
							'legend'	=> array(0 => "Раунды, количество")
						),
						array(
							'title'		=> "Продолжительность раунда",
							'legend'	=> $this->games
						)
					)
				),
				'max_energy' => array(
					'id'		=> $id++,
					'title'		=> "Максимальная энергия",
					'description'	=> "Максимальная энергия игроков, сгруппированная по их уровняям",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки, количество",
							'legend'	=> array(0 => "40", 1 => "41-50", 2 => "51-60", 3 => "61-80", 4 => "81-100", 5 => "101-120", 6 => "121+")
						)
					)
				),
				"-",
				'bots' => array(
					'id'		=> $id++,
					'title'		=> "Игры с ботами",
					'description'	=> "Количество игр с ботами и количество побед игроков в них",
					'graphs'	=> array(
						array(
							'title'		=> "Игры, количество",
							'legend'	=> array(
								1 => "Игры с ботом 1", 2 => "Игры с ботом 2", 3 => "Игры с ботом 3",
								4 => "Игры с ботом 4", 5 => "Игры с ботом 5", 6 => "Игры с ботом 6",
								7 => "Игры с ботом 7", 8 => "Игры с ботом 8", 9 => "Игры с ботом 9"
							)
						),
						array(
							'title'		=> "Победы, процент",
							'legend'	=> array(
								1 => "Победы над ботом 1", 2 => "Победы над ботом 2", 3 => "Победы над ботом 3",
								4 => "Победы над ботом 4", 5 => "Победы над ботом 5", 6 => "Победы над ботом 6",
								7 => "Победы над ботом 7", 8 => "Победы над ботом 8", 9 => "Победы над ботом 9"
							)
						)
					)
				),
				'friends' => array(
					'id'		=> $id++,
					'title'		=> "Друзья",
					'description'	=> "Количество удалений из друзей и добавления в друзья",
					'graphs'	=> array(
						array(
							'title'		=> "Удаления/добавления, количество",
							'legend'	=> array(0 => "Удаления", 1 => "Добавления")
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
					)
				),
				'bans' => array(
					'id'		=> $id++,
					'title'		=> "Баны",
					'description'	=> "Количество заблокированных пользователей",
					'graphs'	=> array(
						array(
							'title'		=> "Пользователи",
							'legend'	=> $this->reasons,
							'show_sumline'	=> true
						)
					)
				),
				"-",
				'retention' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения",
					'description'	=> "Возвращение игроков через N дней и не вернувшиеся игроки",
					'graphs'	=> array(
						array(
							'title'		=> "Возвращения, количество",
							'legend'	=> array(0 => "Не вернувшиеся", 1 => "1d", 2 => "2-6d", 7 => "7-29d", 30 => "30d+")
						)
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
				'levels' => array(
					'id'		=> $id++,
					'title'		=> "Уровни игроков",
					'description'	=> "Распределение игроков по числу побед относительно даты регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки",
							'legend'	=> $this->levels
						)
					),
					'cache'		=> false
				),
				'chat' => array(
					'id'		=> $id++,
					'title'		=> "Активность в чате",
					'description'	=> "Количество игроков, хоть раз за день отправлявших сообщения в общем, приватном чате и в бою",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "В общем", 1 => "Приват в общем", 2 => "В бою")
						),
					)
				)
			)
		);
	}

	/**
	 * Функции индикаторов
	 */
	public static function arpu_indicator($Analytics, $report, $periods, $graph, $type)
	{
		$date_begin = $report['date_begin'];
		$date_end = $report['date_end'];
		$data = array();

		while (list($key, $offsets) = each($periods))
		{
			$days = (($offsets['max'] + 1 - $offsets['min']) / 86400);
			$data[$key] = array('days' => $days, 'value' => 0, 'month_days' => $offsets['month_days']);

			if ($offsets['month_days'] > $days)
				continue;

			if ($days == 31)
				$periods[$key]['min'] += 86400;
			if ($days < 30)
				$periods[$key]['min'] -= 86400 * (30 - $offsets['month_days']);
		}

		$last_month = &$periods[count($periods) - 1];
		if ($last_month['month_days'] < 30)
			$date_begin -= 86400 * (30 - $last_month['month_days']);

		switch ($graph)
		{
			case 0:
				$path = "payments_all";
				break;
			case 1:
				$path = "payments_net";
				break;
			case 2:
				$path = "payments_age";
				break;
			case 3:
				$path = "payments_sex";
				break;
		}

		$result = $Analytics->DB->get_filtered_cache($report['service'], $path, 0, $type, $date_begin, $date_end);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$data[$key]['value'] += $row['value'] * 0.7;
			}
		}

		$mau = array();

		$result = $Analytics->DB->get_filtered_cache($report['service'], "counters_mau", $graph, $type, $date_begin, $date_end);
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

			if ($values['month_days'] > $values['days'])
				$values['value'] = $values['value'] / $values['days'] * $values['month_days'];

			$data[$key]['value'] = round($values['value'] / $mau[$key], 2);
		}

		return $data;
	}

	public static function arppu_indicator($Analytics, $report, $periods, $graph, $type)
	{
		$date_begin = $report['date_begin'];
		$date_end = $report['date_end'];
		$data = array();

		while (list($key, $offsets) = each($periods))
		{
			$days = (($offsets['max'] + 1 - $offsets['min']) / 86400);
			$data[$key] = array('days' => $days, 'value' => 0, 'month_days' => $offsets['month_days']);

			if ($offsets['month_days'] > $days)
				continue;

			if ($days == 31)
				$periods[$key]['min'] += 86400;
			if ($days < 30)
				$periods[$key]['min'] -= 86400 * (30 - $offsets['month_days']);
		}

		$last_month = &$periods[count($periods) - 1];
		if ($last_month['month_days'] < 30)
			$date_begin -= 86400 * (30 - $last_month['month_days']);

		switch ($graph)
		{
			case 0:
				$path = "payments_all";
				break;
			case 1:
				$path = "payments_net";
				break;
			case 2:
				$path = "payments_age";
				break;
			case 3:
				$path = "payments_sex";
				break;
		}

		$result = $Analytics->DB->get_filtered_cache($report['service'], $path, 0, $type, $date_begin, $date_end);
		while ($row = $result->fetch())
		{
			$time = $row['time'];

			reset($periods);
			while (list($key, $offsets) = each($periods))
			{
				if (!($time >= $offsets['min'] && $time <= $offsets['max']))
					continue;

				$data[$key]['value'] += $row['value'] * 0.7;
			}
		}

		$paying = array();

		$result = $Analytics->DB->get_filtered_cache($report['service'], "players_paying_month", $graph, ($graph == 0 ? 1 : $type), $date_begin, $date_end);
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

		if ($graph == 0)
		{
			while (list($key, $values) = each($data))
			{
				if (!isset($paying[$key]))
					continue;
				if ($values['month_days'] > $values['days'])
					$values['value'] = $values['value'] / $values['days'] * $values['month_days'];

				$data[$key]['value'] = round($values['value'] / $paying[$key], 2);
			}

			return $data;
		}

		$mau = array();

		$result = $Analytics->DB->get_filtered_cache($report['service'], "counters_mau", $graph, $type, $date_begin, $date_end);
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

		$mau_paying = array();

		while (list($key, $value) = each($mau))
		{
			if (!isset($paying[$key]))
				continue;
			if ($paying[$key] == 0 || $value == 0)
				continue;

			$mau_paying[$key] = $value * $paying[$key] / 100;
		}

		$mau_paying = &$paying;

		while (list($key, $values) = each($data))
		{
			if (!isset($mau_paying[$key]))
			{
				$data[$key]['value'] = 0;
				continue;
			}

			if ($values['month_days'] > $values['days'])
				$values['value'] = $values['value'] / $values['days'] * $values['month_days'];

			$data[$key]['value'] = round($values['value'] / $mau_paying[$key], 2);
		}

		return $data;
	}

	/**
	 * Платежи
	 */
	public function payments_all($cache_date)
	{
		$result = $this->DB->payments_all($cache_date);
		$data = $this->simple_data($result, array(0 => "sum", 1 => "count"));

		return array($data);
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
		$box = array();
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
					if (!isset($box[$date]))
						$box[$date] = array('sum' => 0, 'count' => 0);

					$box[$date]['sum'] += $row['sum'];
					$box[$date]['count'] += $row['count'];
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

		while (list($date, $values) = each($box))
		{
			$data['sum'][] = array('date' => $date, 'type' => -1, 'value' => $values['sum']);
			$data['count'][] = array('date' => $date, 'type' => -1, 'value' => $values['count']);
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
	 * Покупки
	 */
	public function buyings_common($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_common($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['sum'] == 0)
				continue;

			$sum[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => $row['sum']);
			$count[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

		$result = $this->DB->counters_play_champ($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];

			if (!isset($sum[$date."-6"]))
			{
				$sum[$date."-6"] = array('date' => $date, 'type' => 6, 'value' => 0);
				$count[$date."-6"] = array('date' => $date, 'type' => 6, 'value' => 0);
			}

			$sum[$date."-6"]['value'] += $row['value'] * 10;
			$count[$date."-6"]['value'] += $row['value'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_common_paying($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->counters_paying_sum($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$sum[$date."-".$type] = array('date' => $date, 'type' => $row['data'], 'value' => $row['value']);
		}

		$result = $this->DB->counters_paying_count($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$count[$date."-".$type] = array('date' => $date, 'type' => $row['data'], 'value' => $row['value']);
		}

		$result = $this->DB->counters_paying_champ($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];

			$sum[$date."-6"] = array('date' => $date, 'type' => 6, 'value' => $row['value']);
			$count[$date."-6"] = array('date' => $date, 'type' => 6, 'value' => $row['value']/10);
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_artefact($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_artefact($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['sum'] == 0)
				continue;

			$sum[] = array('date' => $date, 'type' => $type, 'value' => $row['sum']);
			$count[] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	public function buyings_energy($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_energy($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($this->energy[$type]))
				$type = -1;

			if (!isset($sum[$date."_".$type]) || !isset($count[$date."_".$type]))
			{
				$sum[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
				$count[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			}

			$sum[$date."_".$type]['value'] += $row['sum'];
			$count[$date."_".$type]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_subscription($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_subscription($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['sum'] == 0)
				continue;

			$sum[] = array('date' => $date, 'type' => $type, 'value' => $row['sum']);
			$count[] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	public function buyings_elixir($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_elixir($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($row['sum'] == 0)
				continue;

			$sum[] = array('date' => $date, 'type' => $type, 'value' => $row['sum']);
			$count[] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

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

		$result = $this->DB->counters_dau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_dau_sex($cache_date);
		$sex = $this->type_data($result);

		$result = $this->DB->counters_dau_rank($cache_date);
		$rank = $this->type_data($result);

		return array($all, $net, $age, $sex, $rank);
	}

	public function counters_wau($cache_date)
	{
		$result = $this->DB->counters_wau_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->counters_wau_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_wau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_wau_sex($cache_date);
		$sex = $this->type_data($result);

		$result = $this->DB->counters_wau_rank($cache_date);
		$rank = $this->type_data($result);

		return array($all, $net, $age, $sex, $rank);
	}

	public function counters_mau($cache_date)
	{
		$result = $this->DB->counters_mau_all($cache_date);
		$all = $this->type_data($result);

		$result = $this->DB->counters_mau_net($cache_date);
		$net = $this->type_data($result);

		$result = $this->DB->counters_mau_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_mau_sex($cache_date);
		$sex = $this->type_data($result);

		$result = $this->DB->counters_mau_rank($cache_date);
		$rank = $this->type_data($result);

		return array($all, $net, $age, $sex, $rank);
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

		$result = $this->DB->counters_online_age($cache_date);
		$age = $this->type_data($result);

		$result = $this->DB->counters_online_sex($cache_date);
		$sex = $this->type_data($result);

		return array($all, $net, $age, $sex);
	}

	public function counters_mau_percent($cache_date)
	{
		$all = $this->counters_mau_percent_type($cache_date, "all");
		$net = $this->counters_mau_percent_type($cache_date, "net");
		$age = $this->counters_mau_percent_type($cache_date, "age");
		$sex = $this->counters_mau_percent_type($cache_date, "sex");

		return array($all, $net, $age, $sex);
	}

	public function counters_crystal($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_crystal($cache_date);
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

	public function counters_free_crystals($cache_date)
	{
		$result = $this->DB->counters_free_crystals($cache_date);
		$data = $this->type_data($result);

		return array($data);
	}

	public function counters_free_energy($cache_date)
	{
		$result = $this->DB->counters_free_energy($cache_date);
		$data = $this->type_data($result);

		return array($data);
	}

	public function counters_active($cache_date)
	{
		return $this->counters_active_type($cache_date, "all");
	}

	public function counters_active_net($cache_date)
	{
		return $this->counters_active_type($cache_date, "net");
	}

	public function counters_active_age($cache_date)
	{
		return $this->counters_active_type($cache_date, "age");
	}

	public function counters_active_sex($cache_date)
	{
		return $this->counters_active_type($cache_date, "sex");
	}

	public function counters_active_pay($cache_date)
	{
		$dau = array();
		$sum = array();
		$count = array();
		$rounds_time = array();
		$rounds_count = array();

		$result = $this->DB->counters_payings_dau($cache_date);
		while ($row = $result->fetch())
			$dau[$row['date']] = $row['value'];

		$result = $this->DB->counters_payings($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];

			if (!isset($dau[$date]) || $dau[$date] <= 0)
				continue;

			$sum[] = array('date' => $date, 'type' => 0, 'value' => round($row['sum'] / $dau[$date] / 60, 2));
			$count[] = array('date' => $date, 'type' => 0, 'value' => round($row['count'] / $dau[$date], 2));
		}

		$result = $this->DB->counters_payings_rounds($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];

			$rounds_time[] = array('date' => $date, 'type' => 0, 'value' => round($row['sum'] / 60 /$row['count'], 2));

			if (!isset($dau[$date]) || $dau[$date] <= 0)
				continue;

			$rounds_count[] = array('date' => $date, 'type' => 0, 'value' => round($row['count'] / $dau[$date], 2));
		}

		return array($sum, $count, $rounds_time, $rounds_count);
	}

	public function counters_play($cache_date)
	{
		$result = $this->DB->counters_play_random($cache_date);
		$random = $this->type_data($result);

		$result = $this->DB->counters_play_bot($cache_date);
		$bot = $this->type_data($result);

		$result = $this->DB->counters_play_duel($cache_date);
		$duel = $this->type_data($result);

		$champ = array();

		$result = $this->DB->counters_play_champ_total($cache_date);
		while ($row = $result->fetch())
			$champ[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']/8);

		return array($random, $bot, $duel, $champ);
	}

	public function counters_puzzle($cache_date)
	{
		$result = $this->DB->counters_puzzle_easy($cache_date);
		$easy = $this->type_data($result);

		$result = $this->DB->counters_puzzle_normal($cache_date);
		$normal = $this->type_data($result);

		$result = $this->DB->counters_puzzle_hard($cache_date);
		$hard = $this->type_data($result);

		return array($easy, $normal, $hard);
	}

	public function counters_referrals($cache_date)
	{
		$result = $this->DB->counters_referrals($cache_date);
		$data = $this->type_data($result);

		return array($data);
	}

	public function counters_games($cache_date)
	{
		$result = $this->DB->counters_games($cache_date);
		$data = $this->type_data($result);

		return array($data);
	}

	public function counters_rounds($cache_date)
	{
		$rounds_total = array();
		$rounds_per_session_avg = array();
		$round_duration_avg = array();
		$sessions_total = array();

		$result = $this->DB->counters_rounds_total($cache_date);
		while ($row = $result->fetch())
			$rounds_total[$row['date']."-".$row['data']] = $row['value'];

		$result = $this->DB->counters_sessions_total($cache_date);
		while ($row = $result->fetch())
			$sessions_total[$row['date']] = $row['value'];

		$result = $this->DB->counters_time_total($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (empty($rounds_total[$date."-".$type]) || empty($sessions_total[$date]))
				continue;

			if (!isset($rounds_per_session_avg[$date]))
				$rounds_per_session_avg[$date] = array('date' => $date, 'type' => 0, 'value' => 0);

			$rounds_per_session_avg[$date]['value'] += round($rounds_total[$date."-".$type] / $sessions_total[$date], 2);
			$round_duration_avg[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => round($row['value'] / $rounds_total[$date."-".$type] / 60, 2));
		}

		$rounds_per_session_avg = array_values($rounds_per_session_avg);
		$round_duration_avg = array_values($round_duration_avg);

		return array($rounds_per_session_avg, $round_duration_avg);
	}

	public function counters_max_energy($cache_date)
	{
		$result = $this->DB->counters_max_energy($cache_date);
		$data = $this->type_data($result);

		return array($data);
	}

	public function counters_bots($cache_date)
	{
		$total = array();
		$wins = array();
		$percent = array();

		$result = $this->DB->counters_bots($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($type > 10)
				$type = $type - 4294967296;
			else
				$wins[$date."-".$type] = $row['value'];

			if (!isset($total[$date."-".$type]))
				$total[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);

			$total[$date."-".$type]['value'] += $row['value'];

			if (isset($wins[$date."-".$type]))
				$percent[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => round($wins[$date."-".$type]/$total[$date."-".$type]['value']*100, 1));
		}

		$total = array_values($total);
		$percent = array_values($percent);

		return array($total, $percent);
	}

	public function counters_friends($cache_date)
	{
		$result = $this->DB->counters_friends($cache_date);
		$friends = $this->type_data($result);

		return array($friends);
	}

	/**
	 * Игроки
	 */
	public function players_new($cache_date)
	{
		$result = $this->DB->players_new_all($cache_date);
		$all = $this->type_data($result);

		$net = array();

		$result = $this->DB->players_new_net($cache_date);
		$net = $this->type_data($result);

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

		return array($all, $net, $ages, $sex);
	}

	public function players_bans($cache_date)
	{
		$result = $this->DB->players_bans($cache_date);
		$bans = $this->type_data($result);

		return array($bans);
	}

	public function players_retention($cache_date)
	{
		$retention = array();

		$result = $this->DB->players_retention_all($cache_date);
		while ($row = $result->fetch())
		{
			for ($interval = count($this->retention_intervals)-1; $interval >= 0; $interval--)
			{
				$current = $this->retention_intervals[$interval];
				$date = $row['date'];

				if ($current > $row['data'])
					continue;

				if (!isset($retention[$date."-".$current]))
					$retention[$date."-".$current] = array('date' => $date, 'type' => $current, 'value' => 0);

				$retention[$date."-".$current]['value'] += $row['value'];
				break;
			}
		}

		$result = $this->DB->players_lost($cache_date);
		while ($row = $result->fetch())
			$retention[$row['date']."-0"] = array('date' => $row['date'], 'type' => 0, 'value' => $row['value']);

		$retention = array_values($retention);

		return array($retention);
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
		$levels = array(0 => "<5", 5 => "5-14", 15 => "15-49", 50 => "50-109", 110 => "110-229", 230 => "230-529", 530 => "530-1499", 1500 => "1500-3999", 4000 => "4000+");
		$data = array();

		$result = $this->DB->players_levels();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_index($row['data'], $levels);

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$date."-".$type]['value'] += $row['value'];
		}

		$data = array_values($data);

		return array($data);
	}

	public function players_chat($cache_date)
	{
		$result = $this->DB->players_chat($cache_date);
		$chat = $this->type_data($result);

		return array($chat);
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

			if (!isset($this->costs[$net]))
				continue;
			$row['sum'] *= $this->costs[$net];

			if (!isset($data[$date."-".$type]))
				$data[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => 0, 'full' => false);
			$point = &$data[$date."-".$type];

			$point['value'] += $row['sum']*100;

			if (!isset($data_all[$date]))
				$data_all[$date] = array('date' => $date, 'type' => 0, 'value' => 0, 'full' => false);
			$data_all[$date]['value'] += $row['sum']*100;
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

			$point['value'] = round($point['value'] / $value, 3);
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

			$point['value'] = round($point['value'] / $value, 3);
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

			if (!isset($this->costs[$net]))
				continue;
			$row['sum'] *= $this->costs[$net];

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
		if ($age >= 50)
			return 7;
		if ($age >= 41)
			return 6;
		if ($age >= 36)
			return 5;
		if ($age >= 30)
			return 4;
		if ($age >= 24)
			return 3;
		if ($age >= 18)
			return 2;
		if ($age >= 0)
			return 1;
		return 0;
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

	private function get_index($data, $values, $default = false)
	{
		$last = $default;
		reset($values);
		while (list($key, $value) = each($values))
		{
			$condition = preg_replace("/\d+/", "", $value);
			$subject = preg_replace("/[^\d]/", "", $value);
			$subject = intval($subject);

			if ($condition == "" && $data == $subject)
				return $key;

			if ($condition == "<" && $data < $subject)
				return $key;

			if ($condition == ">" && $data > $subject)
				return $key;

			if ($condition == "-")
			{
				list($begin, $end) = explode("-", $value);

				if ($data >= $begin && $data <= $end)
					return $key;
			}

			if ($condition == "+" && $data >= $subject)
				$last = $key;

			if ($condition == "+" && $data < $subject)
				return $last;
		}

		return $last;
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