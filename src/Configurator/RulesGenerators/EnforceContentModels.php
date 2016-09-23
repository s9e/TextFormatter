<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class EnforceContentModels implements BooleanRulesGenerator, TargetedRulesGenerator
{
	/**
	* @var TemplateForensics
	*/
	protected $br;

	/**
	* @var TemplateForensics
	*/
	protected $span;

	/**
	* Constructor
	*
	* Prepares the TemplateForensics for <br/> and <span>
	*/
	public function __construct()
	{
		$this->br   = new TemplateForensics('<br/>');
		$this->span = new TemplateForensics('<span><xsl:apply-templates/></span>');
	}

	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = [];
		if ($src->isTransparent())
		{
			$rules['isTransparent'] = true;
		}
		if (!$src->allowsChild($this->br))
		{
			$rules['preventLineBreaks'] = true;
			$rules['suspendAutoLineBreaks'] = true;
		}
		if (!$src->allowsDescendant($this->br))
		{
			$rules['disableAutoLineBreaks'] = true;
			$rules['preventLineBreaks'] = true;
		}

		return $rules;
	}

	/**
	* {@inheritdoc}
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		if (!$src->allowsChildElements())
		{
			// If this template does not allow child elements, we use the same content model as a
			// span element to allow for some fallback content if this template is disabled
			$src = $this->span;
		}

		$rules = [];
		if (!$src->allowsChild($trg))
		{
			$rules[] = 'denyChild';
		}
		if (!$src->allowsDescendant($trg))
		{
			$rules[] = 'denyDescendant';
		}

		return $rules;
	}
}