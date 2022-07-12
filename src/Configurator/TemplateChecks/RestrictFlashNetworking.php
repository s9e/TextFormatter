<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

class RestrictFlashNetworking extends AbstractFlashRestriction
{
	/**
	* @var string Default AllowNetworking setting
	* @link http://help.adobe.com/en_US/ActionScript/3.0_ProgrammingAS3/WS1EFE2EDA-026D-4d14-864E-79DFD56F87C6.html
	*/
	public $defaultSetting = 'all';

	/**
	* {@inheritdoc}
	*/
	protected $settingName = 'allowNetworking';

	/**
	* @var array Valid AllowNetworking values
	*/
	protected $settings = [
		'all'      => 3,
		'internal' => 2,
		'none'     => 1
	];
}