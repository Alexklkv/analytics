<?php

/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

/**
 * Интерпретатор для формул пользовательских отчётов.
 * В основе - шаблон проектирования "Interpreter"
 *
 * EBNF:
 * expression ::= addend ( ( '+'|'-' ) addend )*
 * addend ::= factor ( ( '*'|'/' ) factor )*
 * factor ::= ( const | '(' expression ')' | funcExp )
 * const ::= \d+\.*\d*
 * funcExp ::= [a-zA-Z]+ '(' metric ')'
 * metric ::= m\d+
 */
class ObjectAninterpreter extends Object
{
	/**
	 * @var SequenceParse Объект выражения
	 */
	private $expression;

	/**
	 * @var Expression Объект дерева формулы
	 */
	private $interpreter;

	/**
	 * @var string Дата, с которой собирать данные по формулам
	 */
	private $init_date;

	/**
	 * @var ObjectUreports Объект модели пользовательских отчётов
	 */
	private $ureports_obj;

	/**
	 * Запуск формул пользовательских отчётов
	 *
	 * @param string $statement_str Строка формулы
	 * @param string $init_date Дата, с которой собирать данные по формулам
	 * @return mixed результат вычислений
	 */
	public function evaluate($statement_str, $init_date)
	{
		try
		{
			$this->ureports_obj = $this->Ureports;
			$this->init_date = $init_date;
			$this->compile($statement_str);
			if (!$this->interpreter instanceof Expression)
				$result = $this->interpreter;
			else
			{
				$icontext = new InterpreterContext();
				$this->interpreter->interpret($icontext);
				$result = $icontext->lookup($this->interpreter);
			}
		}
		catch (Exception $e)
		{
			$this->Log->error($e->getMessage());
		}

		return $result;
	}

	/**
	 * Построение дерева выражений
	 *
	 * @param string $statement_str
	 * @throws Exception Исключение в случае ошибки синтаксического анализа
	 */
	private function compile($statement_str)
	{
		$context = new Context();
		$context->set_init_date($this->init_date);
		$context->set_ureports_obj($this->ureports_obj);
		$scanner = new Scanner(new StringReader($statement_str), $context);
		$statement = $this->expression();
		$scanresult = $statement->scan($scanner);

		if (!$scanresult || $scanner->token_type() != Scanner::EOF)
		{
			$msg = " line: {$scanner->line_no()} ";
			$msg .= " char: {$scanner->char_no()}";
			$msg .= " token: {$scanner->token()}\n";
			throw new Exception($msg);
		}

		$this->interpreter = $scanner->get_context()->pop_result();
	}

	/**
	 * Основное выражение (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Основное выражение
	 */
	private function expression()
	{
		if (!isset($this->expression))
		{
			$this->expression = new SequenceParse();
			$this->expression->add($this->addend());
			$rep = new RepetitionParse();
			$seq = new SequenceParse();
			$alt = new AlternationParse();
			$alt->add($this->op_add());
			$alt->add($this->op_sub());
			$seq->add($alt);
			$seq->add($this->addend());
			$seq->set_handler(new ArithmeticHandler());
			$rep->add($seq);

			$this->expression->add($rep);
		}

		return $this->expression;
	}

	/**
	 * Слагаемое (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект слагаемого
	 */
	private function addend()
	{
		$addend = new SequenceParse();
		$addend->add($this->factor());
		$rep = new RepetitionParse();
		$seq = new SequenceParse();
		$alt = new AlternationParse();
		$alt->add($this->op_mult());
		$alt->add($this->op_div());
		$seq->add($alt);
		$seq->add($this->factor());
		$seq->set_handler(new ArithmeticHandler());
		$rep->add($seq);
		$addend->add($rep);

		return $addend;
	}

	/**
	 * Множитель (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект множителя
	 */
	private function factor()
	{
		$factor = new SequenceParse();
		$alt = new AlternationParse();
		$alt->add($this->const_exp());
		$exp = new SequenceParse();
		$exp->add(new OperatorParse("("))->discard();
		$exp->add($this->expression());
		$exp->add(new OperatorParse(")"))->discard();
		$alt->add($exp);
		$alt->add($this->func_exp());
		$factor->add($alt);

		return $factor;
	}

	/**
	 * Константа (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект константы
	 */
	private function const_exp()
	{
		$con = new SequenceParse();
		$con->add(new NumParse());
		$con->set_handler(new ConstExpHandler());

		return $con;
	}

