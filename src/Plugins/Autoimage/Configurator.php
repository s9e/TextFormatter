<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoimage;

use s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractConfigurator;

class Configurator extends AbstractConfigurator
{
	public    array  $fileExtensions = ['gif', 'jpeg', 'jpg', 'png', 'svg', 'svgz', 'webp'];
	protected        $attrName       = 'src';
	protected        $tagName        = 'IMG';

	protected function getTemplate(): string
	{
		return '<img src="{@' . $this->attrName . '}"/>';
	}
}