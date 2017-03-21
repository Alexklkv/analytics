<?php

/**
 * Реализует экспорт данных с Аналитики
 *
 * @uses ObjectLog
 * @uses StringBuilder
 *
 * @version 1.0.0
 */

class ObjectExport extends Object
{
	public $title;

	public $has_formulas = true;

	private $data = array();
	private $columns = array();
	private $active_columns = array();
	private $sub_titles = array();

	private $xls_data = array();

	private $workbook;
	private $table;

	private $index;
	private $row_counter = 0;
	private $current_row;
	private $row_start;

	public function set_defaults($title)
	{
		$this->title = $title;

		$xml = new StringBuilder("xml", 2);
		$this->xls_data[] = $xml->set_attribute("version", "1.0");

		$mso = new StringBuilder("mso-application", 2);
		$this->xls_data[] = $mso->set_attribute("progid", "Excel.Sheet");

		$this->workbook = new StringBuilder("Workbook");
		$this->workbook->set_attribute("xmlns", "urn:schemas-microsoft-com:office:spreadsheet");
		$this->workbook->set_attribute("xmlns:o", "urn:schemas-microsoft-com:office:office");
		$this->workbook->set_attribute("xmlns:x", "urn:schemas-microsoft-com:office:excel");
		$this->workbook->set_attribute("xmlns:c", "urn:schemas-microsoft-com:office:component:spreadsheet");
		$this->workbook->set_attribute("xmlns:ss", "urn:schemas-microsoft-com:office:spreadsheet");
		$this->workbook->set_attribute("xmlns:html", "http://www.w3.org/TR/REC-html40");

		$this->set_styles();

		return $this;
	}

	public function get_xls()
	{
		if (empty($this->columns))
			$this->Log->error("Empty columns for export");
		if (empty($this->data))
			$this->Log->error("Empty data for export");

		reset($this->columns);
		while (list($key, $columns) = each($this->columns))
		{
			if (!isset($this->data[$key]))
				continue;

			$title = $this->title;
			if (isset($this->sub_titles[$key]))
				$title = $this->sub_titles[$key];

			$this->create_table($title);

			$this->index = $key;
			$this->set_columns();

			$this->row_start = $this->row_counter + 1;

			$this->set_data();
			$this->set_formulas();
		}

		$this->xls_data[] = $this->workbook;

		$xls_string = "";
		while (list(, $xml) = each($this->xls_data))
			$xls_string .= $xml->generate_string();

		return $xls_string;
	}

	public function add_data($data)
	{
		$this->data[] = $data;
	}

	public function add_data_row($block, $data)
	{
		if (!isset($this->data[$block]))
			$this->data[$block] = array();
		$this->data[$block][] = $data;
	}

	public function add_title($block, $title)
	{
		$this->sub_titles[$block] = $title;
	}

	public function add_column($data_block, $label, $data_name, $format_data = false, $order = false)
	{
		if (!isset($this->columns[$data_block]))
			$this->columns[$data_block] = array();
		$columns = &$this->columns[$data_block];

		if ($order === false)
			$order = count($columns);
		else
		{
			reset($columns);
			while (list($key) = each($columns))
			{
				if ($columns[$key]['order'] < $order)
					continue;

				$columns[$key]['order'] += 1;
			}
		}

		$columns[$data_name] = array('name' => $label, 'order' => $order, 'format_data' => $format_data);
	}

	private function add_row()
	{
		$this->row_counter += 1;
		$this->current_row = $this->table->add_child("Row");
	}

	private function add_cells($data)
	{
		while (list(, $value) = each($data))
			$this->add_cell($value['value'], $value['type'], $value['options']);
	}

