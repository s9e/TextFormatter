<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;

interface TargetedRulesGenerator
{
	/**
	* Generate targeted rules that apply to given template forensics
	*
	* @param  TemplateForensics $src Source template forensics
	* @param  TemplateForensics $trg Target template forensics
	* @return array                  List of rules that apply from the source template to the target
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg);
}