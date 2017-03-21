<?php
class TaskExport extends ComponentTask
{
	public function on_export()
	{
		$this->Analytics->export();
	}
}