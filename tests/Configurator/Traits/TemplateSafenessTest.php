<?php

namespace s9e\TextFormatter\Tests\Configurator\Traits;

use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Traits\TemplateSafeness
*/
class TemplateSafenessTest extends Test
{
	/**
	* @testdox isSafeAsURL() returns false by default
	*/
	public function testIsSafeAsURLDefault()
	{
		$item = new DummyItem;
		$this->assertFalse($item->isSafeAsURL());
	}

	/**
	* @testdox isSafeAsURL() returns true if markAsSafeAsURL() was called
	*/
	public function testIsSafeAsURLMarked()
	{
		$item = new DummyItem;
		$item->markAsSafeAsURL();
		$this->assertTrue($item->isSafeAsURL());
	}

	/**
	* @testdox isSafeInCSS() returns false by default
	*/
	public function testIsSafeInCSSDefault()
	{
		$item = new DummyItem;
		$this->assertFalse($item->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if markAsSafeInCSS() was called
	*/
	public function testIsSafeInCSSMarked()
	{
		$item = new DummyItem;
		$item->markAsSafeInCSS();
		$this->assertTrue($item->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false by default
	*/
	public function testIsSafeInJSDefault()
	{
		$item = new DummyItem;
		$this->assertFalse($item->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if markAsSafeInJS() was called
	*/
	public function testIsSafeInJSMarked()
	{
		$item = new DummyItem;
		$item->markAsSafeInJS();
		$this->assertTrue($item->isSafeInJS());
	}

	/**
	* @testdox markAsSafeAsURL() is chainable
	*/
	public function testMarkAsSafeAsURLChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeAsURL());
	}

	/**
	* @testdox markAsSafeInCSS() is chainable
	*/
	public function testMarkAsSafeInCSSChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeInCSS());
	}

	/**
	* @testdox markAsSafeInJS() is chainable
	*/
	public function testMarkAsSafeInJSChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeInJS());
	}

	/**
	* @testdox resetSafeness() resets the contexts marked as safe
	*/
	public function testResetSafeness()
	{
		$item = new DummyItem;
		$item->markAsSafeAsURL();
		$item->markAsSafeInCSS();
		$item->markAsSafeInJS();
		$item->resetSafeness();

		$this->assertFalse($item->isSafeAsURL());
		$this->assertFalse($item->isSafeInCSS());
		$this->assertFalse($item->isSafeInJS());
	}

	/**
	* @testdox resetSafeness() is chainable
	*/
	public function testResetSafenessChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->resetSafeness());
	}
}

class DummyItem
{
	/**
	* @var array Contexts in which this object is considered safe to be used
	*/
	protected $markedSafe = array();

	/**
	* Return whether this object is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test whether this attribute was marked as safe in given context
		return !empty($this->markedSafe[$context]);
	}

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return self
	*/
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return self
	*/
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return self
	*/
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = true;

		return $this;
	}

	/**
	* Reset the "marked safe" statuses
	*
	* @return self
	*/
	public function resetSafeness()
	{
		$this->markedSafe = array();

		return $this;
	}

}