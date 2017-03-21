<?php

require_once "legends_counters.inc.php";

/**
 * Реализует отчёты Войны легенд
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
class ObjectLegends extends Object implements DatabaseInterface
{
	/**
	 * Идентификатор проекта в системе аналитики
	 */
	private static $service_id;

	const OfferNone = 0;

	const ReferrersMobile	= "100001, 110001";
	const ReferrersMax	= 110001;

	const MobileProviders	= "40, 41";		// Apple, Google

	const RightsDeveloper	= 1;

	private $networks = array(0 => "ВКонтакте", 1 => "МойМир", 4 => "Одноклассники", 5 => "Facebook");
	private $devices = array(1 => "PC", 2 => "iOS", 3 => "Android");
	private $ages = array(0 => "1-13", 1 => "14-15", 2 => "16-17", 3 => "18-34", 4 => "35+", 99 => "Не задан");
	private $sex = array(2 => "Мужской", 1 => "Женский", 0 => "Не задан");
	private $periods = array(0 => "0-1d", 2 => "2-7d", 8 => "8-14d", 15 => "15-30d", 31 => "31d+");
	private $tags = array(0 => "Без тега");

	private $client_types = array(0 => "Web player", 1 => "WebGL", 2 =>"Неизвестно");
	private $levels = array(1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5", 6 => "6", 7 => "7", 8 => "8", 9 => "9", 10 => "10", 11 => "11", 12 => "12", 13 => "13", 14 => "14", 15 => "15", 16 => "16", 17 => "17", 18 => "18", 19 => "19", 20 => "20", 21 => "21", 22 => "22", 23 => "23", 24 => "24", 25 => "25", 26 => "26", 27 => "27", 28 => "28", 29 => "29", 30 => "30", 31 => "31", 32 => "32", 33 => "33", 34 => "34", 35 => "35", );
	private $goods = array(0 => "Апгрейд тотема", 1 => "Ускорение апгрейда тотема", 2 => "Мгновенный апгрейд тотема", 3 => "Параллельный апгрейд тотемов", 4 => "Заточка", 5 => "Бустеры", 6 => "Разблокировка локации", 7 => "Снаряжение", 8 => "Декорации", 9 => "Карта", 10 => "Набор карт", 11 => "VIP", 12 => "Слот для апгрейда карт", 13 => "Мгновенный апгрейд карты", 14 => "Энергия", 15 => "Монеты");
	private $locations = array(255 => "Тренировочный бой", 5 => "Перевал Балора", 10 => "Пирамида Даров", 15 => "Сторожевая Башня", 20 => "Ущелье Магов", 25 => "Хранители Памяти", 30 => "Колизей Боли", 35 => "Тенистое ущелье", 40 => "Пик Воинов", 45 => "Логово Сотни Чудищ", 50 => "Дьявольский Утес", 55 => "Забытая Скала", 60 => "Пристанище Духов", 1 => "Укрепление Севера", 6 => "Кузница Мастеров", 11 => "Город Героев", 16 => "Казармы Наместника", 21 => "Торговая площадь", 26 => "Святилище Богов", 31 => "Крепость Паладинов", 36 => "Форт Спасения", 41 => "Северная Столица", 46 => "Бесконечный Рудник", 51 => "Цитадель Хоархина", 56 => "Последний Бастион", 61 => "Торговая Бухта", 3 => "Некрополь Спящих", 8 => "Проклятая Деревня", 13 => "Ядовитая Пустошь", 18 => "Ведьмино Гнездо", 23 => "Кровавый замок", 28 => "Гибельные Земли", 33 => "Оружейная Нума", 38 => "Пламенный Зиккурат", 43 => "Пик Некроманта", 48 => "Дворец Боли", 53 => "Покои Ксаада", 58 => "Адская котельная", 63 => "Склеп Наместника", 2 => "Башня Лучников", 7 => "Школа магов", 12 => "Деревня кентавров", 17 => "Таверна 'У Элрога'", 22 => "Вековой Лес", 27 => "Тропа Духов", 32 => "Храм Аксис и Эрил", 37 => "Обсерватория", 42 => "Дом Целителей", 47 => "Склеп Падших", 52 => "Древо Индры", 57 => "Оракул Богов", 62 => "Источник силы", 4 => "Засада мародеров", 9 => "Забытый могильник", 14 => "Болотистый Грот", 19 => "Поселение кузнецов", 24 => "Сигнальная Башня", 29 => "Озерный Утес", 34 => "Кладбище Храбрецов", 39 => "Древние Руины", 44 => "Лагерь наемников", 49 => "Яма Пыток", 54 => "Пещера Дракона", 59 => "Залы Тхараха", 64 => "Замок Варона");
	private $quests = array(0 => "Выполнить ежедневные задания", 1 => "Повергни трех достойных противников из земель Северной федерации", 2 => "Повергни трех достойных противников из земель Зеленой кожи", 3 => "Повергни трех достойных противников во Владениях Смерти", 4 => "Повергни трех достойных противников из Рыжего леса", 5 => "Повергни трех достойных противников на Равнинах Свободных", 6 => "Накопи в боях 500 золота", 7 => "Накопи в боях 3000 золота", 8 => "Накопи в боях 8000 золота", 9 => "Накопи в боях 16000 золота", 10 => "Накопи в боях 28000 золота", 11 => "Накопи в боях 35000 золота", 12 => "Накопи в боях 50000 золота", 13 => "Убей 10 существ Северной федерации", 14 => "Убей 10 существ Общины", 15 => "Убей 10 существ Лесного братства", 16 => "Убей 10 существ Мертвого легиона", 17 => "Убей 15 существ Северной федерации", 18 => "Убей 15 существ Общины", 19 => "Убей 15 существ Лесного братства", 20 => "Убей 15 существ Мертвого легиона", 21 => "Убей 20 существ Северной федерации", 22 => "Убей 20 существ Общины", 23 => "Убей 20 существ Лесного братства", 24 => "Убей 20 существ Мертвого легиона", 25 => "Убей 10 существ Огненной стихии", 26 => "Убей 10 существ Воздушной стихии", 27 => "Убей 10 существ Водной стихии", 28 => "Убей 10 существ Земляной стихии", 29 => "Убей 15 существ Огненной стихии", 30 => "Убей 15 существ Воздушной стихии", 31 => "Убей 15 существ Водной стихии", 32 => "Убей 15 существ Земляной стихии", 33 => "Убей 20 существ Огненной стихии", 34 => "Убей 20 существ Воздушной стихии", 35 => "Убей 20 существ Водной стихии", 36 => "Убей 20 существ Земляной стихии", 37 => "Сразиться на 5 локациях", 38 => "Сразиться на 10 локациях", 39 => "Сразиться на 15 локациях", 40 => "Завербуй новое существо", 41 => "Завербуй новое могущественное существо", 42 => "Убей 10 разрушителей", 43 => "Убей 10 стражей", 44 => "Убей 10 существ поддержки", 45 => "Убей 5 летающих существ", 46 => "Убей 20 разрушителей", 47 => "Убей 20 стражей", 48 => "Убей 20 существ поддержки", 49 => "Убей 10 летающих существ", 50 => "Проведи 5 комбо-атак", 51 => "Проведи 7 комбо-атак", 52 => "Проведи 10 комбо атак", 53 => "Произнеси 10 заклинаний", 54 => "Произнеси 20 заклинаний", 55 => "Произнеси 30 заклинаний", 56 => "Сразись с другом-призывателем", 57 => "Принеси в жертву существо", 58 => "Отправить гонца с дарами", 59 => "Благословить разрушенную землю", 60 => "Сразись с пятью случайными противниками", 61 => "Посети башни двух друзей-призывателей", 62 => "Продвинься в своем могуществе", 63 => "Добудь 5 квестовых предметов", 64 => "Возвысься над людьми", 65 => "Изучи новое заклинание", 66 => "Обучи существо военной науке", 67 => "Превратить 5 существ в Пороховых Обезьян", 68 => "Создай противнику 5 неприятных ситуаций");
	private $cards = array(0 => "Копейщик (0)", 1 => "Пехотинец (1)", 2 => "Щитоносец (2)", 3 => "Бугай (3)", 4 => "Хоб-гоблин (4)", 5 => "Гремлин-мутант (5)", 6 => "Эльф-Следопыт (6)", 7 => "Эльф-страж (7)", 8 => "Огненный эльф (8)", 9 => "Скелет-мечник (9)", 10 => "Скелет в доспехах (10)", 11 => "Скелет орка-воина (11)", 12 => "Следопыт (12)", 13 => "Пращник (13)", 14 => "Наемный лучник (14)", 15 => "Гоблин-лучник (15)", 16 => "Рядовой орк (16)", 17 => "Ночной гоблин (17)", 18 => "Ученик рейнджера (18)", 19 => "Легкий лучник (19)", 20 => "Хранитель очага (20)", 21 => "Скелет с огн. Стрелами (21)", 22 => "Тролль-трупоед (22)", 23 => "Гончая ордена (23)", 24 => "Знахарь (24)", 25 => "Целитель (25)", 26 => "Ученик чародея (26)", 27 => "Гоблин-шаман (27)", 28 => "Снотлинг-техник (28)", 29 => "Гремлин-поджигатель (29)", 30 => "Дева леса (30)", 31 => "Наблюдатель (31)", 32 => "Маг пламени (32)", 33 => "Призыватель скелетов (33)", 34 => "Самосожженный монах (34)", 35 => "Ученик огненного культа (35)", 36 => "Матрос (36)", 37 => "Юнга (37)", 38 => "Контрабандист (38)", 39 => "Многорукий Наг (39)", 40 => "Анаконда (40)", 41 => "Змееглав (41)", 42 => "Эльф-Водонос (42)", 43 => "Защитник леса (43)", 44 => "Эльф щитник (44)", 45 => "Утопец (45)", 46 => "Рыбоящер (46)", 47 => "Саламандра (47)", 48 => "Пороховая улитка (48)", 49 => "Матрос с крюком (49)", 50 => "Хоббит (50)", 51 => "Лучник нага (51)", 52 => "Варан мечник (52)", 53 => "Шипоглав (53)", 54 => "Водяная фея (54)", 55 => "Морской эльф (55)", 56 => "Светлый эльф (56)", 57 => "Пират с затон. Корабля (57)", 58 => "Водяной-воин (58)", 59 => "Сколопендра (59)", 60 => "Шаман моря (60)", 61 => "Гарпунщик (61)", 62 => "Бравый капитан (62)", 63 => "Отшельник (63)", 64 => "Нага-монах (64)", 65 => "Вождь ящеров (65)", 66 => "Водный друид (66)", 67 => "Морской защитник (67)", 68 => "Маг дождя (68)", 69 => "Болотный лич (69)", 70 => "Водяной-шаман (70)", 71 => "Гниющий ящер (71)", 72 => "Фермер (72)", 73 => "Пахарь (73)", 74 => "Кожемяка (74)", 75 => "Рядовой Тролль (75)", 76 => "Пещерный Тролль (76)", 77 => "Гоблин-шипоголов (77)", 78 => "Молодой Кентавр (78)", 79 => "Зеленый энт (79)", 80 => "Кентавр в кожаных доспехах (80)", 81 => "Зомби (81)", 82 => "Мертвяк (82)", 83 => "Паук-Тенетник (83)", 84 => "Ополченец (84)", 85 => "Бродячий пес (85)", 86 => "Крестьянин (86)", 87 => "Гремлины (87)", 88 => "Гоблин-шахтер (88)", 89 => "Шипоголов воин (89)", 90 => "Кентавр-лучник (90)", 91 => "Шипастый энт (91)", 92 => "Кентавр-копейщик (92)", 93 => "Гуль (93)", 94 => "Паук-прыгун (94)", 95 => "Червь-кровосос (95)", 96 => "Агроном-генетик (96)", 97 => "Лесник (97)", 98 => "Деревенский лекарь (98)", 99 => "Тролль-шаман (99)", 100 => "Тролль-землекоп (100)", 101 => "Старший шипоголов (101)", 102 => "Кентавр-жрец (102)", 103 => "Мудрец-отшельник (103)", 104 => "Кентавр-знаменосец (104)", 105 => "Земляной лич (105)", 106 => "Паучье гнездо (106)", 107 => "Искатель могил (107)", 108 => "Бродяга (108)", 109 => "Щипач (109)", 110 => "Амбал (110)", 111 => "Громила (111)", 112 => "Хобгоблин-латник (112)", 113 => "Орк-защитник (113)", 114 => "Эльфы со щитом (114)", 115 => "Друид-ученик (115)", 116 => "Эльфийский тигр (116)", 117 => "Повешенный (117)", 118 => "Самоубийца (118)", 119 => "Падальщик (119)", 120 => "Карманник (120)", 121 => "Метатель ножей (121)", 122 => "Карлик (122)", 123 => "Орк-Берсерк (123)", 124 => "Орк-рубака (124)", 125 => "Орк-костолом (125)", 126 => "Патрульная сова (126)", 127 => "Друид-волк (127)", 128 => "Молодой Саблезуб (128)", 129 => "Ведьма на метле (129)", 130 => "Призрак двуглавого пса (130)", 131 => "Кикимора (131)", 132 => "Шарлатан (132)", 133 => "Шаман воздуха (133)", 134 => "Одноглазая знахарка (134)", 135 => "Пророк племени (135)", 136 => "Гоблин-алхимик (136)", 137 => "Орк-командир со знаменем (137)", 138 => "Заклинатель снов (138)", 139 => "Дух леса (139)", 140 => "Ведун (140)", 141 => "Ведьма (141)", 142 => "Мертвый гробовщик (142)", 143 => "Каннибал (143)", 144 => "Легионер огня (144)", 145 => "Мечник (145)", 146 => "Огр (146)", 147 => "Огр-мясник (147)", 148 => "Огненный эльф-рыцарь (148)", 149 => "Мастер-щитник (149)", 150 => "Восставший кентавр (150)", 151 => "Огненный палач (151)", 152 => "Огненный лучник (152)", 153 => "Пламенная пелуза (153)", 154 => "Танцующий с клинками (154)", 155 => "Орк-Дуболом (155)", 156 => "Опытный рейнджер (156)", 157 => "Пылающий клинок (157)", 158 => "Скелет с пращей (158)", 159 => "Скарабей-воин (159)", 160 => "Жрец пламени (160)", 161 => "Красный маг (161)", 162 => "Шаман среднего звена (162)", 163 => "Жабовидный горящий мутант (163)", 164 => "Огненная нимфа (164)", 165 => "Друид-чаровник (165)", 166 => "Некромант (166)", 167 => "Заклинатель скарабеев (167)", 168 => "Пират (168)", 169 => "Канонир (169)", 170 => "Двухвостый ллигатор (170)", 171 => "Ящер (171)", 172 => "Эльф-ратник (172)", 173 => "Страж побережья (173)", 174 => "Мертвый болотный бегемот (174)", 175 => "Ужасная минога (175)", 176 => "Боцман (176)", 177 => "Потерянный пират (177)", 178 => "Аллигатор-метатель молотов (178)", 179 => "Ящер-громила (179)", 180 => "Болотная нага (180)", 181 => "Морской охотник (181)", 182 => "Гниющий упырь (182)", 183 => "Минога-убийца (183)", 184 => "Сундук с сокровищами (184)", 185 => "Старпом капитана (185)", 186 => "Шаман-кайман (186)", 187 => "Древний ящер (187)", 188 => "Заклинательница богомолов (188)", 189 => "Мастер прилива (189)", 190 => "Ведьма с гнилого болота (190)", 191 => "Минога-мутант (191)", 192 => "Солдат дворцовой стражи (192)", 193 => "Оружейник (193)", 194 => "Гоблин на тролле (194)", 195 => "Каменный тролль (195)", 196 => "Единорог (196)", 197 => "Кентавр-латник (197)", 198 => "Обращенный вампиром (198)", 199 => "Падший эльф (199)", 200 => "Арбалетчик (200)", 201 => "Дружинник (201)", 202 => "Охотник за головами (202)", 203 => "Хобгоблины-пращники (203)", 204 => "Лучник на единороге (204)", 205 => "Кентавр-стражник (205)", 206 => "Опустошитель (206)", 207 => "Паучья королева (207)", 208 => "Жрец земли (208)", 209 => "Монах (209)", 210 => "Подземный тролль (210)", 211 => "Огр-Снабженец (211)", 212 => "Вождь единорогов (212)", 213 => "Старейшина кентавров (213)", 214 => "Тысячелетняя нимфа смерти (214)", 215 => "Заклинательница пауков (215)", 216 => "Цыган вор (216)", 217 => "Городской охранник (217)", 218 => "Рубака орк в доспехах (218)", 219 => "Безумный орк (219)", 220 => "Майский жук (220)", 221 => "Высокий энт (221)", 222 => "Мертвый дух (222)", 223 => "Тень падшего воина (223)", 224 => "Гном метатель копий (224)", 225 => "Городской палач (225)", 226 => "Минотавр-каменщик (226)", 227 => "Орк-рассекатель (227)", 228 => "Рой ос (228)", 229 => "Скрипящий энт (229)", 230 => "Дух убийцы (230)", 231 => "Объятия смерти (231)", 232 => "Слепая гадалка (232)", 233 => "Одноногая цыганка (233)", 234 => "Слепой кузнец (234)", 235 => "Орк-громобой (235)", 236 => "Заклинатель ос (236)", 237 => "Хранитель леса (237)", 238 => "Ловец душ (238)", 239 => "Носферату (239)", 240 => "Огненный Гвардеец (240)", 241 => "Легионер орков (241)", 242 => "Матерый эльф щитоносец (242)", 243 => "Драугр (243)", 244 => "Маг огня (244)", 245 => "Арбалетный расчет (245)", 246 => "Осадный лучник (246)", 247 => "Варвар-ревенант (247)", 248 => "Дух огня (248)", 249 => "Знаменосец-легионер (249)", 250 => "Ночная танцовщица (250)", 251 => "Мертвый знаменосец (251)", 252 => "Флибустьер (252)", 253 => "Королевский крокодил (253)", 254 => "Кентавр-водонос (254)", 255 => "Болотная змея (255)", 256 => "Пушкарь (256)", 257 => "Нага -копейщик (257)", 258 => "Сирена (258)", 259 => "Восставший хоббит (259)", 260 => "Квартермейстер (260)", 261 => "Нага с боевым рогом (261)", 262 => "Погонщик волн (262)", 263 => "Мертвая водяная фея (263)", 264 => "Рота гномов из гор (264)", 265 => "Оружейник Общины (265)", 266 => "Капитан гвардии Королевы фей (266)", 267 => "Оборотень (267)", 268 => "Первый сын (268)", 269 => "Кочевая артиллерия (269)", 270 => "Энт-кочевник (270)", 271 => "Суккуб (271)", 272 => "Горный старец (272)", 273 => "Верховный шаман (273)", 274 => "Повелитель энтов (274)", 275 => "Королева личей (275)", 276 => "Повелитель воров (276)", 277 => "Ветеран орк-рубака (277)", 278 => "Бронир. майский жук (278)", 279 => "Полутелесный дух (279)", 280 => "Командир белых полосок (280)", 281 => "Минотавр-Берсерк (281)", 282 => "Отряд эльфов с бумерангами (282)", 283 => "Полтергейст (283)", 284 => "Фея-бродяга (284)", 285 => "Гремлины-кузнецы (285)", 286 => "Хранитель тишины(друид ветра) (286)", 287 => "Дух мертвого арахнида (287)", 288 => "Наездник на орле (288)", 289 => "Нетопырь (289)", 290 => "Лесная фея (290)", 291 => "Парящие головы (291)", 292 => "Водный ящер (292)", 293 => "Всадник на летучей рыбе (293)", 294 => "Летающие рыбы (294)", 295 => "Кровосос (295)", 296 => "Каменный ворон (296)", 297 => "Гремлин в пушке (297)", 298 => "Пегасо-кентавр (298)", 299 => "Фестрал (299)", 300 => "Зефир (300)", 301 => "Виверна (301)", 302 => "Эльф на сове (302)", 303 => "Гарпия (303)", 304 => "Феникс (304)", 305 => "Василиск (305)", 306 => "Лесной дракончик (306)", 307 => "Костяной сокол (307)", 308 => "Летучий голландец (308)", 309 => "Водный дракончик (309)", 310 => "Радужная стрекоза (310)", 311 => "Восставший водный ящер (311)", 312 => "Королевский орел (312)", 313 => "Скальный нетопырь (313)", 314 => "Пегас (314)", 315 => "Вампир-хозяин (315)", 316 => "Летающий воин с трезубцем (316)", 317 => "Бронированная виверна (317)", 318 => "Гигантские бабочки (318)", 319 => "Дух дракона (319)", 320 => "Огненный дракон (320)", 321 => "Мантикора (321)", 322 => "Пылающий дракон (322)", 323 => "Костяной дракон (323)", 324 => "Морской ящер (324)", 325 => "Морской змей (325)", 326 => "Радужный дракон (326)", 327 => "Мертвый морской змей (327)", 328 => "Горгулья (328)", 329 => "Летучая полумышь - полудева (329)", 330 => "Перекати-поле энт (330)", 331 => "Вий (331)", 332 => "Жрица ночи (332)", 333 => "Старейшина виверн (333)", 334 => "Классический дракон (334)", 335 => "Призрак первого дракона (335)", 336 => "Капитан стражи (336)", 337 => "Генерал орков (337)", 338 => "Мастер-рейнджер (338)", 339 => "Легендарный драугр (339)", 340 => "Лучник на пегасе (340)", 341 => "Вождь минотавров (341)", 342 => "Легендарный кентавр-лучник (342)", 343 => "Верховный суккуб (343)", 344 => "Сер Флейм (344)", 345 => "Легендарный шаман (345)", 346 => "Королева сирен (346)", 347 => "Ведьма 1го круга (347)", 348 => "3х глазый каменный ворон (348)", 349 => "Летающий морской змей (349)", 350 => "Пернатый дракон (350)", 351 => "Дух ведьмы, пожирающей младенцев (351)", 352 => "Погонщик единорогов (352)", 353 => "Морской разведчик (353)", 354 => "Гоблин-дипломат (354)", 355 => "Допельгангер (355)", 356 => "Неудержимый гремлин (356)", 357 => "Эльфийский менестрель (357)");

	private $currency = " р.";

	private $referrers_vk = array(0 => "Прямые переходы [0]", 1 => "Рекламный блок в каталоге [catalog_ads][1]", 2 => "Популярное в каталоге [catalog_popular][2]", 3 => "Активность друзей [friends_feed][3]", 4 => "Со стены пользователя [wall_view, wall_view_inline][4]", 5 => "Из приложений группы [group][5]", 6 => "По приглашению [request][6]", 7 => "Быстрый поиск [quick_search][7]", 8 => "Мои приложения [user_apps][8]", 9 => "Из левого меню [menu][9]", 10 => "Из уведомления [notification][10]", 11 => "Уведомление в реальном времени [notification_realtime][11]", 12 => "Специальный рекламный блок в каталоге [app_suggestions][12]", 13 => "Рекомендуемые в каталоге [featured][13]", 14 => "Из статуса [profile_status][14]", 15 => "Кассовые приложения [top_grossing][15]", 16 => "По названию из приглашения [join_request][16]", 17 => "Приложения друзей [friends_apps][17]", 18 => "Подборки приложений [collections][18]", 22 => "Страница уведомлений passive_friend_invitation [notifications_page][22]");
	private $referrers_mm = array(10000 => "Прямые переходы [10000]", 10001 => "Лента активности - установка [stream.install][10001]", 10002 => "Лента активности - действия [stream.publish][10002]", 10003 => "По приглашению [invitation][10003]", 10004 => "Лучшие в каталоге [catalog][10004]", 10005 => "«Попробуйте» на странице приложения [suggests][10005]", 10006 => "«Попробуйте» из левого меню [left_menu_suggest][10006]", 10007 => "Новинки в каталоге [new apps][10007]", 10008 => "Из гостевой книги [guestbook][10008]", 10009 => "«Играть» Mail.Ru Агента [agent][10009]", 10010 => "Поиск [search][10010]", 10011 => "Левое меню [left_menu][10011]", 10012 => "Promo [promo][10012]", 10013 => "Mail.ru рекоммендует [mailru_featured][10013]", 10014 => "Виджет [widget][10014]", 10015 => "Установленные приложения [installed_apps][10015]", 10016 => "Баннер в каталоге [banner_catalog][10016]", 10017 => "Уведомление [notification][10017]", 10018 => "Приложения друга [friends_apps][10018]", 10019 => "Реклама [advertisement][10019]", 10020 => "Левое меню Promo [left_promo][10020]", 10021 => "Лента Promo [feed_promo][10021]", 10022 => "[request][10022]");
	private $referrers_ok = array(20000 => "Прямые переходы [20000]", 20001 => "Из каталога [catalog][20001]", 20002 => "Из баннера в каталоге [banner][20002]", 20003 => "По приглашению [friend_invitation][20003]", 20004 => "Лента активности [friend_feed][20004]", 20005 => "Из оповещения [friend_notification][20005]", 20006 => "Новые приложения [new_apps][20006]", 20007 => "Топ приложений [top_apps][20007]", 20008 => "Поиск [app_search_apps][20008]", 20009 => "Мои приложения [user_apps][20009]", 20010 => "Уведомление [app_notification][20010]", 20011 => "Приложения друга [friend_apps][20011]", 20012 => "Список приложений внизу [user_apps_bottom_app_main][20012]", 20013 => "Подсказки игрокам [friend_suggest][20013]", 20014 => "По пассивному приглашению [passive_friend_invitation][20014]");
	private $referrers_fb = array(30000 => "Прямые переходы [30000]", 30001 => "[aggregation][30001]", 30002 => "Центр приложений [appcenter][30002]", 30003 => "Центр приложений по инвайту [appcenter_request][30003]", 30004 => "Закладки в профиле, блок приложений [bookmark_apps][30004]", 30005 => "Избранное в профиле [bookmark_favorites][30005]", 30006 => "\"Показать еще\" в закладках [bookmark_seeall][30006]", 30007 => "Закладки в приложениях [canvasbookmark][30007]", 30008 => "\"Показать еще\" приложениях [canvasbookmark_more][30008]", 30009 => "Рекоммендованные в приложениях [canvasbookmark_recommended][30009]", 30010 => "Старая лента закладки [dashboard_bookmark][30010]", 30011 => "Старая лента топ приложений [dashboard_toplist][30011]", 30012 => "Диалог разрешений [dialog_permission][30012]", 30013 => "Предложенные приложения [ego][30013]", 30014 => "Лента [feed][30014]", 30015 => "[nf][30015]", 30016 => "Лента Достижение [feed_achievement][30016]", 30017 => "Лента Лучших результатов [feed_highscore][30017]", 30018 => "Лента Пост с музыкой [feed_music][30018]", 30019 => "Лента Остальное [feed_opengraph][30019]", 30020 => "Лента Победа над другим игроком [feed_passing][30020]", 30021 => "Лента Играют сейчас [feed_playing][30021]", 30022 => "Лента Видео пост [feed_video][30022]", 30023 => "Мои недавние игры [games_my_recent][30023]", 30024 => "Игры друзей [games_friends_apps][30024]", 30025 => "Диалог при наведении на приложение [hovercard][30025]", 30026 => "Из сообщения [message][30026]", 30027 => "[mf][30027]", 30028 => "Из уведомлений [notification][30014]", 30029 => "[other_multiline][30029]", 30030 => "[pymk][30030]", 30031 => "Последняя активность [recent_activity][30031]", 30032 => "Напоминания о частых приложениях [reminders][30032]", 30033 => "[request][30033]", 30034 => "Поиск [search][30034]", 30035 => "[ticker][30035]", 30036 => "История пользователя в приложении [timeline_og][30036]", 30037 => "История последних действий [timeline_news][30037]", 30038 => "История победа над игроком [timeline_passing][30038]", 30039 => "История недавние достижения [timeline_recent][30039]", 30040 => "Закладка в боковой панели [sidebar_bookmark][30040]", 30041 => "Рекоммендованные в боковой панели [sidebar_recommended][30041]");

	private $counters_dau = array('all' => LegendsCounters::DAU_ALL, 'net' => LegendsCounters::DAU_NET, 'device' => -1, 'age' => -1, 'sex' => LegendsCounters::DAU_SEX, 'tag' => -1, 'level' => LegendsCounters::DAU_LEVEL, 'paying' => LegendsCounters::DAU_PAYING, 'client_type' => LegendsCounters::DAU_CLIENT_TYPE);
	private $counters_wau = array('all' => LegendsCounters::WAU_ALL, 'net' => -1, 'device' => -1, 'age' => -1, 'sex' => -1, 'tag' => -1, 'level' => -1, 'paying' => -1);
	private $counters_mau = array('all' => LegendsCounters::MAU_ALL, 'net' => -1, 'device' => -1, 'age' => -1, 'sex' => -1, 'tag' => -1, 'level' => -1, 'paying' => -1);

	private $payments_specific = array(-21 => "21 Кристалл", -35 => "35 Кристаллов", -120 => "120 Кристаллов", -150 => "150 Кристаллов", -500 => "500 Кристаллов", -1500 => "1500 Кристаллов", -1800 => "1800 Кристаллов", 0 => "Другие");

	private $hours = array(0 => "Полночь", 1 => "01:00", 2 => "02:00", 3 => "03:00", 4 => "04:00", 5 => "05:00", 6 => "06:00", 7 => "07:00", 8 => "08:00", 9 => "09:00", 10 => "10:00", 11 => "11:00", 12 => "12:00", 13 => "13:00", 14 => "14:00", 15 => "15:00", 16 => "16:00", 17 => "17:00", 18 => "18:00", 19 => "19:00", 20 => "20:00", 21 => "21:00", 22 => "22:00", 23 => "23:00");

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
			'payments_all'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`",
			'payments_net'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_age'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_sex'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_tag'			=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_device_new'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pl.`referrer` IN(".self::ReferrersMobile.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",

			'payments_candles'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, HOUR(pm.`time`) as `hour` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= DATE_SUB(@s, INTERVAL 1 DAY) AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `hour`",
			'payments_weekly'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`",
			'payments_newbies'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` = DATE(pm.`time`) AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`",
			'payments_specific'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, pm.`amount` as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`offer` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `offer`, `date`, `amount`",

			'payments_hourly'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, HOUR(pm.`time`) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'payments_hourly_last'		=> "SELECT IF(`date` = @s, 0, IF(`date` = @s, 1, 2)) as `type`, `chart`, `type` as `hour`, `value` FROM `@pcache` WHERE `service` = ".self::$service_id." AND `report` = 'payments_hourly' AND `date` IN(@s, @s, @s)",

			'payments_first'		=> "SELECT DATE(p2.`time`) as `date`, p2.`provider_id`, SUM(p2.`amount`) as `amount`, SUM(p2.`revenue`) as `revenue`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `orders` WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `orders` p2 FORCE INDEX (`time`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` = p1.`time` INNER JOIN `players` pl ON pl.`type` = p2.`type` AND pl.`net_id` = p2.`net_id` WHERE p2.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, p2.`provider_id`",
			'payments_repeated'		=> "SELECT DATE(p2.`time`) as `date`, p2.`provider_id`, SUM(p2.`amount`) as `amount`, SUM(p2.`revenue`) as `revenue`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` WHERE (`net_id`, `type`) IN(SELECT `net_id`, `type` FROM `orders` WHERE `time` >= @s) GROUP BY `type`, `net_id`) p1 INNER JOIN `orders` p2 FORCE INDEX (`type`) ON p2.`net_id` = p1.`net_id` AND p2.`type` = p1.`type` AND p2.`time` != p1.`time` INNER JOIN `players` pl ON pl.`type` = p2.`type` AND pl.`net_id` = p2.`net_id` AND pl.`rights` & ".self::RightsDeveloper." = 0 WHERE p2.`time` >= @s GROUP BY `date`, p2.`provider_id`",

			'payments_day_first'		=> "SELECT pl.`register_time` as `date`, DATEDIFF(pm.`time`, pl.`register_time`) as `days`, COUNT(*) as `count` FROM (SELECT `type`, `net_id`, MIN(`time`) as `time` FROM `orders` GROUP BY `type`, `net_id`) pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `days` ORDER BY `date` ASC",
			'payments_day_next'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`amount`) as `amount`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `type`, pm.`net_id` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `net_id` ORDER BY `time` ASC",

			'finance_arpu_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arpu_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(*) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s GROUP BY `provider_id` AND pl.`rights` & ".self::RightsDeveloper." = 0, `date`, `data`",

			'finance_arppu_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",
			'finance_arppu_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `count`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `provider_id`, `date`, `data`",

			'finance_ltv_net'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `provider_id`, pm.`net_id`",
			'finance_ltv_age'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, IF(pl.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(pl.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_sex'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pl.`sex` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_tag'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pl.`tag` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` NOT IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",
			'finance_ltv_device'		=> "SELECT DATE(pm.`time`) as `date`, pm.`provider_id`, SUM(pm.`revenue`) as `revenue`, pm.`provider_id` as `data` FROM `orders` pm INNER JOIN `players` pl ON pl.`type` = pm.`type` AND pl.`net_id` = pm.`net_id` WHERE pl.`register_time` >= '2016-01-01' AND pm.`provider_id` IN(".self::MobileProviders.") AND pm.`time` >= @s AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, `provider_id`, pm.`net_id`, `data`",

			'buyings_gold'			=> "SELECT b.`time` as `date`, b.`good_id` as `data`, SUM(b.`gold`) as `value`, COUNT(*) as `count` FROM `buyings` b INNER JOIN `players` pl ON pl.`inner_id` = b.`owner_id` WHERE b.`time` >= @s AND b.`gold` > 0 AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, b.`good_id`",
			'buyings_souls'			=> "SELECT b.`time` as `date`, b.`good_id` as `data`, SUM(b.`souls`) as `value`, COUNT(*) as `count` FROM `buyings` b INNER JOIN `players` pl ON pl.`inner_id` = b.`owner_id` WHERE b.`time` >= @s AND b.`souls` > 0 AND pl.`rights` & ".self::RightsDeveloper." = 0 GROUP BY `date`, b.`good_id`",

			'counters_daily_get'		=> "SELECT `date`, `data`, `value`, `type` FROM `counters_daily` WHERE `type` = @i AND `date` >= @s",
			'counters_daily_load'		=> "SELECT `date`, `data`, `value`, `type` FROM `counters_daily` WHERE `type` IN(@l) AND `date` >= @s",

//			'counters_weekly_get'		=> "SELECT IF(`week` = 52, LAST_DAY(CONCAT_WS('-', `year`, 12, 1)), DATE_ADD(CONCAT_WS('-', `year`, 1, 7), INTERVAL `week` WEEK)) as `date`, `data`, `value` FROM `counters_weekly` WHERE `type` = @i AND IF(`week` = 52, LAST_DAY(CONCAT_WS('-', `year`, 12, 1)), DATE_ADD(CONCAT_WS('-', `year`, 1, 7), INTERVAL `week` WEEK)) >= @s",
//			'counters_monthly_get'		=> "SELECT LAST_DAY(CONCAT_WS('-', `year`, `month`, 1)) as `date`, `data`, `value` FROM `counters_monthly` WHERE `type` = @i AND LAST_DAY(CONCAT_WS('-', `year`, `month`, 1)) >= @s",

			'counters_online_all'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `max`, MIN(`value`) as `min` FROM `counters` WHERE `type` = 0 AND `time` >= @s GROUP BY `date`",
			'counters_online_net'		=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, `data` FROM `counters` WHERE `type` = 1 AND `time` >= @s GROUP BY `date`, `data`",
			'counters_online_playing'	=> "SELECT DATE(`time`) as `date`, MAX(`value`) as `value`, `data` FROM `counters` WHERE `type` = 2 AND `time` >= @s GROUP BY `date`, `data`",

			'players_new_all'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, 0 as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_net'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `type` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_age'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_sex'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `sex` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_tag'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `tag` as `data` FROM `players` WHERE `register_time` >= @s GROUP BY `date`, `data`",
			'players_new_device'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, IF(`referrer` = 100001, 2, 3) as `data` FROM `players` WHERE `register_time` >= @s AND `referrer` IN(".self::ReferrersMobile.") GROUP BY `date`, `data`",
			'players_new_referrer'		=> "SELECT `register_time` as `date`, COUNT(*) as `value`, `referrer` as `data` FROM `players` WHERE `register_time` >= @s AND `referrer` BETWEEN @i AND @i GROUP BY `date`, `data`",

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

//			'players_retention_1d_all'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` DAY) as `registered`, DATEDIFF(`date`, '1970-01-01') - `data` as `days`, `value` FROM `counters_daily` WHERE `type` = 42",
//			'players_retention_1d_net'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 43 AND `date` >= @s",
//			'players_retention_1d_age'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 45 AND `date` >= @s",
//			'players_retention_1d_sex'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 44 AND `date` >= @s",
//			'players_retention_1d_tag'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFFFFFF DAY) as `registered`, `data` >> 32 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFFFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 354 AND `date` >= @s",
//			'players_retention_1d_device'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFF DAY) as `registered`, `data` >> 16 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 236 AND `date` >= @s",
//			'players_retention_1d_paying'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` DAY) as `registered`, DATEDIFF(`date`, '1970-01-01') - `data` as `days`, `value` FROM `counters_daily` WHERE `type` = 356",
//			'players_retention_1d_referrer'	=> "SELECT `date`, DATE_ADD('1970-01-01', INTERVAL `data` & 0xFFFFFFFF DAY) as `registered`, `data` >> 32 as `data`, (DATEDIFF(`date`, '1970-01-01') - (`data` & 0xFFFFFFFF)) as `days`, `value` FROM `counters_daily` WHERE `type` = 240 AND `date` >= @s",

			'players_retention_all'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`",
			'players_retention_net'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `type` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_age'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, IF(`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(`bday`), `register_time`)) as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_sex'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `sex` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_tag'		=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `tag` as `data` FROM `players` WHERE `logout_time` != 0 GROUP BY `date`, `days`, `data`",
			'players_retention_device'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, IF(`referrer` = 100001, 2, 3) as `data` FROM `players` WHERE `logout_time` != 0 AND `referrer` IN(".self::ReferrersMobile.") GROUP BY `date`, `days`, `data`",
			'players_retention_paying'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(DISTINCT pl.`inner_id`) as `value` FROM `players` pl INNER JOIN `orders` pm ON pm.`type` = pl.`type` AND pm.`net_id` = pl.`net_id` WHERE pl.`logout_time` != 0 AND pl.`register_time` >= '2016-01-01' GROUP BY `date`, `days`",
			'players_retention_referrer'	=> "SELECT `register_time` as `date`, DATEDIFF(FROM_UNIXTIME(`logout_time`), `register_time`) as `days`, COUNT(*) as `value`, `referrer` as `data` FROM `players` WHERE `logout_time` != 0 AND `referrer` BETWEEN @i AND @i GROUP BY `date`, `days`, `data`",

			'hidden_paying_month_net'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') AND pm.`provider_id` NOT IN(".self::MobileProviders.") GROUP BY `date`, `data`",
			'hidden_paying_month_age'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, IF(p.`bday` = 0, -1, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(p.`bday`), pm.`time`)) as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_sex'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, p.`sex` as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_tag'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, p.`tag` as `data` FROM `orders` pm INNER JOIN `players` p ON p.`net_id` = pm.`net_id` AND p.`type` = pm.`type` WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') GROUP BY `date`, `data`",
			'hidden_paying_month_device'	=> "SELECT LAST_DAY(pm.`time`) as `date`, COUNT(DISTINCT pm.`net_id`, pm.`type`) as `value`, pm.`provider_id` as `data` FROM `orders` pm WHERE pm.`time` >= DATE_FORMAT(@s, '%Y-%m-01') AND pm.`provider_id` IN(".self::MobileProviders.") GROUP BY `date`, `data`",

			'replace_cache'			=> "REPLACE INTO `@pcache` VALUES @t"
		);
	}

	public function get_jobs()
	{
		return array();
	}

	public function get_categories()
	{
		return array(
			'payments'	=> "Платежи",
			'finance'	=> "Финансы",
			'buyings'	=> "Покупки",
			'counters'	=> "Счётчики",
			'players'	=> "Игроки",
			'hidden'	=> "Скрытая категория"
		);
	}

	public function get_reports()
	{
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
			),
			'buyings' => array(
				'gold' => array(
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
						'show_sumline'	=> true,
						'sort_graphs'	=> "asc"
					)
				),
				'souls' => array(
					'id'		=> $id++,
					'title'		=> "За кристаллы",
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
					),
					'params'	=> array(
						'show_sumline'	=> true,
						'sort_graphs'	=> "asc"
					)
				),
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
							'title'		=> "В игре",
							'legend'	=> array(0 => "Игроки")
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
							'legend'	=> array(0 => "Игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "По уровню",
							'legend'	=> $this->levels
						),
						array(
							'title'		=> "По типу клиента",
							'legend'	=> $this->client_types
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
							'legend'	=> array(0 => "Платящие игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "По уровню",
							'legend'	=> $this->levels
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
							'title'		=> "Платящие",
							'legend'	=> array(0 => "Платящие игроки")
						),
						array(
							'title'		=> "По сетям",
							'legend'	=> $this->networks
						),
						array(
							'title'		=> "По полу",
							'legend'	=> $this->sex
						),
						array(
							'title'		=> "По уровню",
							'legend'	=> $this->levels
						)
					),
					'params'	=> array(
						'show_sums'	=> false,
						'indicator'	=> array('type' => "fixed")
					)
				),
