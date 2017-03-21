<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Скрипт для парсинга агентов
 */

	define('RED_COLOR',		"\033[0;31m");
	define('ORANGE_COLOR',		"\033[0;33m");
	define('NORMAL_COLOR',		"\033[0m");

	define('ERROR_TEXT',		RED_COLOR."Error: ".NORMAL_COLOR);
	define('WARNING_TEXT',		ORANGE_COLOR."Warning: ".NORMAL_COLOR);

	require_once "config.inc.php";

	$tokens = array(
		// Known browsers
		'Mozilla'		=> array(),
		'Mozzila'		=> array(),					// Fly smartphones
		'Opera'			=> array(),					// Opera 12- (Presto-based engine)
		'OPR'			=> array(),					// Opera 15+ (Blink-based engine)
		'Firefox'		=> array(),					// https://ru.wikipedia.org/wiki/Mozilla_Firefox
		'Shiretoko'		=> array(),					// https://wiki.mozilla.org/Firefox3.5
		'Namoroka'		=> array(),					// https://wiki.mozilla.org/Firefox/Namoroka
		'Minefield'		=> array(),					// Кодовое имя тестируемых пре-альфа версий Mozilla Firefox с версии 3.0 до версии 4.2.
		'Chrome'		=> array(),					// https://ru.wikipedia.org/wiki/Google_Chrome
		'Chromium'		=> array(),					// https://ru.wikipedia.org/wiki/Chromium
		'Safari'		=> array(),					// https://ru.wikipedia.org/wiki/Safari
		'YaBrowser'		=> array(),					// https://ru.wikipedia.org/wiki/%DF%ED%E4%E5%EA%F1.%C1%F0%E0%F3%E7%E5%F0
		'CoolNovo'		=> array(),					// https://ru.wikipedia.org/wiki/CoolNovo
		'Iron'			=> array(),					// https://ru.wikipedia.org/wiki/SRWare_Iron
		'Nichrome'		=> array(),					// http://soft.rambler.ru/browser/
		'PlayFreeBrowser'	=> array(),					// http://www.playfree.org
		'SeaMonkey'		=> array(),					// https://ru.wikipedia.org/wiki/SeaMonkey
		'Comodo_Dragon'		=> array(),					// https://ru.wikipedia.org/wiki/Comodo_Dragon
		'Dragon'		=> array(),					// --//--
		'Maxthon'		=> array(),					// https://ru.wikipedia.org/wiki/Maxthon
		'Byffox'		=> array(),					// http://byffox.sourceforge.net
		'CometBird'		=> array(),					// http://www.cometbird.com/
		'Puffin'		=> array(),					// http://www.puffinbrowser.com
		'UCBrowser'		=> array(),					// https://ru.wikipedia.org/wiki/UC_Browser
		'UCWEB'			=> array(),					// --//--
		'IceDragon'		=> array(),					// https://en.wikipedia.org/wiki/Comodo_IceDragon
		'PaleMoon'		=> array(),					// http://www.palemoon.org
		'Superbird'		=> array(),					// http://superbird-browser.com
		'Dolfin'		=> array(),					// http://dolphin.com
		'Waterfox'		=> array(),					// https://ru.wikipedia.org/wiki/Waterfox
		'Iceweasel'		=> array(),					// https://ru.wikipedia.org/wiki/Iceweasel
		'Orca'			=> array(),					// https://ru.wikipedia.org/wiki/Orca_Browser
		'S40OviBrowser'		=> array(),					// https://en.wikipedia.org/wiki/Nokia_Xpress
		'NokiaBrowser'		=> array(),					// https://en.wikipedia.org/wiki/Nokia_Browser_for_Symbian
		'BrowserNG'		=> array(),					// https://ru.wikipedia.org/wiki/Nokia_E72
		'Photon'		=> array(),					// http://www.appsverse.com
		'Fennec'		=> array(),					// https://ru.wikipedia.org/wiki/%D0%9C%D0%BE%D0%B1%D0%B8%D0%BB%D1%8C%D0%BD%D1%8B%D0%B9_Firefox
		'Beamrise'		=> array(),					// http://www.beamrise.com
		'Neuron'		=> array(),					// http://neuronbrowser.net
		'Coast'			=> array(),					// https://ru.wikipedia.org/wiki/Coast
		'Cyberfox'		=> array(),					// https://8pecxstudios.com/cyberfox-web-browser
		'RockMelt'		=> array(),					// https://ru.wikipedia.org/wiki/RockMelt
		'Zvu'			=> array(),					// http://zvu.com
		'Wyzo'			=> array(),					// https://en.wikipedia.org/wiki/Wyzo
		'Sleipnir'		=> array(),					// https://ru.wikipedia.org/wiki/Sleipnir
		'SlimBoat'		=> array(),					// http://www.slimboat.com
		'Arora'			=> array(),					// https://ru.wikipedia.org/wiki/Arora
		'Spark'			=> array(),					// http://en.browser.baidu.com
		'BDSpark'		=> array(),					// --//--
		'FlyFlow'		=> array(),					// --//--
		'Alienforce3.0'		=> array(),					// http://sourceforge.net/projects/alienforce/
		'YRCWeblink'		=> array(),					// http://yrcweblink.en.softonic.com
		'Awesomium'		=> array(),					// http://www.awesomium.com
		'ChromePlus'		=> array(),					// https://ru.wikipedia.org/wiki/CoolNovo
		'CoolNovoChromePlus'	=> array(),					// --//--
		'K-Meleon'		=> array(),					// https://ru.wikipedia.org/wiki/K-Meleon
		'MxBrowser'		=> array(),					// https://ru.wikipedia.org/wiki/Maxthon
		'MxNitro'		=> array(),					// http://usa.maxthon.com/nitro/
		'coc_coc_browser'	=> array(),					// https://en.wikipedia.org/wiki/C%E1%BB%91c_C%E1%BB%91c
		'CoRom'			=> array(),					// --//--
		'AvantBrowser'		=> array(),					// https://ru.wikipedia.org/wiki/Avant_Browser
		'BlackHawk'		=> array(),					// http://www.netgate.sk/blackhawk/
		'Konqueror'		=> array(),					// https://ru.wikipedia.org/wiki/Konqueror
		'Mercury'		=> array(),					// https://mercury-browser.com
		'Midori'		=> array(),					// https://ru.wikipedia.org/wiki/Midori_(%D0%B1%D1%80%D0%B0%D1%83%D0%B7%D0%B5%D1%80)
		'QupZilla'		=> array(),					// https://ru.wikipedia.org/wiki/QupZilla
		'QQBrowser'		=> array(),					// http://mb.qq.com
		'Bluebird'		=> array(),					// http://sourceforge.net/projects/bbwebbrowser/
		'DT-Browser'		=> array(),					// https://www.npmjs.com/package/dt-browser
		'NetFront'		=> array(),					// https://ru.wikipedia.org/wiki/NetFront
		'SuperBird'		=> array(),					// http://superbird-browser.com
		'Coowon'		=> array(),					// http://coowon.com
		'TeslaBrowser'		=> array(),					// http://www.teslabrowser.com
		'rekonq'		=> array(),					// https://ru.wikipedia.org/wiki/Rekonq
		'Diglo'			=> array(),					// http://www.diglo.com
		'Lunascape'		=> array(),					// https://ru.wikipedia.org/wiki/Lunascape
		'Aviator'		=> array(),					// https://www.whitehatsec.com/aviator/
		'Kylo'			=> array(),					// http://en.wikipedia.org/wiki/Kylo_(web_browser)
		'Flock'			=> array(),					// https://ru.wikipedia.org/wiki/Flock
		'Epiphany'		=> array(),					// https://ru.wikipedia.org/wiki/Web_(GNOME)
		'Skyfire'		=> array(),					// https://ru.wikipedia.org/wiki/Skyfire
		'MRCHROME'		=> array(),					// http://internet.mail.ru
		'InternetSurfboard'	=> array(),					// http://inetsurfboard.sourceforge.net
		'Viera'			=> array(),					// https://ru.wikipedia.org/wiki/Viera
		'UBrowser'		=> array(),					// http://callumprentice.github.io/other/ubrowser.com/
		'Amigo'			=> array(),					// http://s1.amigo.mail.ru
		'OneBrowser'		=> array(),					// https://play.google.com/store/apps/details?id=com.tencent.ibibo.mtt
		'ApacheBench'		=> array(),					// https://ru.wikipedia.org/wiki/ApacheBench
		'Stainless'		=> array(),					// http://www.mesadynamics.com
		'Oupeng'		=> array(),					// http://4pda.ru/forum/index.php?showtopic=558077
		'MiniBowserM'		=> array(),					// http://www.softpedia.com/get/Internet/Browsers/MiniBrowser-a.shtml
		'WebClip'		=> array(),					// Mac OS X Dashboard Web Clip
		'XiaoMi'		=> array(),					// MiuiBrowser
		'CriOS'			=> array(),					// Chrome for iOS
		'wOSBrowser'		=> array(),					// WebOS browser
		'OPiOS'			=> array(),					// Opera Mini for iOS
		'TaomeeBrowser'		=> array(),					// Taomee Browser

		// Unknown browsers
		'bdbrowser_i18n'	=> array(),					// Baidu?
		'bdbrowserhd_i18n'	=> array(),					// Baidu?
		'BIDUBrowser'		=> array(),					// Baidu?
		'baidubrowser'		=> array(),					// Baidu?
		'baiduboxapp'		=> array(),					// Baidu?
		'ru.mail.my'		=> array(),					// Mail.ru mobile browser
		'mmMobileClient'	=> array(),					// Mail.ru mobile browser

		// Crawlers
		'GSA'			=> array(),					// https://ru.wikipedia.org/wiki/Google_Search_Appliance
		'Googlebot'		=> array(),					// https://ru.wikipedia.org/wiki/Googlebot
		'WebIndex'		=> array(),					// http://habrahabr.ru/post/247465/

		// Engines
		'Version'		=> array(),					// First token verion
		'Gecko'			=> array(),
		'WebKit'		=> array(),
		'AppleWebKit'		=> array(),
		'Presto'		=> array(),

		// Addons
		'CyanogenMod'		=> array(),					// https://ru.wikipedia.org/wiki/CyanogenMod
		'AlexaToolbar'		=> array(),					// https://ru.wikipedia.org/wiki/Alexa_Toolbar
		'PhantomJS'		=> array(),					// http://phantomjs.org
		'YB'			=> array(),					// http://bar.yandex.ru
		'uToolBar'		=> array(),					// http://utoolbar.ucoz.net
		'Silk'			=> array(),					// https://ru.wikipedia.org/wiki/SILK
		'NexPlayer'		=> array(),					// https://en.wikipedia.org/wiki/NexStreaming
		'KHTML'			=> array(),					// https://ru.wikipedia.org/wiki/KHTML
		'SecondLife'		=> array(),					// https://ru.wikipedia.org/wiki/Second_Life
		'1Password'		=> array(),					// https://agilebits.com/onepassword
		'FirePHP'		=> array(),					// http://www.firephp.org
		'Qt'			=> array(),					// https://ru.wikipedia.org/wiki/Qt
		'Origin'		=> array(),					// https://www.origin.com
		'DepositFiles'		=> array(),					// http://filemanager.dfiles.ru/ru/filemanager.html
		'AdobeAIR'		=> array(),					// https://ru.wikipedia.org/wiki/Adobe_Integrated_Runtime
		'SugarLabs'		=> array(),					// https://en.wikipedia.org/wiki/Sugar_Labs
		'Edge'			=> array(),					// http://msdn.microsoft.com/en-us/library/ie/hh869301(v=vs.85).aspx
		'Lightning'		=> array(),					// https://wiki.archlinux.org/index.php/thunderbird#Lightning_-_Calendar
		'T5'			=> array(),					// Baidu Browser HTML5 engine
		'U2'			=> array(),					// UC Browser v.2
		'U3'			=> array(),					// UC Browser v.3
		'AdCentriaIM'		=> array(),					// Adware
		'BoBrowser'		=> array(),					// Adware
		'AndroidTranslate'	=> array(),
		'FireDownload'		=> array(),
		'FireTorrent'		=> array(),

		// OS
		'Linux'			=> array(),
		'Ubuntu'		=> array(),
		'Fedora'		=> array(),
		'ALTLinux'		=> array(),
		'Mint'			=> array(),
		'CentOS'		=> array(),
		'SUSE'			=> array(),
		'Debian'		=> array(),
		'Moblin'		=> array(),
		'Mandriva'		=> array(),
		'Dalvik'		=> array(),

		// Devices
		'Mobile'		=> array(),
		'SmartTV'		=> array(),
		'GoogleTV'		=> array(),
		'TouchPad'		=> array(),
		'Kindle'		=> array(),
		'PocketBook'		=> array(),
		'Athens15_TD'		=> array(),
		'LenovoA658t_TD'	=> array(),
		'MTK6592_TD'		=> array(),
		'HUAWEI_G730-T00_TD'	=> array(),
		'ZTE-TU880_TD'		=> array(),
		'RunboX5-W'		=> array(),
		'BlackBerry9300'	=> array(),

		// Unknowns
		'IR'			=> array(),
		'SMM-MMS'		=> array(),
		'ACHEETAHI'		=> array(),
		'MMS'			=> array(),
		'HCF'			=> array(),
		'TT'			=> array(),
		'MYI'			=> array(),
		'SVN'			=> array(),
		'Versio'		=> array(),
		'Client'		=> array(),
		'MBBMS'			=> array(),
		'Jasmine'		=> array(),
		'SHP'			=> array(),
		'BRI'			=> array(),
		'MB95'			=> array(),
		'SparkSafe'		=> array(),
		'Java'			=> array(),
		'WebBrowser'		=> array(),
		'VendorID'		=> array(),
		'AMIGOAPP'		=> array(),

		'Profile'		=> array(),
		'Configuration'		=> array(),
		'System'		=> array(),
		'Release'		=> array(),
		'Browser'		=> array(),
		'Android'		=> array(),

		// AskTb - http://apnstatic.ask.com/static/toolbar/everest/download/index.html
		'/prefixes/'		=> array("AskTb.+", "Nokia.+", "SAMSUNG-GT-.+", "Lenovo-.+", "Haier_.+", "HTC_.+", "Philips.+", "MT\d+.*", "sprd-.+", "CHANNEL2?_[a-zA-Z0-9]+", "UUID_[a-zA-Z0-9]+")
	);

	$defaults = array();

	fill_tokens($tokens, $defaults);

	$tokens = array_change_key_case($tokens);

	$sql = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

	$skipped = array();
	$firsts = array();
	$failed = array();
	$total = 0;

	$result = $sql->query("SELECT * FROM `an_agents` ORDER BY `id` ASC");
	while ($row = $result->fetch_assoc())
	{
		$total++;

		if ($row['agent'] == "")
			continue;

		$parsed = parse_ua($row['agent'], $error);
		if ($parsed === false)
		{
			$failed[$row['agent']] = $row['id'];
			continue;
		}

	//		print_r($parsed);
	//		echo $row['id'].": ".$row['agent']."\n\n";
	}

	clean($skipped);
	clean($firsts);
	clean($failed);

	echo "Parsed {$total}, failed ".count($failed)."\n";

	//	print_r($failed);
	//	print_r($skipped);
	//	print_r($firsts);

	function parse_ua($agent, &$error)
	{
		global $skipped, $firsts;

		$error = "";
		$agent .= " ";
		$length = strlen($agent);

		if ($length == 256)
		{
			$error = ERROR_TEXT."User agent length is max";
			return false;
		}

		$next = "token";
		$last_index = 0;
		$brackets = 0;
		$results = array();
		$result = array();

		for ($i = 0; $i < $length; $i++)
		{
			$sym = $agent[$i];

			if ($next == "version")
			{
				if ($sym != ' ' && $sym != '(' && $sym != ')')
					continue;
			}
			else
			{
				if ($sym != ' ' && $sym != '/' && $sym != '(' && $sym != ')')
					continue;
			}

			if ($sym == ')')
			{
				if ($brackets == 0)
				{
					$error = ERROR_TEXT."Unexpected close bracket at pos {$i}";
					return false;
				}

				$brackets--;

				if ($brackets != 0)
					continue;

				if (isset($result['options']))
				{
					$error = ERROR_TEXT."Options set twice";
					return false;
				}

				$last_index++;

				$piece = substr($agent, $last_index, $i - $last_index);

				$options = parse_options($piece);
				if ($options === false)
				{
					$error = ERROR_TEXT."Options parce failed";
					return false;
				}

				$result['options'] = $options;

				$last_index = $i + 1;
				continue;
			}

			if ($i != $last_index && $brackets == 0)
			{
				$piece = substr($agent, $last_index, $i - $last_index);
				$piece = trim($piece);

				if ($piece == "")
				{
					if ($sym != ' ')
					{
						$error = ERROR_TEXT."Empty token at pos {$i}";
						return false;
					}

					$last_index = $i + 1;
					continue;
				}

				switch ($next)
				{
					case "version":
					{
						$result['version'] = $piece;
						$next = "token";
						break;
					}
					case "token":
					{
						add_result($results, $result);

						if ($sym == "/")
							$next = "version";

						$first = ($last_index == 0);

						if (!has_token($piece))
						{
							$skipped[$piece] = true;

							if ($first)
							{
								$firsts[$piece] = true;
								return false;
							}

							$results['skip'] = true;
						}

						$result['token'] = $piece;
						break;
					}
				}

				$last_index = $i;

				if ($sym == ' ' || $sym == "/")
					$last_index++;
			}

			if ($sym == '(')
			{
				if ($last_index == 0)
				{
					$error = ERROR_TEXT."User agent can't start from options";
					return false;
				}

				$brackets++;
			}
		}

		add_result($results, $result);

		if ($brackets != 0)
		{
			$error = ERROR_TEXT."Open brackets left at the end of line";
			return false;
		}

		return $results;
	}

	function has_token($token)
	{
		global $tokens;

		$token_lower = strtolower($token);

		if ($token == "/prefixes/")
			return false;

		if (isset($tokens[$token_lower]))
			return true;

		reset($tokens['/prefixes/']);
		while (list(, $prefix) = each($tokens['/prefixes/']))
		{
			if (preg_match("/".$prefix."/i", $token) != 0)
				return true;
		}

		return false;
	}

	function add_result(&$results, &$result)
	{

		if (!empty($result) && !isset($result['skip']))
			$results[$result['token']] = $result;

		$result = array();
	}

	function parse_options($options)
	{
		return true;
	}

	function fill_tokens(&$tokens, $defaults)
	{
		while (list($token, $params) = each($tokens))
		{
			reset($defaults);
			while (list($param, $default) = each($defaults))
			{
				if (isset($params[$param]))
					continue;

				$tokens[$token][$param] = $default;
			}
		}

		reset($tokens);
	}

	function clean(&$tokens)
	{
		$tokens = array_keys($tokens);
		sort($tokens);
		reset($tokens);
	}

?>