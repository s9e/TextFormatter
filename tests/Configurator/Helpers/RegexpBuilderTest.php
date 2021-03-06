<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\RegexpBuilder
*/
class RegexpBuilderTest extends Test
{
	// Start of content generated by ../../../scripts/patchRegexpBuilderTest.php
	/**
	* @testdox fromList(['foo']) returns 'foo'
	*/
	public function test_2E6B327F()
	{
		$this->fromListTestCase(0);
	}

	/**
	* @testdox fromList(['foo', 'foo']) returns 'foo'
	*/
	public function test_EFC21F10()
	{
		$this->fromListTestCase(1);
	}

	/**
	* @testdox fromList(['FOO', 'foo']) returns '(?:FOO|foo)'
	*/
	public function test_D43386C()
	{
		$this->fromListTestCase(2);
	}

	/**
	* @testdox fromList(['FOO', 'foo'], ["caseInsensitive" => true]) returns 'foo'
	*/
	public function test_D2493E8A()
	{
		$this->fromListTestCase(3);
	}

	/**
	* @testdox fromList(['a']) returns 'a'
	*/
	public function test_37E7519F()
	{
		$this->fromListTestCase(4);
	}

	/**
	* @testdox fromList(['a', 'a']) returns 'a'
	*/
	public function test_46ECDB38()
	{
		$this->fromListTestCase(5);
	}

	/**
	* @testdox fromList(['/']) returns '\\/'
	*/
	public function test_E2479414()
	{
		$this->fromListTestCase(6);
	}

	/**
	* @testdox fromList(['/'], ["delimiter" => "#"]) returns '/'
	*/
	public function test_B3EEE700()
	{
		$this->fromListTestCase(7);
	}

	/**
	* @testdox fromList(['#'], ["delimiter" => "#"]) returns '\\#'
	*/
	public function test_A1D05928()
	{
		$this->fromListTestCase(8);
	}

	/**
	* @testdox fromList(['apple', 'april']) returns 'ap(?:ple|ril)'
	*/
	public function test_8B59A499()
	{
		$this->fromListTestCase(9);
	}

	/**
	* @testdox fromList(['bar', 'baz']) returns 'ba[rz]'
	*/
	public function test_E7FFB86F()
	{
		$this->fromListTestCase(10);
	}

	/**
	* @testdox fromList(['foo', 'fool']) returns 'fool?'
	*/
	public function test_1DFF1AB5()
	{
		$this->fromListTestCase(11);
	}

	/**
	* @testdox fromList(['ax', 'axed']) returns 'ax(?:ed)?'
	*/
	public function test_B4F156BE()
	{
		$this->fromListTestCase(12);
	}

	/**
	* @testdox fromList(['!', '#', '$', '(', ')', '*', '+', '-', '.', '/', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}']) returns '[!#$(-+\\--\\/:<-?[-\\^{-}]'
	*/
	public function test_E5019FE2()
	{
		$this->fromListTestCase(13);
	}

	/**
	* @testdox fromList([':)', ':(', ':]', ':[', ':|', ':/', ':\\']) returns ':[()\\/[-\\]|]'
	*/
	public function test_3E99A2EA()
	{
		$this->fromListTestCase(14);
	}

	/**
	* @testdox fromList(['xy', '^y'], ["specialChars" => ["^" => "^"]]) returns '(?:^|x)y'
	*/
	public function test_763AB43D()
	{
		$this->fromListTestCase(15);
	}

	/**
	* @testdox fromList(['xy', 'x$'], ["specialChars" => ["$" => "$"]]) returns 'x(?:$|y)'
	*/
	public function test_D6F8D16A()
	{
		$this->fromListTestCase(16);
	}

	/**
	* @testdox fromList(['foo', 'bar']) returns '(?:bar|foo)'
	*/
	public function test_A3302107()
	{
		$this->fromListTestCase(17);
	}

	/**
	* @testdox fromList(['a', 'b']) returns '[ab]'
	*/
	public function test_C90B2457()
	{
		$this->fromListTestCase(18);
	}

