<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

class CensorConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'C';

	/**
	* @var string Name of attribute used to for the replacement
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
		$this->cb->addTag(
			$this->tagName,
			array(
				'defaultRule' => 'deny',

				'attrs' => array(
					$this->attrName => array(
						'type'       => 'text',
						'isRequired' => false
					)
				),

				'template' =>
					'<xsl:choose>' .
						'<xsl:when test="@' . htmlspecialchars($this->attrName) . '">' .
							'<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>' .
						'</xsl:when>' .
						'<xsl:otherwise>' .
							htmlspecialchars($this->defaultReplacement) .
						'</xsl:otherwise>' .
					'</xsl:choose>'
			)
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

		$regexp = ConfigBuilder::buildRegexpFromList(
			$this->words,
			array('*' => '\\pL*', '?' => '.?')
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
}