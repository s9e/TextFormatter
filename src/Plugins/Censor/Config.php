<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\ConfigBuilder;
use s9e\TextFormatter\ConfigBuilder\Tag;
use s9e\TextFormatter\Plugins\Config as PluginConfig;

class CensorConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'C';

	/**
	* @var string Name of attribute used for the replacement
	*/
	protected $attrName = 'with';

	/**
	* @var string Default string used to replace censored words
	*/
	protected $defaultReplacement = '****';

	/**
	* @var array  2D array of censored words/masks. First dimension is the type of masks
	*/
	protected $words = array();

	/**
	* @var array  Hash of replacements
	*/
	protected $replacements = array();

	public function setUp()
	{
		if ($this->cb->tagExists($this->tagName))
		{
			return;
		}

		$tag = new Tag(array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
		$this->cb->addTag($this->tagName, $tag);

		$tag->addAttribute($this->attrName)->required = false;

		$tag->setTemplate(
			'<xsl:choose>' .
				'<xsl:when test="@' . htmlspecialchars($this->attrName) . '">' .
					'<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>' .
				'</xsl:when>' .
				'<xsl:otherwise>' .
					htmlspecialchars($this->defaultReplacement) .
				'</xsl:otherwise>' .
			'</xsl:choose>'
		);
	}

	/**
	* Add a word to the censor list
	*
	* @param string $word
	* @param string $replacement If left null, $this->defaultReplacement will be used
	*/
	public function addWord($word, $replacement = null)
	{
		$this->words[] = $word;

		if (isset($replacement))
		{
			$mask = '#^'
			      . strtr(
			        	preg_quote($word, '#'),
			        	array(
			        		'\\*' => '.*',
			        		'\\?' => '.?'
			        	)
			        )
			      . '$#iDu';

			$this->replacements[$mask] = $replacement;
		}
	}

	public function getConfig()
	{
		if (empty($this->words))
		{
			return false;
		}

		$regexp = $this->cb->getRegexpHelper()->buildRegexpFromList(
			$this->words,
			array('specialChars' => array('*' => '\\pL*', '?' => '.?'))
		);

		$config = array(
			'tagName'  => $this->tagName,
			'attrName' => $this->attrName,
			'regexp'   => '#\\b' . $regexp . '\\b#iu'
		);

		if (!empty($this->replacements))
		{
			$config['replacements'] = $this->replacements;
		}

		return $config;
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSConfig()
	{
		$config = $this->getConfig();

		if (isset($config['replacements']))
		{
			$replacements = array();

			foreach ($config['replacements'] as $regexp => $replacement)
			{
				$replacements[] = array($regexp, $replacement);
			}

			$config['replacements'] = $replacements;
		}

		return $config;
	}

	public function getJSConfigMeta()
	{
		return array(
			'isRegexp' => array(
				array('replacements', true, 0)
			)
		);
	}

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/CensorParser.js');
	}
}