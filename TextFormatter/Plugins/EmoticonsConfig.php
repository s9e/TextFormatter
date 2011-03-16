<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\PluginConfig;

class EmoticonsConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	static public $tagName = 'E';

	/**
	* @var array
	*/
	protected $emoticons = array();

	public function setUp()
	{
		$this->cb->addTag(static::$tagName, array(
			'defaultRule' => 'deny'
		));
	}

	/**
	* Add an emoticon
	*
	* @param string $code Emoticon code
	* @param string $tpl  Emoticon template, e.g. <img src="emot.png"/> -- must be well-formed XML
	*/
	public function add($code, $tpl)
	{
		$this->emoticons[$code] = $tpl;
		$this->commitXSL();
	}

	/**
	* @return array
	*/
	public function getConfig()
	{
		// Non-anchored pattern, will benefit from the S modifier
		$regexp = '#' . ConfigBuilder::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return array(
			'regexp' => $regexp;
		);
	}

	/**
	* Commit the XSL needed to render emoticons to ConfigBuilder
	*/
	protected function commitXSL()
	{
		$tpls = array();
		foreach ($this->emoticons as $code => $tpl)
		{
			$tpls[$tpl][] = $code;
		}

		$xsl = '<xsl:choose>';
		foreach ($tpls as $tpl => $codes)
		{
			$xsl .= '<xsl:when test=".=\'' . implode("' or .='", $codes) . '\'">'
			      . $tpl
			      . '</xsl:when>';
		}
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';

		$this->cb->setTagTemplate(static::$tagName, $xsl);
	}
}