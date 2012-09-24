<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\ConfigBuilder;
use s9e\TextFormatter\ConfigBuilder\RegexpHelper;
use s9e\TextFormatter\ConfigBuilder\TemplateHelper;
use s9e\TextFormatter\Plugins\Config as PluginConfig;

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
		$this->cb->tags->add($this->tagName, array(
			'defaultChildRule'      => 'deny',
			'defaultDescendantRule' => 'deny'
		));
	}

	/**
	* Add an emoticon
	*
	* @param string $code     Emoticon code
	* @param string $template Emoticon template, e.g. <img src="emot.png"/>
	*/
	public function addEmoticon($code, $template)
	{
		$this->emoticons[$code] = TemplateHelper::normalizeTemplate($template);
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

		$rm = new RegexpHelper;

		// Non-anchored pattern, will benefit from the S modifier
		$regexp = '#' . $rm->buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return array(
			'tagName' => $this->tagName,
			'regexp'  => $regexp
		);
	}

	/**
	* Generate the dynamic template that renders all emoticons
	*
	* @return string
	*/
	protected function getXSL()
	{
		$templates = array();
		foreach ($this->emoticons as $code => $template)
		{
			$templates[$template][] = $code;
		}

		$xsl = '<xsl:template match="' . $this->tagName . '">';

		// Iterate over codes, replace codes with their representation as a string (with quotes)
		// and create variables as needed
		foreach ($templates as $template => &$codes)
		{
			foreach ($codes as &$code)
			{
				if (strpos($code, "'") === false)
				{
					// :)  => <xsl:when test=".=':)'">
					$code = "'" . htmlspecialchars($code) . "'";
				}
				elseif (strpos($code, '"') === false)
				{
					// :') => <xsl:when test=".=&quot;:')&quot;">
					$code = '&quot;' . $code . '&quot;';
				}
				else
				{
					// This code contains both ' and " so we store its content in a variable
					$id = uniqid();

					$xsl .= '<xsl:variable name="e' . $id . '">'
					      . htmlspecialchars($code)
					      . '</xsl:variable>';

					$code = '$e' . $id;
				}
			}
			unset($code);
		}
		unset($codes);

		// Now build the <xsl:choose> node
		$xsl .= '<xsl:choose>';

		// Iterate over codes, create an <xsl:when> for each group of codes
		foreach ($templates as $template => $codes)
		{
			$xsl .= '<xsl:when test=".=' . implode(' or .=', $codes) . '">'
			      . $template
			      . '</xsl:when>';
		}

		// Finish it with an <xsl:otherwise> that displays the unknown codes as text
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>';

		return $xsl;
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/EmoticonsParser.js');
	}
}