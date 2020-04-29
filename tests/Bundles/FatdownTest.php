<?php

namespace s9e\TextFormatter\Tests\Bundles;

/**
* @covers s9e\TextFormatter\Bundles\Fatdown
* @covers s9e\TextFormatter\Bundles\Fatdown\Renderer
*/
class FatdownTest extends AbstractTest
{
	protected function postprocessActualHtml(string $html): string
	{
		return preg_replace('(data-task-id="\\K\\w++)', '...', $html);
	}
}