/*
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
*/
				"-",
				'gold' => array(
					'id'		=> $id++,
					'title'		=> "Баланс золота",
					'description'	=> "Количество полученных и потраченных игроками золотых монет",
					'graphs'	=> array(
						array(
							'title'		=> "Общее количество",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				'souls' => array(
					'id'		=> $id++,
					'title'		=> "Баланс кристаллов",
					'description'	=> "Количество полученных и потраченных игроками кристаллов",
					'graphs'	=> array(
						array(
							'title'		=> "Общее количество",
							'legend'	=> array(1 => "Полученные", 0 => "Потраченные", 2 => "Итог"),
							'negative'	=> array(1 => false, 0 => true, 2 => false)
						)
					)
				),
				"-",
				'active' => array(
					'id'		=> $id++,
					'title'		=> "Активность",
					'description'	=> "Общее время всех игр; общее количество всех ходов",
					'graphs'	=> array(
						array(
							'title'		=> "Время всех игр",
							'legend'	=> array(0 => "Общее время")
						),
						array(
							'title'		=> "Количество ходов",
							'legend'	=> array(0 => "Общее количество")
						)
					)
				),
				'active_locations' => array(
					'id'		=> $id++,
					'title'		=> "Активность по локациям",
					'description'	=> "Количество начатых и завершенных игр по локациям",
					'graphs'	=> array(
						array(
							'title'		=> "Количество pvp-игр",
							'legend'	=> array(0 => "Количество")
						),
						array(
							'title'		=> "Количество начатых игр по локациям",
							'legend'	=> $this->locations
						),
						array(
							'title'		=> "Количество завершенных игр по локациям",
							'legend'	=> $this->locations
						)
					)
				),
				'active_other' => array(
					'id'		=> $id++,
					'title'		=> "Прочая активность",
					'description'	=> "Количество выполненных ежедневных квестов и количество сообщений в чате",
					'graphs'	=> array(
						array(
							'title'		=> "Количество выполненных ежедневных квестов",
							'legend'	=> $this->quests
						),
						array(
							'title'		=> "Количество сообщений в чате",
							'legend'	=> array(0 => "Количество")
						)
					)
				),
				'recent_notification' => array(
					'id'		=> $id++,
					'title'		=> "Заходы по уведомлениям",
					'description'	=> "Количество заходов в игру в течение 30 минут после уведомления",
					'graphs'	=> array(
						array(
							'title'		=> "Общее",
							'legend'	=> array(0 => "Количество")
						)
					)
				),
				'card_drop' => array(
					'id'		=> $id++,
					'title'		=> "Выпадение карт",
					'description'	=> "Количество полученных игроками карт по типам карт и по локациям",
					'graphs'	=> array(
						array(
							'title'		=> "По типам карт",
							'legend'	=> $this->cards
						),
						array(
							'title'		=> "По локациям",
							'legend'	=> $this->locations
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
				"-",
				'retention' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения",
					'description'	=> "Возвращение игроков через N дней",
					'graphs'	=> array(
						array(
							'title'		=> "%",
							'legend'	=> array(/*0 => "1d", 1 => "2d", 2 => "7d",*/ 3 => "1d+", 4 => "2d+", 5 => "7d+", 6 => "30d+")
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
							'legend'	=> array(/*0 => "1d", 1 => "2d", 2 => "7d",*/ 3 => "1d+", 4 => "2d+", 5 => "7d+", 6 => "30d+")
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
/*
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
*/
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
				'retention_age' => array(
					'id'		=> $id++,
					'title'		=> "Возвращения по возрасту",
					'description'	=> "Возвращение игроков через N дней по возрасту",
					'graphs'	=> array(
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
/*
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
*/
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
*/
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
	 * Возвращает сумму в рублях, которую мы получим от соц. сети
	 * $revenue в валюте системы
	 * Курсы бутылок:
	 * ВК: 1 голос = 3 рубля (-45%-18%, примерно)
	 * ММ: 1 мэйлик = 0.42 рубля (-50%-18%)
	 * ОК: 1 ОК = 0.42 рубля (-50%-18%)
	 * ФБ: 1 рубль = 0.7 рублей (-30%)
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
			case 40:
				return round($revenue * 0.7, 2);
			case 41:
				return round($revenue * 0.7, 2);
		}
		return 0;
	}

	/**
	 * Возвращает сумму в рублях, потраченную пользователем
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

	/**
	 * Покупки
	 */
	public function buyings_gold($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_gold($cache_date);
		while ($row = $result->fetch())
		{
			$sum[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
			$count[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	public function buyings_souls($cache_date)
	{
		$sum = array();
		$count = array();

		$result = $this->DB->buyings_souls($cache_date);
		while ($row = $result->fetch())
		{
			$sum[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['value']);
			$count[] = array('date' => $row['date'], 'type' => $row['data'], 'value' => $row['count']);
		}

		return array($sum, $count);
	}

	/**
	 * Счётчики
	 */
	public function counters_dau($cache_date)
	{
		$result = $this->DB->counters_daily_get($this->counters_dau['all'], $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_dau['paying'], $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_dau['net'], $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_dau['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_dau['level'], $cache_date);
		$level = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_dau['client_type'], $cache_date);
		$client_type = $this->data_type($result);

		return array($all, $paying, $net, $sex, $level, $client_type);
	}

	public function counters_wau($cache_date)
	{
		$result = $this->DB->counters_daily_get($this->counters_wau['all'], $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_wau['paying'], $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_wau['net'], $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_wau['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_wau['level'], $cache_date);
		$level = $this->data_type($result);

		return array($all, $paying, $net, $sex, $level);
	}

	public function counters_mau($cache_date)
	{
		$result = $this->DB->counters_daily_get($this->counters_mau['all'], $cache_date);
		$all = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_mau['paying'], $cache_date);
		$paying = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_mau['net'], $cache_date);
		$net = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_mau['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_daily_get($this->counters_mau['level'], $cache_date);
		$level = $this->data_type($result);

		return array($all, $paying, $net, $sex, $level);
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

		$result = $this->DB->counters_online_playing($cache_date);
		$playing = $this->data_type($result);

		return array($all, $net, $playing);
	}

/*
	public function counters_mau_percent($cache_date)
	{
		$all = $this->counters_mau_percent_type($cache_date, "all");
		$net = $this->counters_mau_percent_type($cache_date, "net");
		$device = $this->counters_mau_percent_type($cache_date, "device");
		$age = $this->counters_mau_percent_type($cache_date, "age");
		$sex = $this->counters_mau_percent_type($cache_date, "sex");

		return array($all, $net, $device, $age, $sex);
	}
*/

	public function counters_souls($cache_date)
	{
		$souls = array();

		$result = $this->DB->counters_daily_get(LegendsCounters::SOULS_FLOW, $cache_date);
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

			if (!isset($souls[$date."_".$type]))
				$souls[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($souls[$date."_2"]))
				$souls[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$souls[$date."_".$type]['value'] += $value;
			$souls[$date."_2"]['value'] += $value;
		}

		$souls = array_values($souls);

		return array($souls);
	}

	public function counters_gold($cache_date)
	{
		$gold = array();

		$result = $this->DB->counters_daily_get(LegendsCounters::COINS_FLOW, $cache_date);
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

			if (!isset($gold[$date."_".$type]))
				$gold[$date."_".$type] = array('date' => $date, 'type' => $type, 'value' => 0);
			if (!isset($gold[$date."_2"]))
				$gold[$date."_2"] = array('date' => $date, 'type' => 2, 'value' => 0);

			$gold[$date."_".$type]['value'] += $value;
			$gold[$date."_2"]['value'] += $value;
		}

		$gold = array_values($gold);

		return array($gold);
	}

	public function counters_active($cache_date)
	{
		$result = $this->DB->counters_daily_get(LegendsCounters::BATTLE_TIME, $cache_date);
		$time = $this->data_type($result);

		$result = $this->DB->counters_daily_get(LegendsCounters::BATTLE_MOVES, $cache_date);
		$moves = $this->data_type($result);

		return array($time, $moves);
	}

	public function counters_active_locations($cache_date)
	{
		$result = $this->DB->counters_daily_get(LegendsCounters::PVP, $cache_date);
		$pvp = $this->data_type($result);

		$result = $this->DB->counters_daily_get(LegendsCounters::LOCATION_START, $cache_date);
		$location_start = $this->data_type($result);

		$result = $this->DB->counters_daily_get(LegendsCounters::LOCATION_FINISH, $cache_date);
		$location_finish = $this->data_type($result);

		return array($pvp, $location_start, $location_finish);
	}

	public function counters_active_other($cache_date)
	{
		$result = $this->DB->counters_daily_get(LegendsCounters::QUEST_FINISH, $cache_date);
		$quest_finish = $this->data_type($result);

		$result = $this->DB->counters_daily_get(LegendsCounters::CHAT, $cache_date);
		$chat = $this->data_type($result);

		return array($quest_finish, $chat);
	}

	public function counters_recent_notification($cache_date)
	{
		$result = $this->DB->counters_daily_get(LegendsCounters::RECENT_NOTIFICATION, $cache_date);
		$all = $this->data_type($result);

		return array($all);
	}

	public function counters_card_drop($cache_date)
	{
		$result = $this->DB->counters_daily_get(LegendsCounters::CARD_DROP, $cache_date);
		$cards = $this->data_type($result);

		$result = $this->DB->counters_daily_get(LegendsCounters::LOCATION_CARD_DROP, $cache_date);
		$locations = $this->data_type($result);

		return array($cards, $locations);
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
		$result = $this->DB->players_new_referrer($cache_date, 0, 9999);
		$vk = $this->data_type($result);

		$result = $this->DB->players_new_referrer($cache_date, 10000, 19999);
		$mm = $this->data_type($result);

		$result = $this->DB->players_new_referrer($cache_date, 20000, 29999);
		$ok = $this->data_type($result);

		$result = $this->DB->players_new_referrer($cache_date, 30000, 39999);
		$fb = $this->data_type($result);

		return array($vk, $ok, $mm, $fb);
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
				$returned[$date] = array('registered' => 0, '1d' => 0, '2d' => 0, '7d' => 0, '1d+' => 0, '2d+' => 0, '7d+' => 0, '30d+' => 0);
			$point = &$returned[$date];

			$point['registered'] += $value;

			if ($days == 0)
				continue;
			if ($days >= 1)
				$point['1d+'] += $value;
			if ($days >= 2)
				$point['2d+'] += $value;
			if ($days >= 7)
				$point['7d+'] += $value;
			if ($days >= 30)
				$point['30d+'] += $value;
		}

/*
		$result = $this->DB->players_retention_1d_all();
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
			else if ($days == 2)
				$point['2d'] = $value;
			else if ($days == 7)
				$point['7d'] = $value;
		}
*/

		$data = array();
		while (list($date, $values) = each($returned))
		{
			$registered = &$values['registered'];

//			$data[] = array('date' => $date, 'type' => 0, 'value' => round(($values['1d'] * 100) / $registered, 2));
//			$data[] = array('date' => $date, 'type' => 1, 'value' => round(($values['2d'] * 100) / $registered, 2));
//			$data[] = array('date' => $date, 'type' => 2, 'value' => round(($values['7d'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 3, 'value' => round(($values['1d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 4, 'value' => round(($values['2d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 5, 'value' => round(($values['7d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 6, 'value' => round(($values['30d+'] * 100) / $registered, 2));
		}

		return array($data);
	}

	public function players_retention_paying($cache_date)
	{
		$returned = array();

		$result = $this->DB->players_retention_paying();
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$value = $row['value'];
			$days = $row['days'];

			if (!isset($returned[$date]))
				$returned[$date] = array('registered' => 0, '1d' => 0, '2d' => 0, '7d' => 0, '1d+' => 0, '2d+' => 0, '7d+' => 0, '30d+' => 0);
			$point = &$returned[$date];

			$point['registered'] += $value;

			if ($days == 0)
				continue;
			if ($days >= 1)
				$point['1d+'] += $value;
			if ($days >= 2)
				$point['2d+'] += $value;
			if ($days >= 7)
				$point['7d+'] += $value;
			if ($days >= 30)
				$point['30d+'] += $value;
		}

/*
		$result = $this->DB->players_retention_1d_paying();
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
			else if ($days == 2)
				$point['2d'] = $value;
			else if ($days == 7)
				$point['7d'] = $value;
		}
*/

		$data = array();
		while (list($date, $values) = each($returned))
		{
			$registered = &$values['registered'];

//			$data[] = array('date' => $date, 'type' => 0, 'value' => round(($values['1d'] * 100) / $registered, 2));
//			$data[] = array('date' => $date, 'type' => 1, 'value' => round(($values['2d'] * 100) / $registered, 2));
//			$data[] = array('date' => $date, 'type' => 2, 'value' => round(($values['7d'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 3, 'value' => round(($values['1d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 4, 'value' => round(($values['2d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 5, 'value' => round(($values['7d+'] * 100) / $registered, 2));
			$data[] = array('date' => $date, 'type' => 6, 'value' => round(($values['30d+'] * 100) / $registered, 2));
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

/*
	public function players_life_time($cache_date)
	{
		list($all, $net) = $this->players_life_time_type($cache_date, "net");
		$age = $this->players_life_time_type($cache_date, "age");
		$device = $this->players_life_time_type($cache_date, "device");
		$sex = $this->players_life_time_type($cache_date, "sex");
		$tag = $this->players_life_time_type($cache_date, "tag");

		return array($all, $net, $age, $device, $sex, $tag);
	}
*/

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

		$result = $this->DB->counters_monthly_get($this->counters_mau['net'], $cache_date);
		while ($row = $result->fetch())
		{
			$date = $row['date'];
			$type = $row['data'];

			$net[] = array('date' => $date, 'type' => $type, 'value' => $row['value']);

			if (!isset($all[$date]))
				$all[$date] = array('date' => $date, 'type' => 0, 'value' => 0);
			$all[$date]['value'] += $row['value'];
		}

		$result = $this->DB->counters_monthly_get($this->counters_mau['device'], $cache_date);
		$device = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau['age'], $cache_date);
		$age = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau['sex'], $cache_date);
		$sex = $this->data_type($result);

		$result = $this->DB->counters_monthly_get($this->counters_mau['tag'], $cache_date);
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
	 * Helper functions
	 */
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

	private function finance_offers_data($cache_date, $legend)
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

		$result = $this->DB->counters_daily_get($this->counters_dau[$key], $cache_date);
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

		$result = $this->DB->counters_daily_get($this->counters_dau['all'], $cache_date);
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

		$result = $this->DB->counters_daily_get($this->counters_dau[$key], $cache_date);
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

/*
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
*/

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

		$result = $this->DB->counters_daily_get($this->counters_dau[$key], $cache_date);
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

		$result = $this->DB->counters_daily_get($this->counters_dau['all'], $cache_date);
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
				$returned[$date][$type] = array('registered' => 0, '1d' => 0, '2d' => 0, '7d' => 0, '1d+' => 0, '2d+' => 0, '7d+' => 0, '30d+' => 0);
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
			if ($days >= 30)
				$point['30d+'] += $value;
		}

/*
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
		}
*/

		$data0 = array();
		$data1 = array();
		$data2 = array();
		$data3 = array();
		$data4 = array();
		$data5 = array();
		$data6 = array();

		reset($returned);
		while (list($date, $types) = each($returned))
		{
			reset($types);
			while (list($type, $values) = each($types))
			{
				$registered = &$values['registered'];

//				$data0[] = array('date' => $date, 'type' => $type, 'value' => round(($values['1d'] * 100) / $registered, 2));
//				$data1[] = array('date' => $date, 'type' => $type, 'value' => round(($values['2d'] * 100) / $registered, 2));
//				$data2[] = array('date' => $date, 'type' => $type, 'value' => round(($values['7d'] * 100) / $registered, 2));
				$data3[] = array('date' => $date, 'type' => $type, 'value' => round(($values['1d+'] * 100) / $registered, 2));
				$data4[] = array('date' => $date, 'type' => $type, 'value' => round(($values['2d+'] * 100) / $registered, 2));
				$data5[] = array('date' => $date, 'type' => $type, 'value' => round(($values['7d+'] * 100) / $registered, 2));
				$data6[] = array('date' => $date, 'type' => $type, 'value' => round(($values['30d+'] * 100) / $registered, 2));
			}
		}

		return array(/*$data0, $data1, $data2,*/ $data3, $data4, $data5, $data6);
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
}

?>
