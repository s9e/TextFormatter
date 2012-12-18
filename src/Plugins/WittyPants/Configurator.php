<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* This plugin combines some of the functionalities found in SmartyPants and Textile.
*
* @link http://daringfireball.net/projects/smartypants/
* @link http://textile.thresholdstate.com/
*/
class WittyPantsConfig extends ConfiguratorBase
{
	/**
	* @var string Name of attribute used to for the replacement
	*/
	protected $attrName = 'char';

	/**
	* @var string Name of the tag used to mark the text to replace
	*/
	protected $tagName = 'WP';

	/**
	* Plugin's setup
	*
	* Will initialize create the plugin's tag if it does not exist
	*/
	public function setUp()
	{
		if (!$this->configurator->tagExists($this->tagName))
		{
			$tag = $this->configurator->addTag($this->tagName);
			$tag->setAttribute($this->attrName);
			$tag->setTemplate('<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>');
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return array(
			'attrName' => $this->attrName,
			'regexp' => array(
				'apostrophe' => "#(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})|(?<=[0-9])'(?=s)#uS",
				// Covers the multiply sign and primes
				'numbers'    => '#[0-9](?:["\']? ?x(?= ?[0-9])|["\'])#S',
				'quotes'     => '#(?<![0-9\\pL])(["\']).+?\\1(?![0-9\\pL])#uS',
				'singletons' => '#(?:---?|\\.\\.\\.)#S',
				'symbols'    => '#\\((?:c|r|tm)\\)#i'
			),
			'replacements' => array(
				'apostrophe' => "\xE2\x80\x99",
				'multiply'   => "\xC3\x97",
				'primes' => array(
					"'"   => "\xE2\x80\xB2",
					'"'   => "\xE2\x80\xB3"
				),
				'quotes' => array(
					"'" => array("\xE2\x80\x98", "\xE2\x80\x99"),
					'"' => array("\xE2\x80\x9C", "\xE2\x80\x9D")
				),
				'singletons' => array(
					'--'  => "\xE2\x80\x93",
					'---' => "\xE2\x80\x94",
					'...' => "\xE2\x80\xA6"
				),
				'symbols' => array(
					'(tm)' => "\xE2\x84\xA2",
					'(r)'  => "\xC2\xAE",
					'(c)'  => "\xC2\xA9"
				)
			),
			'tagName' => $this->tagName
		);
	}
}