<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

class RestrictFlashScriptAccess extends AbstractFlashRestriction
{
	/**
	* @var string Default AllowScriptAccess setting
	* @link http://helpx.adobe.com/flash-player/kb/changes-allowscriptaccess-default-flash-player.html
	*/
	public $defaultSetting = 'sameDomain';

	/**
	* {@inheritdoc}
	*/
	protected $settingName = 'allowScriptAccess';

	/**
	* @var array Valid AllowScriptAccess values
	*/
	protected $settings = [
		'always'     => 3,
		'samedomain' => 2,
		'never'      => 1
	];
}