	/**
	 * Функция (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект функции
	 */
	private function func_exp()
	{
		$func = new SequenceParse();
		$func->add(new WordParse());
		$func->add(new OperatorParse("("))->discard();
		$func->add(new WordParse());
		$func->add(new OperatorParse(")"))->discard();
		$func->set_handler(new FuncHandler());

		return $func;
	}

	/**
	 * Плюс (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse
	 */
	private function op_add()
	{
		$op = new SequenceParse();
		$op->add(new OperatorParse("+"));
		$op->set_handler(new OperatorHandler());

		return $op;
	}

	/**
	 * Минус (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект оператора
	 */
	private function op_sub()
	{
		$op = new SequenceParse();
		$op->add(new OperatorParse("-"));
		$op->set_handler(new OperatorHandler());

		return $op;
	}

	/**
	 * Умножить (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект оператора
	 */
	private function op_mult()
	{
		$op = new SequenceParse();
		$op->add(new OperatorParse("*"));
		$op->set_handler(new OperatorHandler());

		return $op;
	}

	/**
	 * Разделить (см. EBNF в комментарии к этому классу)
	 *
	 * @return SequenceParse Объект оператора
	 */
	private function op_div()
	{
		$op = new SequenceParse();
		$op->add(new OperatorParse("/"));
		$op->set_handler(new OperatorHandler());

		return $op;
	}
}

/**
 * Интерфейс источника (строка, файл и др.)
 */
interface IReader
{
	/**
	 * Получить следующий символ
	 *
	 * @return string Следующий символ формулы
	 */
	public function get_char();

	/**
	 * Получть текущую позицию
	 *
	 * @return int Текущая позиция при синтаксическом разобре
	 */
	public function get_pos();

	/**
	 * Протолкунуть символ обратно в разбираемый источник
	 */
	public function push_back_char();
}

/**
 * Интерфейс обработчика выражений
 */
interface IHandler
{
	/**
	 * Закрепляет за объектом выражения обработчик
	 *
	 * @param Parser $parser Синтаксический анализатор
	 * @param Scanner $scanner Разборщик строк формул
	 */
	public function handle_match(Parser $parser, Scanner $scanner);
}

/**
 * Синтаксический анализатор
 */
abstract class Parser
{
	const GIP_RESPECTSPACE = 1;
	protected $respect_space = false;
	protected static $debug = false;
	protected $discard = false;
	protected $name;
	private static $count = 0;

	public function __construct($name = null, $options = null)
	{
		if (is_null($name))
		{
			self::$count++;
			$this->name = get_class($this)." (".self::$count.")";
		}
		else
			$this->name = $name;

		if (is_array($options))
		{
			if (isset($options[self::GIP_RESPECTSPACE]))
				$this->respect_space = true;
		}
	}

	protected function next(Scanner $scanner)
	{
		$scanner->next_token();
		if (!$this->respect_space)
			$scanner->eat_white_space();
	}

	public function set_handler(IHandler $handler)
	{
		$this->handler = $handler;
	}

	final public function scan(Scanner $scanner)
	{
		if ($scanner->token_type() == Scanner::SOF)
			$scanner->next_token();

		$ret = $this->do_scan($scanner);
		if ($ret && !$this->discard && $this->term())
			$this->push($scanner);

		if ($ret)
			$this->invoke_handler($scanner);

		if ($this->term() && $ret)
			$this->next($scanner);

		$this->report("::scan returning $ret");

		return $ret;
	}

	public function discard()
	{
		$this->discard = true;
	}

	abstract public function trigger(Scanner $scanner);

	public function term()
	{
		return true;
	}

	protected function invoke_handler(Scanner $scanner)
	{
		if (!empty($this->handler))
		{
			$this->report("calling handler: ".get_class($this->handler));
			$this->handler->handle_match($this, $scanner);
		}
	}

	protected function report($msg)
	{
		if (self::$debug)
			echo "<{$this->name}> ".get_class($this).": $msg\n";
	}

	protected function push(Scanner $scanner)
	{
		$context = $scanner->get_context();
		$context->push_result($scanner->token());
	}

	abstract protected function do_scan(Scanner $scan);
}

/**
 * Коллекция объектов Parser
 */
abstract class CollectionParse extends Parser
{
	protected $parsers = array();

	public function add(Parser $p)
	{
		if (is_null($p))
			throw new Exception("argument is null");

		$this->parsers[] = $p;

		return $p;
	}

	public function term()
	{
		return false;
	}
}

abstract class Expression
{
	private static $keycount = 0;
	private $key;

	abstract function interpret(InterpreterContext $context);

