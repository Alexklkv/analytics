<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Модуль обработки пользовательских файлов
 *
 * @uses ComponentAdmin
 *
 * @version 1.0.1
 */
class AdminFiles extends ComponentAdmin
{
	public function get_name()
	{
		return "";
	}

	public function get_services()
	{
		return array();
	}

	/**
	 * Возвращает пользовательский массив прав доступа для каждого метода-обработчика,
	 * а также общие права на доступ к конкретному модулю
	 *
	 * @return array Массив прав доступа
	 */
	public function get_access_overrides()
	{
		return array(
			'index' => "INDEX"
		);
	}

	public function on_upload()
	{
		header('Content-Type: application/json');
		if (empty($_FILES) || !isset($_FILES['file_upl']))
		{
			echo json_encode(array(
				'success'	=> false,
				'error'		=> 0
			));
			exit;
		}

		$errors = false;

		switch ($_FILES['file_upl']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				$errors = true;
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$errors = true;
				break;
			default:
				$errors = true;
				break;
		}

		if ($_FILES['file_upl']['size'] > FILES_MAX_SIZE)
		{
			$errors = true;
		}

		if ($errors === true)
		{
			echo json_encode(array(
				'success'	=> false,
				'error'		=> 1
			));
			exit;
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (false === $ext = array_search(
			$finfo->file($_FILES['file_upl']['tmp_name']),
			array(
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			),
			true
		))
		{
			echo json_encode(array(
				'success'	=> false,
				'error'		=> 2
			));
			exit;
		}

		$filename = sprintf(UPLOAD_DIR."/%s.%s", sha1_file($_FILES['file_upl']['tmp_name']), $ext);
		if (move_uploaded_file($_FILES['file_upl']['tmp_name'], $filename))
		{
			echo json_encode(array(
				'success'	=> true,
				'filename'	=> basename($filename),
				'error'		=> 0
			));
			exit;
		}

		echo json_encode(array(
			'success'	=> false,
			'error'		=> 3
		));
		exit;
	}
}

?>