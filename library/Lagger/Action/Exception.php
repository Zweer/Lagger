<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
namespace Lagger\Action;

use Lagger\Action;
use Lagger\Handler;

class Exception extends Action{

	protected $messageTemplate;
	
	public function __construct($messageTemplate = null) {
		$this->messageTemplate = $messageTemplate ? $messageTemplate : '{message}';
	}

	protected function make() {
		Handler::$skipNexInternalException = true;
		throw new \ErrorException($this->eventspace->fetch($this->messageTemplate), (int)$this->eventspace->code, 0, $this->eventspace->file, $this->eventspace->line);
	}
}