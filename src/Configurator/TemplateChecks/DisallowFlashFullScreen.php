<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

class DisallowFlashFullScreen extends AbstractFlashRestriction
{
	/**
	* @var string Default allowFullScreen setting
	* @link http://help.adobe.com/en_US/ActionScript/3.0_ProgrammingAS3/WS5b3ccc516d4fbf351e63e3d118a9b90204-7c5d.html
	*/
	public $defaultSetting = 'false';

	/**
	* {@inheritdoc}
	*/
	protected $settingName = 'allowFullScreen';

	/**
	* @var array Valid allowFullScreen values
	*/
	protected $settings = [
		'true'  => 1,
		'false' => 0
	];

	/**
	* Constructor
	*
	* @param  bool   $onlyIfDynamic Whether this restriction applies only to elements using any kind
	*                               of dynamic markup: XSL elements or attribute value templates
	*/
	public function __construct($onlyIfDynamic = false)
	{
		parent::__construct('false', $onlyIfDynamic);
	}
}