	/**
	* @testdox fromList(['♠', '♣', '♥', '♦']) returns '[♠♣♥♦]'
	*/
	public function test_6335367B()
	{
		$this->fromListTestCase(19);
	}

	/**
	* @testdox fromList(['lock', 'sock']) returns '[ls]ock'
	*/
	public function test_A3CF0B4D()
	{
		$this->fromListTestCase(20);
	}

	/**
	* @testdox fromList(['boast', 'boost']) returns 'bo[ao]st'
	*/
	public function test_4BBAD47D()
	{
		$this->fromListTestCase(21);
	}

	/**
	* @testdox fromList(['pest', 'pst']) returns 'pe?st'
	*/
	public function test_596D7420()
	{
		$this->fromListTestCase(22);
	}

	/**
	* @testdox fromList(['boast', 'boost', 'bost']) returns 'bo[ao]?st'
	*/
	public function test_E644DCEB()
	{
		$this->fromListTestCase(23);
	}

	/**
	* @testdox fromList(['boost', 'best']) returns 'b(?:e|oo)st'
	*/
	public function test_AEB9F217()
	{
		$this->fromListTestCase(24);
	}

	/**
	* @testdox fromList(['boost', 'bst']) returns 'b(?:oo)?st'
	*/
	public function test_BE3CCBA2()
	{
		$this->fromListTestCase(25);
	}

	/**
	* @testdox fromList(['best', 'boost', 'bust']) returns 'b(?:[eu]|oo)st'
	*/
	public function test_9753E32D()
	{
		$this->fromListTestCase(26);
	}

	/**
	* @testdox fromList(['boost', 'bst', 'cool']) returns '(?:b(?:oo)?st|cool)'
	*/
	public function test_9A764069()
	{
		$this->fromListTestCase(27);
	}

	/**
	* @testdox fromList(['boost', 'bst', 'cost']) returns '(?:b(?:oo)?|co)st'
	*/
	public function test_53994AB6()
	{
		$this->fromListTestCase(28);
	}

	/**
	* @testdox fromList(['aax', 'aay', 'aax', 'aay']) returns 'aa[xy]'
	*/
	public function test_560C8444()
	{
		$this->fromListTestCase(29);
	}

	/**
	* @testdox fromList(['aaax', 'aaay', 'baax', 'baay']) returns '[ab]aa[xy]'
	*/
	public function test_F3709BEF()
	{
		$this->fromListTestCase(30);
	}

	/**
	* @testdox fromList(['aaax', 'aaay', 'bbaax', 'bbaay']) returns '(?:a|bb)aa[xy]'
	*/
	public function test_7C889532()
	{
		$this->fromListTestCase(31);
	}

	/**
	* @testdox fromList(['aaax', 'aaay', 'aax', 'aay']) returns 'aaa?[xy]'
	*/
	public function test_77F7FECB()
	{
		$this->fromListTestCase(32);
	}

	/**
	* @testdox fromList(['abx', 'aby', 'cdx', 'cdy']) returns '(?:ab|cd)[xy]'
	*/
	public function test_62E02B3A()
	{
		$this->fromListTestCase(33);
	}

	/**
	* @testdox fromList(['axx', 'ayy', 'bbxx', 'bbyy']) returns '(?:a|bb)(?:xx|yy)'
	*/
	public function test_8A86C707()
	{
		$this->fromListTestCase(34);
	}

	/**
	* @testdox fromList(['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']) returns '(?:a(?:xx|yy|zz)|bb(?:xx|yy)|c)'
	*/
	public function test_A2146E6E()
	{
		$this->fromListTestCase(35);
	}

	/**
	* @testdox fromList(['ac', 'af', 'bbc', 'bbf', 'c']) returns '(?:a[cf]|bb[cf]|c)'
	*/
	public function test_AC842E3E()
	{
		$this->fromListTestCase(36);
	}

	/**
	* @testdox fromList(['^example.org$', '.example.org$', '^localhost$', '.localhost$'], ["specialChars" => ["^" => "^", "$" => "$"]]) returns '(?:\\.|^)(?:example\\.org|localhost)$'
	*/
	public function test_D463A304()
	{
		$this->fromListTestCase(37);
	}

