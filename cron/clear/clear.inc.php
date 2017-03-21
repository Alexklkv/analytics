<?php

/**
 * Реализует задачу очистки хитов
 *
 * @uses ComponentTask
 * @uses ObjectApi
 *
 * @version 1.0.0
 */
class TaskClear extends ComponentTask
{
	public function on_clear()
	{
		$this->Api->clear();
	}
}

?>