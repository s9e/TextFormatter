<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\TaskLists;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Tag;

class Helper
{
	public static function filterListItem(Parser $parser, Tag $listItem, string $text): void
	{
		// Test whether the list item is followed by a task checkbox
		$pos  = $listItem->getPos() + $listItem->getLen();
		$pos += strspn($text, ' ', $pos);
		$str  = substr($text, $pos, 3);
		if (!preg_match('/\\[[ Xx]\\]/', $str))
		{
			return;
		}

		// Create a tag for the task and assign it a random ID
		$taskId    = uniqid();
		$taskState = ($str === '[ ]') ? 'unchecked' : 'checked';

		$task = $parser->addSelfClosingTag('TASK', $pos, 3);
		$task->setAttribute('id',    $taskId);
		$task->setAttribute('state', $taskState);

		$listItem->cascadeInvalidationTo($task);
	}

	/**
	* Return stats from a parsed representation
	*
	* @param  string $xml Parsed XML
	* @return array       Number of "checked" and "unchecked" tasks
	*/
	public static function getStats(string $xml): array
	{
		$stats = ['checked' => 0, 'unchecked' => 0];

		preg_match_all('((?<=<)TASK(?: [^=]++="[^"]*+")*? state="\\K\\w++)', $xml, $m);
		foreach ($m[0] as $state)
		{
			if (!isset($stats[$state]))
			{
				$stats[$state] = 0;
			}
			++$stats[$state];
		}

		return $stats;
	}

	/**
	* Mark given task checked in XML
	*
	* @param  string $xml Parsed XML
	* @param  string $id  Task's ID
	* @return string      Updated XML
	*/
	public static function checkTask(string $xml, string $id): string
	{
		return self::setTaskState($xml, $id, 'checked', 'x');
	}

	/**
	* Mark given task unchecked in XML
	*
	* @param  string $xml Parsed XML
	* @param  string $id  Task's ID
	* @return string      Updated XML
	*/
	public static function uncheckTask(string $xml, string $id): string
	{
		return self::setTaskState($xml, $id, 'unchecked', ' ');
	}

	/**
	* Change the state and marker of given task in XML
	*
	* @param  string $xml    Parsed XML
	* @param  string $id     Task's ID
	* @param  string $state  Task's state ("checked" or "unchecked")
	* @param  string $marker State marker ("x" or " ")
	* @return string         Updated XML
	*/
	protected static function setTaskState(string $xml, string $id, string $state, string $marker): string
	{
		return preg_replace_callback(
			'((?<=<)TASK(?: [^=]++="[^"]*+")*? id="' . preg_quote($id) . '"\\K([^>]*+)>[^<]*+(?=</TASK>))',
			function ($m) use ($state, $marker)
			{
				preg_match_all('( ([^=]++)="[^"]*+")', $m[1], $m);

				$attributes          = array_combine($m[1], $m[0]);
				$attributes['state'] = ' state="' . $state . '"';
				ksort($attributes);

				return implode('', $attributes) . '>[' . $marker . ']';
			},
			$xml
		);
	}
}