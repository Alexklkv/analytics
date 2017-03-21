<?php

require_once "bottle_counters.inc.php";

use Bottle\Counters as Counters;

/**
 * Реализует отчёты Бутылочки
 *
 * @uses DatabaseInterface
 * @uses DateTime
 * @uses ObjectCache
 * @uses ObjectEasyForms
 * @uses ObjectLog
 * @uses ObjectTemplates
 *
 * @version 1.0.1
 */
class ObjectBottle extends Object implements DatabaseInterface
{
	/**
	 * Идентификатор проекта в системе аналитики
	 */
	private static $service_id;

	const CacheClass = "bottle";

	const OfferNone = 0;

	const ReferrersMobile	= "100001, 110001";
	const ReferrersMax	= 110001;

	const MobileProviders	= "33, 40, 41";		// HTML5, Apple, Google

	const BombId = 1012;

	private $networks = array(0 => "ВКонтакте", 1 => "МойМир", 4 => "Одноклассники", 5 => "Facebook", 6 => "Мамба", 30 => "ФотоСтрана", 32 => "StandAlone");
	private $ages = array(0 => "1-13", 1 => "14-15", 2 => "16-17", 3 => "18-34", 4 => "35+", 99 => "Не задан");
	private $sex = array(2 => "Мужской", 1 => "Женский", 0 => "Не задан");
	private $devices = array(2 => "iOS", 3 => "Android", 4 => "HTML5");
	private $goods = array(0 => "Лента", 1 => "Сердечки", 2 => "Подарки", 3 => "Бомба", 4 => "Магия", 5 => "Ухаживания", 6 => "Друзья", 7 => "Стол", 10 => "RICH", 11 => "Комнаты", 12 => "Хочу общаться", 14 => "Бутылка", 15 => "Музыка", 22 => "VIP-Карта", 23 => "Звуковая тема", 24 => "Открытие профиля", 25 => "Перенос даты свадьбы", 26 => "Бесплатные подарки", 27 => "Комнаты в домах", 28 => "Томаты", 29 => "Костюм", 30 => "Костюм в подарок", 31 => "Бесплатные бомбы", 32 => "Много внимания (PowerUP)", 34 => "Очищающая магия", 36 => "Воздушный поцелуй", 37 => "Тортики", 38 => "Далацин", 39 => "Фон профиля", 40 => "Украшение дерева", 41 => "Уровни дерева", 42 => "Элитный рейтинг", 43 => "Очки роста дерева", 44 => "Ускорение роста яйца", 45 => "Подарки Bruno Banani", 46 => "Стикеры", 47 => "Сертификат стикеров", 48 => "Приоритетные показы", 49 => "[Хэллоуин] Ингридиенты", 50 => "[Хэллоуин] Акция", 218 => "Игра Мафия", 219 => "Валентинка конкретному игроку", 220 => "Валентинки случайным игрокам", 221 => "Музыкальный подарок", 222 => "Роль доктора", 223 => "Роль комиссара", 224 => "Роль мафии", 225 => "Радио", 226 => "Воскрешение питомца", 227 => "Действия питомцев", 228 => "Одежда питомцев", 229 => "Питомцы", 230 => "Дарение набора мебели", 231 => "Статусный подарок", 232 => "Уникальный подарок", 233 => "VIP-Подписка", 236 => "Дарение мебели в дом", 237 => "[Свадьба] Развод", 238 => "[Свадьба] Торт", 239 => "[Свадьба] Приглашение", 240 => "[Свадьба] Букет", 241 => "[Свадьба] Подвязка", 242 => "[Свадьба] Ведущий", 243 => "[Свадьба] Бутылочка", 244 => "[Свадьба] Стол", 245 => "[Свадьба] Кольцо", 246 => "[Свадьба] Предложение", 251 => "VIP-Подарки", 252 => "Смена возраста", 255 => "Статус");
	private $periods = array(0 => "0-1d", 2 => "2-7d", 8 => "8-14d", 15 => "15-30d", 31 => "31d+");
	private $retentions = array(0 => "1d", 1 => "2d", 2 => "7d", 3 => "14d", 4 => "30d", 5 => "1d+", 6 => "2d+", 7 => "7d+", 8 => "14d+", 9 => "30d+");
	private $rooms = array(2 => "2 комната", 3 => "3 комната", 4 => "4 комната", 5 => "5 комната", 6 => "6 комната", 7 => "7 комната", 8 => "8 комната", 9 => "9 комната", 10 => "10 комната", 11 => "11 комната", 12 => "12 комната", 13 => "13 комната", 14 => "14 комната", 15 => "15 комната", 16 => "16 комната", 17 => "17 комната", 18 => "18 комната");
	private $pages = array(0 => "Игра", 1 => "Поиск", 3 => "Комната", 4 => "Своя комната", 6 => "Профиль", 7 => "Чат", 8 => "Рейтинг", 9 => "Свадьба", 10 => "Достижения", 12 => "Друзья", 13 => "Сообщения", 14 => "Мафия");
	private $screens = array(0 => "Профиль", 1 => "Игра", 2 => "Игра: Крутящему бутылочку", 3 => "Игра: Через чат", 4 => "Поиск: Общий", 5 => "Поиск: По региону", 6 => "Чат: Топ-лента", 7 => "Чат: Хочу общаться", 8 => "Чат: Общий", 9 => "Чат: По возрасту", 10 => "Чат: Вип", 11 => "Чат: В привате", 12 => "Чат: Анонимный", 13 => "Рейтинг: Общий", 14 => "Рейтинг: Дневной", 15 => "Рейтинг: Форбс", 16 => "Рейтинг: Амур", 17 => "Амур", 18 => "Амур: Главному игроку", 19 => "Амур: Через ленту", 20 => "Мафия", 21 => "Свадьба", 22 => "Дома", 23 => "Рейтинг: Свадьбы", 24 => "Рейтинг: Питомцы", 25 => "Рейтинг: Дома");
	private $tags = array(0 => "Без тега", 24 => "Фотопоказы отдельной вкладкой", 25 => "Фотопоказы в маленькой форме", 26 => "Прогресс бар 1", 27 => "Прогресс бар 2", 28 => "Виральные действия", 29 => "Виральный квест", 30 => "Фотопоказы отдельно", 31 => "Фотопоказы вместе с бутылками", 32 => "Обучалка через флеш ролик", 33 => "Обучалка через тултипы");
	private $tabs = array(1 => "Колдовство", 2 => "Сердечки", 3 => "Разные", 4 => "VIP", 5 => "Цветы", 6 => "Еда и напитки", 7 => "Мужские", 8 => "18+", 9 => "Семейная жизнь", 10 => "Свадебные", 11 => "День рождения", 12 => "Весенние", 13 => "Всё для дома", 14 => "Уникальные", 15 => "Статусные", 16 => "Костюмы", 17 => "Питомцы", 18 => "Популярные", 19 => "С Днем Победы", 20 => "Майские", 21 => "Пасха", 22 => "1 апреля", 23 => "8 марта", 24 => "Музыкальные", 25 => "Летние 2014", 26 => "День России", 27 => "Сертификат", 28 => "Новые", 29 => "Осенние подарки", 30 => "Мафия", 32 => "Хэллоуин", 33 => "Зимние", 34 => "Романтика", 35 => "Эксклюзивные", 36 => "50 Оттенков серого", 37 => "23 февраля", 38 => "Весенние", 39 => "8 марта", 40 => "Пасха", 41 => "Майские", 42 => "9 мая", 43 => "Летние 2015", 44 => "День поцелуев", 45 => "День шоколада", 46 => "Поцелуйчики", 47 => "Комплименты", 48 => "День науки", 49 => "Ф.М. Достоевский", 50 => "День Деда Мороза", 51 => "День Бармена", 52 => "Весенние", 55 => "День космонавтики", 57 => "Пасха", 58 => "Пасхальные яйца", 59 => "9 Мая", 60 => "bruno banani", 61 => "Хоккей", 62 => "Летние 2016", 64 => "Футбол");

	private $currency = " р.";
	private $content = "http://bottle2.itsrealgames.com/content.xml";

	private $categories = array();
	private $gifts = array();

	private $referrers_vk = array(0 => "Прямые переходы [0]", 1 => "Рекламный блок в каталоге [catalog_ads][1]", 2 => "Популярное в каталоге [catalog_popular][2]", 3 => "Активность друзей [friends_feed][3]", 4 => "Со стены пользователя [wall_view, wall_view_inline][4]", 5 => "Из приложений группы [group][5]", 6 => "По приглашению [request][6]", 7 => "Быстрый поиск [quick_search][7]", 8 => "Мои приложения [user_apps][8]", 9 => "Из левого меню [menu][9]", 10 => "Из уведомления [notification][10]", 11 => "Уведомление в реальном времени [notification_realtime][11]", 12 => "Специальный рекламный блок в каталоге [app_suggestions][12]", 13 => "Рекомендуемые в каталоге [featured][13]", 14 => "Из статуса [profile_status][14]", 15 => "Кассовые приложения [top_grossing][15]", 16 => "По названию из приглашения [join_request][16]", 17 => "Приложения друзей [friends_apps][17]", 18 => "Подборки приложений [collections][18]", 22 => "Страница уведомлений passive_friend_invitation [notifications_page][22]", 1002 => "Реклама [ad_16070976][1002]", 1003 => "Реклама [ad_19377013][1003]", 1004 => "Реклама [ad_19427320][1004]", 1005 => "Реклама [ad_19471304][1005]", 1006 => "Реклама [ad_19519929][1006]", 1007 => "Реклама [ad_22960631][1007]", 1008 => "Реклама [ad_22960631][1008]", 1009 => "Реклама [ad_29089750][1009]");
	private $referrers_mm = array(10000 => "Прямые переходы [10000]", 10001 => "Лента активности - установка [stream.install][10001]", 10002 => "Лента активности - действия [stream.publish][10002]", 10003 => "По приглашению [invitation][10003]", 10004 => "Лучшие в каталоге [catalog][10004]", 10005 => "«Попробуйте» на странице приложения [suggests][10005]", 10006 => "«Попробуйте» из левого меню [left_menu_suggest][10006]", 10007 => "Новинки в каталоге [new apps][10007]", 10008 => "Из гостевой книги [guestbook][10008]", 10009 => "«Играть» Mail.Ru Агента [agent][10009]", 10010 => "Поиск [search][10010]", 10011 => "Левое меню [left_menu][10011]", 10012 => "Promo [promo][10012]", 10013 => "Mail.ru рекоммендует [mailru_featured][10013]", 10014 => "Виджет [widget][10014]", 10015 => "Установленные приложения [installed_apps][10015]", 10016 => "Баннер в каталоге [banner_catalog][10016]", 10017 => "Уведомление [notification][10017]", 10018 => "Приложения друга [friends_apps][10018]", 10019 => "Реклама [advertisement][10019]", 10020 => "Левое меню Promo [left_promo][10020]", 10021 => "Лента Promo [feed_promo][10021]", 10022 => "[request][10022]");
	private $referrers_ok = array(20000 => "Прямые переходы [20000]", 20001 => "Из каталога [catalog][20001]", 20002 => "Из баннера в каталоге [banner][20002]", 20003 => "По приглашению [friend_invitation][20003]", 20004 => "Лента активности [friend_feed][20004]", 20005 => "Из оповещения [friend_notification][20005]", 20006 => "Новые приложения [new_apps][20006]", 20007 => "Топ приложений [top_apps][20007]", 20008 => "Поиск [app_search_apps][20008]", 20009 => "Мои приложения [user_apps][20009]", 20010 => "Уведомление [app_notification][20010]", 20011 => "Приложения друга [friend_apps][20011]", 20012 => "Список приложений внизу [user_apps_bottom_app_main][20012]", 20013 => "Подсказки игрокам [friend_suggest][20013]", 20014 => "По пассивному приглашению [passive_friend_invitation][20014]", 21001 => "Реклама [ref1][21001]", 21002 => "Реклама [ref2][21002]", 21003 => "Реклама [ref3][21003]", 21004 => "Реклама [ref4][21004]", 21005 => "Реклама [ref5][21005]", 21006 => "Реклама [ref6][21006]", 21007 => "Реклама [ref7][21007]", 21008 => "Реклама [ref8][21008]", 21009 => "Реклама [ref9][21009]", 21010 => "Реклама [ref10][21010]", 21011 => "Реклама [ref11][21011]", 21012 => "Реклама [ref12][21012]", 21013 => "Реклама [ref13][21013]", 21016 => "Реклама [ref16][21016]");
	private $referrers_fb = array(30000 => "Прямые переходы [30000]", 30001 => "[aggregation][30001]", 30002 => "Центр приложений [appcenter][30002]", 30003 => "Центр приложений по инвайту [appcenter_request][30003]", 30004 => "Закладки в профиле, блок приложений [bookmark_apps][30004]", 30005 => "Избранное в профиле [bookmark_favorites][30005]", 30006 => "\"Показать еще\" в закладках [bookmark_seeall][30006]", 30007 => "Закладки в приложениях [canvasbookmark][30007]", 30008 => "\"Показать еще\" приложениях [canvasbookmark_more][30008]", 30009 => "Рекоммендованные в приложениях [canvasbookmark_recommended][30009]", 30010 => "Старая лента закладки [dashboard_bookmark][30010]", 30011 => "Старая лента топ приложений [dashboard_toplist][30011]", 30012 => "Диалог разрешений [dialog_permission][30012]", 30013 => "Предложенные приложения [ego][30013]", 30014 => "Лента [feed][30014]", 30015 => "[nf][30015]", 30016 => "Лента Достижение [feed_achievement][30016]", 30017 => "Лента Лучших результатов [feed_highscore][30017]", 30018 => "Лента Пост с музыкой [feed_music][30018]", 30019 => "Лента Остальное [feed_opengraph][30019]", 30020 => "Лента Победа над другим игроком [feed_passing][30020]", 30021 => "Лента Играют сейчас [feed_playing][30021]", 30022 => "Лента Видео пост [feed_video][30022]", 30023 => "Мои недавние игры [games_my_recent][30023]", 30024 => "Игры друзей [games_friends_apps][30024]", 30025 => "Диалог при наведении на приложение [hovercard][30025]", 30026 => "Из сообщения [message][30026]", 30027 => "[mf][30027]", 30028 => "Из уведомлений [notification][30014]", 30029 => "[other_multiline][30029]", 30030 => "[pymk][30030]", 30031 => "Последняя активность [recent_activity][30031]", 30032 => "Напоминания о частых приложениях [reminders][30032]", 30033 => "[request][30033]", 30034 => "Поиск [search][30034]", 30035 => "[ticker][30035]", 30036 => "История пользователя в приложении [timeline_og][30036]", 30037 => "История последних действий [timeline_news][30037]", 30038 => "История победа над игроком [timeline_passing][30038]", 30039 => "История недавние достижения [timeline_recent][30039]", 30040 => "Закладка в боковой панели [sidebar_bookmark][30040]", 30041 => "Рекоммендованные в боковой панели [sidebar_recommended][30041]");

	private $collections_names = array(0 => "Первая", 1 => "Вторая", 2 => "Третья", 3 => "Четвёртая", 4 => "Пятая", 5 => "Шестая", 10 => "Коллекция тучек", 11 => "Коллекция урожая", 12 => "Коллекция листьев", 13 => "Коллекция одежды", 14 => "Коллекция «Шесть чудес света»", 15 => "Коллекция XCombat", 20 => "Свадебные напитки", 21 => "Свадебные кольца", 22 => "Свадебные игрушки", 23 => "Свадебные букеты", 24 => "Свадебные торты", 30 => "Олимпийские награды", 31 => "Олимпийская любовь", 32 => "Олимпийские талисманы", 33 => "Олимпийские виды спорта", 34 => "Олимпийские города", 40 => "Коллекция замков", 41 => "Коллекция фруктов", 42 => "Коллекция ракушек", 43 => "Коллекция рыбок", 44 => "Коллекция кораблей", 50 => "Осенние грибочки", 51 => "Осенний урожай", 52 => "Осенний лес", 53 => "Осенняя мода", 54 => "Осенние подарки", 60 => "Магические сладости", 61 => "Волшебные эликсиры", 62 => "Коты полуночи", 63 => "Зловещий урожай", 64 => "Ночные духи", 70 => "Северное сияние", 71 => "Поцелуй снежной королевы", 72 => "В ожидании чуда", 73 => "Снежные истории", 74 => "Новогодние узоры", 75 => "Золотая колллекция", 80 => "В поисках мечты", 81 => "Мартовские котики", 82 => "Дары весны", 83 => "Чудесный сад", 84 => "Любовь торжествует", 85 => "Патриотическая", 90 => "Все на дачу", 91 => "На рыбалке", 92 => "Футбол", 93 => "Лето за городом", 94 => "Походная жизнь", 95 => "Морская", 96 => "Олимпиада — 80", 100 => "Кухня", 101 => "Гостиная", 102 => "Спортзал", 103 => "Гараж", 104 => "Кабинет", 110 => "Летние лакомства", 111 => "Путешествие", 112 => "Умелые руки", 113 => "Пляжный сезон", 114 => "Напитки", 115 => "Чудеса света", 116 => "Олимпиада в Рио", 120 => "Утепляемся!", 121 => "Поздние ягоды", 122 => "Отложенные витамины", 123 => "Осенние букеты", 124 => "Хмельной Октоберфест", 125 => "Дивный Хэллоуин", 126 => "Лесные зверята");

	private $wedding_items = array(246 => "Предложение", 245 => "Кольцо", 244 => "Стол", 243 => "Бутылочка", 242 => "Ведущий", 241 => "Подвязка", 240 => "Букет невесты", 239 => "Приглашение на свадьбу", 238 => "Свадебный торт", 237 => "Развод");

	private $bottles_flow = array(0 => "Без группы", 1 => "Активность", 2 => "Ухаживание", 3 => "Сердечки", 4 => "Вступление в группу", 5 => "Приглашение друзей", 6 => "Бонус за вход", 7 => "Коллекции", 8 => "Хэллоуин", 9 => "Пазлы", 10 => "Платёж друга", 12 => "За открытие шкатулок", 13 => "За репост на стену", 14 => "За лотерею", 15 => "Победа в мафии", 16 => "Новогодний бонус", 17 => "Бутылочка ухажёру", 18 => "Новые игроки", 19 => "Удаление комнат", 20 => "Платёж: бутылочки", 21 => "Валентинки", 22 => "Ромашки", 23 => "Бонус ауры Амура", 24 => "QIWI", 25 => "Отзывы", 26 => "Коробка", 28 => "Реклама в мобилках", 29 => "Виральные действия", 35 => "Кулон Везения", 36 => "Кулон Удачи", 37 => "Обучение", 38 => "Подарки офферов");

	private $tree_count = array(0 => "Пост на стену", 1 => "Поцелуй", 2 => "Дарение сердечка", 3 => "Трата бутылок", 4 => "Платёж", 5 => "Оценка фото", 6 => "Покупка за бутылки");
	private $tree_decor = array(0 => "Поцелуй", 1 => "Подарок", 2 => "Сердце", 3 => "Ухаживание", 4 => "Приглашение друга", 5 => "Прокачка комнаты (покупка мебели, дополнительных комнат)", 6 => "Прокачка питомца", 7 => "Платежи", 8 => "Оценка фото");
	private $tree_levelups = array("Платёж", "Покупка (только на стэндэлоне)", "Приглашение друга", "Пробуждение друга", "Поцелуи", "Подарки");

	private $counters_dau_new = array('all' => Counters::DAU_NEW_ALL, 'net' => Counters::DAU_NEW_NET, 'device' => Counters::DAU_NEW_DEVICE, 'age' => Counters::DAU_NEW_AGE, 'sex' => Counters::DAU_NEW_SEX, 'tag' => Counters::DAU_NEW_TAG, 'paying' => Counters::DAU_NEW_PAYING);
	private $counters_wau_new = array('all' => Counters::WAU_NEW_ALL, 'net' => Counters::WAU_NEW_NET, 'device' => Counters::WAU_NEW_DEVICE, 'age' => Counters::WAU_NEW_AGE, 'sex' => Counters::WAU_NEW_SEX, 'tag' => Counters::WAU_NEW_TAG, 'paying' => Counters::WAU_NEW_PAYING);
	private $counters_mau_new = array('all' => Counters::MAU_NEW_ALL, 'net' => Counters::MAU_NEW_NET, 'device' => Counters::MAU_NEW_DEVICE, 'age' => Counters::MAU_NEW_AGE, 'sex' => Counters::MAU_NEW_SEX, 'tag' => Counters::MAU_NEW_TAG, 'paying' => Counters::MAU_NEW_PAYING);

	private $counters_mau = array('all' => Counters::MAU_ALL, 'net' => Counters::MAU_NET, 'device' => Counters::MAU_DEVICE, 'age' => Counters::MAU_AGE, 'sex' => Counters::MAU_SEX, 'tag' => Counters::MAU_TAG);

	private $payments_specific = array(-30 => "30 Бутылочек", -75 => "75 Бутылочек", -150 => "150 Бутылочек", -750 => "750 Бутылочек", -1500 => "1500 Бутылочек", 0 => "Другие", -1 => "Коробки", -2 => "Кулоны", -3 => "Стикеры", -4 => "Ивент 23 февраля", 3 => "Интеграция ВК", 7 => "Влиятельные персоны", 23 => "Сертификат Стикеров", 30 => "Снежки", 33 => "Показы", 34 => "Подписка", 35 => "Пак показов", 36 => "Повышение уровня дерева", 49 => "Колесо фортуны", 50 => "Сундук со снежками", 55 => "Подарок FB", 70 => "Поздравление с 8 марта (личное)", 71 => "Поздравление с 8 марта (массовое)", 74 => "VIP-Подписка на неделю", 75 => "VIP-Подписка на месяц", 82 => "Обычная копилка", 83 => "Серебрянная копилка", 84 => "Золотая копилка", 86 => "Колодец");
	private $payments_boxes = array(39 => "Серебряная", 40 => "Золотая", 42 => "Сундук Шахерезады", 48 => "Ящик Пандоры", 72 => "Золотая (в подарок)", 73 => "Ящик Пандоры (в подарок)", 77 => "Сундук Изобилия ур.1", 78 => "Сундук Изобилия ур.2", 79 => "Сундук Изобилия ур.3", 80 => "Сундук Изобилия ур.4", 87 => "Элитный сундук", 88 => "Малахитовый сундук", 89 => "Волшебный сундук");
	private $payments_stickers = array(16 => "Кубики", 17 => "Рожицы", 18 => "Глаза", 19 => "Девушка", 20 => "Сердечки", 21 => "Зефирчик", 22 => "Ретро-смайлы", 24 => "Летние", 26 => "Пингвинчики", 27 => "Мафия", 28 => "Хэллоуин", 29 => "Снеговики", 37 => "Совушки", 45 => "Котики");

	private $posting_types = array(0 => "Скриншот", 1 => "Питомец заболел", 2 => "Приглашение в игру", 3 => "Дерево любви", 4 => "Обмен пазлами", 5 => "Получение пазла", 6 => "Достижение", 7 => "Обмен коллекциями", 8 => "Сердечко отдано", 9 => "Рейтинг Forbes", 10 => "Статусный подарок", 11 => "Подарок-приглашение");
	private $hours = array(0 => "Полночь", 1 => "01:00", 2 => "02:00", 3 => "03:00", 4 => "04:00", 5 => "05:00", 6 => "06:00", 7 => "07:00", 8 => "08:00", 9 => "09:00", 10 => "10:00", 11 => "11:00", 12 => "12:00", 13 => "13:00", 14 => "14:00", 15 => "15:00", 16 => "16:00", 17 => "17:00", 18 => "18:00", 19 => "19:00", 20 => "20:00", 21 => "21:00", 22 => "22:00", 23 => "23:00");