	private function set_data()
	{
		$index = $this->index;
		$data = &$this->data[$index];
		$columns = &$this->columns[$index];
		$empty = $this->fill_empty($columns);
		$old_length = array();

		while (list($key, $rows) = each($data))
		{
			$ordered = $empty;
			$this->add_row();

			reset($columns);
			while (list($ckey, $column) = each($columns))
			{
				if (!isset($rows[$ckey]))
					continue;

				$value = $rows[$ckey];
				$ordered[$column['order']] = $this->format_data($value, $column['format_data']);

				$length = mb_strlen($value) * 5;
				if (!isset($old_length[$ckey]))
					$old_length[$ckey] = $this->active_columns[$ckey]->get_attribute("ss:Width");

				if ($length < $old_length[$ckey])
					continue;

				$this->active_columns[$ckey]->set_attribute("ss:Width", $length);
				$old_length[$ckey] = $length;
			}

			$this->add_cells($ordered);
		}
	}

	private function set_columns()
	{
		$this->active_columns = array();

		$columns = &$this->columns[$this->index];

		reset($columns);
		while (list($key, $column) = each($columns))
			$this->active_columns[$key] = $this->table->add_child("Column", 1)->set_attribute("ss:AutoFitWidth", 1)->set_attribute("ss:Hidden", 0)->set_attribute("ss:Width", mb_strlen($column['name']) * 8);

		$this->add_row();

		reset($columns);
		while (list(, $column) = each($columns))
			$this->add_cell($column['name'], "String", array('style' => "bold-center"));
	}

	private function set_formulas()
	{
		if ($this->has_formulas === false)
			return;

		$skip = true;
		$columns = $this->columns[$this->index];
		$this->add_row();

		reset($columns);
		while (list(, $column) = each($columns))
		{
			if ($skip === true)
			{
				$this->add_cell("Сумма", "String", array('style' => "bold"));
				$skip = false;
				continue;
			}

			$this->add_cell("1", "Number", array('formula' => "=SUM(R[".($this->row_start - $this->row_counter)."]C:R[-1]C)"));
		}

		$skip = true;
		$this->add_row();

		reset($columns);
		while (list(, $column) = each($columns))
		{
			if ($skip === true)
			{
				$this->add_cell("Среднее", "String", array('style' => "bold"));
				$skip = false;
				continue;
			}

			$this->add_cell("1", "Number", array('formula' => "=ROUND(AVERAGE(R[".($this->row_start - $this->row_counter)."]C:R[-2]C), 2)"));
		}
	}

	private function add_cell($value, $type = "String", $options = false)
	{
		$cell = $this->current_row->add_child("Cell");
		$cell->add_child("Data", 0, $value)->set_attribute("ss:Type", $type);

		if ($options === false)
			return;

		if (isset($options['style']))
			$cell->set_attribute("ss:StyleID", $options['style']);
		if (isset($options['merge_across']))
			$cell->set_attribute("ss:MergeAcross", $options['merge_across']);
		if (isset($options['formula']))
			$cell->set_attribute("ss:Formula", $options['formula']);
	}

	private function format_data($value, $method = false)
	{
		if ($method === false)
			return array('value' => $value, 'type' => "Number", 'options' => false);
		if ($method == "text")
			return array('value' => $value, 'type' => "String", 'options' => false);
		if ($method == "date")
			return array('value' => date("Y-m-d\T00:00:00.000", $value), 'type' => "DateTime", 'options' => array('style' => "formated-date"));
		if ($method == "big_numbers")
		{
			if (intval($value) == $value && $value < 1000)
				return array('value' => $value, 'type' => "Number", 'options' => array('style' => "text-center"));
			return array('value' => $value, 'type' => "Number", 'options' => array('style' => "formated-number"));
		}

		return array('value' => $value, 'type' => "String", 'options' => false);
	}

	private function fill_empty($fill_with)
	{
		$filled = array();

		if (empty($fill_with))
			return false;

		reset($fill_with);
		while (list(, $data) = each($fill_with))
			$filled[$data['order']] = $this->format_data(0, $data['format_data']);

		return $filled;
	}

	private function create_table($title)
	{
		if (mb_strlen($title) > 31)
			$this->format_title($title);

		$worksheet = $this->workbook->add_child("Worksheet")->set_attribute("ss:Name", $title);
		$this->table = $worksheet->add_child("Table")->set_attribute("ss:DefaultColumnWidth", 70);
		$worksheet->add_child("WorksheetOptions")->set_attribute("xmlns", "urn:schemas-microsoft-com:office:excel");
	}

