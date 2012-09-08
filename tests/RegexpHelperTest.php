
	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public fu
	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_2e6b327f()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_efc21f10()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo', 'foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a']) returns 'a'
	*/
	public function test_buildRegexpFromList_37e7519f()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'a']) returns 'a'
	*/
	public function test_buildRegexpFromList_46ecdb38()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a', 'a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['apple', 'april']) returns 'ap(?:ple|ril)'
	*/
	public function test_buildRegexpFromList_8b59a499()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			$this->rm->buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['bar', 'baz']) returns 'ba[rz]'
	*/
	public function test_buildRegexpFromList_e7ffb86f()
	{
		$this->assertSame(
			'ba[rz]',
			$this->rm->buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'fool']) returns 'fool?'
	*/
	public function test_buildRegexpFromList_1dff1ab5()
	{
		$this->assertSame(
			'fool?',
			$this->rm->buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'axed']) returns 'ax(?:ed)?'
	*/
	public function test_buildRegexpFromList_b4f156be()
	{
		$this->assertSame(
			'ax(?:ed)?',
			$this->rm->buildRegexpFromList(array('ax', 'axed'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}']) returns '[-!:<=>\\#\\\\\\]}$()*+.?[{|^]'
	*/
	public function test_buildRegexpFromList_8cb33695()
	{
		$this->assertSame(
			'[-!:<=>\\#\\\\\\]}$()*+.?[{|^]',
			$this->rm->buildRegexpFromList(array('!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', '.'], ["specialChars" => ["." => "."]]) returns '(?:a|.)'
	*/
	public function test_buildRegexpFromList_8540e325()
	{
		$this->assertSame(
			'(?:a|.)',
			$this->rm->buildRegexpFromList(
				array('a', '.'),
				array('specialChars' => array('.' => '.'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', '^y'], ["specialChars" => ["^" => "^"]]) returns '(?:x|^)y'
	*/
	public function test_buildRegexpFromList_8a71e271()
	{
		$this->assertSame(
			'(?:x|^)y',
			$this->rm->buildRegexpFromList(
				array('xy', '^y'),
				array('specialChars' => array('^' => '^'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', 'x$'], ["specialChars" => ["$" => "$"]]) returns 'x(?:y|$)'
	*/
	public function test_buildRegexpFromList_13ba1bfa()
	{
		$this->assertSame(
			'x(?:y|$)',
			$this->rm->buildRegexpFromList(
				array('xy', 'x$'),
				array('specialChars' => array('$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar']) returns '(?:bar|foo)'
	*/
	public function test_buildRegexpFromList_a3302107()
	{
		$this->assertSame(
			'(?:bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['*foo', '\\bar'], ["useLookahead" => true]) returns '(?=[\\\\*])(?:\\*foo|\\\\bar)'
	*/
	public function test_buildRegexpFromList_8b9e42a9()
	{
		$this->assertSame(
			'(?=[\\\\*])(?:\\*foo|\\\\bar)',
			$this->rm->buildRegexpFromList(
				array('*foo', '\\bar'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['?', 'bar'], ["specialChars" => ["?" => "."], "useLookahead" => true]) returns '(?:.|bar)'
	*/
	public function test_buildRegexpFromList_7ffb138d()
	{
		$this->assertSame(
			'(?:.|bar)',
			$this->rm->buildRegexpFromList(
				array('?', 'bar'),
				array('specialChars' => array('?' => '.'), 'useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'b']) returns '[ab]'
	*/
	public function test_buildRegexpFromList_c90b2457()
	{
		$this->assertSame(
			'[ab]',
			$this->rm->buildRegexpFromList(array('a', 'b'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['♠', '♣', '♥', '♦']) returns '[♠♣♥♦]'
	*/
	public function test_buildRegexpFromList_6335367b()
	{
		$this->assertSame(
			'[♠♣♥♦]',
			$this->rm->buildRegexpFromList(array('♠', '♣', '♥', '♦'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['lock', 'sock']) returns '[ls]ock'
	*/
	public function test_buildRegexpFromList_a3cf0b4d()
	{
		$this->assertSame(
			'[ls]ock',
			$this->rm->buildRegexpFromList(array('lock', 'sock'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'afoo'], ["useLookahead" => true]) returns '(?=[af])a?foo'
	*/
	public function test_buildRegexpFromList_8613531b()
	{
		$this->assertSame(
			'(?=[af])a?foo',
			$this->rm->buildRegexpFromList(
				array('foo', 'afoo'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost']) returns 'bo[ao]st'
	*/
	public function test_buildRegexpFromList_4bbad47d()
	{
		$this->assertSame(
			'bo[ao]st',
			$this->rm->buildRegexpFromList(array('boast', 'boost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['pest', 'pst']) returns 'pe?st'
	*/
	public function test_buildRegexpFromList_596d7420()
	{
		$this->assertSame(
			'pe?st',
			$this->rm->buildRegexpFromList(array('pest', 'pst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost', 'bost']) returns 'bo[ao]?st'
	*/
	public function test_buildRegexpFromList_e644dceb()
	{
		$this->assertSame(
			'bo[ao]?st',
			$this->rm->buildRegexpFromList(array('boast', 'boost', 'bost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'best']) returns 'b(?:e|oo)st'
	*/
	public function test_buildRegexpFromList_aeb9f217()
	{
		$this->assertSame(
			'b(?:e|oo)st',
			$this->rm->buildRegexpFromList(array('boost', 'best'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst']) returns 'b(?:oo)?st'
	*/
	public function test_buildRegexpFromList_be3ccba2()
	{
		$this->assertSame(
			'b(?:oo)?st',
			$this->rm->buildRegexpFromList(array('boost', 'bst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['best', 'boost', 'bust']) returns 'b(?:[eu]|oo)st'
	*/
	public function test_buildRegexpFromList_9753e32d()
	{
		$this->assertSame(
			'b(?:[eu]|oo)st',
			$this->rm->buildRegexpFromList(array('best', 'boost', 'bust'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cool']) returns '(?:b(?:oo)?st|cool)'
	*/
	public function test_buildRegexpFromList_9a764069()
	{
		$this->assertSame(
			'(?:b(?:oo)?st|cool)',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cost']) returns '(?:b(?:oo)?|co)st'
	*/
	public function test_buildRegexpFromList_53994ab6()
	{
		$this->assertSame(
			'(?:b(?:oo)?|co)st',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aax', 'aay', 'aax', 'aay']) returns 'aa[xy]'
	*/
	public function test_buildRegexpFromList_560c8444()
	{
		$this->assertSame(
			'aa[xy]',
			$this->rm->buildRegexpFromList(array('aax', 'aay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'baax', 'baay']) returns '[ab]aa[xy]'
	*/
	public function test_buildRegexpFromList_f3709bef()
	{
		$this->assertSame(
			'[ab]aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'baax', 'baay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'bbaax', 'bbaay']) returns '(?:a|bb)aa[xy]'
	*/
	public function test_buildRegexpFromList_7c889532()
	{
		$this->assertSame(
			'(?:a|bb)aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'bbaax', 'bbaay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'aax', 'aay']) returns 'aaa?[xy]'
	*/
	public function test_buildRegexpFromList_77f7fecb()
	{
		$this->assertSame(
			'aaa?[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['abx', 'aby', 'cdx', 'cdy']) returns '(?:ab|cd)[xy]'
	*/
	public function test_buildRegexpFromList_62e02b3a()
	{
		$this->assertSame(
			'(?:ab|cd)[xy]',
			$this->rm->buildRegexpFromList(array('abx', 'aby', 'cdx', 'cdy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy']) returns '(?:a|bb)(?:xx|yy)'
	*/
	public function test_buildRegexpFromList_8a86c707()
	{
		$this->assertSame(
			'(?:a|bb)(?:xx|yy)',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy', 'c']) returns '(?:c|(?:a|bb)(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_59fb8e0()
	{
		$this->assertSame(
			'(?:c|(?:a|bb)(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']) returns '(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_ea302659()
	{
		$this->assertSame(
			'(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ac', 'af', 'bbc', 'bbf', 'c']) returns '(?:c|a[cf]|bb[cf])'
	*/
	public function test_buildRegexpFromList_1cc75402()
	{
		$this->assertSame(
			'(?:c|a[cf]|bb[cf])',
			$this->rm->buildRegexpFromList(array('ac', 'af', 'bbc', 'bbf', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['^example.org$', '.example.org$', '^localhost$', '.localhost$'], ["specialChars" => ["^" => "^", "$" => "$"]]) returns '(?:\\.|^)(?:example\\.org|localhost)$'
	*/
	public function test_buildRegexpFromList_d463a304()
	{
		$this->assertSame(
			'(?:\\.|^)(?:example\\.org|localhost)$',
			$this->rm->buildRegexpFromList(
				array('^example.org$', '.example.org$', '^localhost$', '.localhost$'),
				array('specialChars' => array('^' => '^', '$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xoxox']) returns 'x(?:ixi|oxo)x'
	*/
	public function test_buildRegexpFromList_b33646c2()
	{
		$this->assertSame(
			'x(?:ixi|oxo)x',
			$this->rm->buildRegexpFromList(array('xixix', 'xoxox'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xixox', 'xoxox', 'xoxix']) returns 'x[io]x[io]x'
	*/
	public function test_buildRegexpFromList_c7d616c2()
	{
		$this->assertSame(
			'x[io]x[io]x',
			$this->rm->buildRegexpFromList(array('xixix', 'xixox', 'xoxox', 'xoxix'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']) returns '(?:a|bb)(?:bar|foo)?'
	*/
	public function test_buildRegexpFromList_b02e083b()
	{
		$this->assertSame(
			'(?:a|bb)(?:bar|foo)?',
			$this->rm->buildRegexpFromList(array('afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by']) returns '[ab][xy]'
	*/
	public function test_buildRegexpFromList_4619fd0f()
	{
		$this->assertSame(
			'[ab][xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c']) returns '(?:c|[ab][xy])'
	*/
	public function test_buildRegexpFromList_f7523978()
	{
		$this->assertSame(
			'(?:c|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'x', 'y']) returns '[ab]?[xy]'
	*/
	public function test_buildRegexpFromList_660fc1a9()
	{
		$this->assertSame(
			'[ab]?[xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'x', 'y'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bbx', 'bby', 'c']) returns '(?:c|a[xy]|bb[xy])'
	*/
	public function test_buildRegexpFromList_58a1b850()
	{
		$this->assertSame(
			'(?:c|a[xy]|bb[xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bbx', 'bby', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']) returns '(?:c|dd[xy]|[ab][xy])'
	*/
	public function test_buildRegexpFromList_23c1bd26()
	{
		$this->assertSame(
			'(?:c|dd[xy]|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['']) returns ''
	*/
	public function test_buildRegexpFromList_5cbf14d3()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array(''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['', '']) returns ''
	*/
	public function test_buildRegexpFromList_418d8f44()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array('', ''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ad', 'bd'], ["specialChars" => ["d" => "\\d"]]) returns '[ab]\\d'
	*/
	public function test_buildRegexpFromList_5b18c2d1()
	{
		$this->assertSame(
			'[ab]\\d',
			$this->rm->buildRegexpFromList(
				array('ad', 'bd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'ax', 'ad', 'd', 'dx', 'dd'], ["specialChars" => ["d" => "\\d"]]) returns '[\\da][\\dx]?'
	*/
	public function test_buildRegexpFromList_4032006c()
	{
		$this->assertSame(
			'[\\da][\\dx]?',
			$this->rm->buildRegexpFromList(
				array('a', 'ax', 'ad', 'd', 'dx', 'dd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'y', 'z']) returns '(?:[yz]|bar|foo)'
	*/
	public function test_buildRegexpFromList_561fe181()
	{
		$this->assertSame(
			'(?:[yz]|bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'baz', 'y', 'z']) returns '(?:[yz]|ba[rz]|foo)'
	*/
	public function test_buildRegexpFromList_c80c5a7f()
	{
		$this->assertSame(
			'(?:[yz]|ba[rz]|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'baz', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))'
	*/
	public function test_buildRegexpFromList_140f192a()
	{
		$this->assertSame(
			'(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))',
			$this->rm->buildRegexpFromList(array('a', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:aa|bb)(?:cc|dd)?'
	*/
	public function test_buildRegexpFromList_fa3816e9()
	{
		$this->assertSame(
			'(?:aa|bb)(?:cc|dd)?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy']) returns '(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?'
	*/
	public function test_buildRegexpFromList_4eeb6994()
	{
		$this->assertSame(
			'(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public fu
	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_2e6b327f()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_efc21f10()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo', 'foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a']) returns 'a'
	*/
	public function test_buildRegexpFromList_37e7519f()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'a']) returns 'a'
	*/
	public function test_buildRegexpFromList_46ecdb38()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a', 'a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['apple', 'april']) returns 'ap(?:ple|ril)'
	*/
	public function test_buildRegexpFromList_8b59a499()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			$this->rm->buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['bar', 'baz']) returns 'ba[rz]'
	*/
	public function test_buildRegexpFromList_e7ffb86f()
	{
		$this->assertSame(
			'ba[rz]',
			$this->rm->buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'fool']) returns 'fool?'
	*/
	public function test_buildRegexpFromList_1dff1ab5()
	{
		$this->assertSame(
			'fool?',
			$this->rm->buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'axed']) returns 'ax(?:ed)?'
	*/
	public function test_buildRegexpFromList_b4f156be()
	{
		$this->assertSame(
			'ax(?:ed)?',
			$this->rm->buildRegexpFromList(array('ax', 'axed'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}']) returns '[-!:<=>\\#\\\\\\]}$()*+.?[{|^]'
	*/
	public function test_buildRegexpFromList_8cb33695()
	{
		$this->assertSame(
			'[-!:<=>\\#\\\\\\]}$()*+.?[{|^]',
			$this->rm->buildRegexpFromList(array('!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', '.'], ["specialChars" => ["." => "."]]) returns '(?:a|.)'
	*/
	public function test_buildRegexpFromList_8540e325()
	{
		$this->assertSame(
			'(?:a|.)',
			$this->rm->buildRegexpFromList(
				array('a', '.'),
				array('specialChars' => array('.' => '.'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', '^y'], ["specialChars" => ["^" => "^"]]) returns '(?:x|^)y'
	*/
	public function test_buildRegexpFromList_8a71e271()
	{
		$this->assertSame(
			'(?:x|^)y',
			$this->rm->buildRegexpFromList(
				array('xy', '^y'),
				array('specialChars' => array('^' => '^'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', 'x$'], ["specialChars" => ["$" => "$"]]) returns 'x(?:y|$)'
	*/
	public function test_buildRegexpFromList_13ba1bfa()
	{
		$this->assertSame(
			'x(?:y|$)',
			$this->rm->buildRegexpFromList(
				array('xy', 'x$'),
				array('specialChars' => array('$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar']) returns '(?:bar|foo)'
	*/
	public function test_buildRegexpFromList_a3302107()
	{
		$this->assertSame(
			'(?:bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['*foo', '\\bar'], ["useLookahead" => true]) returns '(?=[\\\\*])(?:\\*foo|\\\\bar)'
	*/
	public function test_buildRegexpFromList_8b9e42a9()
	{
		$this->assertSame(
			'(?=[\\\\*])(?:\\*foo|\\\\bar)',
			$this->rm->buildRegexpFromList(
				array('*foo', '\\bar'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['?', 'bar'], ["specialChars" => ["?" => "."], "useLookahead" => true]) returns '(?:.|bar)'
	*/
	public function test_buildRegexpFromList_7ffb138d()
	{
		$this->assertSame(
			'(?:.|bar)',
			$this->rm->buildRegexpFromList(
				array('?', 'bar'),
				array('specialChars' => array('?' => '.'), 'useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'b']) returns '[ab]'
	*/
	public function test_buildRegexpFromList_c90b2457()
	{
		$this->assertSame(
			'[ab]',
			$this->rm->buildRegexpFromList(array('a', 'b'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['♠', '♣', '♥', '♦']) returns '[♠♣♥♦]'
	*/
	public function test_buildRegexpFromList_6335367b()
	{
		$this->assertSame(
			'[♠♣♥♦]',
			$this->rm->buildRegexpFromList(array('♠', '♣', '♥', '♦'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['lock', 'sock']) returns '[ls]ock'
	*/
	public function test_buildRegexpFromList_a3cf0b4d()
	{
		$this->assertSame(
			'[ls]ock',
			$this->rm->buildRegexpFromList(array('lock', 'sock'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'afoo'], ["useLookahead" => true]) returns '(?=[af])a?foo'
	*/
	public function test_buildRegexpFromList_8613531b()
	{
		$this->assertSame(
			'(?=[af])a?foo',
			$this->rm->buildRegexpFromList(
				array('foo', 'afoo'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost']) returns 'bo[ao]st'
	*/
	public function test_buildRegexpFromList_4bbad47d()
	{
		$this->assertSame(
			'bo[ao]st',
			$this->rm->buildRegexpFromList(array('boast', 'boost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['pest', 'pst']) returns 'pe?st'
	*/
	public function test_buildRegexpFromList_596d7420()
	{
		$this->assertSame(
			'pe?st',
			$this->rm->buildRegexpFromList(array('pest', 'pst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost', 'bost']) returns 'bo[ao]?st'
	*/
	public function test_buildRegexpFromList_e644dceb()
	{
		$this->assertSame(
			'bo[ao]?st',
			$this->rm->buildRegexpFromList(array('boast', 'boost', 'bost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'best']) returns 'b(?:e|oo)st'
	*/
	public function test_buildRegexpFromList_aeb9f217()
	{
		$this->assertSame(
			'b(?:e|oo)st',
			$this->rm->buildRegexpFromList(array('boost', 'best'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst']) returns 'b(?:oo)?st'
	*/
	public function test_buildRegexpFromList_be3ccba2()
	{
		$this->assertSame(
			'b(?:oo)?st',
			$this->rm->buildRegexpFromList(array('boost', 'bst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['best', 'boost', 'bust']) returns 'b(?:[eu]|oo)st'
	*/
	public function test_buildRegexpFromList_9753e32d()
	{
		$this->assertSame(
			'b(?:[eu]|oo)st',
			$this->rm->buildRegexpFromList(array('best', 'boost', 'bust'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cool']) returns '(?:b(?:oo)?st|cool)'
	*/
	public function test_buildRegexpFromList_9a764069()
	{
		$this->assertSame(
			'(?:b(?:oo)?st|cool)',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cost']) returns '(?:b(?:oo)?|co)st'
	*/
	public function test_buildRegexpFromList_53994ab6()
	{
		$this->assertSame(
			'(?:b(?:oo)?|co)st',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aax', 'aay', 'aax', 'aay']) returns 'aa[xy]'
	*/
	public function test_buildRegexpFromList_560c8444()
	{
		$this->assertSame(
			'aa[xy]',
			$this->rm->buildRegexpFromList(array('aax', 'aay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'baax', 'baay']) returns '[ab]aa[xy]'
	*/
	public function test_buildRegexpFromList_f3709bef()
	{
		$this->assertSame(
			'[ab]aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'baax', 'baay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'bbaax', 'bbaay']) returns '(?:a|bb)aa[xy]'
	*/
	public function test_buildRegexpFromList_7c889532()
	{
		$this->assertSame(
			'(?:a|bb)aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'bbaax', 'bbaay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'aax', 'aay']) returns 'aaa?[xy]'
	*/
	public function test_buildRegexpFromList_77f7fecb()
	{
		$this->assertSame(
			'aaa?[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['abx', 'aby', 'cdx', 'cdy']) returns '(?:ab|cd)[xy]'
	*/
	public function test_buildRegexpFromList_62e02b3a()
	{
		$this->assertSame(
			'(?:ab|cd)[xy]',
			$this->rm->buildRegexpFromList(array('abx', 'aby', 'cdx', 'cdy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy']) returns '(?:a|bb)(?:xx|yy)'
	*/
	public function test_buildRegexpFromList_8a86c707()
	{
		$this->assertSame(
			'(?:a|bb)(?:xx|yy)',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy', 'c']) returns '(?:c|(?:a|bb)(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_59fb8e0()
	{
		$this->assertSame(
			'(?:c|(?:a|bb)(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']) returns '(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_ea302659()
	{
		$this->assertSame(
			'(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ac', 'af', 'bbc', 'bbf', 'c']) returns '(?:c|a[cf]|bb[cf])'
	*/
	public function test_buildRegexpFromList_1cc75402()
	{
		$this->assertSame(
			'(?:c|a[cf]|bb[cf])',
			$this->rm->buildRegexpFromList(array('ac', 'af', 'bbc', 'bbf', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['^example.org$', '.example.org$', '^localhost$', '.localhost$'], ["specialChars" => ["^" => "^", "$" => "$"]]) returns '(?:\\.|^)(?:example\\.org|localhost)$'
	*/
	public function test_buildRegexpFromList_d463a304()
	{
		$this->assertSame(
			'(?:\\.|^)(?:example\\.org|localhost)$',
			$this->rm->buildRegexpFromList(
				array('^example.org$', '.example.org$', '^localhost$', '.localhost$'),
				array('specialChars' => array('^' => '^', '$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xoxox']) returns 'x(?:ixi|oxo)x'
	*/
	public function test_buildRegexpFromList_b33646c2()
	{
		$this->assertSame(
			'x(?:ixi|oxo)x',
			$this->rm->buildRegexpFromList(array('xixix', 'xoxox'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xixox', 'xoxox', 'xoxix']) returns 'x[io]x[io]x'
	*/
	public function test_buildRegexpFromList_c7d616c2()
	{
		$this->assertSame(
			'x[io]x[io]x',
			$this->rm->buildRegexpFromList(array('xixix', 'xixox', 'xoxox', 'xoxix'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']) returns '(?:a|bb)(?:bar|foo)?'
	*/
	public function test_buildRegexpFromList_b02e083b()
	{
		$this->assertSame(
			'(?:a|bb)(?:bar|foo)?',
			$this->rm->buildRegexpFromList(array('afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by']) returns '[ab][xy]'
	*/
	public function test_buildRegexpFromList_4619fd0f()
	{
		$this->assertSame(
			'[ab][xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c']) returns '(?:c|[ab][xy])'
	*/
	public function test_buildRegexpFromList_f7523978()
	{
		$this->assertSame(
			'(?:c|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'x', 'y']) returns '[ab]?[xy]'
	*/
	public function test_buildRegexpFromList_660fc1a9()
	{
		$this->assertSame(
			'[ab]?[xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'x', 'y'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bbx', 'bby', 'c']) returns '(?:c|a[xy]|bb[xy])'
	*/
	public function test_buildRegexpFromList_58a1b850()
	{
		$this->assertSame(
			'(?:c|a[xy]|bb[xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bbx', 'bby', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']) returns '(?:c|dd[xy]|[ab][xy])'
	*/
	public function test_buildRegexpFromList_23c1bd26()
	{
		$this->assertSame(
			'(?:c|dd[xy]|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['']) returns ''
	*/
	public function test_buildRegexpFromList_5cbf14d3()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array(''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['', '']) returns ''
	*/
	public function test_buildRegexpFromList_418d8f44()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array('', ''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ad', 'bd'], ["specialChars" => ["d" => "\\d"]]) returns '[ab]\\d'
	*/
	public function test_buildRegexpFromList_5b18c2d1()
	{
		$this->assertSame(
			'[ab]\\d',
			$this->rm->buildRegexpFromList(
				array('ad', 'bd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'ax', 'ad', 'd', 'dx', 'dd'], ["specialChars" => ["d" => "\\d"]]) returns '[\\da][\\dx]?'
	*/
	public function test_buildRegexpFromList_4032006c()
	{
		$this->assertSame(
			'[\\da][\\dx]?',
			$this->rm->buildRegexpFromList(
				array('a', 'ax', 'ad', 'd', 'dx', 'dd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'y', 'z']) returns '(?:[yz]|bar|foo)'
	*/
	public function test_buildRegexpFromList_561fe181()
	{
		$this->assertSame(
			'(?:[yz]|bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'baz', 'y', 'z']) returns '(?:[yz]|ba[rz]|foo)'
	*/
	public function test_buildRegexpFromList_c80c5a7f()
	{
		$this->assertSame(
			'(?:[yz]|ba[rz]|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'baz', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))'
	*/
	public function test_buildRegexpFromList_140f192a()
	{
		$this->assertSame(
			'(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))',
			$this->rm->buildRegexpFromList(array('a', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:aa|bb)(?:cc|dd)?'
	*/
	public function test_buildRegexpFromList_fa3816e9()
	{
		$this->assertSame(
			'(?:aa|bb)(?:cc|dd)?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy']) returns '(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?'
	*/
	public function test_buildRegexpFromList_4eeb6994()
	{
		$this->assertSame(
			'(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public fu
	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_2e6b327f()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_efc21f10()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo', 'foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a']) returns 'a'
	*/
	public function test_buildRegexpFromList_37e7519f()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'a']) returns 'a'
	*/
	public function test_buildRegexpFromList_46ecdb38()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a', 'a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['apple', 'april']) returns 'ap(?:ple|ril)'
	*/
	public function test_buildRegexpFromList_8b59a499()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			$this->rm->buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['bar', 'baz']) returns 'ba[rz]'
	*/
	public function test_buildRegexpFromList_e7ffb86f()
	{
		$this->assertSame(
			'ba[rz]',
			$this->rm->buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'fool']) returns 'fool?'
	*/
	public function test_buildRegexpFromList_1dff1ab5()
	{
		$this->assertSame(
			'fool?',
			$this->rm->buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'axed']) returns 'ax(?:ed)?'
	*/
	public function test_buildRegexpFromList_b4f156be()
	{
		$this->assertSame(
			'ax(?:ed)?',
			$this->rm->buildRegexpFromList(array('ax', 'axed'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}']) returns '[-!:<=>\\#\\\\\\]}$()*+.?[{|^]'
	*/
	public function test_buildRegexpFromList_8cb33695()
	{
		$this->assertSame(
			'[-!:<=>\\#\\\\\\]}$()*+.?[{|^]',
			$this->rm->buildRegexpFromList(array('!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', '.'], ["specialChars" => ["." => "."]]) returns '(?:a|.)'
	*/
	public function test_buildRegexpFromList_8540e325()
	{
		$this->assertSame(
			'(?:a|.)',
			$this->rm->buildRegexpFromList(
				array('a', '.'),
				array('specialChars' => array('.' => '.'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', '^y'], ["specialChars" => ["^" => "^"]]) returns '(?:x|^)y'
	*/
	public function test_buildRegexpFromList_8a71e271()
	{
		$this->assertSame(
			'(?:x|^)y',
			$this->rm->buildRegexpFromList(
				array('xy', '^y'),
				array('specialChars' => array('^' => '^'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', 'x$'], ["specialChars" => ["$" => "$"]]) returns 'x(?:y|$)'
	*/
	public function test_buildRegexpFromList_13ba1bfa()
	{
		$this->assertSame(
			'x(?:y|$)',
			$this->rm->buildRegexpFromList(
				array('xy', 'x$'),
				array('specialChars' => array('$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar']) returns '(?:bar|foo)'
	*/
	public function test_buildRegexpFromList_a3302107()
	{
		$this->assertSame(
			'(?:bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['*foo', '\\bar'], ["useLookahead" => true]) returns '(?=[\\\\*])(?:\\*foo|\\\\bar)'
	*/
	public function test_buildRegexpFromList_8b9e42a9()
	{
		$this->assertSame(
			'(?=[\\\\*])(?:\\*foo|\\\\bar)',
			$this->rm->buildRegexpFromList(
				array('*foo', '\\bar'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['?', 'bar'], ["specialChars" => ["?" => "."], "useLookahead" => true]) returns '(?:.|bar)'
	*/
	public function test_buildRegexpFromList_7ffb138d()
	{
		$this->assertSame(
			'(?:.|bar)',
			$this->rm->buildRegexpFromList(
				array('?', 'bar'),
				array('specialChars' => array('?' => '.'), 'useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'b']) returns '[ab]'
	*/
	public function test_buildRegexpFromList_c90b2457()
	{
		$this->assertSame(
			'[ab]',
			$this->rm->buildRegexpFromList(array('a', 'b'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['♠', '♣', '♥', '♦']) returns '[♠♣♥♦]'
	*/
	public function test_buildRegexpFromList_6335367b()
	{
		$this->assertSame(
			'[♠♣♥♦]',
			$this->rm->buildRegexpFromList(array('♠', '♣', '♥', '♦'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['lock', 'sock']) returns '[ls]ock'
	*/
	public function test_buildRegexpFromList_a3cf0b4d()
	{
		$this->assertSame(
			'[ls]ock',
			$this->rm->buildRegexpFromList(array('lock', 'sock'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'afoo'], ["useLookahead" => true]) returns '(?=[af])a?foo'
	*/
	public function test_buildRegexpFromList_8613531b()
	{
		$this->assertSame(
			'(?=[af])a?foo',
			$this->rm->buildRegexpFromList(
				array('foo', 'afoo'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost']) returns 'bo[ao]st'
	*/
	public function test_buildRegexpFromList_4bbad47d()
	{
		$this->assertSame(
			'bo[ao]st',
			$this->rm->buildRegexpFromList(array('boast', 'boost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['pest', 'pst']) returns 'pe?st'
	*/
	public function test_buildRegexpFromList_596d7420()
	{
		$this->assertSame(
			'pe?st',
			$this->rm->buildRegexpFromList(array('pest', 'pst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost', 'bost']) returns 'bo[ao]?st'
	*/
	public function test_buildRegexpFromList_e644dceb()
	{
		$this->assertSame(
			'bo[ao]?st',
			$this->rm->buildRegexpFromList(array('boast', 'boost', 'bost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'best']) returns 'b(?:e|oo)st'
	*/
	public function test_buildRegexpFromList_aeb9f217()
	{
		$this->assertSame(
			'b(?:e|oo)st',
			$this->rm->buildRegexpFromList(array('boost', 'best'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst']) returns 'b(?:oo)?st'
	*/
	public function test_buildRegexpFromList_be3ccba2()
	{
		$this->assertSame(
			'b(?:oo)?st',
			$this->rm->buildRegexpFromList(array('boost', 'bst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['best', 'boost', 'bust']) returns 'b(?:[eu]|oo)st'
	*/
	public function test_buildRegexpFromList_9753e32d()
	{
		$this->assertSame(
			'b(?:[eu]|oo)st',
			$this->rm->buildRegexpFromList(array('best', 'boost', 'bust'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cool']) returns '(?:b(?:oo)?st|cool)'
	*/
	public function test_buildRegexpFromList_9a764069()
	{
		$this->assertSame(
			'(?:b(?:oo)?st|cool)',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cost']) returns '(?:b(?:oo)?|co)st'
	*/
	public function test_buildRegexpFromList_53994ab6()
	{
		$this->assertSame(
			'(?:b(?:oo)?|co)st',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aax', 'aay', 'aax', 'aay']) returns 'aa[xy]'
	*/
	public function test_buildRegexpFromList_560c8444()
	{
		$this->assertSame(
			'aa[xy]',
			$this->rm->buildRegexpFromList(array('aax', 'aay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'baax', 'baay']) returns '[ab]aa[xy]'
	*/
	public function test_buildRegexpFromList_f3709bef()
	{
		$this->assertSame(
			'[ab]aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'baax', 'baay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'bbaax', 'bbaay']) returns '(?:a|bb)aa[xy]'
	*/
	public function test_buildRegexpFromList_7c889532()
	{
		$this->assertSame(
			'(?:a|bb)aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'bbaax', 'bbaay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'aax', 'aay']) returns 'aaa?[xy]'
	*/
	public function test_buildRegexpFromList_77f7fecb()
	{
		$this->assertSame(
			'aaa?[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['abx', 'aby', 'cdx', 'cdy']) returns '(?:ab|cd)[xy]'
	*/
	public function test_buildRegexpFromList_62e02b3a()
	{
		$this->assertSame(
			'(?:ab|cd)[xy]',
			$this->rm->buildRegexpFromList(array('abx', 'aby', 'cdx', 'cdy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy']) returns '(?:a|bb)(?:xx|yy)'
	*/
	public function test_buildRegexpFromList_8a86c707()
	{
		$this->assertSame(
			'(?:a|bb)(?:xx|yy)',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy', 'c']) returns '(?:c|(?:a|bb)(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_59fb8e0()
	{
		$this->assertSame(
			'(?:c|(?:a|bb)(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']) returns '(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_ea302659()
	{
		$this->assertSame(
			'(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ac', 'af', 'bbc', 'bbf', 'c']) returns '(?:c|a[cf]|bb[cf])'
	*/
	public function test_buildRegexpFromList_1cc75402()
	{
		$this->assertSame(
			'(?:c|a[cf]|bb[cf])',
			$this->rm->buildRegexpFromList(array('ac', 'af', 'bbc', 'bbf', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['^example.org$', '.example.org$', '^localhost$', '.localhost$'], ["specialChars" => ["^" => "^", "$" => "$"]]) returns '(?:\\.|^)(?:example\\.org|localhost)$'
	*/
	public function test_buildRegexpFromList_d463a304()
	{
		$this->assertSame(
			'(?:\\.|^)(?:example\\.org|localhost)$',
			$this->rm->buildRegexpFromList(
				array('^example.org$', '.example.org$', '^localhost$', '.localhost$'),
				array('specialChars' => array('^' => '^', '$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xoxox']) returns 'x(?:ixi|oxo)x'
	*/
	public function test_buildRegexpFromList_b33646c2()
	{
		$this->assertSame(
			'x(?:ixi|oxo)x',
			$this->rm->buildRegexpFromList(array('xixix', 'xoxox'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xixox', 'xoxox', 'xoxix']) returns 'x[io]x[io]x'
	*/
	public function test_buildRegexpFromList_c7d616c2()
	{
		$this->assertSame(
			'x[io]x[io]x',
			$this->rm->buildRegexpFromList(array('xixix', 'xixox', 'xoxox', 'xoxix'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']) returns '(?:a|bb)(?:bar|foo)?'
	*/
	public function test_buildRegexpFromList_b02e083b()
	{
		$this->assertSame(
			'(?:a|bb)(?:bar|foo)?',
			$this->rm->buildRegexpFromList(array('afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by']) returns '[ab][xy]'
	*/
	public function test_buildRegexpFromList_4619fd0f()
	{
		$this->assertSame(
			'[ab][xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c']) returns '(?:c|[ab][xy])'
	*/
	public function test_buildRegexpFromList_f7523978()
	{
		$this->assertSame(
			'(?:c|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'x', 'y']) returns '[ab]?[xy]'
	*/
	public function test_buildRegexpFromList_660fc1a9()
	{
		$this->assertSame(
			'[ab]?[xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'x', 'y'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bbx', 'bby', 'c']) returns '(?:c|a[xy]|bb[xy])'
	*/
	public function test_buildRegexpFromList_58a1b850()
	{
		$this->assertSame(
			'(?:c|a[xy]|bb[xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bbx', 'bby', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']) returns '(?:c|dd[xy]|[ab][xy])'
	*/
	public function test_buildRegexpFromList_23c1bd26()
	{
		$this->assertSame(
			'(?:c|dd[xy]|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['']) returns ''
	*/
	public function test_buildRegexpFromList_5cbf14d3()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array(''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['', '']) returns ''
	*/
	public function test_buildRegexpFromList_418d8f44()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array('', ''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ad', 'bd'], ["specialChars" => ["d" => "\\d"]]) returns '[ab]\\d'
	*/
	public function test_buildRegexpFromList_5b18c2d1()
	{
		$this->assertSame(
			'[ab]\\d',
			$this->rm->buildRegexpFromList(
				array('ad', 'bd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'ax', 'ad', 'd', 'dx', 'dd'], ["specialChars" => ["d" => "\\d"]]) returns '[\\da][\\dx]?'
	*/
	public function test_buildRegexpFromList_4032006c()
	{
		$this->assertSame(
			'[\\da][\\dx]?',
			$this->rm->buildRegexpFromList(
				array('a', 'ax', 'ad', 'd', 'dx', 'dd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'y', 'z']) returns '(?:[yz]|bar|foo)'
	*/
	public function test_buildRegexpFromList_561fe181()
	{
		$this->assertSame(
			'(?:[yz]|bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'baz', 'y', 'z']) returns '(?:[yz]|ba[rz]|foo)'
	*/
	public function test_buildRegexpFromList_c80c5a7f()
	{
		$this->assertSame(
			'(?:[yz]|ba[rz]|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'baz', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))'
	*/
	public function test_buildRegexpFromList_140f192a()
	{
		$this->assertSame(
			'(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))',
			$this->rm->buildRegexpFromList(array('a', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:aa|bb)(?:cc|dd)?'
	*/
	public function test_buildRegexpFromList_fa3816e9()
	{
		$this->assertSame(
			'(?:aa|bb)(?:cc|dd)?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy']) returns '(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?'
	*/
	public function test_buildRegexpFromList_4eeb6994()
	{
		$this->assertSame(
			'(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_2e6b327f()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'foo']) returns 'foo'
	*/
	public function test_buildRegexpFromList_efc21f10()
	{
		$this->assertSame(
			'foo',
			$this->rm->buildRegexpFromList(array('foo', 'foo'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a']) returns 'a'
	*/
	public function test_buildRegexpFromList_37e7519f()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'a']) returns 'a'
	*/
	public function test_buildRegexpFromList_46ecdb38()
	{
		$this->assertSame(
			'a',
			$this->rm->buildRegexpFromList(array('a', 'a'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['apple', 'april']) returns 'ap(?:ple|ril)'
	*/
	public function test_buildRegexpFromList_8b59a499()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			$this->rm->buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['bar', 'baz']) returns 'ba[rz]'
	*/
	public function test_buildRegexpFromList_e7ffb86f()
	{
		$this->assertSame(
			'ba[rz]',
			$this->rm->buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'fool']) returns 'fool?'
	*/
	public function test_buildRegexpFromList_1dff1ab5()
	{
		$this->assertSame(
			'fool?',
			$this->rm->buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'axed']) returns 'ax(?:ed)?'
	*/
	public function test_buildRegexpFromList_b4f156be()
	{
		$this->assertSame(
			'ax(?:ed)?',
			$this->rm->buildRegexpFromList(array('ax', 'axed'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}']) returns '[-!:<=>\\#\\\\\\]}$()*+.?[{|^]'
	*/
	public function test_buildRegexpFromList_8cb33695()
	{
		$this->assertSame(
			'[-!:<=>\\#\\\\\\]}$()*+.?[{|^]',
			$this->rm->buildRegexpFromList(array('!', '#', '$', '(', ')', '*', '+', '-', '.', ':', '<', '=', '>', '?', '[', '\\', ']', '^', '{', '|', '}'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', '.'], ["specialChars" => ["." => "."]]) returns '(?:a|.)'
	*/
	public function test_buildRegexpFromList_8540e325()
	{
		$this->assertSame(
			'(?:a|.)',
			$this->rm->buildRegexpFromList(
				array('a', '.'),
				array('specialChars' => array('.' => '.'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', '^y'], ["specialChars" => ["^" => "^"]]) returns '(?:x|^)y'
	*/
	public function test_buildRegexpFromList_8a71e271()
	{
		$this->assertSame(
			'(?:x|^)y',
			$this->rm->buildRegexpFromList(
				array('xy', '^y'),
				array('specialChars' => array('^' => '^'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xy', 'x$'], ["specialChars" => ["$" => "$"]]) returns 'x(?:y|$)'
	*/
	public function test_buildRegexpFromList_13ba1bfa()
	{
		$this->assertSame(
			'x(?:y|$)',
			$this->rm->buildRegexpFromList(
				array('xy', 'x$'),
				array('specialChars' => array('$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar']) returns '(?:bar|foo)'
	*/
	public function test_buildRegexpFromList_a3302107()
	{
		$this->assertSame(
			'(?:bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['*foo', '\\bar'], ["useLookahead" => true]) returns '(?=[\\\\*])(?:\\*foo|\\\\bar)'
	*/
	public function test_buildRegexpFromList_8b9e42a9()
	{
		$this->assertSame(
			'(?=[\\\\*])(?:\\*foo|\\\\bar)',
			$this->rm->buildRegexpFromList(
				array('*foo', '\\bar'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['?', 'bar'], ["specialChars" => ["?" => "."], "useLookahead" => true]) returns '(?:.|bar)'
	*/
	public function test_buildRegexpFromList_7ffb138d()
	{
		$this->assertSame(
			'(?:.|bar)',
			$this->rm->buildRegexpFromList(
				array('?', 'bar'),
				array('specialChars' => array('?' => '.'), 'useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'b']) returns '[ab]'
	*/
	public function test_buildRegexpFromList_c90b2457()
	{
		$this->assertSame(
			'[ab]',
			$this->rm->buildRegexpFromList(array('a', 'b'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['♠', '♣', '♥', '♦']) returns '[♠♣♥♦]'
	*/
	public function test_buildRegexpFromList_6335367b()
	{
		$this->assertSame(
			'[♠♣♥♦]',
			$this->rm->buildRegexpFromList(array('♠', '♣', '♥', '♦'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['lock', 'sock']) returns '[ls]ock'
	*/
	public function test_buildRegexpFromList_a3cf0b4d()
	{
		$this->assertSame(
			'[ls]ock',
			$this->rm->buildRegexpFromList(array('lock', 'sock'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'afoo'], ["useLookahead" => true]) returns '(?=[af])a?foo'
	*/
	public function test_buildRegexpFromList_8613531b()
	{
		$this->assertSame(
			'(?=[af])a?foo',
			$this->rm->buildRegexpFromList(
				array('foo', 'afoo'),
				array('useLookahead' => true)
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost']) returns 'bo[ao]st'
	*/
	public function test_buildRegexpFromList_4bbad47d()
	{
		$this->assertSame(
			'bo[ao]st',
			$this->rm->buildRegexpFromList(array('boast', 'boost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['pest', 'pst']) returns 'pe?st'
	*/
	public function test_buildRegexpFromList_596d7420()
	{
		$this->assertSame(
			'pe?st',
			$this->rm->buildRegexpFromList(array('pest', 'pst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boast', 'boost', 'bost']) returns 'bo[ao]?st'
	*/
	public function test_buildRegexpFromList_e644dceb()
	{
		$this->assertSame(
			'bo[ao]?st',
			$this->rm->buildRegexpFromList(array('boast', 'boost', 'bost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'best']) returns 'b(?:e|oo)st'
	*/
	public function test_buildRegexpFromList_aeb9f217()
	{
		$this->assertSame(
			'b(?:e|oo)st',
			$this->rm->buildRegexpFromList(array('boost', 'best'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst']) returns 'b(?:oo)?st'
	*/
	public function test_buildRegexpFromList_be3ccba2()
	{
		$this->assertSame(
			'b(?:oo)?st',
			$this->rm->buildRegexpFromList(array('boost', 'bst'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['best', 'boost', 'bust']) returns 'b(?:[eu]|oo)st'
	*/
	public function test_buildRegexpFromList_9753e32d()
	{
		$this->assertSame(
			'b(?:[eu]|oo)st',
			$this->rm->buildRegexpFromList(array('best', 'boost', 'bust'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cool']) returns '(?:b(?:oo)?st|cool)'
	*/
	public function test_buildRegexpFromList_9a764069()
	{
		$this->assertSame(
			'(?:b(?:oo)?st|cool)',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cool'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['boost', 'bst', 'cost']) returns '(?:b(?:oo)?|co)st'
	*/
	public function test_buildRegexpFromList_53994ab6()
	{
		$this->assertSame(
			'(?:b(?:oo)?|co)st',
			$this->rm->buildRegexpFromList(array('boost', 'bst', 'cost'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aax', 'aay', 'aax', 'aay']) returns 'aa[xy]'
	*/
	public function test_buildRegexpFromList_560c8444()
	{
		$this->assertSame(
			'aa[xy]',
			$this->rm->buildRegexpFromList(array('aax', 'aay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'baax', 'baay']) returns '[ab]aa[xy]'
	*/
	public function test_buildRegexpFromList_f3709bef()
	{
		$this->assertSame(
			'[ab]aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'baax', 'baay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'bbaax', 'bbaay']) returns '(?:a|bb)aa[xy]'
	*/
	public function test_buildRegexpFromList_7c889532()
	{
		$this->assertSame(
			'(?:a|bb)aa[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'bbaax', 'bbaay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aaax', 'aaay', 'aax', 'aay']) returns 'aaa?[xy]'
	*/
	public function test_buildRegexpFromList_77f7fecb()
	{
		$this->assertSame(
			'aaa?[xy]',
			$this->rm->buildRegexpFromList(array('aaax', 'aaay', 'aax', 'aay'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['abx', 'aby', 'cdx', 'cdy']) returns '(?:ab|cd)[xy]'
	*/
	public function test_buildRegexpFromList_62e02b3a()
	{
		$this->assertSame(
			'(?:ab|cd)[xy]',
			$this->rm->buildRegexpFromList(array('abx', 'aby', 'cdx', 'cdy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy']) returns '(?:a|bb)(?:xx|yy)'
	*/
	public function test_buildRegexpFromList_8a86c707()
	{
		$this->assertSame(
			'(?:a|bb)(?:xx|yy)',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'bbxx', 'bbyy', 'c']) returns '(?:c|(?:a|bb)(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_59fb8e0()
	{
		$this->assertSame(
			'(?:c|(?:a|bb)(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c']) returns '(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))'
	*/
	public function test_buildRegexpFromList_ea302659()
	{
		$this->assertSame(
			'(?:c|a(?:xx|yy|zz)|bb(?:xx|yy))',
			$this->rm->buildRegexpFromList(array('axx', 'ayy', 'azz', 'bbxx', 'bbyy', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ac', 'af', 'bbc', 'bbf', 'c']) returns '(?:c|a[cf]|bb[cf])'
	*/
	public function test_buildRegexpFromList_1cc75402()
	{
		$this->assertSame(
			'(?:c|a[cf]|bb[cf])',
			$this->rm->buildRegexpFromList(array('ac', 'af', 'bbc', 'bbf', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['^example.org$', '.example.org$', '^localhost$', '.localhost$'], ["specialChars" => ["^" => "^", "$" => "$"]]) returns '(?:\\.|^)(?:example\\.org|localhost)$'
	*/
	public function test_buildRegexpFromList_d463a304()
	{
		$this->assertSame(
			'(?:\\.|^)(?:example\\.org|localhost)$',
			$this->rm->buildRegexpFromList(
				array('^example.org$', '.example.org$', '^localhost$', '.localhost$'),
				array('specialChars' => array('^' => '^', '$' => '$'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xoxox']) returns 'x(?:ixi|oxo)x'
	*/
	public function test_buildRegexpFromList_b33646c2()
	{
		$this->assertSame(
			'x(?:ixi|oxo)x',
			$this->rm->buildRegexpFromList(array('xixix', 'xoxox'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['xixix', 'xixox', 'xoxox', 'xoxix']) returns 'x[io]x[io]x'
	*/
	public function test_buildRegexpFromList_c7d616c2()
	{
		$this->assertSame(
			'x[io]x[io]x',
			$this->rm->buildRegexpFromList(array('xixix', 'xixox', 'xoxox', 'xoxix'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb']) returns '(?:a|bb)(?:bar|foo)?'
	*/
	public function test_buildRegexpFromList_b02e083b()
	{
		$this->assertSame(
			'(?:a|bb)(?:bar|foo)?',
			$this->rm->buildRegexpFromList(array('afoo', 'abar', 'bbfoo', 'bbbar', 'a', 'bb'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by']) returns '[ab][xy]'
	*/
	public function test_buildRegexpFromList_4619fd0f()
	{
		$this->assertSame(
			'[ab][xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c']) returns '(?:c|[ab][xy])'
	*/
	public function test_buildRegexpFromList_f7523978()
	{
		$this->assertSame(
			'(?:c|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'x', 'y']) returns '[ab]?[xy]'
	*/
	public function test_buildRegexpFromList_660fc1a9()
	{
		$this->assertSame(
			'[ab]?[xy]',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'x', 'y'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bbx', 'bby', 'c']) returns '(?:c|a[xy]|bb[xy])'
	*/
	public function test_buildRegexpFromList_58a1b850()
	{
		$this->assertSame(
			'(?:c|a[xy]|bb[xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bbx', 'bby', 'c'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy']) returns '(?:c|dd[xy]|[ab][xy])'
	*/
	public function test_buildRegexpFromList_23c1bd26()
	{
		$this->assertSame(
			'(?:c|dd[xy]|[ab][xy])',
			$this->rm->buildRegexpFromList(array('ax', 'ay', 'bx', 'by', 'c', 'ddx', 'ddy'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['']) returns ''
	*/
	public function test_buildRegexpFromList_5cbf14d3()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array(''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['', '']) returns ''
	*/
	public function test_buildRegexpFromList_418d8f44()
	{
		$this->assertSame(
			'',
			$this->rm->buildRegexpFromList(array('', ''))
		);
	}

	/**
	* @testdox buildRegexpFromList(['ad', 'bd'], ["specialChars" => ["d" => "\\d"]]) returns '[ab]\\d'
	*/
	public function test_buildRegexpFromList_5b18c2d1()
	{
		$this->assertSame(
			'[ab]\\d',
			$this->rm->buildRegexpFromList(
				array('ad', 'bd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'ax', 'ad', 'd', 'dx', 'dd'], ["specialChars" => ["d" => "\\d"]]) returns '[\\da][\\dx]?'
	*/
	public function test_buildRegexpFromList_4032006c()
	{
		$this->assertSame(
			'[\\da][\\dx]?',
			$this->rm->buildRegexpFromList(
				array('a', 'ax', 'ad', 'd', 'dx', 'dd'),
				array('specialChars' => array('d' => '\\d'))
			)
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'y', 'z']) returns '(?:[yz]|bar|foo)'
	*/
	public function test_buildRegexpFromList_561fe181()
	{
		$this->assertSame(
			'(?:[yz]|bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['foo', 'bar', 'baz', 'y', 'z']) returns '(?:[yz]|ba[rz]|foo)'
	*/
	public function test_buildRegexpFromList_c80c5a7f()
	{
		$this->assertSame(
			'(?:[yz]|ba[rz]|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar', 'baz', 'y', 'z'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['a', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))'
	*/
	public function test_buildRegexpFromList_140f192a()
	{
		$this->assertSame(
			'(?:a(?:a(?:cc|dd))?|bb(?:cc|dd))',
			$this->rm->buildRegexpFromList(array('a', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd']) returns '(?:aa|bb)(?:cc|dd)?'
	*/
	public function test_buildRegexpFromList_fa3816e9()
	{
		$this->assertSame(
			'(?:aa|bb)(?:cc|dd)?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aacc', 'aadd', 'bbcc', 'bbdd'))
		);
	}

	/**
	* @testdox buildRegexpFromList(['aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy']) returns '(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?'
	*/
	public function test_buildRegexpFromList_4eeb6994()
	{
		$this->assertSame(
			'(?:aa|bb)(?:(?:cc|dd)(?:xx|yy))?',
			$this->rm->buildRegexpFromList(array('aa', 'bb', 'aaccxx', 'aaddxx', 'bbccxx', 'bbddxx', 'aaccyy', 'aaddyy', 'bbccyy', 'bbddyy'))
		);
	}