	/**
	* @testdox fromList(['xixix', 'xoxox']) returns 'x(?:ixi|oxo)x'
	*/
	public function test_B33646C2()
	{
		$this->fromListTestCase(38);
	}

	/**
	* @testdox fromList(['xixix', 'xixox', 'xoxox', 'xoxix']) returns 'x[io]x[io]x'
	*/
	public function test_C7D616C2()
	{
		$this->fromListTestCase(39);
	}

	/**
	* @testdox fromList(['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']) returns '(?:a|bb)(?:bar|foo)?'
	*/
	public function test_B02E083B()
	{
		$this->fromListTestCase(40);
	}

	/**
	* @testdox fromList(['ax', 'ay', 'bx', 'by']) returns '[ab][xy]'
	*/
	public function test_4619FD0F()
	{
		$this->fromListTestCase(41);
	}

	/**
	* @testdox fromList(['ax', 'ay', 'bx', 'by', 'c']) returns '(?:[ab][xy]|c)'
	*/
	public function test_F4F23225()
	{
		$this->fromListTestCase(42);
	}

	/**
	* @testdox fromList(['ax', 'ay', 'bx', 'by', 'x', 'y']) returns '[ab]?[xy]'
	*/
	public function test_660FC1A9()
	{
		$this->fromListTestCase(43);
	}

	/**
	* @testdox fromList(['03', '04', '13', '14', '3', '4']) returns '[01]?[34]'
	*/
	public function test_B3EB536()
	{
		$this->fromListTestCase(44);
	}

	/**
	* @testdox fromList(['ax', 'ay', 'bbx', 'bby', 'c']) returns '(?:a[xy]|bb[xy]|c)'
	*/
	public function test_646B76E0()
	{
		$this->fromListTestCase(45);
	}

	/**
	* @testdox fromList(['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']) returns '(?:[ab][xy]|c|dd[xy])'
	*/
	public function test_943C31D5()
	{
		$this->fromListTestCase(46);
	}

	/**
	* @testdox fromList(['']) returns ''
	*/
	public function test_5CBF14D3()
	{
		$this->fromListTestCase(47);
	}

	/**
	* @testdox fromList(['', '']) returns ''
	*/
	public function test_418D8F44()
	{
		$this->fromListTestCase(48);
	}

	/**
	* @testdox fromList([]) returns ''
	*/
	public function test_1E8614E3()
	{
		$this->fromListTestCase(49);
	}

	/**
	* @testdox fromList(['ad', 'bd'], ["specialChars" => ["d" => "\\d"]]) returns '[ab]\\d'
	*/
	public function test_5B18C2D1()
	{
		$this->fromListTestCase(50);
	}

	/**
	* @testdox fromList(['a', 'ax', 'ad', 'd', 'dx', 'dd'], ["specialChars" => ["d" => "\\d"]]) returns '[a\\d][\\dx]?'
	*/
	public function test_B87EA3C6()
	{
		$this->fromListTestCase(51);
	}

	/**
	* @testdox fromList(['foo', 'bar', 'y', 'z']) returns '(?:[yz]|bar|foo)'
	*/
	public function test_561FE181()
	{
		$this->fromListTestCase(52);
	}

	/**
	* @testdox fromList(['foo', 'bar', 'baz', 'y', 'z']) returns '(?:[yz]|ba[rz]|foo)'
	*/
	public function test_C80C5A7F()
	{
		$this->fromListTestCase(53);
	}

	/**
	* @testdox fromList(['a', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))'
	*/
	public function test_140F192A()
	{
		$this->fromListTestCase(54);
	}

	/**
	* @testdox fromList(['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:aa|bb)(?:cc|dd)?'
	*/
	public function test_FA3816E9()
	{
		$this->fromListTestCase(55);
	}

	/**
	* @testdox fromList(['aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy']) returns '(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?'
	*/
	public function test_4EEB6994()
	{
		$this->fromListTestCase(56);
	}

	/**
	* @testdox fromList(['m.', 'mb'], ["specialChars" => ["." => ".", "b" => "\\b"]]) returns 'm(?:.|\\b)'
	*/
	public function test_B05A865()
	{
		$this->fromListTestCase(57);
	}

