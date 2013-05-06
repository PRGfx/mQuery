<?php
namespace ManiaQuery;

/**
 * Charakterizing Maniascript functions
 */
class ManiascriptFunction
{
		
	private $type;
	private $name;
	private $arguments;
	private $body;

	function __construct($type, $name, $arguments, $body)
	{
		$this->type = $type;
		$this->name = $name;
		$this->arguments = $arguments;
		$this->body = $body;
	}

	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function setArguments($arguments) {
		if(!is_array($arguments))
			$arguments = preg_split('/, ?/', $arguments);
		$this->arguments = $arguments;
		return $this;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getType() {
		return $this->type;
	}

	public function getName() {
		return $this->name;
	}

	public function getArguments() {
		return $this->arguments;
	}

	public function getBody() {
		return $this->body;
	}

	public function toString() {
		return $this->type . " " . $this->name . "(" . implode(', ', $this->arguments) . ") {\n" . $this->body . "\n}";
	}
}
?>