	private $newbies_game_time = array("1", "2", "3", "4", "5-10", "11-30", "31-60", "61+");
	private $newbies_kisses = array("1", "2", "3", "4", "5-10", "11-30", "31-60", "61+");
	private $newbies_rates_views = array("1", "2", "3", "4", "5-10", "11-30", "31-60", "61+");
	private $newbies_tree_level = array("1", "2", "3", "4", "5", "6", "7", "8", "9-12", "13-17", "18+");

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
			'payments_all'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`",
			'payments_net'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_age'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_sex'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_tag'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_device_new'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pl.`referrer` IN(".self::ReferrersMobile.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",

			'payments_candles'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, HOUR(pm.`time`) as `hour` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 1 DAY) GROUP BY `provider_id`, `date`, `hour`",
			'payments_weekly'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`",
			'payments_boxes'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`offer` as `data` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pm.`offer` IN(@l) GROUP BY `provider_id`, `date`, `data`",
			'payments_newbies'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` = DATE(pm.`time`) AND pm.`time` >= @s GROUP BY `provider_id`, `date`",
			'payments_stickers'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`offer` as `data` FROM `orders` pm WHERE pm.`time` >= @s AND pm.`offer` IN(@l) GROUP BY `provider_id`, `date`, `data`",
			'payments_specific'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, pm.`amount` as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`offer` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `offer`, `date`, `amount`",

			'payments_hourly'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, HOUR(pm.`time`) as `data` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'payments_hourly_last'		=> "SELECT IF(`date` = @s, 0, IF(`date` = @s, 1, 2)) as `type`, `chart`, `type` as `hour`, `value` FROM `@pcache` WHERE `service` = ".self::$service_id." AND `report` = 'payments_hourly' AND `date` IN(@s, @s, @s)",

			'payments_first'		=> "SELECT DATE(p2.`time`) as `date`, p2.`provider_id`, SUM(p2.`amount`) as `amount`, SUM(p2.`revenue`) as `revenue`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `orders` WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `orders` p2 FORCE INDEX (`time`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` = p1.`time` WHERE p2.`time` >= @s GROUP BY `date`, p2.`provider_id`",
			'payments_repeated'		=> "SELECT DATE(p2.`time`) as `date`, p2.`provider_id`, SUM(p2.`amount`) as `amount`, SUM(p2.`revenue`) as `revenue`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `orders` WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `orders` p2 FORCE INDEX (`type`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` != p1.`time` WHERE p2.`time` >= @s GROUP BY `date`, p2.`provider_id`",

			'payments_day_first'		=> "SELECT pl.`register_time` as `date`, DATEDIFF(pm.`time`, pl.`register_time`) as `days`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` GROUP BY `type`, `net_id`) pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' GROUP BY `date`, `days` ORDER BY `date` ASC",
			'payments_day_next'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `type`, pm.`net_id` FROM `orders` pm GROUP BY `provider_id`, `date`, `net_id` ORDER BY `time` ASC",

			'finance_arpu_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",

			'finance_arppu_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id`, `date`, `data`",

			'finance_ltv_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`",
			'finance_ltv_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_referrer'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pl.`referrer` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",

			'buyings_common'		=> "SELECT b.`time` as `date`, SUM(b.`price`) as `sum`, COUNT(*) as `count`, b.`good_id` as `data`, (IF(p.`payment_time` > 0, 1, 0)) as `paid` FROM `buyings` b INNER JOIN `players` p ON p.`inner_id` = b.`owner_id` WHERE b.`time` >= @s GROUP BY b.`time`, b.`good_id`, `paid`",
			'buyings_gifts'			=> "SELECT b.`time` as `date`, SUM(b.`price`) as `sum`, COUNT(*) as `count`, b.`data` as `data` FROM `buyings` b WHERE b.`time` >= @s AND b.`good_id` IN(2, 3, 4, 15, 231, 251) GROUP BY `date`, `data`",
			'buyings_wedding'		=> "SELECT b.`time` as `date`, SUM(b.`price`) as `sum`, COUNT(*) as `count`, b.`good_id` as `data` FROM `buyings` b WHERE b.`time` >= @s AND b.`good_id` IN(237, 238, 239, 240, 241, 242, 243, 244, 245, 246) GROUP BY `date`, `good_id`",
			'buyings_rooms'			=> "SELECT b.`time` as `date`, SUM(b.`price`) as `sum`, COUNT(*) as `count`, b.`data` as `data` FROM `buyings` b WHERE b.`time` >= @s AND b.`good_id` = 27 GROUP BY `date`, `data`",

			'counters_daily_get'		=> "SELECT `date`, `data`, `value`, `type` FROM `counters_daily` WHERE `type` = @i AND `date` >= @s",
			'counters_daily_load'		=> "SELECT `date`, `data`, `value`, `type` FROM `counters_daily` WHERE `type` IN(@l) AND `date` >= @s",

			'counters_weekly_get'		=> "SELECT IF(`week` = 52, LAST_DAY(CONCAT_WS('-', `year`, 12, 1)), DATE_ADD(CONCAT_WS('-', `year`, 1, 7), INTERVAL `week` WEEK)) as `date`, `data`, `value` FROM `counters_weekly` WHERE `type` = @i AND IF(`week` = 52, LAST_DAY(CONCAT_WS('-', `year`, 12, 1)), DATE_ADD(CONCAT_WS('-', `year`, 1, 7), INTERVAL `week` WEEK)) >= @s",
			'counters_monthly_get'		=> "SELECT LAST_DAY(CONCAT_WS('-', `year`, `month`, 1)) as `date`, `data`, `value` FROM `counters_monthly` WHERE `type` = @i AND LAST_DAY(CONCAT_WS('-', `year`, `month`, 1)) >= @s",

			'counters_online_all'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 0 AND `time` >= @s GROUP BY `date`",
			'counters_online_net'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, `data` FROM `counters` WHERE `type` = 1 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_age'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, `data` FROM `counters` WHERE `type` = 2 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_sex'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, IF(`type` = 95, 2, 1) as `data` FROM `counters` WHERE `type` IN(95, 96) AND `time` >= @s GROUP BY `date`, `type`",
			'counters_online_device'	=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, IF(`type` = 237, 2, 3) as `data` FROM `counters` WHERE `type` IN(237, 238) AND `time` >= @s GROUP BY `date`, `type`",

			'players_new_all'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, 0 as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_net'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `type` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_age'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_sex'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `sex` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_tag'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `tag` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_device'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`referrer` = 100001, 2, 3) as `data` FROM `players` WHERE `register_time` >= @s AND `referrer` IN(".self::ReferrersMobile.") GROUP BY `date`, `data`",
			'players_new_referrer'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `referrer` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_referrer_all'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `referrer` as `data` FROM `players` WHERE `register_time` >= @s AND `referrer` BETWEEN @i AND @i GROUP BY `date`, `data`",

			'players_new_net_paying'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `type` as `data` FROM `players` WHERE `register_time` >= @s AND `payment_time` > 0 GROUP BY `date`, `data`",
			'players_new_age_paying'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `data` FROM `players` WHERE `register_time` >= @s AND `payment_time` > 0 GROUP BY `date`, `data`",
			'players_new_sex_paying'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `sex` as `data` FROM `players` WHERE `register_time` >= @s AND `payment_time` > 0 GROUP BY `date`, `data`",
			'players_new_tag_paying'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `tag` as `data` FROM `players` WHERE `register_time` >= @s AND `payment_time` > 0 GROUP BY `date`, `data`",
			'players_new_device_paying'	=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`referrer` = 100001, 2, 3) as `data` FROM `players` WHERE `register_time` >= @s AND `payment_time` > 0 AND `referrer` IN(".self::ReferrersMobile.") GROUP BY `date`, `data`",

			'players_paying_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, pm.`provider_id` as `data`, pm.`provider_id`, COUNT(*) as `count` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",
			'players_paying_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",
			'players_paying_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",
			'players_paying_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",
			'players_paying_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",
			'players_paying_groups'		=> "SELECT DATE(pm.`time`) as `date`, pm.`net_id` as `net_id`, pm.`provider_id` as `data`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue` FROM `orders` pm WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 30 DAY) GROUP BY `date`, `net_id`, `data` ORDER BY `date` ASC",

			'players_retention_1d_all'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` DAY) as `registered`, DATEDIFF(`date`, '1970-01-01') - `data` as `days`, `value`, 0 as `data` FROM `counters_daily` WHERE `type` = 42",
			'players_retention_1d_net'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 43 AND `date` >= @s",
			'players_retention_1d_age'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 45 AND `date` >= @s",
			'players_retention_1d_sex'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 44 AND `date` >= @s",
			'players_retention_1d_tag'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFFFFFF DAY) as `registered`, `data` >> 32 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFFFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 354 AND `date` >= @s",
			'players_retention_1d_device'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 236 AND `date` >= @s",
			'players_retention_1d_paying'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` DAY) as `registered`, DATEDIFF(`date`, '1970-01-01') - `data` as `days`, `value`, 0 as `data` FROM `counters_daily` WHERE `type` = 356",
			'players_retention_1d_referrer'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFFFFFF DAY) as `registered`, `data` >> 32 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFFFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 240 AND `date` >= @s",

			'players_retention_all'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, 0 as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`",
			'players_retention_net'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `type` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_age'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_sex'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `sex` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_tag'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `tag` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_device'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, IF(`referrer` = 100001, 2, 3) as `data` FROM `players` WHERE `logout_time` != 0 AND `referrer` IN(".self::ReferrersMobile.") GROUP BY `date`, `days`, `data`",
			'players_retention_paying'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(DISTINCT pl.`inner_id`) as `value`, 0 as `data` FROM `players` pl INNER JOIN `orders` pm ON pm.`type` = pl.`type` AND pm.`net_id` = pl.`net_id` WHERE pl.`logout_time` != 0 AND pl.`register_time` >= '2016-01-01' GROUP BY `date`, `days`",
			'players_retention_referrer'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `referrer` as `data` FROM `players` WHERE `logout_time` != 0 AND `referrer` BETWEEN @i AND @i GROUP BY `date`, `days`, `data`",

			'players_bans'			=> "SELECT DATE(`start`) as `date`, 0 as `data`, COUNT(*) as `value` FROM `bans` WHERE `finish` >= @s GROUP BY `date`",

//			'events_collection_efficiency'	=> "SELECT DATE(`time`) as `date`, `provider_id`, SUM(`amount`) as `amount`, SUM(`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` WHERE `time` >= @s GROUP BY `date`, `provider_id`",

			'hidden_paying_month_net'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') AND pm.`provider_id` NOT IN(".self::MobileProviders.") GROUP BY `date`, `data`",
			'hidden_paying_month_age'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, IF(p.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(p.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_sex'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, p.`sex` as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_tag'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, p.`tag` as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_device'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') AND pm.`provider_id` IN(".self::MobileProviders.") GROUP BY `date`, `data`",

			'update_adv_params'		=> "REPLACE `@padv_params` SET `cache_service` = @i, `cache_report` = @s, `cache_type` = @i, `adv_cost` = @f, `clicks` = @i, `shows` = @i, `filename` = @s",

			'replace_cache'			=> "REPLACE INTO `@pcache` VALUES @t",

/*
			'players_ab_counts'		=> "SELECT r.*, SUM(IF(p.`invites_count` > 0, 1, 0)) as `players_inviters`, MAX(`register_time`) as `date` FROM `referrers` r INNER JOIN `players` p ON p.`tag` = r.`referrer` WHERE r.`referrer` IN(@t) AND r.`tier` = 2 GROUP BY r.`referrer`",
			'players_ab_active'		=> "SELECT `tag`, CAST(`game_time` / 60 as UNSIGNED) as `minutes`, COUNT(*) as `value` FROM `players` WHERE `tag` IN(@t) GROUP BY `tag`, `minutes`",
			'players_ab_payments'		=> "SELECT pl.`tag`, MAX(`balance`) as `max`, SUM(`balance`) as `sum`, SUM(`balance` * `balance`) as `squared`, COUNT(DISTINCT pl.`inner_id`) as `players` FROM `payments` pm INNER JOIN `players` pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` WHERE pl.`tag` IN(@t) GROUP BY pl.`tag`, pl.`type`",
			'players_ab_payments_groups'	=> "SELECT pl.`tag`, pm.`balance`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` WHERE pl.`tag` IN(@t) GROUP BY pl.`tag`, pm.`balance`",
			'players_ab_payments_newbie'	=> "SELECT pl.`tag`, SUM(pm.`balance`) as `sum`, COUNT(*) as `count` FROM `payments` pm INNER JOIN `players` pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` AND pl.`register_time` = DATE(pm.`time`) WHERE pl.`tag` IN(@t) GROUP BY pl.`tag`",
			'players_ab_distribution'	=> "SELECT `tag`, `type`, `sex`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `age`, COUNT(*) as `value` FROM `players` WHERE `tag` IN(@t) GROUP BY `tag`, `type`, `sex`, `age`",
			'players_ab_tags'		=> "SELECT DISTINCT `tag` FROM `players`",
			'players_ab_photoshows'		=> "SELECT `date`, `data`, `value` FROM `counters_daily` WHERE `type` = 424 AND `date` >= @s AND `data` > 0",
