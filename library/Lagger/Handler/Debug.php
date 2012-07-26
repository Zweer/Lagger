<?php

/**
 *
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
namespace Lagger\Handler;

use Lagger\Handler;

class Debug extends Handler {

	const defaultTags = 'debug';

	public function handle($message = null, $tags = null) {
		if(!$tags) {
			$tags = self::defaultTags;
		}
		$this->handleActions(array('message' => $message, 'type' => $tags), $tags);
	}

	protected function isTagsMatches($eventTags, $incTags, $excTags) {
		return (!$excTags || !array_intersect($eventTags, $excTags)) && (!$incTags || count(array_intersect($incTags, $eventTags)) == count($incTags));
	}
}