	public function get_key()
	{
		if (!isset($this->key))
		{
			self::$keycount++;
			$this->key = self::$keycount;
		}

		return $this->key;
	}
}

class ScannerState
{
	public $line_no;
	public $char_no;
	public $token;
	public $token_type;
	public $reader;
}

class Context
{
	private $resultstack = array();
	private $init_date;
	private $ureports_obj;

	public function push_result($mixed)
	{
		array_push($this->resultstack, $mixed);
	}

	public function pop_result()
	{
		return array_pop($this->resultstack);
	}

	public function result_count()
	{
		return count($this->resultstack);
	}

	public function peek_result()
	{
		if (empty($this->resultstack))
			throw new Exception ("Empty resultstack");

		return $this->resultstack[count($this->resultstack) - 1];
	}

	public function set_init_date($date)
	{
		$this->init_date = $date;
	}

	public function get_init_date()
	{
		return $this->init_date;
	}

	public function set_ureports_obj($ureports_obj)
	{
		$this->ureports_obj = $ureports_obj;
	}

	public function get_ureports_obj()
	{
		return $this->ureports_obj;
	}
}

/**
 * Разборщик строк формул, разбивает формулы на лексемы
 */
class Scanner
{
	const WORD = 1;
	const NUM = 2;
	const OPERATOR = 3;
	const WHITESPACE = 4;
	const EOL = 5;
	const EOF = 0;
	const SOF = -1;

	protected $line_no = 1;
	protected $char_no = 0;
	protected $token = null;
	protected $token_type = -1;

	private $reader;
	private $context;

	public function __construct(IReader $reader, Context $context)
	{
		$this->reader = $reader;
		$this->context = $context;
	}

	public function get_context() {
		return $this->context;
	}

	public function eat_white_space()
	{
		$ret = 0;
		if ($this->token_type != self::WHITESPACE && $this->token_type != self::EOL)
			return $ret;

		while ($this->next_token() == self::WHITESPACE || $this->token_type == self::EOL)
			$ret++;

		return $ret;
	}

	public function get_type_string($int = -1)
	{
		if ($int < 0)
			$int = $this->token_type();
		if ($int < 0)
			return null;

		$resolve = array(
			self::WORD		=> 'WORD',
			self::NUM		=> 'NUM',
			self::OPERATOR		=> 'OPERATOR',
			self::WHITESPACE	=> 'WHITESPACE',
			self::EOL		=> 'EOL',
			self::EOF		=> 'EOF'
		);

		return $resolve[$int];
	}

	public function token_type()
	{
		return $this->token_type;
	}

	public function token()
	{
		return $this->token;
	}

	public function line_no()
	{
		return $this->line_no;
	}

	public function char_no()
	{
		return $this->char_no;
	}

	public function __clone()
	{
		$this->reader = clone($this->reader);
	}

	public function next_token()
	{
		$this->token = null;
		$type;
		if (!is_bool($char = $this->get_char()))
		{
			if ($this->is_eol_char($char))
			{
				$this->token = $this->manage_eol_chars($char);
				$this->line_no++;
				$this->char_no = 0;
				$type = self::EOL;

				return ($this->token_type = self::EOL);

			}
			else if ($this->is_letter($char))
			{
				$this->token = $this->eat_word_chars($char);
				$type = self::WORD;
			}
			else if ($this->is_num_char($char))
			{
				$this->token = $this->eat_num_chars($char);
				$type = self::NUM;
			}
			else if ($this->is_op_char($char))
			{
				$this->token = $char;
				$type = self::OPERATOR;
			}
			else if ($this->is_space_char($char))
			{
				$this->token = $char;
				$type = self::WHITESPACE;
			}
			else
				throw new Exception ("Syntax error: unexpected token <{$char}>");

			$this->char_no += strlen($this->token());

			return ($this->token_type = $type);
		}

		return ($this->token_type = self::EOF);
	}

	public function get_state()
	{
		$state = new ScannerState();
		$state->line_no = $this->line_no;
		$state->char_no = $this->char_no;
		$state->token = $this->token;
		$state->token_type = $this->token_type;
		$state->reader = clone($this->reader);
		$state->context = clone($this->context);

		return $state;
	}

	public function set_state(ScannerState $state)
	{
		$this->line_no = $state->line_no;
		$this->char_no = $state->char_no;
		$this->token = $state->token;
		$this->token_type = $state->token_type;
		$this->reader = $state->reader;
		$this->context = $state->context;
	}

	public function get_pos()
	{
		return $this->reader->get_pos();
	}

	private function get_char()
	{
		return $this->reader->get_char();
	}