*/

			'players_ad_counts'		=> "SELECT r.* FROM `referrers` r INNER JOIN `players` p ON p.`referrer` = r.`referrer` WHERE r.`referrer` IN(@t) AND r.`tier` < 2 GROUP BY r.`referrer`, r.`tier`",
			'players_ad_active'		=> "SELECT `referrer`, 0 as `tier`, CAST(`game_time` / 60 as UNSIGNED) as `minutes`, COUNT(*) as `value` FROM `players` WHERE `referrer` IN(@t) GROUP BY `referrer`, `minutes` UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, CAST(`game_time` / 60 as UNSIGNED) as `minutes`, COUNT(*) as `value` FROM `players` WHERE `inviter_referrer` IN(@t) GROUP BY `inviter_referrer`, `minutes`",
			'players_ad_retention'		=> "SELECT `referrer`, 0 as `tier`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `referrer` IN(@t) GROUP BY `referrer`, `days` UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `inviter_referrer` IN(@t) GROUP BY `inviter_referrer`, `days`",
			'players_ad_payments'		=> "SELECT pl.`referrer`, pl.`tier`, DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `sum`, MAX(`revenue`) as `max`, SUM(`revenue` * `revenue`) as `squared`, COUNT(DISTINCT pl.`inner_id`) as `players` FROM `orders` pm INNER JOIN (SELECT `referrer`, 0 as `tier`, `inner_id`, `net_id`, `type` FROM `players` WHERE `referrer` IN(@t) UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `inner_id`, `net_id`, `type` FROM `players` WHERE `inviter_referrer` IN(@t)) pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` GROUP BY pl.`referrer`, pl.`tier`, `date`, pm.`provider_id`, pl.`type`",
			'players_ad_payments_age'	=> "SELECT pl.`referrer`, pl.`tier`, DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `sum`, COUNT(*) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pl.`register_time`)) as `age` FROM `orders` pm INNER JOIN (SELECT `referrer`, 0 as `tier`, `bday`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `referrer` IN(@t) UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `bday`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `inviter_referrer` IN(@t)) pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` GROUP BY pl.`referrer`, pl.`tier`, `date`, pm.`provider_id`, `age`",
			'players_ad_payments_sex'	=> "SELECT pl.`referrer`, pl.`tier`, DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `sum`, COUNT(*) as `count`, pl.`sex` FROM `orders` pm INNER JOIN (SELECT `referrer`, 0 as `tier`, `sex`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `referrer` IN(@t) UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `sex`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `inviter_referrer` IN(@t)) pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` GROUP BY pl.`referrer`, pl.`tier`, `date`, pm.`provider_id`, pl.`sex`",
			'players_ad_payments_groups'	=> "SELECT pl.`referrer`, pl.`tier`, DATE(pm.`time`) as `date`, pm.`provider_id`, pm.`revenue`, COUNT(*) as `count` FROM `orders` pm INNER JOIN (SELECT `referrer`, 0 as `tier`, `inner_id`, `net_id`, `type` FROM `players` WHERE `referrer` IN(@t) UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `inner_id`, `net_id`, `type` FROM `players` WHERE `inviter_referrer` IN(@t)) pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` GROUP BY pl.`referrer`, pl.`tier`, `date`, pm.`provider_id`, pm.`revenue`",
			'players_ad_payments_newbie'	=> "SELECT pl.`referrer`, pl.`tier`, DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `sum`, COUNT(*) as `count` FROM `orders` pm INNER JOIN (SELECT `referrer`, 0 as `tier`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `referrer` IN(@t) UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `register_time`, `inner_id`, `net_id`, `type` FROM `players` WHERE `inviter_referrer` IN(@t)) pl ON pl.`net_id` = pm.`net_id` AND pl.`type` = pm.`type` AND pl.`register_time` = DATE(pm.`time`) GROUP BY pl.`referrer`, pl.`tier`, `date`, pm.`provider_id`",
			'players_ad_distribution'	=> "SELECT `referrer`, 0 as `tier`, `type`, `sex`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `age`, COUNT(*) as `value` FROM `players` WHERE `referrer` IN(@t) GROUP BY `referrer`, `type`, `sex`, `age` UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, `type`, `sex`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `age`, COUNT(*) as `value` FROM `players` WHERE `inviter_referrer` IN(@t) GROUP BY `inviter_referrer`, `type`, `sex`, `age`",
			'players_ad_inviters'		=> "SELECT `referrer`, 0 as `tier`, COUNT(*) as `value` FROM `players` WHERE `referrer` IN(@t) AND `invites_count` > 0 GROUP BY `referrer` UNION ALL SELECT `inviter_referrer` as `referrer`, 1 as `tier`, COUNT(*) as `value` FROM `players` WHERE `inviter_referrer` IN(@t) AND `invites_count` > 0 GROUP BY `inviter_referrer`",
			'players_ad_referrers'		=> "SELECT `referrer` FROM `referrers` WHERE `tier` = 0",
			'players_ad_parameters'		=> "SELECT `cache_type`, `adv_cost`, `clicks`, `shows` FROM `@padv_params` WHERE `cache_service` = @i",
			'players_ad_revenue'		=> "SELECT `type`, `value` FROM `@pcache` WHERE `report` = 'hidden_revenue_ad' AND `service` = @i",
			'players_ad_report'		=> "SELECT `date`, `type`, `value` FROM `an_cache` WHERE `report` = 'players_ad' AND `chart` = 0 AND `service` = @i",
			'players_ad_campaign_data'	=> "SELECT * FROM `@padv_params` WHERE `cache_service` = @i AND `cache_report` = @i AND `cache_type` = @i"
		);
	}

	public function get_jobs()
	{
		return array(200 => "");
	}

	public function get_categories()
	{
		return array(
			'payments'	=> "Платежи",
			'finance'	=> "Финансы",
			'buyings'	=> "Покупки",
			'counters'	=> "Счётчики",
			'players'	=> "Игроки",
			'events'	=> "Эвенты",
			'api'		=> "События",
			'mafia'		=> "Мафия",
			'hidden'	=> "Скрытая категория",
			'apipath'	=> "Скрытая для путей"
		);
	}

	public function get_reports()
	{
		$this->parse_content();

		$id = 0;
		return array(
			'hidden' => array(
				'payments_all' => array(
					'id'		=> $id++,
					'title'		=> "Все платежи игроков",
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
					'title'		=> "Платежи по сетям",
					'description'	=> "Сумма платежей для каждой соц. сети в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->networks
						)
					),
					'hidden'	=> true
				),
				'payments_device' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по устройствам",
					'description'	=> "Сумма платежей для каждого устройства",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->devices
						)
					),
					'hidden'	=> true
				),
				'payments_age' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по возрасту",
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
					'title'		=> "Платежи по полу",
					'description'	=> "Сумма платежей для каждого пола в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->sex
						)
					),
					'hidden'	=> true
				),
				'payments_tag' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по тэгам",
					'description'	=> "Сумма платежей для маркированных пользователей в рублях",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->tags
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
							'legend'	=> $this->networks,
						),
						array(
							'title'		=> "По устройствам",
							'legend'	=> $this->devices,
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'hidden'	=> true
				),
				'revenue_ad' => array(
					'id'		=> $id++,
					'title'		=> "Доход от рекламных кампаний",
					'description'	=> "Доход от игроков, привлечённых рекламной кампанией, по дате старта рекламной кампании",
					'graphs'	=> array(
						array(
							'title'		=> "Общий",
							'legend'	=> array(0 => "Сумма")
						)
					),
					'hidden'	=> true,
					'cache'		=> false
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
					'hidden'	=> true
				)
			),
			'payments' => array(
				'all' => array(
					'id'		=> $id++,
					'title'		=> "Все платежи",
					'description'	=> "Сумма и количество платежей",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
				'candles' => array(
					'id'		=> $id++,
					'type'		=> "candles",
					'title'		=> "Все платежи (свечи)",
					'description'	=> "Сумма платежей с минимальным и максимальным значениями в час",
					'legend'	=> "Сумма",
					'graphs'	=> array(
						'value_append'	=> $this->currency
					)
				),
				'specific' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по типам",
					'description'	=> "Сумма и количество платежей, с разделением по типам",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->payments_specific,
							'value_append'	=> $this->currency

						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->payments_specific
						)
					)
				),
				'hourly' => array(
					'id'		=> $id++,
					'type'		=> "stacked",
					'title'		=> "Платежи по часам",
					'description'	=> "Сумма и количество платежей по часам",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->hours,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->hours
						)
					)
				),
				'hourly_last' => array(
					'id'		=> $id++,
					'type'		=> "special",
					'title'		=> "Последние по часам",
					'description'	=> "Сумма и количество платежей за вчера и сегодня и за тот же день недели, что и сегодня, неделю назад",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> array(0 => "Сумма сегодня", 1 => "Сумма вчера", 2 => "Сумма неделю назад"),
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Количество сегодня", 1 => "Количество вчера", 2 => "Количество неделю назад")
						),
					),
					'params'	=> array(
						'show_sums'	=> false
					),
					'cache'		=> false
				),
				'weekly' => array(
					'id'		=> $id++,
					'type'		=> "weekly",
					'title'		=> "Платежи по дням недели",
					'description'	=> "Сумма и количество платежей по дням недели",
					'graphs'	=> array(
						array(
							'title'		=> "Понедельник",
							'legend'	=> array("Понедельник Сумма", "Понедельник Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Вторник",
							'legend'	=> array("Вторник Сумма", "Вторник Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Среда",
							'legend'	=> array("Среда Сумма", "Среда Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Четверг",
							'legend'	=> array("Четверг Сумма", "Четверг Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Пятница",
							'legend'	=> array("Пятница Сумма", "Пятница Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Суббота",
							'legend'	=> array("Суббота Сумма", "Суббота Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						),
						array(
							'title'		=> "Воскресенье",
							'legend'	=> array("Воскресенье Сумма", "Воскресенье Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
				'weekly_net' => array(
					'id'		=> $id++,
					'type'		=> "weekly",
					'title'		=> "Платежи по дням недели по сетям",
					'description'	=> "Сумма и количество платежей по дням недели по сетям",
					'graphs'	=> array(
						array(
							'title'		=> "Пн: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Пн: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Вт: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Вт: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Ср: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Ср: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Чт: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Чт: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Пт: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Пт: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Сб: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Сб: Количество",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "Вс: Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Вс: Количество",
							'legend'	=> $this->networks
						),
					)
				),
				"-",
				'net' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по сетям",
					'description'	=> "Сумма и количество платежей для каждой соц. сети",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->networks,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->networks
						)
					)
				),
				'device' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по устройствам",
					'description'	=> "Сумма и количество платежей для каждого устройства",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->devices,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "Сумма платежей пользователей, привлеченных с iOS и Android",
							'legend'	=> $this->devices,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество платежей пользователей, привлеченных с iOS и Android",
							'legend'	=> $this->devices
						)
					)
				),
				'age' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по возрасту",
					'description'	=> "Сумма и количество платежей для каждой возрастной группы",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->ages,
							'value_append'	=> $this->currency
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
					'description'	=> "Сумма и количество платежей для каждого пола",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->sex,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->sex
						)
					)
				),
				'tag' => array(
					'id'		=> $id++,
					'title'		=> "Платежи по тегам",
					'description'	=> "Сумма и количество платежей для маркированных пользователей",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->tags,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->tags
						)
					)
				),
				"-",
				'first' => array(
					'id'		=> $id++,
					'title'		=> "Первые платежи",
					'description'	=> "Сумма и количество платежей, сделанных игроками в первый раз",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
				'repeated' => array(
					'id'		=> $id++,
					'title'		=> "Повторные платежи",
					'description'	=> "Сумма и количество платежей, сделанных игроками повторно",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
				'newbies' => array(
					'id'		=> $id++,
					'title'		=> "Платежи новичков",
					'description'	=> "Сумма и количество платежей, сделанных игроками в течении суток с момента регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
				"-",
				'day_first' => array(
					'id'		=> $id++,
					'title'		=> "Время первого платежа",
					'description'	=> "Разделение первых платежей по дням, прошедших с момента регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "Проценты",
							'legend'	=> $this->periods,
							'value_append'	=> "%"
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
				'day_next' => array(
					'id'		=> $id++,
					'title'		=> "Время между платежами",
					'description'	=> "Сумма и количество платежей, совершенных через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->periods,
							'value_append'	=> $this->currency
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
/*
				"-",
				'bonus' => array(
					'id'		=> $id++,
					'title'		=> "Использованный 2x бонус",
					'description'	=> "Сумма и количество платежей с начислением двойного бонуса",
					'graphs'	=> array(
						array(
							'title'		=> "Платежи",
							'legend'	=> array(0 => "Сумма", 1 => "Количество"),
							'split_axis'	=> array("0", "1"),
							'value_append'	=> array($this->currency, "")
						)
					)
				),
*/
				"-",
				'boxes' => array(
					'id'		=> $id++,
					'title'		=> "Покупка коробок",
					'description'	=> "Сумма и количество платежей по коробокам",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->payments_boxes,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->payments_boxes
						)
					)
				),
				'boxes_efficiency' => array(
					'id'		=> $id++,
					'type'		=> "nodate",
					'title'		=> "Эффективность коробок",
					'description'	=> "Наложение сумм в рублях и количества платежей на время продаж разных коробок",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->payments_boxes,
							'value_append'	=> $this->currency
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->payments_boxes
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
					'description'	=> "Средний доход от игрока за день в рублях",
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
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
					'description'	=> "Средний доход от платящего игрока за день в рублях",
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency,
						'show_sums'	=> false,
						'indicator'	=> array('type' => "function", 'function' => "arppu_indicator")
					)
				),
				"-",
				'ltv' => array(
					'id'		=> $id++,
					'title'		=> "LTV всех",
					'description'	=> "Life Time Value (совокупная прибыль компании, получаемая от одного клиента за все время сотрудничества с ним)",
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency,
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'ltv_paying' => array(
					'id'		=> $id++,
					'title'		=> "LTV платящих",
					'description'	=> "Life Time Value (совокупная прибыль компании, получаемая от одного клиента за все время сотрудничества с ним)",
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'value_append'	=> $this->currency,
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'ltv_referrer' => array(
					'id'		=> $id++,
					'title'		=> "LTV по реферрерам",
					'description'	=> "Life Time Value (совокупная прибыль компании, получаемая от одного клиента за все время сотрудничества с ним)",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "Мой Мир",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> $this->referrers_fb
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				)
			),
			'buyings' => array(
				'common' => array(
					'id'		=> $id++,
					'title'		=> "Все покупки",
					'description'	=> "Сумма и количество покупок - всех и платящих пользователей",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма, все пользователи",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество, все пользователи",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Сумма, платящие пользователи",
							'legend'	=> $this->goods
						),
						array(
							'title'		=> "Количество, платящие пользователи",
							'legend'	=> $this->goods
						)
					)
				),
				'gifts' => array(
					'id'		=> $id++,
					'title'		=> "Подарки по группам",
					'description'	=> "Сумма и количество бутылочек, потраченные на различные группы подарков",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->categories
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->categories
						)
					)
				),
				'gifts_tabs' => array(
					'id'		=> $id++,
					'title'		=> "Подарки по вкладкам",
					'description'	=> "Количество продаж подарков по вкладкам",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> $this->tabs
						)
					)
				),
				'wedding' => array(
					'id'		=> $id++,
					'title'		=> "Свадьба",
					'description'	=> "Сумма и количество покупок свадебных предметов/действий",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->wedding_items
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->wedding_items
						)
					)
				),
				'rooms'	=> array(
					'id'		=> $id++,
					'title'		=> "Комнаты в домах",
					'description'	=> "Сумма и количество покупок комнат по порядку",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> $this->rooms
						),
						array(
							'title'		=> "Количество",
							'legend'	=> $this->rooms
						)
					),
					'params'	=> array(
						'show_sumline'		=> true
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
							'title'		=> "По устройствам",
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
							'title'		=> "Платящие",
							'legend'	=> array(0 => "Платящие игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По устройствам",
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
							'title'		=> "По вкладкам",
							'legend'	=> $this->pages
						),
						array(
							'title'		=> "По вкладкам устройств",
							'legend'	=> $this->pages
						),
						array(
							'title'		=> "По вкладкам, платящие",
							'legend'	=> $this->pages
						),
						array(
							'title'		=> "По тегам",
							'legend'	=> $this->tags
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
							'title'		=> "Платящие",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По устройствам",
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
						'indicator'	=> array('type' => "average")
					)
				),
				'wau_year_begin' => array(
					'id'		=> $id++,
					'type'		=> "weekly_yb",
					'title'		=> "WAU по неделям",
					'description'	=> "Количество уникальных игроков за семидневные периоды",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "Платящие",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По устройствам",
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
						'indicator'	=> array('type' => "average")
					)
				),
				'mau' => array(
					'id'		=> $id++,
					'type'		=> "monthly",
					'title'		=> "MAU",
					'description'	=> "Количество уникальных игроков за месяц",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "Платящие",
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "fixed")
					)
				),
				"-",
				'dau_referrer' => array(
					'id'		=> $id++,
					'title'		=> "DAU по реферрерам",
					'description'	=> "DAU по рефереррерам для каждой соц. сети",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "Мой Мир",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> $this->referrers_fb
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'dau_net_age' => array(
					'id'		=> $id++,
					'title'		=> "DAU соцсети–возраст",
					'description'	=> "DAU по возрасту для каждой соцсети",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "МойМир",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Мамба",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Фотострана",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "StandAlone",
							'legend'	=> $this->ages
						)
					)
				),
				'mau_net_age' => array(
					'id'		=> $id++,
					'type'		=> "monthly",
					'title'		=> "MAU соцсети–возраст",
					'description'	=> "MAU по возрасту для каждой соцсети",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "МойМир",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Мамба",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "Фотострана",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "StandAlone",
							'legend'	=> $this->ages
						)
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
							'title'		=> "По устройствам",
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
						'value_append'	=> "%",
						'indicator'	=> array('type' => "fixed")
					)
				),
				"-",
				'bottles' => array(
					'id'		=> $id++,
					'title'		=> "Баланс бутылочек",
					'description'	=> "Количество полученных и потраченных игроками бутылочек без учёта платных и с учётом платных в полученных",
					'graphs'	=> array(
						array(
							'title'		=> "Количество без платных",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						),
						array(
							'title'		=> "Количество с платными",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				'bottles_free' => array(
					'id'		=> $id++,
					'title'		=> "Бесплатные бутылочки",
					'description'	=> "Количество бесплатных бутылочек по типу их получения",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> $this->bottles_flow
						)
					)
				),
				'bottles_paid' => array(
					'id'		=> $id++,
					'title'		=> "Платные бутылочки",
					'description'	=> "Количество платных бутылочек по типу их получения",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> $this->bottles_flow
						)
					)
				),
				'bottles_average' => array(
					'id'		=> $id++,
					'title'		=> "Средний баланс",
					'description'	=> "Среднее количество платных и бесплатных бутылочек у игрока",
					'graphs'	=> array(
						array(
							'title'		=> "Средний баланс",
							'legend'	=> array(0 => "Платные", 1 => "Бесплатные", 2 => "Сумма")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'bottles_average_new' => array(
					'id'		=> $id++,
					'title'		=> "Средний баланс по медианам",
					'description'	=> "Среднее количество платных и бесплатных бутылочек у игрока",
					'graphs'	=> array(
						array(
							'title'		=> "Платные",
							'legend'	=> array(0 => "Медиана 50%", 1 => "Медиана 80%", 2 => "Медиана 95%", 3 => "Медиана 100%")
						),
						array(
							'title'		=> "Бесплатные",
							'legend'	=> array(0 => "Медиана 50%", 1 => "Медиана 80%", 2 => "Медиана 95%", 3 => "Медиана 100%")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'rating' => array(
					'id'		=> $id++,
					'title'		=> "Рейтинговые дарения",
					'description'	=> "Дарение сердечек и подарков",
					'graphs'	=> array(
						array(
							'title'		=> "Сердца",
							'legend'	=> array(0 => "За очки", 1 => "С аурами", 2 => "Не засчитаны")
						),
						array(
							'title'		=> "Подарки",
							'legend'	=> array(0 => "За очки", 1 => "С аурами", 2 => "Не засчитаны")
						)
					)
				),
				"-",
				'active' => array(
					'id'		=> $id++,
					'title'		=> "Активность",
					'description'	=> "Количество игроков по времени и количеству сессий в день",
					'graphs'	=> array(
						array(
							'title'		=> "Количеству минут в игре",
							'legend'	=> array(0 => "0", 1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5-10", 6 => "11-30", 7 => "31-60", 8 => "61+")
						),
						array(
							'title'		=> "Количеству игровых сессий",
							'legend'	=> array(0 => "0", 1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5-10", 6 => "11-30", 7 => "31-60", 8 => "61+")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				"-",
				'wedding' => array(
					'id'		=> $id++,
					'title'		=> "Свадьбы",
					'description'	=> "Количество свадеб, тортов на свадьбах и кол-во потраченных бутылочек и количество женатых, ухаживающих игроков",
					'graphs'	=> array(
						array(
							'title'		=> "Свадьбы",
							'legend'	=> array(0 => "Количество свадеб", 1 => "Потрачено бутылочек"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Свадебные торты",
							'legend'	=> array(0 => "Количество тортов", 1 => "Потрачено бутылочек"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Женатые/Ухаживающие",
							'legend'	=> array(0 => "Женатые", 1 => "Ухаживающие"),
							'split_axis'	=> array("0", "1")
						),
						array(
							'title'		=> "Шкала счстья",
							'legend'	=> array(0 => "Потрачено бутылочек")
						)
					)
				),
				'rooms' => array(
					'id'		=> $id++,
					'title'		=> "Дома",
					'description'	=> "Количество уникальных игроков заходивших в гости и в свои дома",
					'graphs'	=> array(
						array(
							'title'		=> "Количество уникальных игроков заходивших в гости",
							'legend'	=> array(0 => "Все", 2 => "Платящие")
						),
						array(
							'title'		=> "Количество уникальных игроков заходивших в свой дом",
							'legend'	=> array(0 => "Все", 2 => "Платящие")
						)
					)
				),
				'pets' => array(
					'id'		=> $id++,
					'title'		=> "Питомцы",
					'description'	=> "Количество бесплатных действий совершенных с своими/чужими питомцами",
					'graphs'	=> array(
						array(
							'title'		=> "Со своими",
							'legend'	=> array(0 => "Ухаживаний", 1 => "Игр", 2 => "Кормлений")
						),
						array(
							'title'		=> "С чужими",
							'legend'	=> array(0 => "Ухаживаний", 1 => "Игр", 2 => "Кормлений")
						)
					)
				),
				'shows' => array(
					'id'		=> $id++,
					'title'		=> "Фотооценка",
					'description'	=> "Количество купленных и потраченных игроками показов и баланс, бесплатные показы, кол-во оценок",
					'graphs'	=> array(
						array(
							'title'		=> "Баланс платных показов",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						),
						array(
							'title'		=> "Количество бесплатных показов",
							'legend'	=> array("Показы")
						),
						array(
							'title'		=> "Количество оценок",
							'legend'	=> array("Оценки")
						)
					)
				),
				'tree' => array(
					'id'		=> $id++,
					'title'		=> "Деревья",
					'description'	=> "Количество очков дерева по способу получения, количество разблокирововок украшений дерева по действиям игрока, повышение уровня дерева по действиям игрока",
					'graphs'	=> array(
						array(
							'title'		=> "Очки",
							'legend'	=> $this->tree_count
						),
						array(
							'title'		=> "Разблокировки",
							'legend'	=> $this->tree_decor
						),
						array(
							'title'		=> "Повышения уровня",
							'legend'	=> $this->tree_levelups
						)
					)
				),
				"-",
				'stickers_use' => array(
					'id'		=> $id++,
					'title'		=> "Использование стикеров",
					'description'	=> "Общее количество использований стикеров и бесплатные использования по типу стикера",
					'graphs'	=> array(
						array(
							'title'		=> "Платные стикеры",
							'legend'	=> array_values($this->payments_stickers)
						),
						array(
							'title'		=> "Бесплатные стикеры",
							'legend'	=> array_values($this->payments_stickers)
						)
					)
				),
				'stickers_certificate' => array(
					'id'		=> $id++,
					'title'		=> "Сертификаты на стикеры",
					'description'	=> "Количество дарений сертификатов и активация сертификатов по типу стикера",
					'graphs'	=> array(
						array(
							'title'		=> "Количество дарений сертификатов",
							'legend'	=> array(0 => "Количество")
						),
						array(
							'title'		=> "Активированные стикеры",
							'legend'	=> array_values($this->payments_stickers)
						)
					)
				),
				"-",
				'verifications' => array(
					'id'		=> $id++,
					'title'		=> "Верификация игроков",
					'description'	=> "Количество игроков, не прошедших верификацию, и прошедших её",
					'graphs'	=> array(
						array(
							'title'		=> "Игроки, количество",
							'legend'	=> array(0 => "Не верифицированные", 1 => "Верифицированные")
						)
					)
				),
				'install_by_invite' => array(
					'id'		=> $id++,
					'title'		=> "Установки по приглашениям",
					'description'	=> "Количество установок по разосланным приглашениям",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Количество")
						),
						array(
							'title'		=> "По соц. сетям",
							'legend'	=> $this->networks
						)
					)
				),
				"-",
				'gifts_devices' => array(
					'id'		=> $id++,
					'title'		=> "Дарения по устройствам",
					'description'	=> "Количество подаренных сердечек и подарков по устройствам",
					'graphs'	=> array(
						array(
							'title'		=> "Через iOS",
							'legend'	=> array(0 => "Подарки", 1 => "Сердечки")
						),
						array(
							'title'		=> "Через Android",
							'legend'	=> array(0 => "Подарки", 1 => "Сердечки")
						)
					)
				),
				'screen' => array(
					'id'		=> $id++,
					'title'		=> "Дарения по вкладкам",
					'description'	=> "Количество подаренных сердечек и подарков по вкладкам/способам дарения",
					'graphs'	=> array(
						array(
							'title'		=> "Подарки",
							'legend'	=> $this->screens
						),
						array(
							'title'		=> "Сердечки",
							'legend'	=> $this->screens
						)
					)
				),
				"-",
				'buttons' => array(
					'id'		=> $id++,
					'title'		=> "Нажатие кнопок",
					'description'	=> "Количество нажатий кнопок игроками и добавление приложения в меню слева",
					'graphs'	=> array(
						array(
							'title'		=> "Кнопки в клиенте",
							'legend'	=> array(0 => "Кнопка «купить бутылочки»", 1 => "Кнопка «купить VIP»", 2 => "Кнопка «купить место в рейтинге влиятельных персон»", 3 => "Кнопка «пригласить друзей»", 4 => "Кнопка «Приложение в AppStore»", 5 => "Кнопка «Приложение в GooglePlay»", 6 => "Кнопка «Играть в бутылочку» в профиле")
						),
						array(
							'title'		=> "Левое меню",
							'legend'	=> array(0 => "Отказались добавить", 1 => "Добавили в левое меню")
						)
					)
				),
				"-",
				'well' => array(
					'id'		=> $id++,
					'title'		=> "Использование колодца",
					'description'	=> "Количество сбора бутылочек с колодца по длине серии",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(1 => "1", 2 => "2", 3 => "3", 7 => "4-7", 14 => "8-14", 21 => "15-21", 30 => "22-30", 31 => "31+")
						)
					)
				),
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
							'title'		=> "По устройствам",
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
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					)
				),
				'new_referrer' => array(
					'id'		=> $id++,
					'title'		=> "Новые игроки по реферрерам",
					'description'	=> "Количество игроков только что установивших приложение по реферрерам соц сетей",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "МойМир",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> $this->referrers_fb
						)
					)
				),
				'new_day_activity' => array(
					'id'		=> $id++,
					'title'		=> "Активность новичков за сутки",
					'description'	=> "Количество игроков по времени, деревьям, поцелуям и фотооценке за первые сутки со дня регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "По времени",
							'legend'	=> $this->newbies_game_time
						),
						array(
							'title'		=> "По всем поцелуям",
							'legend'	=> $this->newbies_kisses
						),
						array(
							'title'		=> "По удачным поцелуям",
							'legend'	=> $this->newbies_kisses
						),
						array(
							'title'		=> "По просмотрам в фотооценке",
							'legend'	=> $this->newbies_rates_views
						),
						array(
							'title'		=> "По уровню дерева",
							'legend'	=> $this->newbies_tree_level
						)
					)
				),
				'new_week_activity' => array(
					'id'		=> $id++,
					'title'		=> "Активность новичков за неделю",
					'description'	=> "Количество игроков по времени, деревьям, поцелуям и фотооценке за первую неделю со дня регистрации",
					'graphs'	=> array(
						array(
							'title'		=> "По времени",
							'legend'	=> $this->newbies_game_time
						),
						array(
							'title'		=> "По всем поцелуям",
							'legend'	=> $this->newbies_kisses
						),
						array(
							'title'		=> "По удачным поцелуям",
							'legend'	=> $this->newbies_kisses
						),
						array(
							'title'		=> "По просмотрам в фотооценке",
							'legend'	=> $this->newbies_rates_views
						),
						array(
							'title'		=> "По уровню дерева",
							'legend'	=> $this->newbies_tree_level
						)
					),
					'cache'		=> false
				),
				'bans' => array(
					'id'		=> $id++,
					'title'		=> "Баны",
					'description'	=> "Количество заблокированных пользователей",
					'graphs'	=> array(
						array(
							'title'		=> "Пользователи",
							'legend'	=> array(0 => "Количество")
						)
					)
				),
				'bans_paying' => array(
					'id'		=> $id++,
					'title'		=> "Баны платящих и неплатящих",
					'description'	=> "Количество заблокированных пользователей, которые сделали хоть один платёж и тех, кто не делал платежей",
					'graphs'	=> array(
						array(
							'title'		=> "Пользователи",
							'legend'	=> array("Неплатящие", "Платящие")
						)
					)
				),
				'vip_duration' => array(
					'id'		=> $id++,
					'title'		=> "Остаток дней по VIP",
					'description'	=> "Количество пользователей с VIP-статусом по количеству дней до окончания",
					'graphs'	=> array(
						array(
							'title'		=> "Пользователи",
							'legend'	=> array(0 => "0-2", 1 => "3-5", 2 => "6-10", 3 => "11-20", 4 => "21-50", 5 => "51-100", 6 => "101-500", 7 => "&gt;500")
						)
					)
				),
/*
				"-",
				'ab' => array(
					'id'		=> $id++,
					'type'		=> "table",
					'title'		=> "АБ Тестирование",
					'description'	=> "Результаты АБ тестирования по группам пользователей",
					'legend'	=> $this->tags,
					'legend_groups'	=> array(
						array(
							'title'		=> "Тест фотобанка",
							'groups'	=> array(24, 25)
						),
						array(
							'title'		=> "Тест вкладки в окне подарков",
							'groups'	=> array(19, 20, 21, 22, 23)
						),
						array(
							'title'		=> "Тест минимального платежа",
							'groups'	=> array(16, 17, 18)
						),
						array(
							'title'		=> "Тест 02.12",
							'groups'	=> array(12, 13, 14, 15)
						),
						array(
							'title'		=> "Тест 25.11-02.12",
							'groups'	=> array(9, 10, 11)
						),
						array(
							'title'		=> "Тест 06.11-25.11",
							'groups'	=> array(6, 7, 8)
						),
						array(
							'title'		=> "Тест 28.10-06.11",
							'groups'	=> array(3, 4, 5)
						),
						array(
							'title'		=> "Тест 24.10-28.10",
							'groups'	=> array(1, 2)
						)
					),

					'rows'		=> array(
						array('title' => "Дата запуска", 'data' => 1, 'value' => "date"),
						array('title' => "Количество игроков", 'data' => 0),
						array('title' => "Пригласили игроков", 'data' => 7),
						array('title' => "Количество пригласивших", 'data' => 9),

						array('title' => "Активность"),
						array('title' => "Меньше 2 минут", 'data' => 38, 'value' => "percent"),
						array('title' => "От 2 до 5 минут", 'data' => 39, 'value' => "percent"),
						array('title' => "От 5 до 30 минут", 'data' => 40, 'value' => "percent"),
						array('title' => "Больше 30 минут", 'data' => 41, 'value' => "percent"),

						array('title' => "Возвращения фиксированные"),
						array('title' => "На 1 день", 'data' => 26, 'value' => "percent"),
						array('title' => "На 2 день", 'data' => 27, 'value' => "percent"),
						array('title' => "На 7 день", 'data' => 28, 'value' => "percent"),
						array('title' => "На 14 день", 'data' => 29, 'value' => "percent"),
						array('title' => "На 30 день", 'data' => 30, 'value' => "percent"),

						array('title' => "Возвращения плавающие"),
						array('title' => "Через 1+ день", 'data' => 31, 'value' => "percent"),
						array('title' => "Через 2+ дня", 'data' => 32, 'value' => "percent"),
						array('title' => "Через 7+ дней", 'data' => 33, 'value' => "percent"),
						array('title' => "Через 14+ дней", 'data' => 34, 'value' => "percent"),
						array('title' => "Через 30+ дней", 'data' => 35, 'value' => "percent"),
						array('title' => "Через 60+ дней", 'data' => 36, 'value' => "percent"),
						array('title' => "Через 90+ дней", 'data' => 37, 'value' => "percent"),

						array('title' => "Счетчики"),
						array('title' => "Отправили подарков", 'data' => 2),
						array('title' => "Получили подарков", 'data' => 3),
						array('title' => "Отправили сердечек", 'data' => 4),
						array('title' => "Получили сердечек", 'data' => 5),
						array('title' => "Среднее время сессии", 'data' => 6, 'value_append' => " с."),

						array('title' => "Платежи"),
						array('title' => "Платящие игроки", 'data' => 8, 'value' => "percent"),
						array('title' => "Сумма платежей", 'data' => 10),
						array('title' => "Количество платежей", 'data' => 11),
						array('title' => "Средний платеж", 'data' => 12),
						array('title' => "Средний кв. платеж", 'data' => 13),
						array('title' => "Максимальный платеж", 'data' => 14),
						array('title' => "ARPU", 'data' => 15, 'value_append' => $this->currency),
						array('title' => "ARPPU", 'data' => 16, 'value_append' => $this->currency),

						array('title' => "Платежи в день регистрации"),
						array('title' => "Сумма", 'data' => 23),
						array('title' => "Количество", 'data' => 24),

						array('title' => "Процент платежей от общего количества"),
						array('title' => "0-20", 'data' => 17, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "21-70", 'data' => 18, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "71-100", 'data' => 19, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "101-500", 'data' => 20, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "501-1000", 'data' => 21, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "1000+", 'data' => 22, 'sub_data' => 10, 'value' => "percent"),

						array('title' => "Процент игроков по сетям"),
						array('title' => "ВКонтакте", 'data' => 42, 'value' => "percent"),
						array('title' => "МойМир", 'data' => 43, 'value' => "percent"),
						array('title' => "Одноклассники", 'data' => 44, 'value' => "percent"),
						array('title' => "Facebook", 'data' => 45, 'value' => "percent"),
						array('title' => "Мамба", 'data' => 46, 'value' => "percent"),
						array('title' => "ФотоСтрана", 'data' => 47, 'value' => "percent"),
						array('title' => "StandAlone", 'data' => 48, 'value' => "percent"),

						array('title' => "Процент игроков по возрасту"),
						array('title' => "1-13", 'data' => 49, 'value' => "percent"),
						array('title' => "14-15", 'data' => 50, 'value' => "percent"),
						array('title' => "16-17", 'data' => 51, 'value' => "percent"),
						array('title' => "18-27", 'data' => 52, 'value' => "percent"),
						array('title' => "28-34", 'data' => 53, 'value' => "percent"),
						array('title' => "35+", 'data' => 54, 'value' => "percent"),
						array('title' => "Не задан", 'data' => 55, 'value' => "percent"),

						array('title' => "Процент игроков по полу"),
						array('title' => "Женский", 'data' => 57, 'value' => "percent"),
						array('title' => "Мужской", 'data' => 58, 'value' => "percent"),
						array('title' => "Не задан", 'data' => 56, 'value' => "percent")
					),
					'start_date'	=> "2014-10-24",
					'cache'		=> false
				),
				'ab_photoshows' => array(
					'id'		=> $id++,
					'title'		=> "АБ тест 5 ноября 2015",
					'description'	=> "Количество покупок фотопоказов из банка в отдельной вкладке и в маленькой форме",
					'graphs'	=> array(
						array(
							'title'		=> "Количество покупок",
							'legend'	=> array(24 => "", 25 => "")
						)
					)
				),
*/
				'ad' => array(
					'id'		=> $id++,
					'type'		=> "table",
					'title'		=> "Рекламные кампании",
					'description'	=> "Результаты рекламных кампаний по группам привлеченных пользователей",
					'legend'	=> array(),
					'show_legend'	=> true,
					'legend_groups'	=> array(
						array('title' => "Привлеченные рекламной кампанией"),
						array('title' => "Приглашенные пользователи")
					),
					'params'	=> array('tooltip' => false),
					'filter'	=> true,
					'editable'	=> true,
					'join_report'	=> "hidden_players_ad_params",
					'show_image'	=> true,

					'rows'		=> array(
						array('title' => "Дата запуска", 'data' => 1, 'value' => "date"),

						array('title' => "Общие показатели кампании"),
						array('title' => "CPI", 'data' => 79, 'value_append' => $this->currency),
						array('title' => "CTR", 'data' => 80, 'value_append' => "%"),
						array('title' => "Окупаемость", 'data' => 81, 'value_append' => $this->currency),
						array('title' => "ROI", 'data' => 82, 'value_append' => "%"),
						array('title' => "Количество игроков", 'data' => 0, 'show_diff' => false),
						array('title' => "Пригласили игроков", 'data' => 7, 'value' => "percent"),
						array('title' => "Процент пригласивших", 'data' => 9, 'value' => "percent"),

						array('title' => "Активность"),
						array('title' => "Меньше 2 минут", 'data' => 38, 'value' => "percent"),
						array('title' => "От 2 до 5 минут", 'data' => 39, 'value' => "percent"),
						array('title' => "От 5 до 30 минут", 'data' => 40, 'value' => "percent"),
						array('title' => "Больше 30 минут", 'data' => 41, 'value' => "percent"),

						array('title' => "Возвращения фиксированные"),
						array('title' => "На 1 день", 'data' => 26, 'value' => "percent"),
						array('title' => "На 2 день", 'data' => 27, 'value' => "percent"),
						array('title' => "На 7 день", 'data' => 28, 'value' => "percent"),
						array('title' => "На 14 день", 'data' => 29, 'value' => "percent"),
						array('title' => "На 30 день", 'data' => 30, 'value' => "percent"),

						array('title' => "Возвращения плавающие"),
						array('title' => "Через 1+ день", 'data' => 31, 'value' => "percent"),
						array('title' => "Через 2+ дня", 'data' => 32, 'value' => "percent"),
						array('title' => "Через 7+ дней", 'data' => 33, 'value' => "percent"),
						array('title' => "Через 14+ дней", 'data' => 34, 'value' => "percent"),
						array('title' => "Через 30+ дней", 'data' => 35, 'value' => "percent"),
						array('title' => "Через 60+ дней", 'data' => 36, 'value' => "percent"),
						array('title' => "Через 90+ дней", 'data' => 37, 'value' => "percent"),

						array('title' => "Счетчики"),
						array('title' => "Отправили подарков", 'data' => 2),
						array('title' => "Получили подарков", 'data' => 3),
						array('title' => "Отправили сердечек", 'data' => 4),
						array('title' => "Получили сердечек", 'data' => 5),
						array('title' => "Среднее время сессии", 'data' => 6, 'value_append' => " с."),

						array('title' => "Платежи"),
						array('title' => "Платящие игроки", 'data' => 8, 'value' => "percent"),
						array('title' => "Сумма платежей", 'data' => 10, 'value_append' => $this->currency),
						array('title' => "Количество платежей", 'data' => 11),
						array('title' => "Средний платеж", 'data' => 12, 'value_append' => $this->currency),
						array('title' => "Средний кв. платеж", 'data' => 13, 'value_append' => $this->currency),
						array('title' => "Максимальный платеж", 'data' => 14, 'value_append' => $this->currency),
						array('title' => "ARPU", 'data' => 15, 'value_append' => $this->currency),
						array('title' => "ARPPU", 'data' => 16, 'value_append' => $this->currency),

						array('title' => "Платежи по полу"),
						array('title' => "Мужской (Сумма)", 'data' => 61, 'value_append' => $this->currency),
						array('title' => "Женский (Сумма)", 'data' => 60, 'value_append' => $this->currency),
						array('title' => "Не задан (Сумма)", 'data' => 59, 'value_append' => $this->currency),
						array('title' => "Мужской (Количество)", 'data' => 64),
						array('title' => "Женский (Количество)", 'data' => 63),
						array('title' => "Не задан (Количество)", 'data' => 62),

						array('title' => "Платежи по возрасту"),
						array('title' => "1-13 (Сумма)", 'data' => 65, 'value_append' => $this->currency),
						array('title' => "14-15 (Сумма)", 'data' => 66, 'value_append' => $this->currency),
						array('title' => "16-17 (Сумма)", 'data' => 67, 'value_append' => $this->currency),
						array('title' => "18-27 (Сумма)", 'data' => 68, 'value_append' => $this->currency),
						array('title' => "28-34 (Сумма)", 'data' => 69, 'value_append' => $this->currency),
						array('title' => "35+ (Сумма)", 'data' => 70, 'value_append' => $this->currency),
						array('title' => "Не задан (Сумма)", 'data' => 71, 'value_append' => $this->currency),
						array('title' => "1-13 (Количество)", 'data' => 72),
						array('title' => "14-15 (Количество)", 'data' => 73),
						array('title' => "16-17 (Количество)", 'data' => 74),
						array('title' => "18-27 (Количество)", 'data' => 75),
						array('title' => "28-34 (Количество)", 'data' => 76),
						array('title' => "35+ (Количество)", 'data' => 77),
						array('title' => "Не задан (Количество)", 'data' => 78),

						array('title' => "Платежи в день регистрации"),
						array('title' => "Сумма", 'data' => 23, 'value_append' => $this->currency),
						array('title' => "Количество", 'data' => 24),

						array('title' => "Процент платежей от общего количества"),
						array('title' => "0-20", 'data' => 17, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "21-70", 'data' => 18, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "71-100", 'data' => 19, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "101-500", 'data' => 20, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "501-1000", 'data' => 21, 'sub_data' => 10, 'value' => "percent"),
						array('title' => "1000+", 'data' => 22, 'sub_data' => 10, 'value' => "percent"),

						array('title' => "Процент игроков по возрасту"),
						array('title' => "1-13", 'data' => 49, 'value' => "percent"),
						array('title' => "14-15", 'data' => 50, 'value' => "percent"),
						array('title' => "16-17", 'data' => 51, 'value' => "percent"),
						array('title' => "18-27", 'data' => 52, 'value' => "percent"),
						array('title' => "28-34", 'data' => 53, 'value' => "percent"),
						array('title' => "35+", 'data' => 54, 'value' => "percent"),
						array('title' => "Не задан", 'data' => 55, 'value' => "percent"),

						array('title' => "Процент игроков по полу"),
						array('title' => "Женский", 'data' => 57, 'value' => "percent"),
						array('title' => "Мужской", 'data' => 58, 'value' => "percent"),
						array('title' => "Не задан", 'data' => 56, 'value' => "percent")
					),
					'cache'		=> false
				),
				"-",
				'retention' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения",
					'description'	=> "Возвращение игроков через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "%",
							'legend'	=> $this->retentions
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_paying' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения платящих",
					'description'	=> "Возвращение платящих игроков через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "%",
							'legend'	=> $this->retentions
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_net' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по сетям",
					'description'	=> "Возвращение игроков через N дней по соц. сетям",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->networks
						),
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
							'title'		=> "14d+",
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
				'retention_age' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по возрасту",
					'description'	=> "Возвращение игроков через N дней по возрасту",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->ages
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->ages
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_sex' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по полу",
					'description'	=> "Возвращение игроков через N дней по полу",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->sex
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_device' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по устройствам",
					'description'	=> "Возвращение игроков через N дней по устройствам",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->devices
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_tag' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по тегам",
					'description'	=> "Воввращения игроков через N дней по тегам (маркированные пользователи)",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->tags
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_referrer_vk' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по реферрерам ВКонтакте",
					'description'	=> "Возвращение игроков через N дней по реферрерам ВКонтакте",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->referrers_vk
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->referrers_vk
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_referrer_mm' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по реферрерам МойМир",
					'description'	=> "Возвращение игроков через N дней по реферрерам МойМир",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->referrers_mm
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->referrers_mm
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_referrer_ok' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по реферрерам Одноклассников",
					'description'	=> "Возвращение игроков через N дней по реферрерам Одноклассников",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->referrers_ok
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->referrers_ok
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
				'retention_referrer_fb' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по реферрерам Facebook",
					'description'	=> "Возвращение игроков через N дней по реферрерам Facebook",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "7d",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "14d",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "30d",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "1d+",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "2d+",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "7d+",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "14d+",
							'legend'	=> $this->referrers_fb
						),
						array(
							'title'		=> "30d+",
							'legend'	=> $this->referrers_fb
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'value_append'	=> "%",
						'indicator'	=> array('type' => "average")
					),
					'cache'		=> false
				),
