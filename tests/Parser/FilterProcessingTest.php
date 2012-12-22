<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser\FilterProcessing;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\FilterProcessing
*/
class FilterProcessingTest extends Test
{
	/**
	* @testdox
	*/
	public function testRegisterVar()
	{
		$dummy = new FilterProcessingDummy;
		$dummy->registerVar('foo', 'bar');

		$this->assertSame(
			array('foo' => 'bar'),
			$dummy->registeredVars
		);
	}
}

class FilterProcessingDummy
{
	use FilterProcessing;

	public $registeredVars = array();
	public $tagsConfig = array(
	);

	public function __construct()
	{
	}
}