	private function eat_word_chars($char)
	{
		$val = $char;
		while ($this->is_word_char($char = $this->get_char()))
			$val .= $char;

		if ($char)
			$this->push_back_char();

		return $val;
	}

	private function eat_num_chars($char)
	{
		$val = $char;
		while ($this->is_num_char($char = $this->get_char()))
			$val .= $char;

		if ($char)
			$this->push_back_char();

		return $val;
	}

	private function push_back_char()
	{
		$this->reader->push_back_char();
	}

	private function is_letter($char)
	{
		return preg_match("/[A-Za-z_]/", $char);
	}

	private function is_word_char($char)
	{
		return preg_match("/[A-Za-z0-9_]/", $char);
	}

	private function is_num_char($char)
	{
		return preg_match("/[0-9\.]/", $char);
	}

	private function is_op_char($char)
	{
		return preg_match("/[\+\-\*\/\(\)]/", $char);
	}

	private function is_space_char($char)
	{
		return preg_match("/\t| /", $char);
	}

	private function is_eol_char($char)
	{
		return preg_match("/\n|\r/", $char);
	}

	private function manage_eol_chars($char)
	{
		if ($char == "\r") {
			$next_char = $this->get_char();
			if ($next_char == "\n")
				return "{$char}{$next_char}";
			else
				$this->push_back_char();
		}

		return $char;
	}
}

class StringReader implements IReader
{
	private $in;
	private $pos = 0;

	public function __construct($in)
	{
		$this->in = $in;
	}

	public function get_char()
	{
		if ($this->pos >= strlen($this->in))
			return false;

		$char = substr($this->in, $this ->pos, 1);
		$this->pos++;
		return $char;
	}

	public function get_pos()
	{
		return $this->pos;
	}

	public function push_back_char()
	{
		$this->pos--;
	}

	public function string()
	{
		return $this->in;
	}
}

class SequenceParse extends CollectionParse
{
	public function trigger(Scanner $scanner)
	{
		if (empty($this->parsers))
			return false;

		return $this->parsers[0]->trigger($scanner);
	}

	protected function do_scan(Scanner $scanner)
	{
		$start_state = $scanner->get_state();
		foreach ($this->parsers as $parser)
		{
			if (!($parser->trigger($scanner) && $parser->scan($scanner)))
			{
				$scanner->set_state($start_state);
				return false;
			}
		}

		return true;
	}
}

class RepetitionParse extends CollectionParse
{
	private $min;
	private $max;

	public function __construct($min = 0, $max = 0, $name = null, $options = null)
	{
		parent::__construct($name, $options);
		if ($max < $min && $max > 0)
			throw new Exception("maximum ($max) larger than minimum ($min)");

		$this->min = $min;
		$this->max = $max;
	}

	public function trigger(Scanner $scanner)
	{
		return true;
	}

	protected function do_scan(Scanner $scanner)
	{
		$start_state = $scanner->get_state();
		if (empty($this->parsers))
			return true;

		$parser = $this->parsers[0];
		$count = 0;

		while (true)
		{
			if ($this->max > 0 && $count >= $this->max)
				return true;

			if (!$parser->trigger($scanner))
			{
				if ($this->min == 0 || $count >= $this->min)
					return true;
				else
				{
					$scanner->set_state($start_state);
					return false;
				}
			}

			if (!$parser->scan($scanner))
			{
				if ( $this->min == 0 || $count >= $this->min )
					return true;
				else
				{
					$scanner->set_state($start_state);
					return false;
				}
			}
			$count++;
		}
		return true;
	}
}

class AlternationParse extends CollectionParse
{

	public function trigger(Scanner $scanner)
	{
		foreach ($this->parsers as $parser)
		{
			if ($parser->trigger($scanner))
				return true;
		}

		return false;
	}

	protected function do_scan(Scanner $scanner)
	{
		$type = $scanner->token_type();
		foreach ($this->parsers as $parser)
		{
			$start_state = $scanner->get_state();
			if ($type == $parser->trigger($scanner) && $parser->scan($scanner))
				return true;
		}

		$scanner->set_state($start_state);

		return false;
	}
}

class WordParse extends Parser
{
	private $word;

	public function __construct($word = null, $name = null, $options = null)
	{
		parent::__construct($name, $options);
		$this->word = $word;
	}

	public function trigger(Scanner $scanner)
	{
		if ($scanner->token_type() != Scanner::WORD)
			return false;

		if (is_null($this->word))
			return true;

		return ($this->word == $scanner->token());
	}

	protected function do_scan(Scanner $scanner)
	{
		$ret = ($this->trigger($scanner));

		return $ret;
	}
}

