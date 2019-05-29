<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;

class TemplateDocument extends DOMDocument
{
	/**
	* @var Template Template instance that created this document
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param Template Template instance that created this document
	*/
	public function __construct(Template $template)
	{
		$this->template = $template;
	}

	/**
	* Update the original template with this document's content
	*
	* @return void
	*/
	public function saveChanges()
	{
		$this->template->setContent(TemplateLoader::save($this));
	}
}