<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

class RestrictFlashScriptAccess extends AbstractFlashRestriction
{
	public $defaultSetting = 'sameDomain';

	protected $settingName = 'allowScriptAccess';

	protected $settings = [
		'always'     => 3,
		'samedomain' => 2,
		'never'      => 1
	];
}