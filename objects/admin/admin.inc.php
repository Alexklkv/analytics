<?php

/**
 * Предоставляет функции регистрации и авторизации пользователей в панели администратора
 *
 * @uses DatabaseInterface
 * @uses ObjectErrors
 * @uses ObjectLog
 * @uses ObjectSessions
 * @uses ObjectTables
 *
 * @version 1.0.3
 */
class ObjectAdmin extends ObjectSessions implements DatabaseInterface
{
	/**
	 * @var string Email пользователя для проверки
	 */
	private $email;

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
			'get'			=> "SELECT * FROM @pusers @W",
			'add'			=> "INSERT INTO @pusers SET @a",
			'update'		=> "UPDATE @pusers SET @a WHERE id = @i",
			'delete'		=> "DELETE FROM @pusers WHERE id = @i",

			'get_data'		=> "SELECT id, email, reg_time, last_login, access FROM @pusers @@W @O @L",
			'get_data_count'	=> "SELECT count(*) as total FROM @pusers @@W",
		);
	}

	public function register($data)
	{
		$data['email'] = trim($data['email']);

		if (!isset($data['access']) || $data['access'] == "")
			$data['access'] = array();

		if (!$this->check_fields($data))
			return false;

		$row = $this->get_by("email", $data['email']);
		if ($row !== false)
		{
			$this->Errors->add(array("auth", "register"), "Пользователь с таким e-amil-ом уже существует");
			return false;
		}

		$data['access'] = serialize($data['access']);
		$data['reg_time'] = $_SERVER['REQUEST_TIME'];
		$data['password'] = $this->encrypt($data['password']);

		$this->DB->add($data);

		return true;
	}

	public function unregister($id)
	{
		$this->DB->delete($id);

		if ($this->DB->affected_rows == 0)
			return false;

		$this->request_update($id);
		return true;
	}

	public function change_password($id, $password)
	{
		if (!$this->check_password($password))
			return false;

		$password = $this->encrypt($password);

		$this->update_user($id, array('password' => $password));
		return true;
	}

	public function get_by($type, $value)
	{
		$result = $this->DB->get(array($type => $value));
		if ($result->is_empty())
			return false;

		return $result->fetch();
	}

	/**
	 * Обновляет данные аккаунта
	 *
	 * @param int $id Идентификатор аккаунта
	 * @param array $data Изменённые данные аккаунта: массив ключ-значение,
	 * где ключ 'access' - имя поля в базе данных и значение -
	 * сериализованный массив прав пользователя
	 */
	public function update_user($id, $data)
	{
		$this->DB->update($data, $id);
		$this->request_update($id);
	}

	/**
	 * Выполняет проверку на наличие прав доступа
	 *
	 * @param string $access Требуемые права доступа
	 * @param array|bool $user_access Альтернативные права доступа,
	 * в которых будет производится проверка, по умолчанию - FALSE,
	 * в случае по умолчанию проверяется наличие права $access в $_SESSION['access'].
	 * @return bool TRUE - пользователь обладает правом $access,
	 * FALSE - пользовательн НЕ обладает правом $access или не авторизован
	 */
	public function check_access($access, $user_access = false)
	{
		if ($user_access === false)
		{
			if (!$this->is_authed())
				return false;

			$user_access = $_SESSION['access'];
		}

		$access = strtoupper($access);
		$access = explode("_", $access);
		while (list(, $piece) = each($access))
		{
			if (!isset($user_access[$piece]))
				return false;

			$user_access = &$user_access[$piece];
		}

		return true;
	}

	/**
	 * Изменяет права доступа аккаунта
	 *
	 * @param int $id Идентификатор пользователя
	 * @param string $access Новый массив прав
	 */
	public function change_access($id, $access)
	{
		$access = serialize($access);

		$this->update_user($id, array('access' => $access));
	}

	/**
	 * Сохраняет пару ключ:значение во внутренних данных пользователя.
	 * Только если пользователь уже авторазиован!
	 *
	 * @param string $key Ключ
	 * @param string $value Значение
	 * @return null Возврат в случае неавторизованного пользователя
	 */
	public function data_set($key, $value)
	{
		if (!$this->is_authed())
			return;

		if (!isset($_SESSION['data']))
			$_SESSION['data'] = array();

		$_SESSION['data'][$key] = $value;

		$this->data_save();
	}

	/**
	 * Возвращает значение по ключу из внутренних данных пользователя.
	 * Только если пользователь уже авторазиован!
	 *
	 * @param string $key Ключ
	 * @return bool|mixed Возвращает FALSE в случае, если значения нет
	 * в $_SESSION['data'], либо значение в $_SESSION['data']
	 */
	public function data_get($key)
	{
		/**
		 * @todo Бросать исключение, так как значением $_SESSION['data'}[$key] может быть FALSE
		 */
		if (!$this->is_authed())
			return false;

		if (!isset($_SESSION['data'][$key]))
			return false;

		return $_SESSION['data'][$key];
	}

	/**
	 * Возвращает результат запроса из БД всех аккаунтов
	 *
	 * @return DatabaseResult Результат запроса
	 */
	public function get_all()
	{
		return $this->DB->get();
	}

	/**
	 * Отправляет данные пользователей в JSON формате
	 */
	public function send_data()
	{
		$table = array(
			'fields'	=> array("id", "email", "reg_time", "last_login", "access"),
			'count'		=> array(&$this->DB, "get_data_count"),
			'data'		=> array(&$this->DB, "get_data")
		);

		$this->Tables->send($table);
	}

	/**
	 * Устанавливает Email пользователя для дополнительной проверки при входе по внутреннему IP
	 *
	 * @param string $email
	 */
	public function set_email($email)
	{
		$this->email = $email;
	}

	/**
	 * Проверяет пароль на корректность
	 *
	 * @param string $password Пароль
	 * @return bool Корректность пароля
	 */
	protected function check_password($password)
	{
		if (mb_strlen($password) > SESSION_MAX_PASSWORD_LEN)
			$this->Errors->add(array("auth", "password"), "Пароль слишком длинный");
		else
			return true;
	}

	protected function set_login_data($data)
	{
		$data['last_login'] = $_SERVER['REQUEST_TIME'];
		$this->DB->update(array('last_login' => $data['last_login']), $data['id']);

		parent::set_login_data($data);
	}

	protected function get_data()
	{
		$data = parent::get_data();

		$data['access']	= serialize($data['access']);
		$data['data']	= serialize($data['data']);

		return $data;
	}

	protected function set_data($data)
	{
		$data['access'] = unserialize($data['access']);
		if ($data['access'] === false)
			$this->Log->error("Error reading user access data");

		if (!empty($data['data']))
			$data['data'] = unserialize($data['data']);
		else
			$data['data'] = array();

		parent::set_data($data);
	}

	protected function get_params()
	{
		return array(
			'prefix'	=> ADMIN_SESSION_PREFIX,
			'salt'		=> ADMIN_PASSWORD_SALT
		);
	}

	protected function extra_check()
	{
		if (preg_match("/@localhost$/i", $this->email) === 0)
			return true;

		$ips = explode(",", SESSION_INTERNAL_IPS);

		if (!in_array($_SERVER['REMOTE_ADDR'], $ips))
			return false;

		return true;
	}

	private function data_save()
	{
		$id = $this->used_id();
		if ($id === false)
			return;

		if (!empty($_SESSION['data']))
			$data = serialize($_SESSION['data']);
		else
			$data = "";

		$this->update_user($id, array('data' => $data));
	}
}

?>