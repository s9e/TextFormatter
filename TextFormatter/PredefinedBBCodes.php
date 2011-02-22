<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

class PredefinedBBCodes
{
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	public function addB()
	{
		$this->cb->addBBCodeFromExample('[B]{TEXT}[/B]', '<strong>{TEXT}</strong>');
	}

	public function addI()
	{
		$this->cb->addBBCodeFromExample('[I]{TEXT}[/I]', '<em>{TEXT}</em>');
	}

	public function addU()
	{
		$this->cb->addBBCodeFromExample(
			'[U]{TEXT}[/U]',
			'<span style="text-decoration: underline">{TEXT}</span>'
		);
	}

	public function addS()
	{
		$this->cb->addBBCodeFromExample(
			'[S]{TEXT}[/S]',
			'<span style="text-decoration: line-through">{TEXT}</span>'
		);
	}

	/**
	* Polymorphic URL tag with optional support for the "title" attribute
	*
	* [URL]http://www.example.org[/URL]
	* [URL=http://www.example.org]example.org[/URL]
	* [URL title="The best site ever"]http://www.example.org[/URL]
	*/
	public function addURL()
	{
		$this->cb->addBBCode('URL', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));

		$this->cb->addBBCodeParam('URL', 'url', 'url');
		$this->cb->addBBCodeParam('URL', 'title', 'text', array('is_required' => false));

		$this->cb->setBBCodeTemplate(
			'URL',
			'<a href="{@url}"><xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>'
		);
	}

/**
	public function addQUOTE($nestingLevel = 3)
	{
	}
/**/
}