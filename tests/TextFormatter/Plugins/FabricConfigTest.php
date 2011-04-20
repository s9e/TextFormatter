<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\FabricConfig
*/
class FabricConfigTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Fabric');
	}

	public function testCreatesAnUrlTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('URL'));
	}

	public function testCreatesAnImgTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('IMG'));
	}

	public function testCreatesADlTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('DL'));
	}

	public function testCreatesADtTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('DT'));
	}

	public function testCreatesADdTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('DD'));
	}

	public function testCreatesAnEmTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('EM'));
	}

	public function testCreatesAnITagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('I'));
	}

	public function testCreatesAStrongTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('STRONG'));
	}

	public function testCreatesABTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('B'));
	}

	public function testCreatesACiteTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('CITE'));
	}

	public function testCreatesADelTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('DEL'));
	}

	public function testCreatesAnInsTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('INS'));
	}

	public function testCreatesASuperTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('SUPER'));
	}

	public function testCreatesASubTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('SUB'));
	}

	public function testCreatesACodeTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('CODE'));
	}

	public function testCreatesASpanTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('SPAN'));
	}

	public function testCreatesANoparseTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('NOPARSE'));
	}

	public function testCreatesAnAcronymTagByDefault()
	{
		$this->assertTrue($this->cb->tagExists('ACRONYM'));
	}

	public function testGeneratesARegexpForImagesAndLinks()
	{
		$config = $this->cb->Fabric->getConfig();
		$this->assertArrayHasKey('regexp', $config);
		$this->assertArrayHasKey('imagesAndLinks', $config['regexp']);
	}

	public function testGeneratesARegexpForBlockModifiers()
	{
		$config = $this->cb->Fabric->getConfig();
		$this->assertArrayHasKey('regexp', $config);
		$this->assertArrayHasKey('blockModifiers', $config['regexp']);
	}

	public function testGeneratesARegexpForPhraseModifiers()
	{
		$config = $this->cb->Fabric->getConfig();
		$this->assertArrayHasKey('regexp', $config);
		$this->assertArrayHasKey('phraseModifiers', $config['regexp']);
	}

	public function testGeneratesARegexpForAcronyms()
	{
		$config = $this->cb->Fabric->getConfig();
		$this->assertArrayHasKey('regexp', $config);
		$this->assertArrayHasKey('acronyms', $config['regexp']);
	}

	public function testGeneratesARegexpForTableRow()
	{
		$config = $this->cb->Fabric->getConfig();
		$this->assertArrayHasKey('regexp', $config);
		$this->assertArrayHasKey('tableRow', $config['regexp']);
	}
}