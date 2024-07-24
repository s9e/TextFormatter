<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers;

use s9e\TextFormatter\Configurator;

abstract class AbstractSiteHelper
{
	public function __construct(protected Configurator $configurator)
	{
	}
}