<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use DOMDocument,
    s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

class EmoticonsConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'E';

	/**
	* @var array
	*/
	protected $emoticons = array();

	public function setUp()
	{
		$this->cb->addTag($this->tagName, array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
	}

	/**
	* Add an emoticon
	*
	* @param string $code Emoticon code
	* @param string $tpl  Emoticon template, e.g. <img src="emot.png"/> -- must be well-formed XML
	*/
	public function addEmoticon($code, $tpl)
	{
		$this->addEmoticons(array($code => $tpl));
	}

	/**
	* Add several emoticons at once
	*
	* Recommended for performance if you want to create several emoticons
	*
	* @param array $emoticons Emoticon's code as key and the corresponding template as value
	*/
	public function addEmoticons(array $emoticons)
	{
		foreach ($emoticons as $code => $tpl)
		{
			$this->emoticons[$code] = $tpl;
		}

		$this->updateXSL();
	}

	/**
	* @return array
	*/
	public function getConfig()
	{
		if (empty($this->emoticons))
		{
			return false;
		}

		// Non-anchored pattern, will benefit from the S modifier
		$regexp = '#' . ConfigBuilder::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return array(
			'tagName' => $this->tagName,
			'regexp'  => $regexp
		);
	}

	/**
	* Commit to ConfigBuilder the XSL needed to render emoticons
	*/
	protected function updateXSL()
	{
		$tpls = array();
		foreach ($this->emoticons as $code => $tpl)
		{
			$tpls[$tpl][] = $code;
		}

		/**
		* Create a temporary stylesheet
		*/
		$dom = new DOMDocument;
		$dom->loadXML('<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" />');

		$template = $dom->documentElement->appendChild(
			$dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:template')
		);
		$template->setAttribute('match', $this->tagName);

		/**
		* Iterate over codes, replace codes with their representation as a string (with quotes)
		* and create variables as needed
		*/
		foreach ($tpls as $tpl => &$codes)
		{
			foreach ($codes as &$code)
			{
				if (strpos($code, "'") === false)
				{
					$code = "'" . $code . "'";
				}
				elseif (strpos($code, '"') === false)
				{
					$code = '"' . $code . '"';
				}
				else
				{
					// this code contains both ' and " so we store its content in a variable
					$id = uniqid();

					$template->appendChild(
						$dom->createElementNS(
							'http://www.w3.org/1999/XSL/Transform',
							'xsl:variable',
							$code
						)
					)->setAttribute('name', 'e' . $id);

					$code = '$e' . $id;
				}
			}
			unset($code);
		}
		unset($codes);

		$choose = $template->appendChild(
			$dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:choose')
		);

		foreach ($tpls as $tpl => $codes)
		{
			$when = $choose->appendChild(
				$dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:when')
			);
			$when->setAttribute('test', '.=' . implode(' or .=', $codes));

			$frag = $dom->createDocumentFragment();
			$frag->appendXML($tpl);
			$when->appendChild($frag);
		}

		$choose
			->appendChild(
				$dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:otherwise')
			)
			->appendChild(
				$dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:value-of')
			)
			->setAttribute('select', '.');

		$this->cb->setTagXSL($this->tagName, $dom->saveXML($template));
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/EmoticonsParser.js');
	}
}