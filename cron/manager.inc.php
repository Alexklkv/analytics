<?php

/**
 * Менеджер компонентов задач
 *
 * @uses Component
 * @uses ObjectComponents
 * @uses ObjectLog
 * @uses ObjectTasks
 *
 * @version 1.0.3
 */
class ManagerTask extends Component
{
	public function __construct(&$copy = null)
	{
		parent::__construct($copy);

		$this->Components->init($this, "Task");

		$this->run();
	}

	private function run()
	{
		$current = getdate();

		$tasks = $this->Tasks->get_list();

		reset($tasks);
		while (list(, $task) = each($tasks))
		{
			if (!$this->check_active($task, $current))
				continue;
//			if (!$this->check_lock($task))
//				continue;

//			$this->Log->info("Running ".$task['module']."::".$task['action']);
			$this->Components->call($task['module'], "on_".$task['action'], $task);
//			$this->clear_lock($task);
		}
	}

	private function check_lock($task)
	{
		$path = MAIN_LOCATION."cron/".$task['module']."_".$task['action'].".lck";

		if (file_exists($path))
			return false;

		if (file_put_contents($path, time()) === false)
			return false;

		return true;
	}

	private function clear_lock($task)
	{
		$path = MAIN_LOCATION."cron/".$task['module']."_".$task['action'].".lck";

		if (!file_exists($path))
			return;

		unlink($path);
	}

	private function check_active($task, $current)
	{
		if (!$this->check_value($task['minute'], $current['minutes']))
			return false;
		if (!$this->check_value($task['hour'], $current['hours']))
			return false;
		if (!$this->check_value($task['day'], $current['mday']))
			return false;
		if (!$this->check_value($task['month'], $current['mon']))
			return false;
		return true;
	}

	private function check_value($need, $current)
	{
		// *
		if ($need == "*")
			return true;

		// */x
		if ($need[0] == '*')
		{
			$need = substr($need, 2);
			$need = intval($need);

			if ($need == 0)
				return true;

			return ($current % $need == 0);
		}

		$need = explode(",", $need);
		return in_array($current, $need);
	}
}

/**
 * Базовый класс для компонентов задач
 *
 * @uses Component
 */
abstract class ComponentTask extends Component
{}

?>