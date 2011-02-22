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
	* Polymorphic URL tag
	*
	* [URL]http://www.example.org[/URL]
	* [URL=http://www.example.org]example.org[/URL]
	*/
	public function addURL()
	{
		$this->cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));

		$this->cb->addBBCodeParam('url', 'url', 'url');

		$this->cb->setBBCodeTemplate('url', '<a href="{@url}"><xsl:apply-templates/></a>');
	}

/**
	public function addQUOTE($nestingLevel = 3)
	{
	}
/**/
}