	private function format_title(&$title)
	{
		$words = explode(" ", $title);

		while (list($key, $word) = each($words))
		{
			if (!preg_match("/[а-яА-Я]/", $word) || mb_strlen($word) < 3)
				continue;

			$words[$key] = mb_substr($word, 0, 4).".";
		}

		$title = implode(" ", $words);
		$title = mb_substr($title, 0, 31);
	}

	private function set_styles()
	{
		$styles = $this->workbook->add_child("Styles");
		$styles->add_child("Style")->set_attribute("ss:ID", "bold")->add_child("Font", 1)->set_attribute("ss:Bold", 1);
		$styles->add_child("Style")->set_attribute("ss:ID", "text-right")->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Right");
		$styles->add_child("Style")->set_attribute("ss:ID", "text-center")->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Center");
		$styles->add_child("Style")->set_attribute("ss:ID", "text-left")->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Left");

		$style = $styles->add_child("Style")->set_attribute("ss:ID", "formated-date");
		$style->add_child("NumberFormat", 1)->set_attribute("ss:Format", "[$-FC19]dd\ mmmm\ yyyy\ \г\.;@");
		$style->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Center");

		$style = $styles->add_child("Style")->set_attribute("ss:ID", "formated-number");
		$style->add_child("NumberFormat", 1)->set_attribute("ss:Format", "[&gt;1000000]0.00,,&quot;кк&quot;;[&gt;1000]0.00,&quot;к&quot;;0.00;");
		$style->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Center");

		$style = $styles->add_child("Style")->set_attribute("ss:ID", "bold-right");
		$style->add_child("Font", 1)->set_attribute("ss:Bold", 1);
		$style->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Right");

		$style = $styles->add_child("Style")->set_attribute("ss:ID", "bold-center");
		$style->add_child("Font", 1)->set_attribute("ss:Bold", 1);
		$style->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Center");

		$style = $styles->add_child("Style")->set_attribute("ss:ID", "bold-left");
		$style->add_child("Font", 1)->set_attribute("ss:Bold", 1);
		$style->add_child("Alignment", 1)->set_attribute("ss:Horizontal", "Left");
	}
}

class StringBuilder
{
	private $tag = "";
	private $type = 0;
	private $content = false;
	private $text = "";
	private $parameters = array();

	private $result_string = "";

	public function __construct($tag, $type = false, $text = false)
	{
		$this->tag = $tag;

		if ($type !== false)
			$this->type = $type;
		if ($text !== false)
			$this->text = $text;
	}

	public function add_child($tag, $type = false, $text = false)
	{
		$child = new StringBuilder($tag, $type, $text);

		if ($this->content === false)
			$this->content = array();

		$this->content[] = $child;

		return $child;
	}

	public function set_attribute($name, $value)
	{
		$this->parameters[$name] = $value;

		return $this;
	}

	public function get_attribute($name)
	{
		if (!isset($this->parameters[$name]))
			return false;
		return $this->parameters[$name];
	}

	public function generate_string()
	{
		$this->generate_open();
		$this->generate_content();
		$this->generate_close();

		return $this->result_string;
	}

	private function generate_content()
	{
		if ($this->content === false)
		{
			$this->result_string .= $this->text;
			return;
		}

		reset($this->content);
		while (list(, $content) = each($this->content))
			$this->result_string .= $content->generate_string();
	}

	private function generate_open()
	{
		$output = "<";
		if ($this->type == 2)
			$output .= "?";

		$output .= $this->tag;

		reset($this->parameters);
		while (list($name, $value) = each($this->parameters))
			$output .= " ".$name."=\"".$value."\"";

		if ($this->type == 0)
			$output .= ">";
		if ($this->type == 1)
			$output .= "/>";
		if ($this->type == 2)
			$output .= "?>";

		$this->result_string .= $output;
	}

	private function generate_close()
	{
		if ($this->type != 0)
			return;

		$this->result_string .= "</".$this->tag.">";
	}

}

?>