/*
				"-",
				'wakeups_referrer' => array(
					'id'		=> $id++,
					'title'		=> "Возвраты по реферрерам",
					'description'	=> "Возвраты игроков по реферрерам через N дней неактивности",
					'graphs'	=> array(
						array(
							'title'		=> "1d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "2d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "3-7d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "8-30d",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "31+d",
							'legend'	=> $this->networks
						)
					),
					'params'	=> array(
						'indicator'	=> array('type' => "average")
					)
				),
*/
				"-",
				'life_time' => array(
					'id'		=> $id++,
					'title'		=> "Среднее время жизни игрока",
					'description'	=> "Среднее время жизни игрока на день посещения (новые игроки не учитываются) в днях",
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
							'title'		=> "По устройствам",
							'legend'	=> $this->devices
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "По тегам",
							'legend'	=> $this->tags
						)
					),
					'params'	=> array(
						'value_append'	=> " д.",
						'indicator'	=> array('type' => "average"),
						'show_sums'	=> true
					)
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
							'title'		=> "По устройствам",
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
							'title'		=> "По устройствам",
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
						'value_append'	=> "%",
						'indicator'	=> array('type' => "fixed")
					)
				),
				'paying_groups' => array(
					'id'		=> $id++,
					'title'		=> "Платящие по суммам",
					'description'	=> "Количество игроков, заплативших сумму N р. за последние 30 дней в игре",
					'graphs'	=> array(
						array(
							'title'		=> "Количество игроков",
							'legend'	=> array(0 => "Меньше 10", 1 => "10-49", 2 => "50-99", 3 => "100-199", 4 => "200-299", 5 => "300-499", 6 => "500-999", 7 => "1000-1999", 8 => "2000-49999", 9 => "5000-9999", 10 => "10000+")
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "average")
					)
				),
				'paying_counts' => array(
					'id'		=> $id++,
					'title'		=> "Платящие по количеству",
					'description'	=> "Количество игроков по количеству платежей за все время в проекте на момент совершения платежа (Номер платежа игрока)",
					'graphs'	=> array(
						array(
							'title'		=> "Количество игроков",
							'legend'	=> array(1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5-9", 6 => "10-14", 7 => "15-39", 8 => "40-69", 9 => "70-99", 10 => "100-499", 11 => "500-999", 12 => "1000+")
						)
					),
					'cache'		=> false
				),
				'ctr_posting' => array(
					'id'		=> $id++,
					'title'		=> "CTR постинга",
					'description'	=> "Отношение кликов к поазам для постинга",
					'graphs'	=> array(
						array(
							'title'		=> "CTR",
							'legend'	=> $this->posting_types
						)
					),
					'params'	=> array(
						'value_append'	=> "%"
					)
				)
			),
			'events' => array(
				'collection_pickup' => array(
					'id'		=> $id++,
					'title'		=> "Собрано коллекций",
					'description'	=> "Количество собранных коллекций",
					'graphs'	=> array(
						array(
							'title'		=> "Коллекции, количество",
							'legend'	=> $this->collections_names
						)
					)
				),
				'collection_assemble' => array(
					'id'		=> $id++,
					'title'		=> "Обменено коллекций",
					'description'	=> "Количество коллекций, обмененных на призы",
					'graphs'	=> array(
						array(
							'title'		=> "Коллекции, количество",
							'legend'	=> $this->collections_names
						)
					)
				),
/*
				'collection_efficiency' => array(
					'id'		=> $id++,
					'type'		=> "nodate",
					'title'		=> "Эффективность коллекций",
					'description'	=> "Наложение сумм и количества платежей во время действия каждой из коллекции.",
					'graphs'	=> array(
						array(
							'title'		=> "Сумма",
							'legend'	=> array(0 => "Пасхальные коллекции (26.04.13-17.05.13)", 1 => "Летние коллекции (09.08.13-23.09.13)", 2 => "Осенние коллекции (27.09.13-12.11.13)", 3 => "Свадебные коллекции (18.11.13-12.12.13)", 4 => "Новогодние пазлы (12.12.13-29.01.14)", 5 => "Олимпийские коллекции (29.01.14-28.02.14)", 6 => "Весенние пазлы (28.02.14-26.04.14)", 7 => "Шкатулки и ключи (09.06.14)", 8 => "Летние коллекции (22.07.14-01.09.2014)", 9 => "Летние пазлы (01.09.14-30.10.14)", 10 => "Осенние коллекции (19.10.14-30.10.14)", 11 => "Хэллоуин коллекции (30.10.14-15.11.14)", 12 => "Новогодние коллекции (12.12.14)")
						),
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Пасхальные коллекции (26.04.13-17.05.13)", 1 => "Летние коллекции (09.08.13-23.09.13)", 2 => "Осенние коллекции (27.09.13-12.11.13)", 3 => "Свадебные коллекции (18.11.13-12.12.13)", 4 => "Новогодние пазлы (12.12.13-29.01.14)", 5 => "Олимпийские коллекции (29.01.14-28.02.14)", 6 => "Весенние пазлы (28.02.14-26.04.14)", 7 => "Шкатулки и ключи (09.06.14)", 8 => "Летние коллекции (22.07.14-01.09.2014)", 9 => "Летние пазлы (01.09.14-30.10.14)", 10 => "Осенние коллекции (19.10.14-30.10.14)", 11 => "Хэллоуин коллекции (30.10.14-15.11.14)", 12 => "Новогодние коллекции (12.12.14)")
						)
					)
				)
*/
			),
			'api' => array(
				'loading' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Загрузка приложения",
					'description'	=> "Количество уникальных загрузок приложения, по этапам загрузки для соц. сетей",
					'graphs'	=> array(
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> array(0 => "Начали загрузку приложения", 1 => "Загрузили приложение", 2 => "Загрузился игрок")
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> array(0 => "Начали загрузку приложения", 1 => "Загрузили приложение", 2 => "Загрузился игрок")
						),
						array(
							'title'		=> "МойМир",
							'legend'	=> array(0 => "Начали загрузку приложения", 1 => "Загрузили приложение", 2 => "Загрузился игрок")
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> array(0 => "Начали загрузку приложения", 1 => "Загрузили приложение", 2 => "Загрузился игрок")
						),
						array(
							'title'		=> "Фотострана",
							'legend'	=> array(0 => "Начали загрузку приложения", 1 => "Загрузили приложение", 2 => "Загрузился игрок")
						)
					)
				),
				'loading_time' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Время загрузки приложения",
					'description'	=> "Количество загрузок приложения по времени в секундах",
					'graphs'	=> array(
						array(
							'title'		=> "Загрузили приложение",
							'legend'	=> array(0 => "Из кэша браузера", 2 => "2", 3 => "3", 4 => "4", 5 => "5-10", 6 => "11-20", 7 => "21-30", 8 => "31-60", 9 => "61+")
						),
						array(
							'title'		=> "Загрузился игрок",
							'legend'	=> array(0 => "0", 1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5-10", 6 => "11-20", 7 => "21-30", 8 => "31-60", 9 => "61+")
						)
					)
				),
				"-",
				'room_change' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Смена комнаты",
					'description'	=> "Количество нажатий на кнопку Сменить комнату",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Общее", 1 => "Уникальные")
						)
					)
				),
				"-",
				'peertopeer' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Пиринговая сеть",
					'description'	=> "Количество пользователей разрешивших p2p соединения по соц. сетям",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> $this->networks,
							'show_sumline'	=> true
						)
					)
				),
				'requests' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Запросы к соц. сетям",
					'description'	=> "Количество запросов к API соц. сетей (поиск, список музыки)",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> $this->networks
						)
					)
				),
				"-",
				'lag_page' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Зависание приложения по вкладкам",
					'description'	=> "Процент зависаний (низкий FPS > 10 секунд) приложения по вкладкам и соц. сетям от общего числа посещений",
					'graphs'	=> array(
						array(
							'title'		=> "Суммарное количество",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба"),
							'value_append'	=> ""
						),
						array(
							'title'		=> "ВКонтакте",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба")
						),
						array(
							'title'		=> "Одноклассники",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба")
						),
						array(
							'title'		=> "МойМир",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба")
						),
						array(
							'title'		=> "Facebook",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба")
						),
						array(
							'title'		=> "Фотострана",
							'legend'	=> array(0 => "Достижения", 1 => "Апартаменты", 2 => "Чат", 3 => "Соединение разорвано", 4 => "Игра", 5 => "Модерация", 6 => "Профиль", 7 => "Рейтинг домов", 8 => "Общий рейтинг", 9 => "Дневной рейтинг", 10 => "Рейтинг Форбс", 11 => "Рейтинг", 12 => "Поиск", 13 => "Свадьба")
						)
					),
					'params'	=> array(
						'value_append'	=> "%",
						'show_sums'	=> false
					)
				),
				"-",
				'chat_stickers' => array(
					'id'		=> $id++,
					'class'		=> "events",
					'title'		=> "Активность по стикерам",
					'description'	=> "Уникальные игроки, кликнувшие по стикерам, и общее количество кликов",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array("Игроки", "Клики")
						)
					)
				)
			),
			'mafia' => array(
				'rooms' => array(
					'id'		=> $id++,
					'title'		=> "Комнаты",
					'description'	=> "Количество комнат по числу игроков (завершенных игр с числом игроков N) и количество игр по длительности в минутах",
					'graphs'	=> array(
						array(
							'title'		=> "Комнаты по игрокам",
							'legend'	=> array(1 => "1 игрок", 2 => "2 игрока", 3 => "3 игрока", 4 => "4 игрока", 5 => "5 игроков", 6 => "6 игроков", 7 => "7 игроков", 8 => "8 игроков", 9 => "9 игроков", 10 => "10 игроков", 11 => "11 игроков", 12 => "12 игроков")
						),
						array(
							'title'		=> "Длительность партий",
							'legend'	=> array(0 => "0 минут", 1 => "1 минута", 2 => "2 минуты", 3 => "3-5 минут", 4 => "6-8 минут", 5 => "9-12 минут", 6 => "13-15 минут", 7 => "16-20 минут", 8 => "21-25 минут", 9 => "26-30 минут", 10 => "31+ минут")
						)
					)
				),
				'exits' => array(
					'id'		=> $id++,
					'title'		=> "Отказы",
					'description'	=> "Количество выходов игроков из мафии по этапам",
					'graphs'	=> array(
						array(
							'title'		=> "Количество",
							'legend'	=> array(0 => "Ожидание комнаты", 1 => "Начало игры", 2 => "Знакомство", 3 => "Ход мафии", 4 => "Ход комиссара", 5 => "Ход доктора", 6 => "Общение", 7 => "Голосование", 8 => "Результаты голосования", 9 => "Окончание игры")
						)
					)
				),
				'game' => array(
					'id'		=> $id++,
					'title'		=> "Ход игры",
					'description'	=> "Количество побед, проигрышей, успешных убийств и точность лечений",
					'graphs'	=> array(
						array(
							'title'		=> "Результаты игр",
							'legend'	=> array(0 => "Победили мирные", 1 => "Победила мафия"),
							'show_sumline'	=> true
						),
						array(
							'title'		=> "Успешные убийства",
							'legend'	=> array(0 => "Мафия", 1 => "Комиссар", 2 => "Суд")
						),
						array(
							'title'		=> "Точность лечений",
							'legend'	=> array(0 => "Соотношение", 1 => "Всего попыток лечения", 2 => "Успешных попыток"),
							'value_append'	=> array(0 => "%", 1 => "", 2 => "")
						)
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
				$path = "hidden_payments_device";
				break;
			case 3:
				$path = "hidden_payments_age";
				break;
			case 4:
				$path = "hidden_payments_sex";
				break;
			case 5:
				$path = "hidden_payments_tag";
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
				$path = "hidden_payments_device";
				break;
			case 3:
				$path = "hidden_payments_age";
				break;
			case 4:
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

		if (date("Y.m") === date("Y.m", $report['date_end']) && date("d") != 1)
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

				$data[0]['diff'] = $data[0]['value'] / (date("d") - 1) * date("t") / $row['value'];
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
	 * Возвращает сумму в основной валюте, которую мы получим от соц. сети
	 * $revenue в валюте системы
	 * Курсы бутылок:
	 * ВК: 1 голос = 3 рубля (-45%-18%, примерно)
	 * ММ: 1 мэйлик = 0.42 рубля (-50%-18%)
	 * ОК: 1 ОК = 0.42 рубля (-50%-18%)
	 * ФБ: 1 рубль = 0.7 рублей (-30%)
	 * МБ: 1 монета = 20 рублей (-50%)
	 * ФС: 1 ФМ = 5.5 рубля (-50%)
	 * SA: 1 бутылочка = 14 / 30 рубля
	 */
	public function get_revenue($revenue, $provider_id, $date)
	{
		switch ($provider_id)
		{
			case 0:
				return round($revenue * 3, 2);
			case 1:
				return round($revenue * 0.42, 2);
			case 4:
				return round($revenue * 0.42, 2);
			case 5:
				return round($revenue * 24.5, 2);
			case 6:
				if (strtotime($date) < strtotime("2016-02-04"))
					return round($revenue * 14, 2);
				return round($revenue * 20, 2);
			case 30:
				return round($revenue * 0.055, 2);
			case 32:
				return round($revenue * 14 / 30, 2);
			case 40:
				return round($revenue * 0.7, 2);
			case 41:
				return round($revenue * 0.7, 2);
		}
		return 0;
	}

	/**
	 * Возвращает сумму в основной валюте, потраченную пользователем
	 * $revenue в валюте системы
	 */
	public function get_payment($revenue, $provider_id, $date)
	{
		switch ($provider_id)
		{
			case 0:
				return round($revenue * 7, 2);
			case 1:
				return round($revenue, 2);
			case 4:
				return round($revenue, 2);
			case 5:
				return round($revenue * 35, 2);
			case 6:
				if (strtotime($date) < strtotime("2016-02-04"))
					return round($revenue * 28, 2);
				return round($revenue * 40, 2);
			case 30:
				return round($revenue * 0.17, 2);
			case 32:
				return round($revenue * 14 / 30 * 1.05, 2);
			case 40:
				return round($revenue, 2);
			case 41:
				return round($revenue, 2);
		}
		return 0;
	}

	public function payments_all($cache_date)
	{
		return $this->payments_simple($cache_date, "all");
	}

	/**
	 * Платежи
	 */
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
				$payment = $this->get_payment($values['revenue'], $values['provider_id'], $values['date']);
				$close += $payment;
				$sums[] = $payment;
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
		$sum = array();
		$count = array();

		$result = $this->DB->payments_specific($cache_date);
		while ($row = $result->fetch())
		{
			$payment = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$offer = $row['offer'];
			$amount = $row['amount'];

			if ($offer == self::OfferNone)
			{
				if (isset($this->payments_specific[-$amount]))
					$type = -$amount;
				else
					$type = 0;
			}
			else if (isset($this->payments_boxes[$offer]))
				$type = -1;
			else if (isset($this->payments_stickers[$offer]))
				$type = -3;
			else
				$type = $offer;

			$index = $date."-".$type;

			if (!isset($sum[$index]))
				$sum[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sum[$index]['value'] += $payment;
			$count[$index]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function payments_hourly($cache_date)
	{
		return $this->payments_type($cache_date, "hourly");
	}

	public function payments_hourly_last($cache_date)
	{
		return array();
	}

	public function payments_weekly($cache_date)
	{
		$data = array();
		$data_res = array();

		$result = $this->DB->payments_weekly($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);
			$data_res[] = $row;
		}

		$data_res = $this->sum_by_column($data_res, "date", array("sum", "count"));

		foreach ($data_res as $row)
		{
			$chart = date("w", strtotime($row['date']));
			$chart = ($chart + 6) % 7;

			$data[$chart][] = array('date' => $row['date'], 'type' => 0, 'value' => $row['sum']);
			$data[$chart][] = array('date' => $row['date'], 'type' => 1, 'value' => $row['count']);
		}

		return $data;
	}

	public function payments_weekly_net($cache_date)
	{
		$data = array();
		$data_res = array();

		$result = $this->DB->payments_weekly($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$type = $row['provider_id'];

			$data_res[$type][] = $row;
		}

		foreach ($data_res as $type => $rows)
		{
			$rows = $this->sum_by_column($rows, "date", array("sum", "count"));

			foreach ($rows as $row)
			{
				$chart = date("w", strtotime($row['date']));
				$chart = ($chart + 6) % 7;
				$chart = $chart * 2;

				$data[$chart][] = array('date' => $row['date'], 'type' => $type, 'value' => $row['sum']);
				$data[$chart + 1][] = array('date' => $row['date'], 'type' => $type, 'value' => $row['count']);
			}
		}

		return $data;
	}

	public function payments_net($cache_date)
	{
		return $this->payments_type($cache_date, "net");
	}

	public function payments_device($cache_date)
	{
		$sum_all = array();
		$count_all = array();
		$data_res = array();

		$result = $this->DB->payments_device($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$type = $this->get_provider_index($row['data']);
			if ($type == 0)
				continue;

			$data_res[$type][] = $row;
		}

		foreach ($data_res as $type => $rows)
		{
			$rows = $this->sum_by_column($rows, "date", array("sum", "count"));

			foreach ($rows as $row)
			{
				$sum_all[] = array('date' => $row['date'], 'type' => $type, 'value' => $row['sum']);
				$count_all[] = array('date' => $row['date'], 'type' => $type, 'value' => $row['count']);
			}
		}

		$sum_new = array();
		$count_new = array();
		$data_res = array();

		$result = $this->DB->payments_device_new($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$type = $this->get_provider_index($row['data']);
			if ($type == 0)
				continue;

			$data_res[$type][] = $row;
		}

		foreach ($data_res as $type => $rows)
		{
			$rows = $this->sum_by_column($rows, "date", array("sum", "count"));

			foreach ($rows as $row)
			{
				$sum_new[] = array('date' => $row['date'], 'type' => $type, 'value' => $row['sum']);
				$count_new[] = array('date' => $row['date'], 'type' => $type, 'value' => $row['count']);
			}
		}

		return array($sum_all, $count_all, $sum_new, $count_new);
	}

	public function payments_age($cache_date)
	{
		return $this->payments_type($cache_date, "age");
	}

	public function payments_sex($cache_date)
	{
		return $this->payments_type($cache_date, "sex");
	}

	public function payments_tag($cache_date)
	{
		return $this->payments_type($cache_date, "tag");
	}

	public function payments_first($cache_date)
	{
		return $this->payments_simple($cache_date, "first");
	}

	public function payments_repeated($cache_date)
	{
		return $this->payments_simple($cache_date, "repeated");
	}

	public function payments_newbies($cache_date)
	{
		return $this->payments_simple($cache_date, "newbies");
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
		$counts = array();

		while (list($date, $values) = each($data))
		{
			$day = &$values['count'];

			$all[] = array('date' => $date, 'type' => 0, 'value' => round($values['0d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 2, 'value' => round($values['2d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 8, 'value' => round($values['8d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 15, 'value' => round($values['15d'] * 100 / $day, 2));
			$all[] = array('date' => $date, 'type' => 31, 'value' => round($values['31d'] * 100 / $day, 2));

			$counts[] = array('date' => $date, 'type' => 0, 'value' => $values['0d']);
			$counts[] = array('date' => $date, 'type' => 2, 'value' => $values['2d']);
			$counts[] = array('date' => $date, 'type' => 8, 'value' => $values['8d']);
			$counts[] = array('date' => $date, 'type' => 15, 'value' => $values['15d']);
			$counts[] = array('date' => $date, 'type' => 31, 'value' => $values['31d']);
		}

		return array($all, $counts);
	}

	public function payments_day_next($cache_date)
	{
		$sum = array();
		$count = array();
		$dates = array();

		$result = $this->DB->payments_day_next();
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$uid = $row['type']."-".$row['net_id'];

			if (!isset($dates[$uid]))
			{
				$dates[$uid] = $date;
				continue;
			}

			$days = $this->date_diff($date, $dates[$uid]);
			$type = $this->get_period_index($days);

			$index = $date."-".$type;

			if (!isset($sum[$index]))
				$sum[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			$point1 = &$sum[$index];

			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			$point2 = &$count[$index];

			$point1['value'] += $row['sum'];
			$point2['value'] += 1;

			$dates[$uid] = $date;
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	/**
	 * Сбор данных для отчёта "Покупка коробок". При добавлении новой коробки
	 * не забыть про отчёт "Эффективность коробок"
	 * @see ObjectBottle::payments_boxes_efficiency()
	 */
	public function payments_boxes($cache_date)
	{
		return $this->payments_boxes_data($cache_date, $this->payments_boxes);
	}

	/**
	 * Сбор данных для отчёта "Эффективность коробок", графики привязаны к стартам продаж
	 * каждого типа коробок, ось x - количество дней от начала продаж. При добавлении новой коробки
	 * не забыть про отчёт "Покупка коробок"
	 * @see ObjectBottle::payments_boxes()
	 */
	public function payments_boxes_efficiency($cache_date)
	{
		return $this->payments_offers_data($cache_date, $this->payments_boxes);
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
			$row['sum'] = $this->get_revenue($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['provider_id'];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			$data[$index]['value'] += $row['sum'];

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
		$device = $this->finance_arpu_type($cache_date, "device");
		$age = $this->finance_arpu_type($cache_date, "age");
		$sex = $this->finance_arpu_type($cache_date, "sex");
		$tag = $this->finance_arpu_type($cache_date, "tag");

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function finance_arppu($cache_date)
	{
		list($all, $net) = $this->finance_arppu_type($cache_date, "net");
		$device = $this->finance_arppu_type($cache_date, "device");
		$age = $this->finance_arppu_type($cache_date, "age");
		$sex = $this->finance_arppu_type($cache_date, "sex");
		$tag = $this->finance_arppu_type($cache_date, "tag");

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function finance_ltv($cache_date)
	{
		list($all, $net) = $this->finance_ltv_type($cache_date, "net");
		$device = $this->finance_ltv_type($cache_date, "device");
		$age = $this->finance_ltv_type($cache_date, "age");
		$sex = $this->finance_ltv_type($cache_date, "sex");
		$tag = $this->finance_ltv_type($cache_date, "tag");

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function finance_ltv_paying($cache_date)
	{
		list($all, $net) = $this->finance_ltv_type($cache_date, "net", true);
		$device = $this->finance_ltv_type($cache_date, "device", true);
		$age = $this->finance_ltv_type($cache_date, "age", true);
		$sex = $this->finance_ltv_type($cache_date, "sex", true);
		$tag = $this->finance_ltv_type($cache_date, "tag", true);

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function finance_ltv_referrer($cache_date)
	{
		$referrers = $this->finance_ltv_type($cache_date, "referrer");

		$vk = array();
		$ok = array();
		$mm = array();
		$fb = array();

		foreach ($referrers as $values)
		{
			$type = $values['type'];

			if (isset($this->referrers_vk[$type]))
				$vk[] = $values;
			if (isset($this->referrers_ok[$type]))
				$ok[] = $values;
			if (isset($this->referrers_mm[$type]))
				$mm[] = $values;
			if (isset($this->referrers_fb[$type]))
				$fb[] = $values;
		}
		return array($vk, $ok, $mm, $fb);
	}

	/**
	 * Покупки
	 */
	public function buyings_common($cache_date)
	{
		$sum = array('all' => array(), 'paid' => array());
		$count = array('all' => array(), 'paid' => array());

		$result = $this->DB->buyings_common($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($sum['all'][$index]))
				$sum['all'][$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($count['all'][$index]))
				$count['all'][$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sum['all'][$index]['value'] += $row['sum'];
			$count['all'][$index]['value'] += $row['count'];

			if ($row['paid'] == 0)
				continue;

			$sum['paid'][] = array('date' => $date, 'type' => $type, 'value' => $row['sum']);
			$count['paid'][] = array('date' => $date, 'type' => $type, 'value' => $row['count']);
		}

		$sum['all'] = array_values($sum['all']);
		$count['all'] = array_values($count['all']);

		return array($sum['all'], $count['all'], $sum['paid'], $count['paid']);
	}

	public function buyings_gifts($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_gifts($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_gift_index($row['data']);

			$index = $date."-".$type;

			if (!isset($sum[$index]))
				$sum[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sum[$index]['value'] += $row['sum'];
			$count[$index]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	public function buyings_gifts_tabs($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::GIFTS_CATEGORY, $cache_date);
		$gifts = $this->data_type($result);

		return array($gifts);
	}

	public function buyings_wedding($cache_date)
	{
		$result = $this->DB->buyings_wedding($cache_date);
		$data = $this->data_sum_two($result);

		return $data;
	}

	public function buyings_rooms($cache_date)
	{
		$result = $this->DB->buyings_rooms($cache_date);
		$data = $this->data_sum_two($result);

		return $data;
	}

	/**
	 * Счётчики
	 */
	public function counters_dau($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_ALL, $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_PAYING, $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_NET, $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_DEVICE, $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_AGE, $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_SEX, $cache_date);
		$sex = $this->data_type($result);

		$pages = array();
		$devices_pages = array();
		$paying_pages = array();
		$types_merge = array(Counters::DAU_BOTTLE => 0, Counters::DAU_SEARCH => 1, Counters::DAU_ROOM => 3, Counters::DAU_ROOM_OWN => 4, Counters::DAU_PROFILE => 6, Counters::DAU_CHAT => 7, Counters::DAU_RATING => 8, Counters::DAU_WEDDING => 9, Counters::DAU_ACHIEVEMENT => 10, Counters::DAU_FRIENDS => 12, Counters::DAU_MESSAGES => 13, Counters::DAU_MAFIA => 14);

		$result = $this->DB->counters_daily_load(array_keys($types_merge), $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['type'];
			$data = $row['data'];

			if (!isset($types_merge[$type]))
				continue;
			$type = $types_merge[$type];

			if ($data == 0)
				$pages[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
			if ($data == 1)
				$devices_pages[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
			if ($data == 2)
				$paying_pages[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_TAG, $cache_date);
		$tag = $this->data_type($result);

		return array($all, $paying, $net, $device, $age, $sex, $pages, $devices_pages, $paying_pages, $tag);
	}

	public function counters_wau($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::WAU_ALL, $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WAU_PAYING, $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WAU_NET, $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WAU_DEVICE, $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WAU_AGE, $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WAU_SEX, $cache_date);
		$sex = $this->data_type($result);

		return array($all, $paying, $net, $device, $age, $sex);
	}

	public function counters_wau_year_begin($cache_date)
	{
		$result = $this->DB->counters_weekly_get($this->counters_wau_new['all'], $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_weekly_get($this->counters_wau_new['paying'], $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_weekly_get($this->counters_wau_new['net'], $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_weekly_get($this->counters_wau_new['device'], $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_weekly_get($this->counters_wau_new['age'], $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_weekly_get($this->counters_wau_new['sex'], $cache_date);
		$sex = $this->data_type($result);

		return array($all, $paying, $net, $device, $age, $sex);
	}

	public function counters_mau($cache_date)
	{
		$result = $this->DB->counters_monthly_get($this->counters_mau_new['all'], $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['paying'], $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['net'], $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['device'], $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['age'], $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['tag'], $cache_date);
		$tag = $this->data_type($result);

		return array($all, $paying, $net, $device, $age, $sex, $tag);
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
		$net = $this->data_type($result);

		$result = $this->DB->counters_online_device($cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_online_age($cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_online_sex($cache_date);
		$sex = $this->data_type($result);

		return array($all, $net, $device, $age, $sex);
	}

	public function counters_dau_referrer($cache_date)
	{
		$vk = array();
		$ok = array();
		$mm = array();
		$fb = array();

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_REFERRER, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (isset($this->referrers_vk[$type]))
				$vk[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
			if (isset($this->referrers_ok[$type]))
				$ok[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
			if (isset($this->referrers_mm[$type]))
				$mm[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
			if (isset($this->referrers_fb[$type]))
				$fb[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		return array($vk, $ok, $mm, $fb);
	}

	public function counters_dau_net_age($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_VK_AGE, $cache_date);
		$dau_vk_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_MM_AGE, $cache_date);
		$dau_mm_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_OK_AGE, $cache_date);
		$dau_ok_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_FB_AGE, $cache_date);
		$dau_fb_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_MB_AGE, $cache_date);
		$dau_mb_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_FS_AGE, $cache_date);
		$dau_fs_age = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_NEW_RG_AGE, $cache_date);
		$dau_rg_age = $this->data_type($result);

		return array($dau_vk_age, $dau_mm_age, $dau_ok_age, $dau_fb_age, $dau_mb_age, $dau_fs_age, $dau_rg_age);
	}

	public function counters_mau_net_age($cache_date)
	{
		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_VK_AGE, $cache_date);
		$mau_vk_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_MM_AGE, $cache_date);
		$mau_mm_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_OK_AGE, $cache_date);
		$mau_ok_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_FB_AGE, $cache_date);
		$mau_fb_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_MB_AGE, $cache_date);
		$mau_mb_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_FS_AGE, $cache_date);
		$mau_fs_age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get(Counters::MAU_NEW_RG_AGE, $cache_date);
		$mau_rg_age = $this->data_type($result);

		return array($mau_vk_age, $mau_mm_age, $mau_ok_age, $mau_fb_age, $mau_mb_age, $mau_fs_age, $mau_rg_age);
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

	public function counters_bottles($cache_date)
	{
		$free = array();

		$result = $this->DB->counters_daily_get(Counters::BOTTLES_FLOW, $cache_date);
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

			if (!isset($free[$date."_".$type]))
				$free[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($free[$date."_2"]))
				$free[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$free[$date."_".$type]['value'] += $value;
			$free[$date."_2"]['value'] += $value;
		}

		$paid = $free;

		$result = $this->DB->counters_daily_get(Counters::BOTTLES_PAID, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if (!isset($paid[$date."_1"]))
				$paid[$date."_1"] = array('date' => $date, 'type' => 1, 'value' => 0);
			if (!isset($paid[$date."_2"]))
				$paid[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$paid[$date."_1"]['value'] += $value;
			$paid[$date."_2"]['value'] += $value;
		}

		$free = array_values($free);
		$paid = array_values($paid);

		return array($free, $paid);
	}

	public function counters_bottles_free($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::BOTTLES_FREE, $cache_date);
		$data = $this->data_type($result);

		return array($data);
	}

	public function counters_bottles_paid($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::BOTTLES_PAID, $cache_date);
		$data = $this->data_type($result);

		return array($data);
	}

	public function counters_bottles_average($cache_date)
	{
		$balance = array();

		$result = $this->DB->counters_daily_get(Counters::BALANCE_AVERAGE_OLD, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($balance[$date."-2"]))
				$balance[$date."-2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$balance[$date."-".$type] = array('date' => $date, 'type' => $type, 'value' => round($row['value'] / 100, 2));
			$balance[$date."-2"]['value'] += round($row['value'] / 100, 2);
		}

		$balance = array_values($balance);

		return array($balance);
	}

	public function counters_bottles_average_new($cache_date)
	{
		$balance_paid = array();
		$balance_free = array();

		$result = $this->DB->counters_daily_get(Counters::BALANCE_AVERAGE, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($type % 2 == 0)
				$balance_paid[$date."-".$type] = array('date' => $date, 'type' => $type / 2, 'value' => round($row['value'] / 100, 2));
			else
				$balance_free[$date."-".$type] = array('date' => $date, 'type' => ($type - 1) / 2, 'value' => round($row['value'] / 100, 2));
		}

		$balance_paid = array_values($balance_paid);
		$balance_free = array_values($balance_free);

		return array($balance_paid, $balance_free);
	}

	public function counters_rating($cache_date)
	{
		$result = $this->DB->counters_daily_load(array(Counters::HEARTS_RATED, Counters::HEARTS_UNRATED), $cache_date);
		$hearts = $this->data_type($result, array(Counters::HEARTS_UNRATED => 2));

		$result = $this->DB->counters_daily_load(array(Counters::GIFTS_RATED, Counters::GIFTS_UNRATED), $cache_date);
		$gifts = $this->data_type($result, array(Counters::GIFTS_UNRATED => 2));

		return array($hearts, $gifts);
	}

	public function counters_active($cache_date)
	{
		$time = array();

		$result = $this->DB->counters_daily_get(Counters::GAME_TIME_ALL, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_active_index($row['data']);

			$index = $date."-".$type;

			if (!isset($time[$index]))
				$time[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$time[$index]['value'] += $row['value'];
		}

		$count = array();

		$result = $this->DB->counters_daily_get(Counters::GAME_COUNT_ALL, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_active_index($row['data']);

			$index = $date."-".$type;

			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$count[$index]['value'] += $row['value'];
		}

		$time = array_values($time);
		$count = array_values($count);

		return array($time, $count);
	}

	public function counters_wedding($cache_date)
	{
		$result = $this->DB->counters_daily_load(array(Counters::WEDDING_COUNT, Counters::WEDDING_BOTTLES), $cache_date);
		$wedding = $this->data_type($result, array(Counters::WEDDING_COUNT => 0, Counters::WEDDING_BOTTLES => 1));

		$result = $this->DB->counters_daily_load(array(Counters::WEDDING_COUNT_CAKE, Counters::WEDDING_BOTTLES_CAKE), $cache_date);
		$cakes = $this->data_type($result, array(Counters::WEDDING_COUNT_CAKE => 0, Counters::WEDDING_BOTTLES_CAKE => 1));

		$result = $this->DB->counters_daily_load(array(Counters::MARRIED, Counters::ADMIRED), $cache_date);
		$admirer = $this->data_type($result, array(Counters::MARRIED => 0, Counters::ADMIRED => 1));

		$result = $this->DB->counters_daily_get(Counters::WEDDING_BOTTLES_HAPPY, $cache_date);
		$bottles = $this->data_type($result);

		return array($wedding, $cakes, $admirer, $bottles);
	}

	public function counters_rooms($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::DAU_ROOM, $cache_date);
		$dau = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DAU_ROOM_OWN, $cache_date);
		$dau_own = $this->data_type($result);

		return array($dau, $dau_own);
	}

	public function counters_pets($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::ANIMAL_ACT_FREE_SELF, $cache_date);
		$self = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::ANIMAL_ACT_FREE_OTHER, $cache_date);
		$other = $this->data_type($result);

		return array($self, $other);
	}

	public function counters_stickers_use($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::STICKERS_USAGE_PAID, $cache_date);
		$paid = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::STICKERS_USAGE_FREE, $cache_date);
		$free = $this->data_type($result);

		return array($paid, $free);
	}

	public function counters_stickers_certificate($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::STICKERS_CERT_TRANSFER, $cache_date);
		$transfer = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::STICKERS_CERT_USE, $cache_date);
		$opens = $this->data_type($result);

		return array($transfer, $opens);
	}

	public function counters_verifications($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::VERIFICATIONS, $cache_date);
		$data = $this->data_type($result);

		return array($data);
	}

	public function counters_install_by_invite($cache_date)
	{
		$by_net = array();
		$common = array();

		$result = $this->DB->counters_daily_get(Counters::INVITES_COUNT, $cache_date);
		while ($row = $result->fetch())
		{
			$by_net[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);

			if (!isset($common[$row['date']]))
				$common[$row['date']] = array('date' => $row['date'], 'type' => 0, 'value' => 0);

			$common[$row['date']]['value'] += $row['value'];
		}

		$common = array_values($common);

		return array($common, $by_net);
	}

	public function counters_gifts_devices($cache_date)
	{
		$result = $this->DB->counters_daily_load(array(Counters::IOS_GIFTS, Counters::IOS_HEARTS), $cache_date);
		$ios = $this->data_type($result, array(Counters::IOS_GIFTS => 0, Counters::IOS_HEARTS => 1));

		$result = $this->DB->counters_daily_load(array(Counters::ANDROID_GIFTS, Counters::ANDROID_HEARTS), $cache_date);
		$android = $this->data_type($result, array(Counters::ANDROID_GIFTS => 0, Counters::ANDROID_HEARTS => 1));

		return array($ios, $android);
	}

	public function counters_screen($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::GIFT_SCREEN, $cache_date);
		$gifts = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::HEART_SCREEN, $cache_date);
		$hearts = $this->data_type($result);

		return array($gifts, $hearts);
	}

	public function counters_buttons($cache_date)
	{
		$buttons = array();

		$result = $this->DB->counters_daily_get(Counters::PAYMENT_BOTTLES, $cache_date);
		while ($row = $result->fetch())
			$buttons[] = array('date' => $row['date'], 'type' => 0, 'value' => $row['value']);

		$result = $this->DB->counters_daily_get(Counters::PAYMENT_VIP, $cache_date);
		while ($row = $result->fetch())
			$buttons[] = array('date' => $row['date'], 'type' => 1, 'value' => $row['value']);

		$result = $this->DB->counters_daily_get(Counters::PAYMENT_RICH, $cache_date);
		while ($row = $result->fetch())
			$buttons[] = array('date' => $row['date'], 'type' => 2, 'value' => $row['value']);

		$result = $this->DB->counters_daily_get(Counters::INVITES_CLICK, $cache_date);
		while ($row = $result->fetch())
			$buttons[] = array('date' => $row['date'], 'type' => 3, 'value' => $row['value']);

		$types_merge = array(0 => 4, 1 => 5);

		$result = $this->DB->counters_daily_get(Counters::ANNOUNCEMENT_MOBILE, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if (!isset($types_merge[$type]))
				continue;
			$type = $types_merge[$type];

			$buttons[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		$result = $this->DB->counters_daily_get(Counters::START_BUTTON, $cache_date);
		while ($row = $result->fetch())
			$buttons[] = array('date' => $row['date'], 'type' => 6, 'value' => $row['value']);

		$result = $this->DB->counters_daily_get(Counters::LEFT_MENU, $cache_date);
		$menu = $this->data_type($result);

		return array($buttons, $menu);
	}

	public function counters_shows($cache_date)
	{
		$flow = array();

		$result = $this->DB->counters_daily_get(Counters::PHOTO_SHOWS_FLOW, $cache_date);
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

			if (!isset($flow[$date."_".$type]))
				$flow[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($flow[$date."_2"]))
				$flow[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$flow[$date."_".$type]['value'] += $value;
			$flow[$date."_2"]['value'] += $value;
		}

		$flow = array_values($flow);

		$result = $this->DB->counters_daily_get(Counters::PHOTO_SHOWS_FREE, $cache_date);
		$free = $this->data_type($result);

		$like = array();

		$result = $this->DB->counters_daily_load(array(Counters::PHOTO_SHOWS_FLOW, Counters::PHOTO_SHOWS_FREE), $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$data = $row['data'];

			if ($data != 0)
				continue;

			if (!isset($like[$date]))
				$like[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$like[$date]['value'] += abs($row['value']);
		}

		$like = array_values($like);

		return array($flow, $free, $like);
	}

	public function counters_tree($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_daily_get(Counters::TREE_POINTS, $cache_date);
		$data[0] = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::TREE_STATS, $cache_date);
		$data[1] = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::TREE_LEVELUPS, $cache_date);
		$data[2] = $this->data_type($result);

		return $data;
	}

	public function counters_well($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::COUNT_WELL_CHAIN_LEN, $cache_date);

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_well_index($row['data']);

			$key = $date."-".$type;

			if (!isset($data[$key]))
				$data[$key] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$key]['value'] += $row['value'];
		}

		$data = array_values($data);

		return array($data);
	}

	/**
	 * Игроки
	 */
	public function players_new($cache_date)
	{
		$result = $this->DB->players_new_all($cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->players_new_net($cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->players_new_device($cache_date);
		$device = $this->data_type($result);

		$ages = array();

		$result = $this->DB->players_new_age($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_age_index($row['data']);

			$index = $date."-".$type;

			if (!isset($ages[$index]))
				$ages[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$ages[$index]['value'] += $row['value'];
		}

		$ages = array_values($ages);

		$result = $this->DB->players_new_sex($cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->players_new_tag($cache_date);
		$tag = $this->data_type($result);

		return array($all, $net, $device, $ages, $sex, $tag);
	}

	public function players_new_referrer($cache_date)
	{
		$result = $this->DB->players_new_referrer_all($cache_date, 0, 9999);
		$vk = $this->data_type($result);

		$result = $this->DB->players_new_referrer_all($cache_date, 10000, 19999);
		$mm = $this->data_type($result);

		$result = $this->DB->players_new_referrer_all($cache_date, 20000, 29999);
		$ok = $this->data_type($result);

		$result = $this->DB->players_new_referrer_all($cache_date, 30000, 39999);
		$fb = $this->data_type($result);

		return array($vk, $ok, $mm, $fb);
	}

	/**
	 * Сбор данных для отчёта по активности новичков за сутки
	 */
	public function players_new_day_activity($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::DNU_GAME_TIME, $cache_date);
		$time = $this->data_type($result);

		$kisses_all = array();
		$kisses_success = array();

		$result = $this->DB->counters_daily_load(array(Counters::DNU_KISSES, Counters::DNU_KISSES_FAILED), $cache_date);
		while ($row = $result->fetch())
		{
			$key = $row['date']."-".$row['data'];

			if (!isset($kisses_all[$key]))
				$kisses_all[$key] = array('date' => $row['date'], 'type' => $row['data'], 'value' => 0);
			$kisses_all[$key]['value'] += $row['value'];

			if ($row['type'] == Counters::DNU_KISSES)
				$kisses_success[$key] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
		}

		$result = $this->DB->counters_daily_get(Counters::DNU_RATES_VIEWS, $cache_date);
		$views = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::DNU_TREE_LEVEL, $cache_date);
		$tree_level = $this->data_type($result);

		$kisses_all = array_values($kisses_all);
		$kisses_success = array_values($kisses_success);

		return array($time, $kisses_all, $kisses_success, $views, $tree_level);
	}

	/**
	 * Сбор данных для отчёта по активности новичков за неделю
	 */
	public function players_new_week_activity($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::WNU_GAME_TIME, $cache_date);
		$time = $this->data_type($result);

		$kisses_all = array();
		$kisses_success = array();

		$result = $this->DB->counters_daily_load(array(Counters::WNU_KISSES, Counters::WNU_KISSES_FAILED), $cache_date);
		while ($row = $result->fetch())
		{
			$key = $row['date']."-".$row['data'];

			if (!isset($kisses_all[$key]))
				$kisses_all[$key] = array('date' => $row['date'], 'type' => $row['data'], 'value' => 0);
			$kisses_all[$key]['value'] += $row['value'];

			if ($row['type'] == Counters::WNU_KISSES)
				$kisses_success[$key] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
		}

		$result = $this->DB->counters_daily_get(Counters::WNU_RATES_VIEWS, $cache_date);
		$views = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::WNU_TREE_LEVEL, $cache_date);
		$tree_level = $this->data_type($result);

		$kisses_all = array_values($kisses_all);
		$kisses_success = array_values($kisses_success);

		return array($time, $kisses_all, $kisses_success, $views, $tree_level);
	}

	/**
	 * Сбор данных для отчёта по забаненным игрокам
	 */
	public function players_bans($cache_date)
	{
		$result = $this->DB->players_bans($cache_date);
		$bans = $this->data_type($result);

		return array($bans);
	}

	/**
	 * Сбор данных для отчёта по забаненным платящим и неплатящим игрокам
	 */
	public function players_bans_paying($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::BANS, $cache_date);
		$bans = $this->data_type($result);

		return array($bans);
	}

	public function players_vip_duration($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_daily_get(Counters::VIP_COUNT, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$group = $this->get_vip_rests_index($row['data']);

			if (!isset($data[$date."-".$group]))
				$data[$date."-".$group] = array('date' => $date, 'type' => $group, 'value' => 0);

			$data[$date."-".$group]['value'] += $row['value'];
		}

		return array($data);
	}
/*
	public function players_ab($cache_date)
	{
		$indexes = "";

		$result = $this->DB->players_ab_tags();
		while ($row = $result->fetch())
		{
			if ($row['tag'] == 0)
				continue;
			if ($indexes !== "")
				$indexes .= ",";

			$indexes .= $row['tag'];
		}

		if ($indexes === "")
			return array();

		$paths = array(
			0 => "players_count",
			1 => "start_date",

			2 => "gifts_sent",
			3 => "gifts_received",

			4 => "hearts_sent",
			5 => "hearts_received",

			6 => "session_time",

			7 => "invites",

			8 => "players_paying",
			9 => "players_inviters",

			10 => "payments_value",
			11 => "payments_count",
			12 => "payments_average",
			13 => "payments_sqrt",
			14 => "payments_max",
			15 => "payments_arpu",
			16 => "payments_arppu",

			17 => "payments_group_0",
			18 => "payments_group_20",
			19 => "payments_group_70",
			20 => "payments_group_100",
			21 => "payments_group_500",
			22 => "payments_group_1000",
			23 => "payments_newbie_sum",
			24 => "payments_newbie_count",

/*
			26 => "players_retention_1d",
			27 => "players_retention_2d",
			28 => "players_retention_7d",
			29 => "players_retention_14d",
			30 => "players_retention_30d",

			31 => "players_retention_1d+",
			32 => "players_retention_2d+",
			33 => "players_retention_7d+",
			34 => "players_retention_14d+",
			35 => "players_retention_30d+",
			36 => "players_retention_60d+",
			37 => "players_retention_90d+",
*//*

			38 => "players_active2",
			39 => "players_active5",
			40 => "players_active30",
			41 => "players_active30+",

			42 => "players_net0",
			43 => "players_net1",
			44 => "players_net4",
			45 => "players_net5",
			46 => "players_net6",
			47 => "players_net30",
			48 => "players_net32",

			49 => "players_age0",
			50 => "players_age1",
			51 => "players_age2",
			52 => "players_age3",
			53 => "players_age4",
			54 => "players_age5",
			55 => "players_age99",

			56 => "players_sex0",
			57 => "players_sex1",
			58 => "players_sex2"
		);

		$tags = array();

		$result = $this->DB->players_ab_counts($indexes);

		while ($row = $result->fetch())
		{
			reset($paths);
			while(list(, $path) = each($paths))
			{
				if (!isset($row[$path]))
					$row[$path] = 0;
			}

			$row['payments_value'] = 0;

			$tags[$row['referrer']] = $row;
		}

		$result = $this->DB->players_ab_payments($indexes);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$tag = $row['tag'];
			$net = $row['net'];

			if (!isset($tags[$tag]))
				continue;

			$point = &$tags[$tag];

			$point['players_paying'] += $row['players'];

			$point['payments_value'] += $row['sum'];
			$point['payments_sqrt'] += $row['squared'];
			$point['payments_max'] = max($row['max'], $point['payments_max']);
		}

		$result = $this->DB->players_ab_payments_groups($indexes);
		while ($row = $result->fetch())
		{
			$tag = $row['tag'];
			$group = $this->get_payments_group_index($row['balance']);

			if (!isset($tags[$tag]))
				continue;

			$point = &$tags[$tag];

			$point["payments_group_".$group] += $row['count'];
		}

		$result = $this->DB->players_ab_payments_newbie($indexes);
		while ($row = $result->fetch())
		{
			$tag = $row['tag'];

			if (!isset($tags[$tag]))
				continue;

			$point = &$tags[$tag];

			$point['payments_newbie_sum'] = $row['sum'];
			$point['payments_newbie_count'] = $row['count'];
		}

		$result = $this->DB->players_ab_distribution($indexes);
		while ($row = $result->fetch())
		{
			$tag = $row['tag'];
			$net = $row['type'];
			$age = $this->get_age_index($row['age']);
			$sex = $row['sex'];

			if (!isset($tags[$tag]))
				continue;

			$point = &$tags[$tag];

			if (!isset($point['players_net'.$net]))
				$point['players_net'.$net] = 0;
			if (!isset($point['players_age'.$age]))
				$point['players_age'.$age] = 0;
			if (!isset($point['players_sex'.$sex]))
				$point['players_sex'.$sex] = 0;

			$point['players_net'.$net] += $row['value'];
			$point['players_age'.$age] += $row['value'];
			$point['players_sex'.$sex] += $row['value'];
		}

		$result = $this->DB->players_ab_active($indexes);
		while ($row = $result->fetch())
		{
			$tag = $row['tag'];
			$minutes = $row['minutes'];

			if (!isset($tags[$tag]))
				continue;

			$point = &$tags[$tag];

			if ($minutes < 2)
				$point['players_active2'] += $row['value'];
			if ($minutes >= 2 && $minutes < 5)
				$point['players_active5'] += $row['value'];
			if ($minutes >= 5 && $minutes < 30)
				$point['players_active30'] += $row['value'];
			if ($minutes >= 30)
				$point['players_active30+'] += $row['value'];
		}

		reset($tags);
		while (list($type, ) = each($tags))
		{
			$point = &$tags[$type];

			$point['session_time'] = round($point['games_time'] / $point['games_count'], 2);

			if ($point['payments_count'] == 0 || $point['players_paying'] == 0)
			{
				$point['payments_average'] = 0;
				$point['payments_sqrt'] = 0;
				$point['payments_arpu'] = 0;
				$point['payments_arppu'] = 0;
			}
			else
			{
				$point['payments_average'] = round($point['payments_value'] / $point['payments_count'], 2);
				$point['payments_sqrt'] = round(sqrt($point['payments_sqrt'] / $point['payments_count']), 2);
				$point['payments_arpu'] = round($point['payments_value'] / $point['players_count'], 2);
				$point['payments_arppu'] = round($point['payments_value'] / $point['players_paying'], 2);
			}
		}

		$data = array();

		reset($tags);
		while (list($type, $values) = each($tags))
		{
			$date = $values['date'];

			reset($paths);
			while (list($id, $path) = each($paths))
			{
				if (!isset($data[$id]))
					$data[$id] = array();
				$data[$id][] = array('date' => $date, 'type' => $type, 'value' => $values[$path]);
			}
		}

		return $data;
	}
*/

	public function players_ad($cache_date)
	{
		$indexes = "";

		$result = $this->DB->players_ad_referrers();
		while ($row = $result->fetch())
		{
			if ($row['referrer'] % 10000 <= 1000 || $row['referrer'] > self::ReferrersMax)
				continue;
			if ($indexes !== "")
				$indexes .= ",";

			$indexes .= $row['referrer'];
		}

		if ($indexes === "")
			return array();

		$paths = array(
			0 => "players_count",
			1 => "start_date",

			2 => "gifts_sent",
			3 => "gifts_received",

			4 => "hearts_sent",
			5 => "hearts_received",

			6 => "session_time",

			7 => "invites",

			8 => "players_paying",
			9 => "players_inviters",

			10 => "payments_value",
			11 => "payments_count",
			12 => "payments_average",
			13 => "payments_sqrt",
			14 => "payments_max",
			15 => "payments_arpu",
			16 => "payments_arppu",

			17 => "payments_group_0",
			18 => "payments_group_20",
			19 => "payments_group_70",
			20 => "payments_group_100",
			21 => "payments_group_500",
			22 => "payments_group_1000",

			23 => "payments_newbie_sum",
			24 => "payments_newbie_count",

			26 => "players_retention_1d",
			27 => "players_retention_2d",
			28 => "players_retention_7d",
			29 => "players_retention_14d",
			30 => "players_retention_30d",

			31 => "players_retention_1d+",
			32 => "players_retention_2d+",
			33 => "players_retention_7d+",
			34 => "players_retention_14d+",
			35 => "players_retention_30d+",
			36 => "players_retention_60d+",
			37 => "players_retention_90d+",

			38 => "players_active2",
			39 => "players_active5",
			40 => "players_active30",
			41 => "players_active30+",

/*
			42 => "players_net0",
			43 => "players_net1",
			44 => "players_net4",
			45 => "players_net5",
			46 => "players_net6",
			47 => "players_net30",
			48 => "players_net32",
*/

			49 => "players_age0",
			50 => "players_age1",
			51 => "players_age2",
			52 => "players_age3",
			53 => "players_age4",
			54 => "players_age5",
			55 => "players_age99",

			56 => "players_sex0",
			57 => "players_sex1",
			58 => "players_sex2",

			59 => "payments_sum_sex0",
			60 => "payments_sum_sex1",
			61 => "payments_sum_sex2",
			62 => "payments_count_sex0",
			63 => "payments_count_sex1",
			64 => "payments_count_sex2",

			65 => "payments_sum_age0",
			66 => "payments_sum_age1",
			67 => "payments_sum_age2",
			68 => "payments_sum_age3",
			69 => "payments_sum_age4",
			70 => "payments_sum_age5",
			71 => "payments_sum_age99",
			72 => "payments_count_age0",
			73 => "payments_count_age1",
			74 => "payments_count_age2",
			75 => "payments_count_age3",
			76 => "payments_count_age4",
			77 => "payments_count_age5",
			78 => "payments_count_age99"
		);

		$referrers = array();

		$result = $this->DB->players_ad_counts($indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			reset($paths);
			while(list(, $path) = each($paths))
			{
				if (!isset($row[$path]))
					$row[$path] = 0;
			}

			$row['payments_value'] = 0;

			$referrers[$referrer] = $row;
		}

		$result = $this->DB->players_ad_payments($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$provider_id = $row['provider_id'];
			$date = $row['date'];

			$row['sum'] = $this->get_payment($row['sum'], $provider_id, $date);
			$row['max'] = $this->get_payment($row['max'], $provider_id, $date);
			$row['squared'] = $this->get_payment($row['squared'], $provider_id, $date);

			$point['players_paying'] += $row['players'];

			$point['payments_value'] += $row['sum'];
			$point['payments_sqrt'] += $row['squared'];
			$point['payments_max'] = max($row['max'], $point['payments_max']);
		}

		$result = $this->DB->players_ad_payments_groups($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$row['revenue'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$group = $this->get_payments_group_index($row['revenue']);

			$point["payments_group_".$group] += $row['count'];
		}

		$result = $this->DB->players_ad_payments_newbie($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$row['sum'] = $this->get_payment($row['sum'], $row['provider_id'], $row['date']);

			$point['payments_newbie_sum'] = $row['sum'];
			$point['payments_newbie_count'] = $row['count'];
		}

		$result = $this->DB->players_ad_distribution($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$age = $this->get_age_index($row['age']);
			$sex = $row['sex'];

			if (!isset($point['players_age'.$age]))
				$point['players_age'.$age] = 0;
			if (!isset($point['players_sex'.$sex]))
				$point['players_sex'.$sex] = 0;

			$point['players_age'.$age] += $row['value'];
			$point['players_sex'.$sex] += $row['value'];
		}

		$result = $this->DB->players_ad_inviters($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$point['players_inviters'] += $row['value'];
		}

		$result = $this->DB->players_ad_active($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$minutes = $row['minutes'];

			if ($minutes < 2)
				$point['players_active2'] += $row['value'];
			if ($minutes >= 2 && $minutes < 5)
				$point['players_active5'] += $row['value'];
			if ($minutes >= 5 && $minutes < 30)
				$point['players_active30'] += $row['value'];
			if ($minutes >= 30)
				$point['players_active30+'] += $row['value'];
		}

		$result = $this->DB->players_ad_retention($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$days = $row['days'];

			if ($days >= 1)
				$point['players_retention_1d+'] += $row['value'];
			if ($days >= 2)
				$point['players_retention_2d+'] += $row['value'];
			if ($days >= 7)
				$point['players_retention_7d+'] += $row['value'];
			if ($days >= 14)
				$point['players_retention_14d+'] += $row['value'];
			if ($days >= 30)
				$point['players_retention_30d+'] += $row['value'];
			if ($days >= 60)
				$point['players_retention_60d+'] += $row['value'];
			if ($days >= 90)
				$point['players_retention_90d+'] += $row['value'];
		}

		$result = $this->DB->players_retention_1d_referrer($cache_date);
		while ($row = $result->fetch())
		{
			$referrer = $row['data'];

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$days = $row['days'];

			if ($days == 1)
				$point['players_retention_1d'] += $row['value'];
			else if ($days == 2)
				$point['players_retention_2d'] += $row['value'];
			else if ($days == 7)
				$point['players_retention_7d'] += $row['value'];
			else if ($days == 14)
				$point['players_retention_14d'] += $row['value'];
			else if ($days == 30)
				$point['players_retention_30d'] += $row['value'];
		}

		$result = $this->DB->players_ad_payments_sex($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$sex = $row['sex'];

			$row['sum'] = $this->get_payment($row['sum'], $row['provider_id'], $row['date']);

			if (!isset($point['payments_sum_sex'.$sex]))
				$point['payments_sum_sex'.$sex] = 0;
			if (!isset($point['payments_count_sex'.$sex]))
				$point['payments_count_sex'.$sex] = 0;

			$point['payments_sum_sex'.$sex] += $row['sum'];
			$point['payments_count_sex'.$sex] += $row['count'];
		}

		$result = $this->DB->players_ad_payments_age($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$age = $this->get_age_index($row['age']);

			$row['sum'] = $this->get_payment($row['sum'], $row['provider_id'], $row['date']);

			if (!isset($point['payments_sum_age'.$age]))
				$point['payments_sum_age'.$age] = 0;
			if (!isset($point['payments_count_age'.$age]))
				$point['payments_count_age'.$age] = 0;

			$point['payments_sum_age'.$age] += $row['sum'];
			$point['payments_count_age'.$age] += $row['count'];
		}

		reset($referrers);
		while (list($type, ) = each($referrers))
		{
			$point = &$referrers[$type];

			$point['session_time'] = round($point['games_time'] / $point['games_count'], 2);

			if ($point['payments_count'] == 0 || $point['players_paying'] == 0)
			{
				$point['payments_average'] = 0;
				$point['payments_sqrt'] = 0;
				$point['payments_arpu'] = 0;
				$point['payments_arppu'] = 0;
			}
			else
			{
				$point['payments_average'] = round($point['payments_value'] / $point['payments_count'], 2);
				$point['payments_sqrt'] = round(sqrt($point['payments_sqrt'] / $point['payments_count']), 2);
				$point['payments_arpu'] = round($point['payments_value'] / $point['players_count'], 2);
				$point['payments_arppu'] = round($point['payments_value'] / $point['players_paying'], 2);
			}
		}

		$data = array();

		reset($referrers);
		while (list($type, $values) = each($referrers))
		{
			$date = date("Y-m-d", $values['start_date']);

			reset($paths);
			while (list($id, $path) = each($paths))
			{
				if (!isset($data[$id]))
					$data[$id] = array();
				$data[$id][] = array('date' => $date, 'type' => $type, 'value' => $values[$path]);
			}
		}

		return $data;
	}

	public function players_ab_photoshows($cache_date)
	{
		$result = $this->DB->players_ab_photoshows($cache_date);
		$data = $this->data_type($result);

		return array($data);
	}

	public function players_retention($cache_date)
	{
		$result = $this->players_retention_type($cache_date, "all");

		$data = array();

		while (!empty($result[0]))
		{
			for ($i = 0; $i < 10; $i++)
			{
				$values = array_shift($result[$i]);
				$values['type'] = $i;

				$data[] = $values;
			}
		}

		return array($data);
	}

	public function players_retention_paying($cache_date)
	{
		$result = $this->players_retention_type($cache_date, "paying");

		$data = array();

		while (!empty($result[0]))
		{
			for ($i = 0; $i < 10; $i++)
			{
				$values = array_shift($result[$i]);
				$values['type'] = $i;

				$data[] = $values;
			}
		}

		return array($data);
	}

	public function players_retention_net($cache_date)
	{
		return $this->players_retention_type($cache_date, "net");
	}

	public function players_retention_age($cache_date)
	{
		return $this->players_retention_type($cache_date, "age");
	}

	public function players_retention_sex($cache_date)
	{
		return $this->players_retention_type($cache_date, "sex");
	}

	public function players_retention_device($cache_date)
	{
		return $this->players_retention_type($cache_date, "device");
	}

	public function players_retention_tag($cache_date)
	{
		return $this->players_retention_type($cache_date, "tag");
	}

	public function players_retention_referrer_vk($cache_date)
	{
		return $this->players_retention_type($cache_date, "referrer", array(0, 9999));
	}

	public function players_retention_referrer_mm($cache_date)
	{
		return $this->players_retention_type($cache_date, "referrer", array(10000, 19999));
	}

	public function players_retention_referrer_ok($cache_date)
	{
		return $this->players_retention_type($cache_date, "referrer", array(20000, 29999));
	}

	public function players_retention_referrer_fb($cache_date)
	{
		return $this->players_retention_type($cache_date, "referrer", array(30000, 39999));
	}

	public function players_life_time($cache_date)
	{
		list($all, $net) = $this->players_life_time_type($cache_date, "net");
		$age = $this->players_life_time_type($cache_date, "age");
		$device = $this->players_life_time_type($cache_date, "device");
		$sex = $this->players_life_time_type($cache_date, "sex");
		$tag = $this->players_life_time_type($cache_date, "tag");

		return array($all, $net, $age, $device, $sex, $tag);
	}

	public function players_paying_day($cache_date)
	{
		list($all, $net) = $this->players_paying_day_type($cache_date, "net");
		$device = $this->players_paying_day_type($cache_date, "device");
		$age = $this->players_paying_day_type($cache_date, "age");
		$sex = $this->players_paying_day_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}

	public function players_paying_month($cache_date)
	{
		list($all, $net) = $this->players_paying_month_type($cache_date, "net");
		$device = $this->players_paying_month_type($cache_date, "device");
		$age = $this->players_paying_month_type($cache_date, "age");
		$sex = $this->players_paying_month_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}

	public function players_paying_groups($cache_date)
	{
		$data = array();
		$daily = array();
		$payments = array();

		$old_date = false;
		$days = 0;
		$cache_time = strtotime($cache_date);

		$result = $this->DB->players_paying_groups($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);
			$row['sum'] = intval($row['sum']);

			$date = $row['date'];
			$uid = $row['data']."-".$row['net_id'];

			if ($old_date === false)
				$old_date = $date;
			if ($old_date != $date)
			{
				if ($cache_time < strtotime($date))
				{
					reset($payments);
					while (list(, $sum) = each($payments))
					{
						if ($sum < 0)
							$this->Log->warning("Wrong payment sum {$sum} at {$date}");
						if ($sum === 0)
							continue;

						$type = $this->get_paid_group_index($sum);

						if (!isset($data[$old_date."-".$type]))
							$data[$old_date."-".$type] = array('date' => $old_date, 'type' => $type, 'value' => 0);

						$data[$old_date."-".$type]['value'] += 1;
					}
				}

				$offset = $this->date_diff($date, $old_date);
				for ($i = 0; $i < $offset; $i++)
				{
					$days += 1;
					$daily[$days] = array();
				}

				$old_date = $date;
			}

			if (!isset($payments[$uid]))
				$payments[$uid] = 0;

			$payments[$uid] += $row['sum'];
			$daily[$days][$uid] = $row['sum'];

			$counter = count($daily);
			if ($counter <= 30)
				continue;

			reset($daily);
			while ((list($key, $users) = each($daily)) && $counter > 30)
			{
				reset($users);
				while (list($user_id, $sum) = each($users))
					$payments[$user_id] -= $sum;

				unset($daily[$key]);
				$counter -= 1;
			}
		}

		reset($payments);
		while (list(, $sum) = each($payments))
		{
			if ($sum < 0)
				$this->Log->warning("Wrong payment sum {$sum} at {$date}");
			if ($sum === 0)
				continue;

			$type = $this->get_paid_group_index($sum);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += 1;
		}

		$data = array_values($data);

		return array($data);
	}

	public function players_paying_counts($cache_date)
	{
		$cache_date = "2000-01-01";

		$players = array();
		$data = array();

		$result = $this->DB->players_paying_net($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$id = $row['data']."-".$row['net_id'];

			if (!isset($players[$id]))
				$players[$id] = 0;
			$players[$id] += $row['count'];

			$type = $this->get_players_paying_count_index($players[$id]);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += 1;
		}

		$data = array_values($data);

		return array($data);
	}

	public function players_ctr_posting($cache_date)
	{
		$data = array();
		$clicks = array();
		$shows = array();

		$result = $this->DB->counters_daily_get(Counters::COUNT_WALL_REQUEST, $cache_date);
		while ($row = $result->fetch())
			$shows[$row['date']."-".$row['data']] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);

		$result = $this->DB->counters_daily_get(Counters::COUNT_WALL_POSTED, $cache_date);
		while ($row = $result->fetch())
			$clicks[$row['date']."-".$row['data']] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);

		while (list($key, $value) = each($shows))
		{
			if ($value['value'] == 0)
				continue;

			if (isset($clicks[$key]))
				$clicks_value = $clicks[$key]['value'];
			else
				$clicks_value = 0;

			$value['value'] = round($clicks_value / $value['value'] * 100, 2);

			$data[0][] = $value;
		}

		return $data;
	}

	/**
	 * События
	 */
	public function events_collection_pickup($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_daily_get(Counters::COLLECTION_PICKUP, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->events_collection_index($row['data']);

			$data[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		return array($data);
	}

	public function events_collection_assemble($cache_date)
	{
		$data = array();

		$result = $this->DB->counters_daily_get(Counters::COLLECTION_ASSEMBLE, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->events_collection_index($row['data']);

			$data[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		return array($data);
	}

/*
	public function events_collection_efficiency($cache_date)
	{
		$releases = array(
			0 => array(
				'min' => mktime(0, 0, 0, 4, 26, 2013),		// Пасхальные 26.04.13-17.05.13
				'max' => mktime(0, 0, 0, 5, 17, 2013)
			),
			1 => array(
				'min' => mktime(0, 0, 0, 8, 9, 2013),		// Летние 09.08.13-23.09.13
				'max' => mktime(0, 0, 0, 9, 23, 2013)
			),
			2 => array(
				'min' => mktime(0, 0, 0, 9, 27, 2013),		// Осенние 27.09.13-12.11.13
				'max' => mktime(0, 0, 0, 11, 12, 2013)
			),
			3 => array(
				'min' => mktime(0, 0, 0, 11, 18, 2013),		// Свадебные 18.11.13-12.12.13
				'max' => mktime(0, 0, 0, 12, 12, 2013)
			),
			4 => array(
				'min' => mktime(0, 0, 0, 12, 12, 2013),		// Новогодние 12.12.13-29.01.14
				'max' => mktime(0, 0, 0, 1, 30, 2014)
			),
			5 => array(
				'min' => mktime(0, 0, 0, 1, 29, 2014),		// Олимпийские коллекции 29.01.14-28.02.14
				'max' => mktime(0, 0, 0, 2, 29, 2014)
			),
			6 => array(
				'min' => mktime(0, 0, 0, 2, 28, 2014),		// Весенние пазлы 28.02.14-26.04.14
				'max' => mktime(0, 0, 0, 4, 27, 2014)
			),
			7 => array(
				'min' => mktime(0, 0, 0, 6, 9, 2014),		// Шкатулки и ключи 09.06.14-??.??.??
				'max' => mktime(0, 0, 0, date("n"), date("d") + 1, date("Y"))
			),
			8 => array(
				'min' => mktime(0, 0, 0, 7, 22, 2014),		// Летние коллекции 22.07.14-01.09.14
				'max' => mktime(0, 0, 0, 9, 2, 2014)
			),
			9 => array(
				'min' => mktime(0, 0, 0, 9, 1, 2014),		// Летние пазлы 01.09.14-30.10.14
				'max' => mktime(0, 0, 0, 9, 20, 2014)
			),
			10 => array(
				'min' => mktime(0, 0, 0, 9, 19, 2014),		// Осенние коллекции 19.10.14-30.10.14
				'max' => mktime(0, 0, 0, 11, 1, 2014)
			),
			11 => array(
				'min' => mktime(0, 0, 0, 10, 30, 2014),		// Хэллоуин коллекции 30.10.14-15.11.14
				'max' => mktime(0, 0, 0, 11, 16, 2014)
			),
			12 => array(
				'min' => mktime(0, 0, 0, 12, 12, 2014),		// Новогодние коллекции 12.12.14-??.??.??
				'max' => mktime(0, 0, 0, date("n"), date("d") + 1, date("Y"))
			)
		);

		$sum = array();
		$count = array();

		$result = $this->DB->events_collection_efficiency($cache_date, $this->coll);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$time = strtotime($row['date']);

			reset($releases);
			while (list($key, $dates) = each($releases))
			{
				if ($time < $dates['min'])
					continue;
				if ($time > $dates['max'])
					continue;

				$sum[] = array('date' => $date, 'type' => $key, 'value' => $row['sum']);
				$count[] = array('date' => $date, 'type' => $key, 'value' => $row['count']);
			}
		}

		return array($sum, $count);
	}
*/

	/**
	 * События (API)
	 */
	public function api_loading($cache_date)
	{
		$paths = array(
			array("FRAME_LOADED", "GAME_LOADED", "PLAYER_LOADED"),
			array("VK", "OK", "MM", "FB", "FS")
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			list($type, $graph) = $paths[$type];

			if (!isset($data[$graph]))
				$data[$graph] = array();
			$data[$graph][] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		return $data;
	}

	public function api_loading_time($cache_date)
	{
		$paths = array(
			array("GAME_LOADED", "PLAYER_LOADED"),
			false,
			true
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			list($graph, $type) = $paths[$type];

			$type = $this->get_loading_time_index($graph, $type);
			if ($type < 0)
				continue;

			if (!isset($data[$graph]))
				$data[$graph] = array();

			$index = $date."-".$type;

			if (!isset($data[$graph][$index]))
				$data[$graph][$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$graph][$index]['value'] += $row['value'];
		}

		return $data;
	}

	public function api_browsers($cache_date)
	{
		$paths = array(
			array("BROWSER"),
			array("CHROME", "FIREFOX", "MSIE", "OPERA", "SAFARI", "YANDEX", "AMIGO", "UNKNOWN"),
			false
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			list(, $type) = $paths[$type];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += $row['value'];
		}

		return array($data);
	}

	public function api_room_change($cache_date)
	{
		$paths = array(
			array("ROOM"),
			array("CHANGE")
		);

		$result = $this->Events->get_mixed(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];

			$data[] = array('date' => $date, 'type' => 0, 'value' => $row['hits']);
			$data[] = array('date' => $date, 'type' => 1, 'value' => $row['visitors']);
		}

		return array($data);
	}

	public function api_peertopeer($cache_date)
	{
		$paths = array(
			array("P2P_ENABLED"),
			array(0 => "VK", 1 => "MM", 4 => "OK", 5 => "FB", 6 => "MB", 30 => "FS")
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			list(, $type) = $paths[$type];

			$data[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);
		}

		return array($data);
	}

	public function api_requests($cache_date)
	{
		$paths = array(
			array("PLAYLIST_QUERY"),
			array(0 => "VK", 1 => "MM", 4 => "OK", 5 => "FB", 6 => "MB", 30 => "FS")
		);

		$result = $this->Events->get_mixed(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			list(, $type) = $paths[$type];

			$requests[] = array('date' => $date, 'type' => $type, 'value' => $row['hits']);
		}

		return array($requests);
	}

	public function api_lag_page($cache_date)
	{
		$paths = array(
			array("PLAYER_LOADED"),
			array("VK", "OK", "MM", "FB", "FS")
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$net = $paths[$type][1] + 1;

			$visitors[$date."-".$net] = $row['value'];
		}

		$paths = array(
			array("LAG"),
			array("ACHIEVEMENTS", "APARTMENTS", "CHAT", "DISCONNECTED", "GAME", "MODERATION", "PROFILE", "RATINGAPARTMENTS", "RATINGCOMMON", "RATINGDAY", "RATINGPETS", "RATINGRICH", "SEARCH", "WEDDING"),
			array("VK", "OK", "MM", "FB", "FS")
		);

		$result = $this->Events->get_visitors(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array(0 => array());
		$all = &$data[0];

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$path = $row['data'];

			$type = $paths[$path][1];
			$net = $paths[$path][2] + 1;

			$index = $date."-".$type;

			if (!isset($all[$index]))
				$all[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			$all[$index]['value'] += $row['value'];

			if (!isset($visitors[$date."-".$net]))
				continue;

			if (!isset($data[$net]))
				$data[$net] = array();
			if (!isset($data[$net][$index]))
				$data[$net][$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			$data[$net][$index]['value'] += $row['value'];
		}

		while (list($index, $net) = each($data))
		{
			if ($index === 0)
			{
				$data[$index] = array_values($data[$index]);
				continue;
			}

			while (list($key, $values) = each($net))
			{
				$point = &$visitors[$values['date']."-".$index];
				$data[$index][$key]['value'] = round($values['value'] / $point * 100, 2);
			}

			$data[$index] = array_values($data[$index]);
		}

		return $data;
	}

	public function api_chat_stickers($cache_date)
	{
		$paths = array(
			array("CHAT_STICKER")
		);

		$result = $this->Events->get_mixed(self::$service_id, $cache_date, $paths);
		if ($result === false)
			return array();

		$data = array();
		while ($row = $result->fetch())
		{
			$data[0][] = array('date' => $row['date'], 'type' => 0, 'value' => $row['visitors']);
			$data[0][] = array('date' => $row['date'], 'type' => 1, 'value' => $row['hits']);
		}

		return $data;
	}

	/**
	 * Мафия
	 */
	public function mafia_rooms($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::MAFIA_PLAYERS_IN_ROOM, $cache_date);
		$rooms = $this->data_type($result);

		$time = array();

		$result = $this->DB->counters_daily_get(Counters::MAFIA_ROUND_TIME, $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $this->get_mafia_time_index($row['data']);

			$index = $date."-".$type;

			if (!isset($time[$index]))
				$time[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$time[$index]['value'] += $row['value'];
		}

		$time = array_values($time);

		return array($rooms, $time);
	}

	public function mafia_exits($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::MAFIA_EXITS, $cache_date);
		$exits = $this->data_type($result);

		return array($exits);
	}

	public function mafia_game($cache_date)
	{
		$result = $this->DB->counters_daily_get(Counters::MAFIA_WINS, $cache_date);
		$wins = $this->data_type($result);

		$result = $this->DB->counters_daily_get(Counters::MAFIA_KILLS, $cache_date);
		$kills = $this->data_type($result);

		$heals_count = array();

		$result = $this->DB->counters_daily_get(Counters::MAFIA_HEALINGS, $cache_date);
		while ($row = $result->fetch())
			$heals_count[$row['date']][$row['data']] = $row['value'];

		$heals = array();

		while (list($date, $data) = each($heals_count))
		{
			if (!isset($data[0]) || !isset($data[1]))
				continue;
			if ($data[0] == 0)
				continue;

			$heals[] = array('date' => $date, 'type' => 0, 'value' => round($data[1] / $data[0] * 100, 2));
			$heals[] = array('date' => $date, 'type' => 1, 'value' => $data[0]);
			$heals[] = array('date' => $date, 'type' => 2, 'value' => $data[1]);
		}

		return array($wins, $kills, $heals);
	}

	/**
	 * Скрытые отчеты
	 */
	public function hidden_payments_all($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_net($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];

			if (!isset($data[$date]))
				$data[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$data[$date]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_payments_net($cache_date)
	{
		$data = array();

		$result = $this->DB->payments_net($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_payments_device($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_device($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $this->get_provider_index($row['data']);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_payments_age($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_age($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $this->get_age_index($row['data']);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_payments_sex($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_sex($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_payments_tag($cache_date)
	{
		$data = array();

		$result = $this->DB->finance_arpu_tag($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$data[$index]['value'] += round($row['sum'], 2);
		}

		$data = array_values($data);

		return array($data);
	}

	public function hidden_counters_mau($cache_date)
	{
		$all = array();
		$net = array();

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['net'], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$net[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);

			if (!isset($all[$date]))
				$all[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$all[$date]['value'] += $row['value'];
		}

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['device'], $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['age'], $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau_new['tag'], $cache_date);
		$tag = $this->data_type($result);

		$all = array_values($all);

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function hidden_paying_month($cache_date)
	{
		list($all, $net) = $this->hidden_paying_month_type($cache_date, "net");
		$device = $this->hidden_paying_month_type($cache_date, "device");
		$age = $this->hidden_paying_month_type($cache_date, "age");
		$sex = $this->hidden_paying_month_type($cache_date, "sex");
		$tag = $this->hidden_paying_month_type($cache_date, "tag");

		return array($all, $net, $device, $age, $sex, $tag);
	}

	public function hidden_revenue_ad($cache_date)
	{
		$indexes = "";

		$result = $this->DB->players_ad_referrers();
		while ($row = $result->fetch())
		{
			if ($row['referrer'] % 10000 <= 1000 || $row['referrer'] > self::ReferrersMax)
				continue;
			if ($indexes !== "")
				$indexes .= ",";

			$indexes .= $row['referrer'];
		}

		if ($indexes === "")
			return array();

		$paths = array(
			0 => "revenue",
			1 => "start_date"
		);

		$referrers = array();

		$result = $this->DB->players_ad_counts($indexes);
		while ($row = $result->fetch())
		{
			$raw_data = array();
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);

			if ($row['players_count'] < 10)
				continue;

			reset($paths);
			while(list(, $path) = each($paths))
			{
				if (!isset($row[$path]))
					$raw_data[$path] = 0;
				else
					$raw_data[$path] = $row[$path];
			}

			$referrers[$referrer] = $raw_data;
		}

		$result = $this->DB->players_ad_payments($indexes, $indexes);
		while ($row = $result->fetch())
		{
			$referrer = ($row['tier'] == 1 ? -$row['referrer'] : $row['referrer']);
			if (!isset($referrers[$referrer]))
				continue;
			$point = &$referrers[$referrer];

			$row['sum'] = $this->get_revenue($row['sum'], $row['provider_id'], $row['date']);

			$point['revenue'] += $row['sum'];
		}

		$data = array();

		reset($referrers);
		while (list($type, $values) = each($referrers))
		{
			$date = date("Y-m-d", $values['start_date']);

			reset($paths);
			while (list($id, $path) = each($paths))
			{
				if ($path == "start_date")
					continue;

				if (!isset($data[$id]))
					$data[$id] = array();
				$data[$id][] = array('date' => $date, 'type' => $type, 'value' => $values[$path]);
			}
		}

		return $data;
	}

	public function apipath_common($cache_date)
	{
		return array();
	}

	/**
	 * Формирует данные для отчета Платежи/Последние по часам.
	 * Такие методы должны быть предназначены для специализированных отчётов,
	 * формат представления данных в которых отличается от обычных форматов,
	 * представленных в данной системе аналитики
	 *
	 * @return DatabaseResult Результат выборки из базы данных для дальнейшей обработки
	 */
	public function special_payments_hourly_last()
	{
		$date = new DateTime();

		$today = $date->format("Y-m-d");
		$yesterday = $date->sub(new DateInterval('P1D'))->format("Y-m-d");
		$week_ago = $date->sub(new DateInterval('P6D'))->format("Y-m-d");

		$result = $this->DB->payments_hourly_last($today, $yesterday, $today, $yesterday, $week_ago);

		return $result;
	}

	/**
	 * Обновляет дополнительные пользовательские параметры
	 * для отчёта по рекламной кампании: ROI, CTR, CPI и другие,
	 * которые вводятся через форму редактирования столбца таблицы отчёта
	 *
	 * @return void Возврат в случае отсутствия данных в редактируемом отчёте
	 */
	public function update_ad_params()
	{
		$paths = array(
			79 => "cpi",
			80 => "ctr",
			81 => "recoupment",
			82 => "roi"
		);

		$referrers = array();

		$result = $this->DB->players_ad_report(self::$service_id);
		while ($row = $result->fetch())
		{
			$referrers[$row['type']]['start_date'] = $row['date'];
			$referrers[$row['type']]['players_count'] = $row['value'];
		}

		if (count($referrers) == 0)
			return;

		$result = $this->DB->players_ad_revenue(self::$service_id);
		while ($row = $result->fetch())
		{
			$referrer = $row['type'];

			if (!isset($referrers[$referrer]))
				continue;

			$point = &$referrers[$referrer];
			$point['revenue'] = $row['value'];
		}

		$result = $this->DB->players_ad_parameters(self::$service_id);
		while ($row = $result->fetch())
		{
			$referrer = $row['cache_type'];

			if (!isset($referrers[$referrer]))
				continue;

			$point = &$referrers[$referrer];

			$point['cpi'] = $row['adv_cost'] / $point['players_count'];
			$point['ctr'] = round($row['clicks'] / $row['shows'] * 100, 2);
			$point['roi'] = round($point['revenue'] / $row['adv_cost'] * 100, 2);
			$point['recoupment'] = $point['revenue'] - $row['adv_cost'];
		}

		$data = array();

		reset($referrers);
		while (list($type, $values) = each($referrers))
		{
			$date = $values['start_date'];

			reset($paths);
			while (list($id, $path) = each($paths))
			{
				if ($path == "start_date")
					continue;

				if (!isset($values[$path]))
					continue;

				if (!isset($data[$id]))
					$data[$id] = array();
				$data[$id][] = array('date' => $date, 'type' => $type, 'value' => $values[$path]);
			}
		}

		reset($data);
		while (list($i) = each($data))
		{
			$values = "";
			$pieces = 0;

			reset($data[$i]);
			while (list(, $point) = each($data[$i]))
			{
				if ($values != "")
					$values .= ",";

				$values .= "('".self::$service_id."', 'common', hidden_players_ad_params', '".$point['date']."', ".$i.", ".$point['type'].", ".$point['value'].")";
				$pieces++;

				if ($pieces != 1000)
					continue;

				$this->DB->replace_cache($values);
				$pieces = 0;
				$values = "";
			}

			if ($values == "")
				continue;

			$this->DB->replace_cache($values);
		}
	}

 	/**
	 * Auto legends
	 */
	public function players_ad_legend($type)
	{
		$type = abs($type);
		$networks = array("ВК", "ММ", "ОК", "FB", "ФС");

		$index = intval($type / 10000);
		$postfix = ($type - 1000) % 10000;

		if (!isset($networks[$index]))
			return false;

		return $networks[$index].$postfix;
	}

	public function players_ad_legend_index($type)
	{
		return ($type > 0 ? 0 : 1);
	}

	public function players_ad_table_filter($legend)
	{
		if (count($legend) === 0)
			return false;

		$filter = array();
		$networks = array("ВКонтакте", "Мой Мир", "Одноклассники", "Facebook", "Фотострана");

		while (list($type, $label) = each($legend))
		{
			$index = intval(abs($type) / 10000);

			if (!isset($networks[$index]))
				return false;

			$filter[$type] = $networks[$index];
		}

		return $filter;
	}

	/**
	 * Edit advertising campaign parameters form
	 */
	public function players_ad_editform($report, $type)
	{
		$data = $this->DB->players_ad_campaign_data(self::$service_id, $report, $type)->fetch();

		if (!$data)
		{
			$data = array();

			$data['cache_report'] = $report;
			$data['cache_type'] = $type;
			$data['adv_cost'] = 0;
			$data['clicks'] = 0;
			$data['shows'] = 0;
		}

		$this->Templates->set("Шаблоны/Форма редактирования рекламной кампании");
		$this->Templates->service = "bottle";
		$this->Templates->cache_report = $data['cache_report'];
		$this->Templates->cache_type = $data['cache_type'];
		$this->Templates->adv_cost = $data['adv_cost'];
		$this->Templates->clicks = $data['clicks'];
		$this->Templates->shows = $data['shows'];

		$ret = new stdClass();
		$ret->editForm = (string) $this->Templates;

		return $ret;
	}

	public function players_ad_saveform()
	{
		$fields = array(
			'report'	=> array('require' => true),
			'type'		=> array('require' => true),
			'adv_cost'	=> array('require' => true),
			'clicks'	=> array('require' => true),
			'shows'		=> array('require' => true),
			'filename'	=> array('require' => false)
		);

		$fields = $this->EasyForms->fields($fields);

		$fields['service'] = self::$service_id;
		$fields['report'] = $fields['report'];
		$fields['type'] = intval($fields['type']);
		$fields['adv_cost'] = floatval($fields['adv_cost']);
		$fields['clicks'] = intval($fields['clicks']);
		$fields['shows'] = intval($fields['shows']);
		$fields['filename'] = $fields['filename'] ? $fields['filename'] : "";

		$this->DB->update_adv_params($fields['service'], $fields['report'], $fields['type'], $fields['adv_cost'], $fields['clicks'], $fields['shows'], $fields['filename']);

		return null;
	}

	public function players_ad_colimage($report, $type)
	{
		$ret = new stdClass();
		$ret->imgUrl = "";
		$data = $this->DB->players_ad_campaign_data(self::$service_id, $report, $type)->fetch();

		if (!$data || $data['filename'] == "")
			return $ret;

		$ret->imgUrl = "/".trim(str_replace(trim($_SERVER['DOCUMENT_ROOT'], "/"), "", UPLOAD_DIR), "/")."/".$data['filename'];

		return $ret;
	}

	/**
	 * Helper functions
	 */
	private function payments_boxes_data($cache_date, $legend)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->payments_boxes($cache_date, array_keys($legend));
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($sum[$index]))
				$sum[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sum[$index]['value'] += $row['sum'];
			$count[$index]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	private function payments_type($cache_date, $key)
	{
		$sums = array();
		$counts = array();

		$result = $this->DB->{"payments_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			$index = $date."-".$type;

			if (!isset($sums[$index]))
				$sums[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($counts[$index]))
				$counts[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sums[$index]['value'] += $row['sum'];
			$counts[$index]['value'] += $row['count'];
		}

		$sums = array_values($sums);
		$counts = array_values($counts);

		return array($sums, $counts);
	}

	private function payments_simple($cache_date, $key)
	{
		$data = array();

		$result = $this->DB->{"payments_".$key}($cache_date, $cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);
			$data[] = $row;
		}

		$data = $this->sum_by_column($data, "date", array("sum", "count"));

		$data = $this->data_sum_one($data);

		return array($data);
	}

	private function payments_offers_data($cache_date, $legend)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->payments_boxes($cache_date, array_keys($legend));
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			$index = $date."-".$type;

			if (!isset($sum[$index]))
				$sum[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($count[$index]))
				$count[$index] = array('date' => $date, 'type' => $type, 'value' => 0);

			$sum[$index]['value'] += round($row['sum'], 2);
			$count[$index]['value'] += $row['count'];
		}

		$sum = array_values($sum);
		$count = array_values($count);

		return array($sum, $count);
	}

	private function finance_arpu_type($cache_date, $key)
	{
		$data = array();
		$data_all = array();

		$result = $this->DB->{"finance_arpu_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);
			if ($key == "device")
				$type = $this->get_provider_index($type);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'value' => 0, 'full' => false);
			if (!isset($data_all[$date]))
				$data_all[$date] = array('date' => $date, 'type' => 0, 'value' => 0, 'full' => false);

			$data[$index]['value'] += $row['sum'];
			$data_all[$date]['value'] += $row['sum'];
		}

		$result = $this->DB->counters_daily_get($this->counters_dau_new[$key], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			$index = $date."-".$type;

			if (!isset($data[$index]))
				continue;
			$point = &$data[$index];

			$point['value'] = round($point['value'] / $value, 2);
			$point['full'] = true;
		}

		$net = array();
		while (list(, $values) = each($data))
		{
			if (!isset($values['full'])||!$values['full'])
				continue;

			$net[] = $values;
		}

		if ($key != "net")
			return $net;

		$result = $this->DB->counters_daily_get($this->counters_dau_new['all'], $cache_date);
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
			$row['sum'] = $this->get_payment($row['revenue'], $row['provider_id'], $row['date']);

			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);
			if ($key == "device")
				$type = $this->get_provider_index($type);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'sum' => 0, 'count' => 0);
			$point = &$data[$index];

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

	private function finance_ltv_type($cache_date, $key, $paying = false)
	{
		$cache_date = "2016-01-01";

		$data = array();

		$result = $this->DB->{"finance_ltv_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$row['sum'] = $this->get_revenue($row['revenue'], $row['provider_id'], $row['date']);

			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);
			if ($key == "device")
				$type = $this->get_provider_index($type);

			$data[$type][] = $row;
		}

		$players = array();

		if ($paying === false)
			$paying = "";
		else
			$paying = "_paying";

		$result = $this->DB->{"players_new_".$key.$paying}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			$players[$type][$date] = $row['value'];
		}

		$end_date = new DateTime();

		$net = array();

		foreach ($data as $type => &$rows)
		{
			$rows = $this->sum_by_column($rows, "date", array("sum"));

			$sum_data = 0;
			$sum_players = 0;

			$cur_date = new DateTime($cache_date);
			while ($cur_date <= $end_date)
			{
				$date = $cur_date->format("Y-m-d");

				if (!isset($rows[$date]))
					$rows[$date] = array('sum' => 0);

				if (!isset($players[$type][$date]))
					$players[$type][$date] = 0;

				$sum_data += $rows[$date]['sum'];
				$sum_players += $players[$type][$date];

				$rows[$date]['sum'] = $sum_data;
				$players[$type][$date] = $sum_players;

				$cur_date->add(new DateInterval("P1D"));

				if ($sum_players == 0)
					continue;

				$net[] = array('date' => $date, 'type' => $type, 'value' => round($sum_data / $sum_players, 2));
			}
		}

		if ($key !== "net")
			return $net;

		$all = array();

		$cur_date = new DateTime($cache_date);
		while ($cur_date <= $end_date)
		{
			$date = $cur_date->format("Y-m-d");

			$sum_data = 0;
			$sum_players = 0;

			foreach ($data as $type => $rows)
			{
				$sum_data += $rows[$date]['sum'];
				$sum_players += $players[$type][$date];
			}

			$cur_date->add(new DateInterval("P1D"));

			if ($sum_players == 0)
				continue;

			$all[] = array('date' => $date, 'type' => 0, 'value' => round($sum_data / $sum_players, 2));
		}

		return array($all, $net);
	}

	private function counters_mau_percent_type($cache_date, $key)
	{
		$dau = array();

		$result = $this->DB->counters_daily_get($this->counters_dau_new[$key], $cache_date);
		while ($row = $result->fetch())
			$dau[$row['date']."-".$row['data']] = $row['value'];

		$data = array();

		$result = $this->DB->counters_daily_get($this->counters_mau[$key], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			$index = $date."-".$type;

			if (!isset($dau[$index]))
				continue;

			$data[] = array('date' => $date, 'type' => $type, 'value' => round(($dau[$index] * 100) / $value, 2));
		}

		return $data;
	}

	private function players_life_time_type($cache_date, $key)
	{
		$data = array();
		$data_all = array();

		$result = $this->DB->{"players_retention_1d_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$days = $row['days'] * $row['value'];

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = array('date' => $date, 'type' => $type, 'days' => 0, 'value' => 0);
			if (!isset($data_all[$date]))
				$data_all[$date] = array('days' => 0, 'value' => 0);

			$point = &$data[$index];
			$point['days'] += $days;
			$point['value'] += $row['value'];

			$point = &$data_all[$date];
			$point['days'] += $days;
			$point['value'] += $row['value'];
		}

		$net = array();

		while (list(, $values) = each($data))
			$net[] = array('date' => $values['date'], 'type' => $values['type'], 'value' => round($values['days'] / $values['value']));

		if ($key !== "net")
			return $net;

		$all = array();

		while (list($date, $values) = each($data_all))
			$all[] = array('date' => $date, 'type' => 0, 'value' => round($values['days'] / $values['value']));

		return array($all, $net);
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
			if ($key == "device")
				$type = $this->get_provider_index($type);

			$index = $date."-".$type;

			if (!isset($data[$index]))
				$data[$index] = 0;
			if (!isset($data_all[$date]))
				$data_all[$date] = 0;

			$data[$index] += $row['count'];
			$data_all[$date] += $row['count'];
		}

		$net = array();

		$result = $this->DB->counters_daily_get($this->counters_dau_new[$key], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			$index = $date."-".$type;

			if (!isset($data[$index]))
				continue;

			$net[] = array('date' => $date, 'type' => $type, 'value' => round(($data[$index] * 100) / $value, 2));
		}

		if ($key != "net")
			return $net;

		$all = array();

		$result = $this->DB->counters_daily_get($this->counters_dau_new['all'], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			if (!isset($data_all[$date]))
				continue;

			$all[] = array('date' => $date, 'type' => 0, 'value' => round(($data_all[$date] * 100) / $value, 2));
			$all[] = array('date' => $date, 'type' => 1, 'value' => $data_all[$date]);
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
			if ($key == "device")
				$type = $this->get_provider_index($type);

			if (!isset($data[$date][$type]))
				$data[$date][$type] = array();
			$point = &$data[$date][$type];

			$point[] = $row['net_id'];
		}

		$values = array();
		$inserts = array();
		$counts = array();
		$counts_net = array();
		$merged = array();

		$last_date = false;
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

		$result = $this->DB->counters_daily_get($this->counters_mau[$key], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			$index = $date."-".$type;

			if (!isset($counts_net[$index]))
				continue;

			$net[] = array('date' => $date, 'type' => $type, 'value' => round(($counts_net[$index] * 100) / $value, 2));
		}

		if ($key != "net")
			return $net;

		$all = array();

		$result = $this->DB->counters_daily_get($this->counters_mau['all'], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];

			if ($value == 0)
				continue;

			if (!isset($counts[$date]))
				continue;

			$all[] = array('date' => $date, 'type' => 0, 'value' => round(($counts[$date] * 100) / $value, 2));
			$all[] = array('date' => $date, 'type' => 1, 'value' => $counts[$date]);
		}

		return array($all, $net);
	}

	private function players_retention_type($cache_date, $key, $params = false)
	{
		$returned = array();

		if ($key == "referrer" && $params !== false)
			$result = $this->DB->{"players_retention_".$key}($params[0], $params[1]);
		else
			$result = $this->DB->{"players_retention_".$key}();

		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];
			$days = $row['days'];
			$value = $row['value'];

			if ($key == "age")
				$type = $this->get_age_index($type);

			if ($key == "net" && ($type == 255 || $type == 253 || $type == 252 || $type == 251))
				continue;

			if (!isset($returned[$date]))
				$returned[$date] = array();

			if (!isset($returned[$date][$type]))
				$returned[$date][$type] = array('registered' => 0, '1d' => 0, '2d' => 0, '7d' => 0, '14d' => 0, '30d' => 0, '1d+' => 0, '2d+' => 0, '7d+' => 0, '14d+' => 0, '30d+' => 0);
			$point = &$returned[$date][$type];

			$point['registered'] += $value;

			if ($days == 0)
				continue;
			if ($days >= 1)
				$point['1d+'] += $value;
			if ($days >= 2)
				$point['2d+'] += $value;
			if ($days >= 7)
				$point['7d+'] += $value;
			if ($days >= 14)
				$point['14d+'] += $value;
			if ($days >= 30)
				$point['30d+'] += $value;
		}

		$result = $this->DB->{"players_retention_1d_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['registered'];
			$type = $row['data'];
			$days = $row['days'];

			if (!isset($returned[$date][$type]))
				continue;

			$point = &$returned[$date][$type];

			if ($days == 1)
				$point['1d'] = $row['value'];
			else if ($days == 2)
				$point['2d'] = $row['value'];
			else if ($days == 7)
				$point['7d'] = $row['value'];
			else if ($days == 14)
				$point['14d'] = $row['value'];
			else if ($days == 30)
				$point['30d'] = $row['value'];
		}

		$data0 = array();
		$data1 = array();
		$data2 = array();
		$data3 = array();
		$data4 = array();
		$data5 = array();
		$data6 = array();
		$data7 = array();
		$data8 = array();
		$data9 = array();

		reset($returned);
		while (list($date, $types) = each($returned))
		{
			reset($types);
			while (list($type, $values) = each($types))
			{
				$registered = &$values['registered'];

				$data0[] = array('date' => $date, 'type' => $type, 'value' => round(($values['1d'] * 100) / $registered, 2));
				$data1[] = array('date' => $date, 'type' => $type, 'value' => round(($values['2d'] * 100) / $registered, 2));
				$data2[] = array('date' => $date, 'type' => $type, 'value' => round(($values['7d'] * 100) / $registered, 2));
				$data3[] = array('date' => $date, 'type' => $type, 'value' => round(($values['14d'] * 100) / $registered, 2));
				$data4[] = array('date' => $date, 'type' => $type, 'value' => round(($values['30d'] * 100) / $registered, 2));
				$data5[] = array('date' => $date, 'type' => $type, 'value' => round(($values['1d+'] * 100) / $registered, 2));
				$data6[] = array('date' => $date, 'type' => $type, 'value' => round(($values['2d+'] * 100) / $registered, 2));
				$data7[] = array('date' => $date, 'type' => $type, 'value' => round(($values['7d+'] * 100) / $registered, 2));
				$data8[] = array('date' => $date, 'type' => $type, 'value' => round(($values['14d+'] * 100) / $registered, 2));
				$data9[] = array('date' => $date, 'type' => $type, 'value' => round(($values['30d+'] * 100) / $registered, 2));
			}
		}

		return array($data0, $data1, $data2, $data3, $data4, $data5, $data6, $data7, $data8, $data9);
	}

	private function hidden_paying_month_type($cache_date, $key)
	{
		$counts = array();
		$counts_all = array();

		$result = $this->DB->{"hidden_paying_month_".$key}($cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			if ($key == "age")
				$type = $this->get_age_index($type);
			if ($key == "device")
				$type = $this->get_provider_index($type);

			$index = $date."-".$type;

			if (!isset($counts[$index]))
				$counts[$index] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($counts_all[$date]))
				$counts_all[$date] = array('date' => $date, 'type' => 0, 'value' => 0);

			$counts[$index]['value'] += $row['value'];
			$counts_all[$date]['value'] += $row['value'];
		}

		$counts = array_values($counts);

		if ($key != "net")
			return $counts;

		$counts_all = array_values($counts_all);

		return array($counts_all, $counts);
	}

	/**
	 * Index map functions
	 */
	private function get_vip_rests_index($days)
	{
		if ($days > 500)
			return 7;
		if ($days >= 101)
			return 6;
		if ($days >= 51)
			return 5;
		if ($days >= 21)
			return 4;
		if ($days >= 11)
			return 3;
		if ($days >= 6)
			return 2;
		if ($days >= 3)
			return 1;

		return 0;
	}

	private function get_age_index($age)
	{
		if ($age >= 35)
			return 4;
		if ($age >= 18)
			return 3;
		if ($age >= 16)
			return 2;
		if ($age >= 14)
			return 1;
		if ($age > 0)
			return 0;
		return 99;
	}

	private function get_provider_index($provider)
	{
		if ($provider == 40)
			return 2;
		if ($provider == 41)
			return 3;

		return 0;
	}

	private function get_gift_index($gift)
	{
		if (isset($this->gifts[$gift]))
			return $this->gifts[$gift];
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

	private function get_active_index($data)
	{
		if ($data >= 61)
			return 8;
		if ($data >= 31)
			return 7;
		if ($data >= 11)
			return 6;
		if ($data >= 5)
			return 5;
		return $data;
	}

	private function get_loading_time_index($graph, $data)
	{
		if ($data > 61)
			return 9;
		if ($data > 31)
			return 8;
		if ($data > 21)
			return 7;
		if ($data > 11)
			return 6;
		if ($data > 5)
			return 5;
		if ($data > 1)
			return intval($data);
		if ($data == 1)
			return intval($graph);
		return 0;
	}

	private function get_paid_group_index($sum)
	{
		if ($sum >= 10000)
			return 10;
		if ($sum >= 5000)
			return 9;
		if ($sum >= 2000)
			return 8;
		if ($sum >= 1000)
			return 7;
		if ($sum >= 500)
			return 6;
		if ($sum >= 300)
			return 5;
		if ($sum >= 200)
			return 4;
		if ($sum >= 100)
			return 3;
		if ($sum >= 50)
			return 2;
		if ($sum >= 10)
			return 1;
		return 0;
	}

	private function get_players_paying_count_index($data)
	{
		if ($data >= 1000)
			return 12;
		if ($data >= 500)
			return 11;
		if ($data >= 100)
			return 10;
		if ($data >= 70)
			return 9;
		if ($data >= 40)
			return 8;
		if ($data >= 15)
			return 7;
		if ($data >= 10)
			return 6;
		if ($data >= 5)
			return 5;
		return $data;
	}

	private function events_collection_index($data)
	{
		$event = $data >> 16;
		$type = $data & 0xFFFF;

		return $event * 10 + $type;
	}

	private function get_payments_group_index($data)
	{
		if ($data > 1000)
			return 1000;
		if ($data > 500)
			return 500;
		if ($data > 100)
			return 100;
		if ($data > 70)
			return 70;
		if ($data > 20)
			return 20;
		return 0;
	}

	private function get_mafia_time_index($data)
	{
		if ($data >= 31)
			return 10;
		if ($data >= 26)
			return 9;
		if ($data >= 21)
			return 8;
		if ($data >= 16)
			return 7;
		if ($data >= 13)
			return 6;
		if ($data >= 9)
			return 5;
		if ($data >= 6)
			return 4;
		if ($data >= 3)
			return 3;
		return $data;
	}

	private function get_well_index($data)
	{
		if ($data <= 3)
			return $data;
		if ($data <= 7)
			return 7;
		if ($data <= 14)
			return 14;
		if ($data <= 21)
			return 21;
		if ($data <= 30)
			return 30;
		return 31;
	}

	/**
	 * Data map functions
	 */
	private function date_diff($date1, $date2)
	{
		$date1 = date_parse($date1);
		$date2 = date_parse($date2);

		$time1 = gmmktime(0, 0, 0, $date1['month'], $date1['day'], $date1['year']);
		$time2 = gmmktime(0, 0, 0, $date2['month'], $date2['day'], $date2['year']);

		return (($time1 - $time2) / 86400);
	}

	private function data_sum_one($result)
	{
		$data = array();
		foreach ($result as $row)
		{
			$data[] = array('date' => $row['date'], 'type' => 0, 'value' => $row['sum']);
			$data[] = array('date' => $row['date'], 'type' => 1, 'value' => $row['count']);
		}

		return $data;
	}

	private function data_sum_two($result)
	{
		$sum = array();
		$count = array();

		while ($row = $result->fetch())
		{
			$sum[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['sum']);
			$count[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	private function data_type($result, $types_map = false)
	{
		$data = array();

		while ($row = $result->fetch())
		{
			$type = $row['data'];

			if (isset($row['type']) && isset($types_map[$row['type']]))
				$type = $types_map[$row['type']];

			$data[] = array('date' => $row['date'], 'type' => $type, 'value' => $row['value']);
		}

		return $data;
	}

	private function sum_by_column($data, $group_by, $columns)
	{
		$result = array();

		foreach ($data as $values)
		{
			if (!isset($values[$group_by]))
				continue;

			$group_value = $values[$group_by];

			if (!isset($result[$group_value]))
				$result[$group_value] = array();

			$group = &$result[$group_value];
			$group[$group_by] = $group_value;

			foreach ($columns as $column)
			{
				if (!isset($values[$column]))
					continue;

				if (!isset($group[$column]))
					$group[$column] = 0;
				$group[$column] += $values[$column];
			}
		}

		return $result;
	}

	private function parse_content()
	{
		if (!empty($this->categories))
			return;

		$gifts = $this->Cache->get("gifts", self::CacheClass);
		if ($gifts !== false)
		{
			$this->categories = $gifts['categories'];
			$this->gifts = $gifts['gifts'];
			return;
		}

		$this->categories = array(0 => "Удалённые");
		$this->gifts = array();

		$xml = simplexml_load_file($this->content);

		foreach ($xml->gifts->category as $category)
		{
			if (!isset($category['id']))
				continue;

			$name = (string) $category['name'];
			if ($name == "Популярные")
				continue;

			$type = intval($category['id']);
			$this->categories[$type] = $name;

			foreach ($category->gift as $gift)
			{
				$id = (string) $gift['id'];
				$this->gifts[$id] = $type;
			}
		}

		$this->categories[-1] = "Статусные";
		$this->categories[31] = "Музыкальные";

		foreach ($xml->statusGifts->category as $category)
		{
			foreach ($category->gift as $gift)
			{
				$id = (string) $gift['id'];
				$this->gifts[$id] = -1;
			}
		}

		$this->categories[-2] = "Бомба";
		$this->gifts[self::BombId] = -2;

		$this->Cache->set("gifts", self::CacheClass, array('categories' => $this->categories, 'gifts' => $this->gifts));
	}
}

?>