	/**
	* @testdox fromList(['m.', 'mB'], ["specialChars" => ["." => ".", "B" => "\\B"]]) returns 'm(?:.|\\B)'
	*/
	public function test_823A9663()
	{
		$this->fromListTestCase(58);
	}

	/**
	* @testdox fromList(['m.', 'mA'], ["specialChars" => ["." => ".", "A" => "\\A"]]) returns 'm(?:.|\\A)'
	*/
	public function test_E58BCD87()
	{
		$this->fromListTestCase(59);
	}

	/**
	* @testdox fromList(['m.', 'mZ'], ["specialChars" => ["." => ".", "Z" => "\\Z"]]) returns 'm(?:.|\\Z)'
	*/
	public function test_95245C1()
	{
		$this->fromListTestCase(60);
	}

	/**
	* @testdox fromList(['m.', 'mz'], ["specialChars" => ["." => ".", "z" => "\\z"]]) returns 'm(?:.|\\z)'
	*/
	public function test_806D7BC7()
	{
		$this->fromListTestCase(61);
	}

	/**
	* @testdox fromList(['m.', 'mG'], ["specialChars" => ["." => ".", "G" => "\\G"]]) returns 'm(?:.|\\G)'
	*/
	public function test_2AE97A4F()
	{
		$this->fromListTestCase(62);
	}

	/**
	* @testdox fromList(['m.', 'mQ'], ["specialChars" => ["." => ".", "Q" => "\\Q"]]) returns 'm(?:.|\\Q)'
	*/
	public function test_A1145284()
	{
		$this->fromListTestCase(63);
	}

	/**
	* @testdox fromList(['m.', 'mE'], ["specialChars" => ["." => ".", "E" => "\\E"]]) returns 'm(?:.|\\E)'
	*/
	public function test_6FC8E8F7()
	{
		$this->fromListTestCase(64);
	}

	/**
	* @testdox fromList(['m.', 'mK'], ["specialChars" => ["." => ".", "K" => "\\K"]]) returns 'm(?:.|\\K)'
	*/
	public function test_6F5D139E()
	{
		$this->fromListTestCase(65);
	}

	/**
	* @testdox fromList(['h$', 'h.'], ["specialChars" => ["." => ".", "$" => "$"]]) returns 'h(?:$|.)'
	*/
	public function test_34005F32()
	{
		$this->fromListTestCase(66);
	}

	/**
	* @testdox fromList([':X', ':D', ':P', ':P']) returns ':[DPX]'
	*/
	public function test_8FED9D1C()
	{
		$this->fromListTestCase(67);
	}

	/**
	* @testdox fromList([':X', ':D', ':P', ':p'], ["caseInsensitive" => true]) returns ':[dpx]'
	*/
	public function test_84BE9ED2()
	{
		$this->fromListTestCase(68);
	}

	/**
	* @testdox fromList(['¼', '½']) returns '[¼½]'
	*/
	public function test_DF7DFA2E()
	{
		$this->fromListTestCase(69);
	}

	/**
	* @testdox fromList(['¼', '½'], ["unicode" => true]) returns '[¼½]'
	*/
	public function test_71FDD57B()
	{
		$this->fromListTestCase(70);
	}

	/**
	* @testdox fromList(['¼', '½'], ["unicode" => false]) returns "\xC2[\xBC\xBD]"
	*/
	public function test_D4EF85F4()
	{
		$this->fromListTestCase(71);
	}
	// End of content generated by ../../../scripts/patchRegexpBuilderTest.php

	protected function fromListTestCase($k)
	{
		$data = $this->getWordsLists();

		$expected = $data[$k][0];
		$words    = $data[$k][1];
		$options  = $data[$k][2] ?? [];

		$this->assertSame($expected, RegexpBuilder::fromList($words, $options));
	}

	/**
	* @testdox fromList() throws a InvalidArgumentException if any word is not legal UTF-8
	*/
	public function testUTF8Exception()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid UTF-8 string');

