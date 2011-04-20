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

	/**
	* @test
	*/
	public function Creates_an_URL_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('URL'));
	}

	/**
	* @test
	*/
	public function Creates_an_IMG_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('IMG'));
	}

	/**
	* @test
	*/
	public function Creates_a_DL_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('DL'));
	}

	/**
	* @test
	*/
	public function Creates_a_DT_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('DT'));
	}

	/**
	* @test
	*/
	public function Creates_a_DD_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('DD'));
	}

	/**
	* @test
	*/
	public function Creates_an_EM_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('EM'));
	}

	/**
	* @test
	*/
	public function Creates_an_I_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('I'));
	}

	/**
	* @test
	*/
	public function Creates_a_STRONG_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('STRONG'));
	}

	/**
	* @test
	*/
	public function Creates_a_B_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('B'));
	}

	/**
	* @test
	*/
	public function Creates_a_CITE_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('CITE'));
	}

	/**
	* @test
	*/
	public function Creates_a_DEL_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('DEL'));
	}

	/**
	* @test
	*/
	public function Creates_an_INS_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('INS'));
	}

	/**
	* @test
	*/
	public function Creates_a_SUPER_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('SUPER'));
	}

	/**
	* @test
	*/
	public function Creates_a_SUB_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('SUB'));
	}

	/**
	* @test
	*/
	public function Creates_a_CODE_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('CODE'));
	}

	/**
	* @test
	*/
	public function Creates_a_SPAN_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('SPAN'));
	}

	/**
	* @test
	*/
	public function Creates_a_NOPARSE_tag_by_default()
	{
		$this->assertTrue($this->cb->tagExists('NOPARSE'));
	}

	/**
	* @test
	*/
	public function Creates_an_ACRONYM_tag_by_default()
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