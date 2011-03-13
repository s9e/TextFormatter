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
		$this->cb->BBCode->add(static::$tagName, array(
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
	}

	/**
	* @return array
	*/
	public function getConfig()
	{
		$config = array();

		/**
		* Create a template for this BBCode.
		* If one already exists, we overwrite it. That's how we roll
		*/
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

		$this->cb->BBCode->setTemplate(static::$tagName, $xsl);

		// Non-anchored pattern, will benefit from the S modifier
		$config['regexp'] =
			'#' . ConfigBuilder::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return $config;
	}
}