		RegexpBuilder::fromList(["\xff\xff"]);
	}

	public function getWordsLists()
	{
		return [
			[
				'foo',
				['foo']
			],
			[
				'foo',
				['foo', 'foo']
			],
			[
				'(?:FOO|foo)',
				['FOO', 'foo']
			],
			[
				'foo',
				['FOO', 'foo'],
				['caseInsensitive' => true]
			],
			[
				'a',
				['a']
			],
			[
				'a',
				['a', 'a']
			],
			[
				'\\/',
				['/'],
			],
			[
				'/',
				['/'],
				['delimiter' => '#']
			],
			[
				'\\#',
				['#'],
				['delimiter' => '#']
			],
			[
				'ap(?:ple|ril)',
				['apple', 'april']
			],
			[
				'ba[rz]',
				['bar', 'baz']
			],
			[
				'fool?',
				['foo', 'fool']
			],
			[
				'ax(?:ed)?',
				['ax', 'axed']
			],
			[
				'[!#$(-+\\--\\/:<-?[-\\^{-}]',
				str_split('!#$()*+-./:<=>?[\\]^{|}', 1)
			],
			[
				':[()\\/[-\\]|]',
				[':)', ':(', ':]', ':[', ':|', ':/', ':\\']
			],
			[
				'(?:^|x)y',
				['xy', '^y'],
				['specialChars' => ['^' => '^']]
			],
			[
				'x(?:$|y)',
				['xy', 'x$'],
				['specialChars' => ['$' => '$']]
			],
			[
				'(?:bar|foo)',
				['foo', 'bar']
			],
			[
				'[ab]',
				['a', 'b']
			],
			[
				'[♠♣♥♦]',
				['♠', '♣', '♥', '♦']
			],
//			[
//				'.',
//				['♠', '♣', '♥', '♦', '.'],
//				['specialChars' => ['.' => '.']]
//			],
			[
				'[ls]ock',
				['lock', 'sock']
			],
			[
				'bo[ao]st',
				['boast', 'boost']
			],
			[
				'pe?st',
				['pest', 'pst']
			],
			[
				'bo[ao]?st',
				['boast', 'boost', 'bost']
			],
			[
				'b(?:e|oo)st',
				['boost', 'best']
			],
			[
				'b(?:oo)?st',
				['boost', 'bst']
			],
			[
				'b(?:[eu]|oo)st',
				['best', 'boost', 'bust']
			],
			[
				'(?:b(?:oo)?st|cool)',
				['boost', 'bst', 'cool']
			],
			[
				'(?:b(?:oo)?|co)st',
				['boost', 'bst', 'cost']
			],
			[
				'aa[xy]',
				['aax', 'aay', 'aax', 'aay']
			],
			[
				'[ab]aa[xy]',
				['aaax', 'aaay', 'baax', 'baay']
			],
			[
				'(?:a|bb)aa[xy]',
				['aaax', 'aaay', 'bbaax', 'bbaay']
			],
			[
				'aaa?[xy]',
				['aaax', 'aaay', 'aax', 'aay']
			],
			[
				'(?:ab|cd)[xy]',
				['abx', 'aby', 'cdx', 'cdy']
			],
			[
				'(?:a|bb)(?:xx|yy)',
				['axx', 'ayy', 'bbxx', 'bbyy']
			],
			[
				// Ensure it doesn't become (?:c|(?:a|bb)(?:xx|yy)|azz) even though it would be
				// shorter, because having fewer alternations at the top level is more important
				'(?:a(?:xx|yy|zz)|bb(?:xx|yy)|c)',
				['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']
			],
			[
				// We don't merge "ac", "af", "bbc" and "bbf" tails because the result
				// (?:c|(?:a|bb)[cf]) is neither more performant nor shorter
				'(?:a[cf]|bb[cf]|c)',
				['ac', 'af', 'bbc', 'bbf', 'c']
			],
			[
				// Typical regexp used in UrlConfig for matching hostnames and subdomains
				'(?:\\.|^)(?:example\\.org|localhost)$',
				['^example.org$', '.example.org$', '^localhost$', '.localhost$'],
				['specialChars' => ['^' => '^', '$' => '$']]
			],
			[
				'x(?:ixi|oxo)x',
				['xixix', 'xoxox']
			],
			[
				'x[io]x[io]x',
				['xixix', 'xixox', 'xoxox', 'xoxix']
			],
			[
				'(?:a|bb)(?:bar|foo)?',
				['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']
			],
			[
				'[ab][xy]',
				['ax', 'ay', 'bx', 'by']
			],
			[
				'(?:[ab][xy]|c)',
				['ax', 'ay', 'bx', 'by', 'c']
			],
			[
				'[ab]?[xy]',
				['ax', 'ay', 'bx', 'by', 'x', 'y']
			],
			[
				'[01]?[34]',
				['03', '04', '13', '14', '3', '4']
			],
			// Ensure that merging tails does not create subpatterns
			[
				'(?:a[xy]|bb[xy]|c)',
				['ax', 'ay', 'bbx', 'bby', 'c']
			],
			[
				'(?:[ab][xy]|c|dd[xy])',
				['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']
			],
			// Those three only exist to make sure nothing bad happens (e.g. no infinite loop)
			[
				'',
				['']
			],
			[
				'',
				['', '']
			],
			[
				'',
				[]
			],
			[
				'[ab]\\d',
				['ad', 'bd'],
				['specialChars' => ['d' => '\\d']]
			],
			[
				'[a\\d][\\dx]?',
				['a', 'ax', 'ad', 'd', 'dx', 'dd'],
				['specialChars' => ['d' => '\\d']]
			],
			// Ensure that character classes made from single characters appear first in alternation
			[
				'(?:[yz]|bar|foo)',
				['foo', 'bar', 'y', 'z']
			],
			[
				'(?:[yz]|ba[rz]|foo)',
				['foo', 'bar', 'baz', 'y', 'z']
			],
			[
				'(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))',
				['a', 'aacc', 'aadd', 'bbcc', 'bbdd']
			],
			[
				'(?:aa|bb)(?:cc|dd)?',
				['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']
			],
			[
				'(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?',
				[
					'aa', 'bb',
					'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx',
					'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy'
				]
			],
			[
				'm(?:.|\\b)',
				['m.', 'mb'],
				['specialChars' => ['.' => '.', 'b' => '\\b']]
			],
			[
				'm(?:.|\\B)',
				['m.', 'mB'],
				['specialChars' => ['.' => '.', 'B' => '\\B']]
			],
			[
				'm(?:.|\\A)',
				['m.', 'mA'],
				['specialChars' => ['.' => '.', 'A' => '\\A']]
			],
			[
				'm(?:.|\\Z)',
				['m.', 'mZ'],
				['specialChars' => ['.' => '.', 'Z' => '\\Z']]
			],
			[
				'm(?:.|\\z)',
				['m.', 'mz'],
				['specialChars' => ['.' => '.', 'z' => '\\z']]
			],
			[
				'm(?:.|\\G)',
				['m.', 'mG'],
				['specialChars' => ['.' => '.', 'G' => '\\G']]
			],
			[
				'm(?:.|\\Q)',
				['m.', 'mQ'],
				['specialChars' => ['.' => '.', 'Q' => '\\Q']]
			],
			[
				'm(?:.|\\E)',
				['m.', 'mE'],
				['specialChars' => ['.' => '.', 'E' => '\\E']]
			],
			[
				'm(?:.|\\K)',
				['m.', 'mK'],
				['specialChars' => ['.' => '.', 'K' => '\\K']]
			],
			[
				'h(?:$|.)',
				['h$', 'h.'],
				['specialChars' => ['.' => '.', '$' => '$']]
			],
			[
				':[DPX]',
				[':X', ':D', ':P', ':P']
			],
			[
				':[dpx]',
				[':X', ':D', ':P', ':p'],
				['caseInsensitive' => true]
			],
			[
				'[¼½]',
				['¼', '½'],
			],
			[
				'[¼½]',
				['¼', '½'],
				['unicode' => true]
			],
			[
				"\xC2[\xBC\xBD]",
				['¼', '½'],
				['unicode' => false]
			],
		];
	}
}