class NumParse extends Parser
{
	private $num;

	public function __construct($num = null, $name = null, $options = null)
	{
		parent::__construct($name, $options);
		$this->num = $num;
	}

	public function trigger(Scanner $scanner)
	{
		if ($scanner->token_type() != Scanner::NUM)
			return false;

		if (is_null($this->num))
			return true;

		return ($this->num == $scanner->token());
	}

	protected function do_scan(Scanner $scanner)
	{
		$ret = ($this->trigger($scanner));

		return $ret;
	}
}

class OperatorParse extends Parser
{
	private $operator;

	public function __construct($operator = null, $name = null, $options = null)
	{
		parent::__construct($name, $options);
		$this->operator = $operator;
	}

	public function trigger(Scanner $scanner)
	{
		if ($scanner->token_type() != Scanner::OPERATOR)
			return false;

		if (is_null($this->operator))
			return true;

		return ($this->operator == $scanner->token());
	}

	protected function do_scan(Scanner $scanner)
	{
		return ($this->trigger($scanner));
	}
}

class InterpreterContext
{
	private $expressionstore = array();

	public function replace(Expression $exp, $value)
	{
		$this->expressionstore[$exp->get_key()] = $value;
	}

	public function lookup(Expression $exp)
	{
		return $this->expressionstore[$exp->get_key()];
	}
}

class ArithmeticExpression extends Expression
{
	const ADD = "+";
	const SUB = "-";
	const MULT = "*";
	const DIV = "/";

	private $l_op;
	private $r_op;
	private $op;

	public function __construct($l_op, $r_op, $op)
	{
		$this->l_op = $l_op;
		$this->r_op = $r_op;
		$this->op = $op;
	}

	public function interpret(InterpreterContext $context)
	{
		if ($this->l_op instanceof ArithmeticExpression)
		{
			$this->l_op->interpret($context);
			$this->l_op = $context->lookup($this->l_op);
		}
		if ($this->r_op instanceof ArithmeticExpression)
		{
			$this->r_op->interpret($context);
			$this->r_op = $context->lookup($this->r_op);
		}

		switch ($this->op) {
			case self::ADD:
				$value = floatval($this->l_op) + floatval($this->r_op);
				break;
			case self::SUB:
				$value = floatval($this->l_op) - floatval($this->r_op);
				break;
			case self::MULT:
				$value = floatval($this->l_op) * floatval($this->r_op);
				break;
			case self::DIV:
				$value = floatval($this->l_op) / floatval($this->r_op);
				break;
			default:
				throw new Exception("unexpected arithmetic operator: <{$this->op}>");
		}

		$context->replace($this, $value);
	}
}

class ArithmeticHandler implements IHandler
{
	public function handle_match(Parser $parser, Scanner $scanner)
	{
		$r_op = $scanner->get_context()->pop_result();
		$op = $scanner->get_context()->pop_result();
		$l_op = $scanner->get_context()->pop_result();
		$scanner->get_context()->push_result(new ArithmeticExpression($l_op, $r_op, $op));
	}
}

class ConstExpHandler implements IHandler
{
	public function handle_match(Parser $parser, Scanner $scanner)
	{
		$float_const = floatval($scanner->get_context()->pop_result());
		$scanner->get_context()->push_result($float_const);
	}
}

class FuncHandler implements IHandler
{
	public function handle_match(Parser $parser, Scanner $scanner)
	{
		$metric_id = intval(substr($scanner->get_context()->pop_result(), 1));
		$func_name = $scanner->get_context()->pop_result();
		$date = $scanner->get_context()->get_init_date();
		$ureports_obj = $scanner->get_context()->get_ureports_obj();
		switch ($func_name)
		{
			case 'avg':
				$value = $this->run_avg($metric_id, $date, $ureports_obj);
				break;
			case 'sum':
				$value = $this->run_sum($metric_id, $date, $ureports_obj);
				break;
			default :
				throw new Exception("invalid function name <{$func_name}>");
		}
		$scanner->get_context()->push_result($value);
	}

	private function run_avg($metric_id, $date, $ureports_obj)
	{
		return $ureports_obj->get_avg($metric_id, $date);
	}

	private function run_sum($metric_id, $date, $ureports_obj)
	{
		return $ureports_obj->get_sum($metric_id, $date);
	}
}

class OperatorHandler implements IHandler
{
	public function handle_match(Parser $parser, Scanner $scanner)
	{
		$op = $scanner->get_context()->pop_result();
		$scanner->get_context()->push_result($op);
	}
}

?>