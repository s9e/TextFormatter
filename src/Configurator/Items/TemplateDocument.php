<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\SweetDOM\Document;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;

class TemplateDocument extends Document
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
		parent::__construct();

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