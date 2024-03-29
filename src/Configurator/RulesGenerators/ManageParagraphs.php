<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class ManageParagraphs implements BooleanRulesGenerator
{
	/**
	* @var TemplateInspector
	*/
	protected $p;

	/**
	* Constructor
	*
	* Prepares the TemplateInspector for <p/>
	*/
	public function __construct()
	{
		$this->p = new TemplateInspector('<p><xsl:apply-templates/></p>');
	}

	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateInspector $src)
	{
		$rules = [];

		if ($src->allowsChild($this->p) && $src->isBlock() && !$this->p->closesParent($src))
		{
			$rules['createParagraphs'] = true;
		}

		if ($src->closesParent($this->p))
		{
			$rules['breakParagraph'] = true;
		}

		return $rules;
	}
}