<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers;

use s9e\RegexpBuilder\Builder;

class BlueskyHelper extends AbstractConfigurableHostHelper
{
	protected Builder $builder;

	public function addHosts(array $hosts): void
	{
		parent::addHosts($hosts);

		if (!isset($this->builder))
		{
			$this->builder = new Builder;
		}

		$siteId = $this->getSiteId();
		$hosts  = $this->getHosts();

		$this->configurator->tags[$siteId]->attributes['embedder']->filterChain[0]->setRegexp(
			'/^(?:[-\w]*\.)*' . $this->builder->build($hosts) . '$/'
		);
	}

	protected function getSiteId(): string
	{
		return 'bluesky';
	}
}