<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Собирает зависимости класса от других классов и перечисляет их
 * в заголовочном комментарии под тегом @uses
 */
class Updater
{
	private $objects = array();
	private $aliases = array();
	private $dependencies = array();

	private $files = array();
	private $pathes = array();

	private $deps;

	public function __construct($deps)
	{
		$this->deps = $deps;
	}

	public function update()
	{
		// Objects
		reset($this->files);
		while (list(, $file) = each($this->files))
			$this->collect_objects($file);

		reset($this->pathes);
		while (list(, $directory) = each($this->pathes))
			$this->parse_dir($directory, array(&$this, "collect_objects"));

		ksort($this->objects);

		// Dependencies
		reset($this->files);
		while (list(, $file) = each($this->files))
			$this->collect_dependencies($file);

		reset($this->pathes);
		while (list(, $directory) = each($this->pathes))
			$this->parse_dir($directory, array(&$this, "collect_dependencies"));

		ksort($this->dependencies);

		// Update
		reset($this->files);
		while (list(, $file) = each($this->files))
			$this->update_source($file);

		reset($this->pathes);
		while (list(, $directory) = each($this->pathes))
			$this->parse_dir($directory, array(&$this, "update_source"));

		if ($this->deps)
		{
			$dot = $this->get_dot($this->dependencies, false);
			file_put_contents("dependencies.dot", $dot);
		}

		reset($this->objects);
		while (list($name, $hits) = each($this->objects))
		{
			if ($hits !== 0)
				continue;

			echo "Class {$name} hits {$hits} times\n";
		}
	}

	public function add_file($file)
	{
		$this->files[] = $file;
	}

	public function add_std($std)
	{
		$this->objects[$std] = false;
	}

	public function add_hide($hide)
	{
		$this->objects[$hide] = -1;
	}

	public function add_path($path)
	{
		$this->pathes[] = $path;
	}

	public function add_alias($used_name, $known_name)
	{
		$this->aliases[$used_name] = $known_name;
	}

	private function collect_objects($source_file)
	{
		$source = file_get_contents($source_file);
		$source = str_replace("\r", "", $source);

		preg_match_all("/(\/\*\*(?:.+?)\*\/)\n+(?:(?:abstract )?class|interface) ([a-z0-9_]+)/sui", $source, $matches);

		while (list(, $object) = each($matches[2]))
		{
			if (isset($this->objects[$object]))
				continue;
			$this->objects[$object] = 0;
		}
	}

	private function collect_dependencies($source_file)
	{
		$source = file_get_contents($source_file);
		$source = str_replace("\r", "", $source);

		preg_match_all("/(\/\*\*(?:[^\\/]+?)\*\/)\n+(?:(?:abstract )?class|interface)\s+([A-Z][a-z0-9_]+)( [a-z0-9_, ]+)?\n(?:\{(.+?)\n\}|\{()\})/sui", $source, $matches, PREG_SET_ORDER);

		reset($matches);
		while (list(, $match) = each($matches))
		{
			$block = $match[1];
			$class_name = $match[2];
			$extends = $match[3];
			$code = $match[4];

			$objects = array();

			preg_match_all("/\\\$this->(?:objects->)?([A-Z][a-zA-Z0-9]*)->/u", $code, $result);
			$objects = array_merge($objects, $result[1]);

			while (list($index, $name) = each($objects))
				$objects[$index] = "Object".$name;

			preg_match_all("/(?:=|return)\s*new\s+([A-Z][a-zA-Z0-9]*)\(/u", $code, $result);
			$objects = array_merge($objects, $result[1]);

			preg_match_all("/ extends\s+([A-Z][a-zA-Z0-9]*)/u", $extends, $result);
			$objects = array_merge($objects, $result[1]);

			if (preg_match("/ implements\s+([a-zA-Z0-9 ,]+)/u", $extends, $result) === 1)
			{
				$result = explode(",", $result[1]);
				$result = array_map("trim", $result);

				$objects = array_merge($objects, $result);
			}

			$description = rtrim($block);
			$description = preg_replace("/ \* @dot.+? \* @enddot\n*/usi", "", $description);

			$refs = $this->get($description, "@ref ");
			$objects = array_merge($objects, $refs);

			$objects = array_unique($objects);
			sort($objects);

			reset($objects);
			while (list($index, $name) = each($objects))
			{
				if ($name === $class_name)
				{
					unset($objects[$index]);
					continue;
				}

				if (isset($this->aliases[$name]))
				{
					$name = $this->aliases[$name];
					unset($objects[$index]);
				}

				if (!isset($this->objects[$name]))
					die("Can't find object {$name} at {$source_file}");

				if ($this->objects[$name] === -1)
				{
					unset($objects[$index]);
					continue;
				}

				if ($this->objects[$name] === false)
					continue;

				$this->objects[$name]++;
			}

			$this->dependencies[$class_name] = $objects;
		}
	}

