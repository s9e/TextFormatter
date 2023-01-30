<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autovideo;

use s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractConfigurator;

class Configurator extends AbstractConfigurator
{
	public    array  $fileExtensions = ['mov', 'mp4', 'ogg', 'webm'];
	protected        $attrName       = 'src';
	protected        $tagName        = 'VIDEO';

	protected function getTemplate(): string
	{
		return '<video controls="" src="{@' . $this->attrName . '}"/>';
	}
}