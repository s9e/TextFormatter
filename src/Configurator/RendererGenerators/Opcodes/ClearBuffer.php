<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\Opcodes;

class ClearBuffer
{
	/**
	* @var int
	*/
	protected int $bufferId;

	public function __construct(int $bufferId, string $select)
	{
		$this->bufferId = $bufferId;
	}
}