<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use function strtolower;
use s9e\TextFormatter\Configurator;

class MastodonHelper
{
	/**
	* @var Configurator
	*/
	protected Configurator $configurator;

	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	public function addHost(string $host): void
	{
		if (!isset($this->configurator->registeredVars['MediaEmbed.sites']['mastodon']))
		{
			$this->configurator->MediaEmbed->add('mastodon');
		}

		$host = strtolower($host);
		$this->configurator->registeredVars['MediaEmbed.hosts'][$host] = 'mastodon';
	}
}