	private function update_source($source_file)
	{
		$source = file_get_contents($source_file);
		$source = str_replace("\r", "", $source);

		preg_match_all("/(\/\*\*(?:[^\\/]+?)\*\/)\n+(?:(?:abstract )?class|interface) ([a-z0-9_]+)/sui", $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		$matches = array_reverse($matches);

		$replaced = false;

		reset($matches);
		while (list(, $match) = each($matches))
		{
			$block = $match[1];
			$class_name = $match[2][0];

			$description = rtrim($block[0]);
			$description = preg_replace("/ \* @dot.+? \* @enddot\n*/usi", "", $description);

			$comments	= $this->get($description, "", false);
			$refs		= $this->get($description, "@ref ");
			$versions	= $this->get($description, "@version ");

			if (!isset($this->dependencies[$class_name]))
				die("Can't get {$class_name} dependencies");

			$objects = $this->dependencies[$class_name];

			if ($this->deps)
			{
				$dot = array();

				$this->fill_related($class_name, $dot);
				$dot = $this->get_dot($dot);
			}
			else
				$dot = "";

			$description = "/**";
			$description .= $this->make($comments, "");
			$description .= $this->make($refs, "@ref ");
			$description .= $this->make($objects, "@uses ");
			$description .= $dot;
			$description .= $this->make($versions, "@version ");
			$description .= "/";

			if ($description == $block[0])
				continue;

			$source = substr_replace($source, $description, $block[1], strlen($block[0]));
			$replaced = true;
		}

		if (!$replaced)
			return;

		file_put_contents($source_file, $source);

		echo "Updated {$source_file}\n";
	}

	private function fill_related($class_name, &$related = array())
	{
		if (!isset($this->dependencies[$class_name]))
			return;

		$deps = $this->dependencies[$class_name];

		$related[$class_name] = $deps;

		reset($deps);
		while (list(, $name) = each($deps))
		{
			if (isset($related[$name]))
				continue;

			$this->fill_related($name, $related);
		}
	}

	private function get_dot($dot, $inline = true)
	{
		$dot_objects = array();
		$dot_edges = array();

		reset($dot);
		while (list($class_name, $objects) = each($dot))
		{
			$dot_objects[] = $class_name;
			$dot_objects = array_merge($dot_objects, $objects);

			$objects_count = count($objects);
			if ($objects_count == 0)
				continue;

			$objects = implode("; ", $objects);

			if ($objects_count == 1)
				$objects = $objects.";";
			else
				$objects = "{".$objects."}";

			$dot_edges[] = "\t".$class_name." -> ".$objects;
		}

		sort($dot_objects);

		$dot_objects = $this->get_nodes($dot_objects);

		if (!empty($dot_edges))
			$dot_objects[] = "";

		$dot_start = array();
		if ($inline)
		{
			$dot_start[] = "";
			$dot_start[] = " @dot";
			$dot_start[] = " digraph G";
			$dot_start[] = " {";
		}
		else
		{
			$dot_start[] = "digraph G";
			$dot_start[] = "{";
		}

		$dot_start[] = "\tedge [color=\"midnightblue\"];";
		$dot_start[] = "\tnode [fontname=\"Verdana\",fontsize=\"11\",shape=record];";
		$dot_start[] = "";

		$dot_end = array();
		if ($inline)
		{
			$dot_end[] = " }";
			$dot_end[] = " @enddot";
			$dot_end[] = "";
		}
		else
			$dot_end[] = "}";

		$dot_data = array_merge($dot_start, $dot_objects, $dot_edges, $dot_end);

		if ($inline)
			$dot_data = implode("\n *", $dot_data);
		else
			$dot_data = implode("\n", $dot_data);

		return $dot_data;
	}

	private function get_nodes($objects)
	{
		$nodes = array();

		$objects = array_unique($objects);

		reset($objects);
		while (list(, $name) = each($objects))
		{
			if ($this->objects[$name] === -1)
				continue;

			if ($this->objects[$name] === false)
				$nodes[] = "\t".$name." [label=\"".$name."\"];";
			else
				$nodes[] = "\t".$name." [label=\"".$name."\" URL=\"\\ref ".$name."\"];";
		}

		return $nodes;
	}

	private function parse_dir($dir, $callback)
	{
		$dir_handle = opendir($dir);
		if ($dir_handle === false)
			die("Can't open directory ".$dir);

		while (($file = readdir($dir_handle)) !== false)
		{
			if ($file === "." || $file === "..")
				continue;

			if (is_dir($dir.$file))
			{
				$this->parse_dir($dir.$file."/", $callback);
				continue;
			}

			if (strstr($file, ".") !== ".inc.php")
				continue;

			call_user_func_array($callback, array($dir.$file));
		}

		closedir($dir_handle);
	}

	private function make($data, $key)
	{
		$data = implode("\n * {$key}", $data);
		if ($data != "")
			$data = "\n * {$key}".$data."\n *";

		return $data;
	}

	private function get($source, $key, $sort = true)
	{
		preg_match_all("/ \* {$key}([^@].+)/ui", $source, $matches);

		if (!$sort)
			return $matches[1];

		$data = array_unique($matches[1]);
		sort($data);
		return $data;
	}
}

	$deps = false;
	if (isset($argv[1]) && $argv[1] == "deps")
		$deps = true;

	$updater = new Updater($deps);

	$updater->add_file("component.inc.php");
	$updater->add_path("objects/");
	$updater->add_path("user/");
	$updater->add_path("cron/");

	$updater->add_std("Memcached");
	$updater->add_std("SimpleXMLElement");
	$updater->add_std("DOMDocument");
	$updater->add_std("SphinxClient");
	$updater->add_std("StringBuilder");
	$updater->add_std("DateTime");
	$updater->add_std("Iterator");

	$updater->add_hide("Object");
	$updater->add_hide("TreeNode");
	$updater->add_hide("Trend");
	$updater->add_hide("Indicator");

	$updater->add_alias("ObjectDB", "Database");
	$updater->add_alias("ObjectFilters", "Filters");

	$updater->update();

?>