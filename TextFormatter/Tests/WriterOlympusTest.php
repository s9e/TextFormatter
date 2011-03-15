<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
	s9e\Toolkit\TextFormatter\Parser;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class WriterOlympusTest extends \PHPUnit_Framework_TestCase
{
	public function testQuotesAndLists()
	{
		$parser = new OlympusParser;

		$text     = "[list][*][quote]\n[*][quote]test[/quote][/quote]:)[/list]";
		$actual   = $parser->parse($text);
		$expected =
			"[list:01234567][*:01234567][quote:01234567]\n[*][quote:01234567]test[/quote:01234567][/quote:01234567]<!-- s:) -->".'<img src="{SMILIES_PATH}/icon_e_smile.gif" alt=":)" title="Smile" /><!-- s:) -->[/*:m:01234567][/list:01234567]';

		$this->assertSame($expected, $actual);
	}
}

/**
* Obviously this is a very crude "parser". It only supports 3 tags, doesn't handle nl2br() doesn't
* follow the custom escaping rules required for [code] tags, etc...
*/
class OlympusParser extends Parser
{
	protected $smilies = array();

	public function __construct()
	{
		$cb = new ConfigBuilder;

		/**
		* Add BBCodes
		*/
		$cb->addBBCode('quote', array(
			'defaultParam' => 'author',
			'nestingLimit' => 10
		));
		$cb->addBBCodeParam('quote', 'author', 'text', array('isRequired' => false));

		$cb->addBBCode('list', array(
			'defaultParam' => 'type'
		));
		$cb->addBBCodeParam('list', 'type', 'simpletext', array('isRequired' => false));

		$cb->addBBCode('li');
		$cb->addBBCodeAlias('li', '*');
		$cb->addBBCodeRule('li', 'requireParent', 'list');
		$cb->addBBCodeRule('li', 'closeParent', 'li');

		/**
		* Add smilies
		*/
		$cb->addEmoticon(':)', '');
		$this->smilies[':)'] =
			'<!-- s:) --><img src="{SMILIES_PATH}/icon_e_smile.gif" alt=":)" title="Smile" /><!-- s:) -->';

		$cb->addEmoticon(':lol:', '');
		$this->smilies[':lol:'] =
			'<!-- s:lol: --><img src="{SMILIES_PATH}/icon_lol.gif" alt=":lol:" title="Laughing" /><!-- s:lol: -->';

		$cb->addBBCode('smiley', array('internal_use' => true));
		$cb->setEmoticonOption('bbcode', 'smiley');

		/**
		* Now construct our parent
		*/
		parent::__construct($cb->getParserConfig());
	}

	/**
	* Overwrite the default output method
	*/
	protected function output()
	{
		$out = '';
		$pos = 0;

		$inCode = 0;

		foreach ($this->tags as $tag)
		{
			$out .= htmlspecialchars(substr($this->text, $pos, $tag['pos'] - $pos));

			$pos = $tag['pos'] + $tag['len'];

			switch ($tag['name'])
			{
				case 'SMILEY':
					$code = substr($this->text, $tag['pos'], $tag['len']);
					$out .= $this->smilies[$code];
					break;

				case 'LI':
					$tag['name'] = '*';

					if (!$tag['len'])
					{
						$tag['name'] .= ':m';
					}
					// no break; here

				default:
					$out .= '['
					      . (($tag['type'] === Parser::END_TAG) ? '/' : '')
						  . strtolower($tag['name'])
						  . ':01234567]';
			}
		}

		return $out . htmlspecialchars(substr($this->text, $pos));
	}
}