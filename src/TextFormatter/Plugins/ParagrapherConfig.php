<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\PluginConfig;

class ParagrapherConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used for paragraphs
	*/
	protected $paragraphTagName = 'P';

	/**
	* @var string|boolean Name of the tag used for single linebreaks, or FALSE to disable linebreaks
	*/
	protected $linebreakTagName = 'BR';

	public function setUp()
	{
		if (!$this->cb->tagExists($this->paragraphTagName))
		{
			$this->cb->addTag(
				$this->paragraphTagName,
				array(
					'trimBefore'   => true,
					'trimAfter'    => true,
					'ltrimContent' => true,
					'rtrimContent' => true,

					'rules' => array(
						'closeParent' => array($this->paragraphTagName)
					),

					'template' =>
						'<xsl:if test="normalize-space(.)"><p><xsl:apply-templates/></p></xsl:if>'
				)
			);
		}

		if ($this->linebreakTagName && !$this->cb->tagExists($this->linebreakTagName))
		{
			$this->cb->addTag(
				$this->linebreakTagName,
				array(
					'trimBefore'   => true,
					'trimAfter'    => true,
					'ltrimContent' => true,
					'rtrimContent' => true,

					'defaultDescendantRule' => 'deny',

					'template' => '<br/>'
				)
			);
		}
	}

	public function getConfig()
	{
		return array(
			'regexp' => '#^\\s*|\\n[\\r\\n]*#',
			'paragraphTagName' => $this->paragraphTagName,
			'linebreakTagName' => $this->linebreakTagName
		);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/ParagrapherParser.js');
	}
}