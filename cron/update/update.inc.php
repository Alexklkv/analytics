<?php

/**
 * Реализует задачу обновления аналитики
 *
 * @uses ComponentTask
 * @uses ObjectAnalytics
 *
 * @version 1.0.0
 */
class TaskUpdate extends ComponentTask
{
	public function on_update()
	{
		global $argv;

		if (isset($argv[1]) && $argv[1] == 1)
			$minutes = 0;
		else
			$minutes = date("i");

		$report = false;
		if (isset($argv[2]))
			$report = $argv[2];

		$this->Analytics->update(($minutes == 0), "common", $report);
	}

	public function on_api()
	{
		global $argv;

		if (isset($argv[1]) && $argv[1] == 1)
			$minutes = 0;
		else
			$minutes = date("i");

		$report = false;
		if (isset($argv[2]))
			$report = $argv[2];

		$this->Analytics->update(($minutes == 0), "events", $report);
	}

	public function on_live_stream()
	{
		$this->Analytics->update_live_stream();
	}

	public function on_ad_params()
	{
		$this->Analytics->update_ad_params();
	}
}

?>