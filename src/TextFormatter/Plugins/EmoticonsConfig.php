<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

class EmoticonsConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'E';

	/**
	* @var bool   Whether to update this plugin's XSL after each new addition
	*/
	protected $autoUpdate = true;

	/**
	* @var array
	*/
	protected $emoticons = array();

	public function setUp()
	{
		$this->cb->addTag($this->tagName, array(
			'defaultRule' => 'deny'
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
		$this->emoticons[$code] = $tpl;

		if ($this->autoUpdate)
		{
			$this->commitXSL();
		}
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
			'regexp' => $regexp
		);
	}

	/**
	* Commit to ConfigBuilder the XSL needed to render emoticons
	*/
	public function commitXSL()
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

		$this->cb->setTagTemplate($this->tagName, $xsl);
	}

	/**
	* Disable the automatic update of this plugin's XSL after each addition
	*/
	public function disableAutoUpdate()
	{
		$this->autoUpdate = false;
	}

	/**
	* Enable the automatic update of this plugin's XSL after each addition
	*/
	public function enableAutoUpdate()
	{
		$this->autoUpdate = true;
	}
}