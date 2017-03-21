<?php

/**
 * Реализует отправку электронной почты
 *
 * @uses ObjectLog
 * @uses MailAttachment
 *
 * @version 1.0.0
 */

class ObjectMail extends Object
{
	private $to;
	private $from;
	private $subject;
	private $body;
	private $body_type;
	private $separator;
	private $attachments = array();
	private $headers = array();

	public function set_to($to)
	{
		$this->to = $to;

		return $this;
	}

	public function set_from($from)
	{
		$this->from = $from;

		return $this;
	}

	public function set_subject($subject)
	{
		$this->subject = $subject;

		return $this;
	}

	public function set_body($body, $type = "text/plain")
	{
		$this->body = $body;
		$this->body_type = $type;

		return $this;
	}

	public function add_user_header($header)
	{
		$this->headers[] = $header;

		return $this;
	}

	public function attach($data, $filename, $content_type)
	{
		try
		{
			$attachment = new MailAttachment($data, $filename, $content_type);
		}
		catch (Exception $e) {
			$this->Log->error($e->getMessage());
		}

		$this->attachments[] = $attachment;

		return $this;
	}

	public function send()
	{
		if (!mail($this->assemble_to(), $this->assemble_subject(), $this->assemble_body(), $this->assemble_headers()))
			$this->Log->error("PHP mail() error");
	}

	private function assemble_to()
	{
		if (!is_array($this->to))
			return $this->to;

		$to = array();
		$to_headers = array();
		while (list($key, $value) = each($this->to))
		{
			if (is_int($key))
			{
				$to[] = $value;
				continue;
			}

			$to_headers = "{$value} <{$key}>";
		}

		if (count($to_headers) > 0)
			$this->add_user_header("To: ".implode(", ", $to_headers));

		return implode(", ", $to);
	}

	private function assemble_subject()
	{
		if (empty($this->subject))
			return;

		return "=?UTF-8?B?".base64_encode($this->subject)."?=";
	}

	private function assemble_body()
	{
		$message = array();

		if (count($this->attachments) > 0)
		{
			$this->separator = $separator = md5(time());
			$message[] = "--".$this->separator;
		}
		$message[] = "Content-type: {$this->body_type}; charset=UTF-8";
		$message[] = "Content-Transfer-Encoding: base64".PHP_EOL;
		$message[] = chunk_split(base64_encode($this->body));

		if (count($this->attachments) > 0)
		{
			$message[] = $this->assemble_attachments();
			$message[] = "--".$this->separator."--";
		}

		return implode(PHP_EOL, $message);
	}

	private function assemble_headers()
	{
		$headers = array();

		if (is_array($this->from))
		{
			list($email, $name) = each($this->from);
			$headers[] = "From: {$name} <{$email}>";
		}
		else
		{
			$headers[] = "From: {$this->from}>";
		}

		$headers[] = "MIME-Version: 1.0";

		if (count($this->attachments) > 0)
			$headers[] = "Content-Type: multipart/mixed; boundary=\"".$this->separator."\"".PHP_EOL;

		return implode(PHP_EOL, $headers);
	}

	private function assemble_attachments()
	{
		$attachments = array();

		while (list(, $attachment) = each($this->attachments))
		{
			$attachments[] = $attachment->assemble($this->separator);
		}

		return implode(PHP_EOL, $attachments);
	}
}

class MailAttachment
{
	private $data;
	private $filename;
	private $content_type;

	public function __construct($data, $filename, $content_type)
	{
		$this->data = $data;
		$this->filename = $filename;
		$this->content_type = $content_type;

		$this->check();
	}

	public function assemble($separator)
	{
		$this->check();

		$attachment[] = "--".$separator;
		$attachment[] = "Content-type: {$this->content_type}; name=\"{$this->filename}\"";
		$attachment[] = "Content-Transfer-Encoding: base64";
		$attachment[] = "Content-Disposition: attachment; filename=\"{$this->filename}\"".PHP_EOL;
		$attachment[] = chunk_split(base64_encode($this->data));

		return implode(PHP_EOL, $attachment);
	}

	private function check()
	{
		if (empty($this->data) || empty($this->filename) || empty($this->content_type))
			throw new Exception("Empty data for email attachment");
	}
}

?>