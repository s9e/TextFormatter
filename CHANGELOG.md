2.9.4 (2021-07-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a8767be0fd9febecf4d1f88a76b8fc01c26abed9...c88fe365373a4656cf8129daf55314a2998c5487)

### Added

 - `6d3c5af8e` MediaEmbed: added Acast
 - `0c4e0c15c` MediaEmbed: added YouMaker
 - `dcf3cbf98` RegexpConvertor: added early return for empty regexps

### Changed

 - `c88fe3653` BBCodes: updated default CODE BBCode
 - `62b4e7f4b` BBCodes: updated default CODE BBCode
 - `29d5bcf16` Change code highlighting theme from `github-gist` to `github`.
 - `37fadd4e9` MediaEmbed: scrape from HTTP headers as well as body
 - `6e66120f0` MediaEmbed: updated 247Sports
 - `83629d2c4` MediaEmbed: updated Medium
 - `03c9dd73a` Use HLJS 11.0.1


2.9.3 (2021-05-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/918f105365229056cb2b8710fc2e2470f4783ae4...699577a02f4d0fd0c6d91900caf1888fc895b8ac)

### Changed

 - `fd0df9fe2` BBCodes: updated default CODE BBCode
 - `699577a02` Quick renderer: do not transform switch statements


2.9.2 (2021-05-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e81057d923f654f1fa6a5a6ed22c872723ff6c33...d7b5af5a7e90c2d2aa203aa4209fe04c71112b21)

### Fixed

 - `5df3c57b9` StylesheetCompressor: fixed an issue with UTF-8 chars incorrectly split


2.9.1 (2021-05-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/d764e9e4ac70b9bc398afe15b45b27568aa3ff23...e58944ee6476cf3732add1c042c40845fa3ee87d)

### Added

 - `2091db6b6` FixUnescapedCurlyBracesInHtmlAttributes: added support for arrow functions
 - `129691ddd` MediaEmbed: added Odysee

### Removed

 - `ce1817bf0` MediaEmbed: removed defunct sites Break, GameTrailers, and Mixer

### Changed

 - `16a01b3df` ElementInspector: updated definitions from HTML specs
 - `70e05f3c3` JavaScript: replaced substr() with substring()
 - `632452c0a` MediaEmbed: updated Odysee
 - `fa6ce8f1a` MediaEmbed: updated YouTube
 - `b680fbcd0` Replaced ternaries with null coalescing operators
 - `e51239b7e` UrlFilter: updated regexp and JavaScript version


2.9.0 (2021-04-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e2d5fba85a92a62b477957943de3aecc0cc4d023...561eec5a1770736c08aa83778e287d05de5fc2d0)

### Added

 - `4256d1f08` MediaEmbed: added Apple Podcasts
 - `beb0082bc` XPathConvertor: added support for PHP 8.0 string functions

### Changed

 - `f801b61f2` MediaEmbed: updated IMDb
 - `44f22bfc8` MediaEmbed: updated Wistia
 - `bd16776c5` Updated bundles
 - `f1fe19a42` XPathConvertor: made the optional PHP features togglable
 - `26ac52914` XSLT: explicitly set htmlspecialchars() escape mode


2.8.6 (2021-03-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/995235f221dbc7fd3ac495313f86af1e46aea441...4b83f7c67060b8a028856d000b5bd89302ce3bb3)

### Changed

 - `4b83f7c67` BBCodes: updated default CODE BBCode
 - `c7b72ac82` MediaEmbed: updated Podbean
 - `ed19f57c0` MediaEmbed: updated SoundCloud
 - `6a233cf46` MediaEmbed: updated TikTok
 - `739cd5f10` MediaEmbed: updated WorldStarHipHop


2.8.5 (2021-03-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/7856f66f4cfaf1402b8ec30c0f0ba124da4e9b76...d11cd32f195904510ab24c0f69f613261e267056)

### Added

 - `33168acaa` MediaEmbed: added AMP metadata

### Changed

 - `b52de21fd` EnforceContentModels: generate breakParagraph rules where applicable
 - `813be2c3d` MediaEmbed: updated Megaphone
 - `126771444` MediaEmbed: updated Sportsnet


2.8.4 (2021-03-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/46ff5bf0a660caae4291f19a50bc742d93b6bf3e...19699dc2fece04414df69e8a16444c538e052038)

### Added

 - `03edc53a0` MediaEmbed: added support for fb.watch URLs

### Fixed

 - `f3a88bece` Litedown: fixed an issue with fenced code blocks inside of lists

### Changed

 - `19699dc2f` Emoji: ignore aliases that are followed by U+FE0E
 - `36e505ced` MediaEmbed: updated BBC News
 - `e838f3779` MediaEmbed: updated Rutube
 - `9f08233e6` MediaEmbed: updated Twitch
 - `03af8d8e2` Parser: simplified outputTag()
 - `a11f4c232` TaskLists: recheck tags at the end of configuration


2.8.3 (2021-02-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a6edb261947230eff8457d9d87ce2cf80de72872...cf913ebbe2c997596aeb63f8586dcfff440ae957)

### Fixed

 - `4d0b7595f` Emoji: fixed a PHP error that can occur with empty aliases
 - `327f06a6a` Emoji: fixed an issue with some Unicode aliases

### Changed

 - `cf913ebbe` BBCodes: updated default CODE BBCode
 - `38f158f2f` MediaEmbed: updated Anchor


2.8.2 (2021-01-23)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2ac2ab8c28849311424a78ea21a8368423053ce3...23ee8a5d5fcfcc3001ee8dfc1e64d92f3a0c2801)

### Added

 - `b5d664bb3` MediaEmbed: added Imgur oEmbed info
 - `c21685645` MediaEmbed: added Instagram Reels
 - `06d5e490f` PHP renderer generator: added a fast path for handling static node names

### Removed

 - `f357b2e64` TemplateParser: removed unused parameters

### Changed

 - `0dd855c9e` MediaEmbed: updated Streamable
 - `23ee8a5d5` MediaEmbed: updated Wistia
 - `f14bd4eca` TemplateParser: mark boolean attributes in the representation


2.8.1 (2020-12-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/52e1d46415f93ea1c8fed9846d8240fb5396b238...16d018d6e2c042ec4c9843c84d82c17b11c5d1f1)

### Fixed

 - `6909b2327` TemplateHelper: fixed handling of HTML special characters in highlightNode()

### Changed

 - `0a661c962` AttributeFilters: refactored HashmapFilter and MapFilter
 - `c8ae84391` BBCodes: updated default CODE BBCode
 - `816182d45` MediaEmbed: updated BitChute
 - `16d018d6e` MediaEmbed: updated TikTok
 - `704b16b4f` RegexpFilter: reworked safeness checks


2.8.0 (2020-12-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/dfb9d4efa61578d89ca250243e2871887e23ba5d...0edf2e91b2ebeb8017a5804bcb2c26664cd2cb31)

### Added

 - `bb8c095e6` FilterSyntaxMatcher: added explicit octal notation
 - `ea51b491d` MediaEmbed: added Falstad Circuit Simulator
 - `159784fb2` MediaEmbed: added JSFiddle
 - `7baf9538c` MediaEmbed: added Rumble
 - `935957bb4` MediaEmbed: added support for dark themes

### Removed

 - `c7548f5e1` MediaEmbed: removed discontinued FOX Sports site

### Changed

 - `0edf2e91b` MediaEmbed: updated JSFiddle
 - `6b99842fe` MediaEmbed: updated Libsyn
 - `16a1e596a` MediaEmbed: updated Twitter
 - `df91465b8` Updated bundles


2.7.6 (2020-11-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/6dd4aa5164e7cda62c52fc7f1f99bbfb59529af1...338e489862167e1986008c6057202f53cf0658b3)

### Changed

 - `2b9267496` BBCodes: updated default CODE BBCode
 - `a583e346c` MediaEmbed: updated 247Sports
 - `0b138efc4` MediaEmbed: updated Amazon
 - `923310847` MediaEmbed: updated BBC News
 - `338e48986` MediaEmbed: updated Spotify
 - `30b0a11c8` MediaEmbed: updated Stitcher
 - `47c929c02` MediaEmbed: updated dumpert


2.7.5 (2020-09-21)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/9a8e77826604e24deacbd280c2543b68b4ff2a84...c49e5a4308b741a8a99207a58ce635e0f11dde8d)

### Changed

 - `c49e5a430` BBCodes: updated default CODE BBCode
 - `480398516` BBCodes: updated default CODE BBCode
 - `c88642cdc` Emoji: updated to Unicode 13.1
 - `5a6c80ea0` MediaEmbed: updated Getty Images
 - `14923f953` MediaEmbed: updated Gfycat
 - `693b57ae9` MediaEmbed: updated Medium


2.7.4 (2020-08-20)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/af4a87455710a5c83d54d71938fbb0e359e852c3...e62774c4e372896dd777d4fce36497a293fa14ef)

### Added

 - `e62774c4e` MediaEmbed: added Clyp
 - `79c359ed8` MediaEmbed: added Mixcloud oEmbed data

### Changed

 - `039a08e49` BBCodes: updated default CODE BBCode
 - `5aa06ccda` MediaEmbed: updated BBC News
 - `6ac0b25b2` MediaEmbed: updated Kaltura
 - `74ee840cd` MediaEmbed: updated Spotify
 - `9f6654eb9` MediaEmbed: updated Stitcher


2.7.3 (2020-07-20)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/aea006fbd90bf23374c538e7c1792eda13e44c89...1fa8bcd3b13425bea643ec5a9fa742746e4c6c96)

### Added

 - `b65d3a585` MediaEmbed: added Castos
 - `d96e5b47b` MediaEmbed: added CodePen
 - `c7265bc7a` MediaEmbed: added Kaltura
 - `33da8e189` MediaEmbed: added TradingView
 - `1fa8bcd3b` MediaEmbed: added Vimeo oEmbed info

### Changed

 - `c81c92802` CallbackGenerator: explicitly cast callback to string
 - `715710926` MediaEmbed: updated MSNBC
 - `e9b901e96` MediaEmbed: updated Medium


2.7.2 (2020-06-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/16a1c759307da654bedd7b649bef6930384e9561...4fd902b91d2bedbcab1b435338a584a58e8a34ac)

### Changed

 - `4fd902b91` BBCodes: updated default CODE BBCode
 - `b2823a510` BBCodes: updated default CODE BBCode
 - `b13470f70` MediaEmbed: updated Audiomack
 - `a5cce0a8e` MediaEmbed: updated Twitch
 - `615378133` MediaEmbed: updated Twitch


2.7.1 (2020-06-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ebeadcdb7c1ed11633de56ca05fd8300b7e31487...ca83829e5a519bffc8a0c3d1219c3cdf3a5c35bf)

### Added

 - `c34a75ea4` MediaEmbed: added Wistia

### Changed

 - `7f2a0a843` BBCodes: updated default CODE BBCode
 - `388e4fbfc` JavaScript: updated externs
 - `4d2792bc4` MediaEmbed: reinstated unofficial support for hashless VK URLs
 - `ca83829e5` RegexpConvertor: reorganized code
 - `b141e1098` RegexpConvertor: updated Unicode properties
 - `c5c2d2cbf` TaskList: replaced template manipulation with SweetDOM


2.7.0 (2020-05-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/9cb0710514fe8eb7e4c662483b6bb2ad028e046b...2e127910c4bdf8b4250cc455239a7a03fc8878d8)

### Added

 - `ea4658c5b` Added template manipulation via SweetDOM
 - `dc61d807c` Litedown: added support for self-generated "id" attributes in headers

### Removed

 - `f9eb0e264` Autolink: removed dead code
 - `2e127910c` PHP Renderer: removed redundant removal of meta-elements

### Changed

 - `cc0c621c2` Litedown: improved compatibility with original Markdown rules for parentheses in links


2.6.0 (2020-05-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/1f92145ebeba84dc3736c608d2e56cdf1c3e31fa...ef7a64e6b1da50a93316c18fc992fb24f92eb7ae)

### Added

 - `62942bbc2` Added AddAttributeValueToElements and SetAttributeOnElements template normalizers
 - `d4d0b66ec` MediaEmbed: added GIPHY
 - `ef7a64e6b` MediaEmbed: added loading="lazy" attribute to iframes
 - `f136125c1` MediaEmbed: added support for GIPHY videos

### Changed

 - `a79accfd0` Emoji: updated Twemoji URL
 - `ea42a6e90` Emoji: updated to Unicode 13.0
 - `3f51c0852` MediaEmbed: updated Facebook
 - `782bbadbb` Renderers: ensure that floating point numbers are displayed in the C locale
 - `a1d089576` SetRelNoreferrerOnTargetedLinks: refactored to extend AddAttributeValueToElements
 - `e08158d2f` TaskLists: ignore task IDs during live preview
 - `1b5d46223` XSLT: ensure that the decimal separator is a dot regardless of locale


2.5.0 (2020-04-29)
==================

**⚠️ This release contains functional changes. See [docs/Internals/Changes.md](https://s9etextformatter.readthedocs.io/Internals/Changes/#250) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/b9e564e26f6b33d775577d24f4188ce98bb0402d...c12c0db9c823a1c1aa519f5eb96b958c813a4fe2)

### Added

 - `f2d1ff160` Added TaskLists plugin
 - `c12c0db9c` Fatdown: added TaskLists
 - `96912cdeb` Litedown: added support for automatic links

### Changed

 - `885f7e234` Renderer: improved performance of safety check
 - `436c2d8d9` Utils: improved the performance of various functions


2.4.1 (2020-04-11)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a9644df49d31c2fcadf65cc2e6b5816c75a390ae...7e3c1a8c0eaf65ab7b62b798696530904b0bd113)

### Added

 - `5c358aa8f` DisallowUnsupportedXSL: added xsl:attribute and xsl:element checks

### Changed

 - `af2944f19` DisallowUnsupportedXSL: refactored attribute requirements
 - `7e3c1a8c0` MediaEmbed: updated Facebook
 - `7290f8b4e` PHP renderer: match libxslt serialization of minimizable attributes in HTML
 - `7414ca9d5` Quick renderer: replaced hardcoded value with constant


2.4.0 (2020-03-31)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/39088b779b71e120098a7a8a8f7dc4900cb8b671...6d9dcd09ca05a503c6ba7b1e146d1f05805f7f20)

### Added

 - `6d9dcd09c` TemplateChecks: added DisallowUncompilableXSL
 - `039015605` TemplateChecks: added DisallowUnsupportedXSL, enabled by default

### Changed

 - `a5ddda129` DisallowUnsafeDynamicURL: improved detection of safe URLs
 - `27dbc900b` MediaEmbed: updated Spotify


2.3.7 (2020-03-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b96dc121d2dfaabe7e3df42e76d93ffb857cf377...544f430f9c182b88cc9da9374a80353f6c4e466f)

### Fixed

 - `12bdf56b7` JavaScript: fixed scripts not being executed on Blink browsers

### Changed

 - `544f430f9` BBCodes: updated default CODE BBCode
 - `61d3dcf2a` BBCodes: updated default CODE BBCode
 - `194bc5388` BBCodes: updated default CODE BBCode
 - `98fa77467` JavaScript: updated externs


2.3.6 (2020-02-24)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/947cfecab65578dc0a50cc319f760e25a4ac3f75...3eae3fbe1e2244677850dfe3b292635619dcea4f)

### Changed

 - `3eae3fbe1` MediaEmbed: updated definitions


2.3.5 (2020-02-24)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2e1fcc28af2131802d4a99f98840279f27f8a34f...732a54ac75d178bb4a07344af946e05fa92c37c9)

### Fixed

 - `15bfc779b` OptimizeChooseDeadBranches: fixed 00 incorrectly considered true
 - `732a54ac7` XPathHelper: fixed an issue with overzealous minification


2.3.4 (2020-02-18)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/d958fd32471905fbecd2337b2e92ae131e45be94...3b08570a2a944b5a52f9282f86b8561bd46a3363)

### Added

 - `9db9113e6` Autolink: added support for trailing underscores
 - `4de32bb77` JavaScript: added minifier hints for plugin regexp

### Fixed

 - `c7d1b9e72` Autolink: fixed an issue where valid characters could be removed from the end of a URL
 - `3b08570a2` TemplateNormalizer: fixed an issue where the XSL namespace could become unregistered in XPath

### Changed

 - `3d1969853` MediaEmbed: updated MSNBC and Tumblr
 - `c1e06bc87` MediaEmbed: updated TikTok
 - `9bf0d3823` MediaEmbed: updated XboxClips, now GameClips.io
 - `19674d155` XPathConvertor: do not add extraneous calls to boolean() in conditions


2.3.3 (2020-01-23)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/dc1858a6a72794050c55484012dba18eaf8348bf...0af04297e21ec50f8fdd0ac385a602991921fe37)

### Removed

 - `b79f84836` MediaEmbed: removed defunct site CollegeHumor

### Changed

 - `0af04297e` BBCodes: updated default CODE BBCode
 - `385992df9` MediaEmbed: updated Vocaroo
 - `138821eac` Tag: reorganized code
 - `ad91b2d37` XPathHelper: improved minification of boolean operators
 - `6943368f2` XPathHelper: improved minification of consecutive non-word characters
 - `55782b27a` XPathHelper: remove redundant parentheses in minify()
 - `26004e063` XPathHelper: replaced string encoder in minify()
 - `00510a5b6` XPathHelper: simplified and improved minification of operators


2.3.2 (2020-01-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/65a0605f163b8ffcf7145357f167b153f31cd168...0f71669094219adf9ea620acf870bff66d356b4d)

### Added

 - `f6c496721` Autolink: added support for rootless URLs
 - `0f7166909` FilterSyntaxMatcher: added support for numeric literal separator
 - `d519bad01` HTMLEntities: added support for HTML5 entities
 - `076524e87` MediaEmbed: added support for Spotify podcasts
 - `1bd195933` TemplateLoader: added support for HTML 5 entities in HTML templates
 - `ba20ec844` TemplateLoader: added support for HTML 5 entities in XSLT templates

### Changed

 - `3fe1f6482` BBCodes: updated default CODE BBCode
 - `b3cea0820` FilterSyntaxMatcher: improved support for escaped characters
 - `94071e2fe` TemplateLoader: improved handling of HTML5's AMP entity
 - `33d90df58` XPathHelper: rewritten string encoder


2.3.1 (2019-12-26)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/26d6ee3a931a25acfea3096f62f0cc42172f3859...84f8f35fdc9f636e9c18a39397baddf0cb99bd5d)

### Added

 - `abc127c7d` MediaEmbed: added Spreaker
 - `0aaff6af6` XPathConvertor: added support for booleans in math expressions

### Removed

 - `0d8acace1` MediaEmbed: removed defunct site Plays.tv

### Fixed

 - `84f8f35fd` Parser: fixed trimFirstLine rule ignored on paired tags

### Changed

 - `0764f9e3c` XPathConvertor: do not backtrack on attribute names
 - `07df31fbe` XPathHelper: improved minification of substractions
 - `b19a9207e` XPathHelper: use the XPathConvertor parser for complex expressions


2.3.0 (2019-11-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/1ccc5c9c22603df36ff0628f4ff1aaae3d8a8fc4...7dde9892c03cf4106d7454f2fb0f1481f417e163)

### Added

 - `7dde9892c` Added support for custom header in generated PHP files
 - `727da332c` Bundle: added getParser() and getRenderer() definitions to base class
 - `e707f6f28` BundleGenerator: added support for bundling the JavaScript source
 - `5c576086a` MediaEmbed: added Anchor
 - `2ec9591ce` MediaEmbed: added Megaphone

### Changed

 - `729fbb540` MediaEmbed: updated CNBC
 - `a669a53d4` Parser: simplified the computation of allowed tags in new context
 - `e4030cf6a` StylesheetCompressor: split into smaller chunks to appease Google Closure Compiler
 - `30654b4f5` Updated JavaScript Logger


2.2.0 (2019-10-27)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#220) for a description. ⚠️**

**⚠️ This release contains functional changes. See [docs/Internals/Changes.md](https://s9etextformatter.readthedocs.io/Internals/Changes/#220) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/de2752a252047ef8899dab0ac0880be306bca474...0e277e649e6551842839eadfaf097c45b39e0bec)

### Added

 - `b52eb84f3` AbstractConstantFolding: added support for folding conditional expressions
 - `da7387e1b` FoldConstantXPathExpressions: added support for boolean expressions
 - `eeb5ec68f` FoldConstantXPathExpressions: added support for simple comparisons
 - `a2c8cc0be` InlineXPathLiterals: added support for true() and false()
 - `cd6eede77` Live preview: added support for data-s9e-livepreview-hash attribute
 - `e560157b9` Live preview: added support for data-s9e-livepreview-onupdate attribute/event
 - `fd984e34e` MediaEmbed: added Mixer
 - `4ef14d2c5` TemplateNormalizations: added DeoptimizeIf
 - `f7dddb9a6` TemplateNormalizations: added OptimizeChooseDeadBranches
 - `179035702` XPathConvertor: added support for true() and false()

### Removed

 - `414f457ed` MediaEmbed: removed defunct site Tinypic
 - `abf0bea88` Ruleset: removed useless code

### Fixed

 - `f52add17f` Fixed Google Closure Compiler warnings in live preview

### Changed

 - `6d8d0e82c` AbstractConstantFolding: improved performance
 - `4d08e2493` Amend document typos
 - `492288d7b` BBCodes: updated [code] BBCode
 - `510176e04` BBCodes: updated [code] BBCode
 - `ad030d0a6` BBCodes: updated [code] BBCode
 - `98acaa712` BBCodes: updated var descriptions in bundled BBCodes
 - `e8501554b` ContextSafeness: consider backticks unsafe in JavaScript
 - `e7baa21e2` ElementInspector: updated to HTML 5.3
 - `8ef407006` Emoji: updated to Unicode 12.1
 - `71c5b4d15` Escaper: escape tildes by default
 - `0e277e649` FoldArithmeticConstants: restricted additive identity folding
 - `507cdadd4` FoldConstantXPathExpressions: ignore irrational numbers
 - `74d308561` JavaScript: updated externs
 - `bde1c604d` Live preview: improved handling of update event
 - `ba54cfa16` Live preview: renamed data-s9e-livepreview-postprocess to data-s9e-livepreview-onrender
 - `6b35b43c7` MediaEmbed: updated Audioboom
 - `912a5f52c` MediaEmbed: updated CNN
 - `3e0df2054` MediaEmbed: updated Facebook
 - `e92f180d6` RecursiveParser: use a DEFINE group for group expressions
 - `99b7f0b84` RegexpConvertor: updated properties to latest Unicode specs
 - `8b2ba9100` XPath: reorganized code
 - `ed9e70daa` XPathConvertor: simplified grouping of boolean operators and expressions


2.1.2 (2019-08-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0c6ba01d3a30ee01c3ac7dd66a87712023a88962...d14f8c92d6f795fe1483cf7fff1f06e0b2439e81)

### Fixed

 - `61d2a9b6c` Emoji: fixed compiler warnings in JavaScript parser
 - `15fa701f5` Fixed compiler warning in JavaScript version of urldecode()
 - `b69ead06d` Fixed various compiler warnings in JavaScript sources
 - `987acc8f4` Fixed various compiler warnings in JavaScript sources
 - `da93809a4` Fixed various compiler warnings in JavaScript sources
 - `55b029742` Keywords: fixed compiler warnings in JavaScript parser
 - `b75c7ccb5` Litedown: fixed JavaScript warnings in Emphasis pass

### Changed

 - `6f2f1fcc9` BBCodes: updated default CODE BBCode
 - `4dd69b5dd` FancyPants: updated JavaScript parser
 - `bb7c27a95` HTMLElements: simplified attributes parsing
 - `1dbbcc606` HTMLElements: updated attribute filter definitions
 - `9e2a4e874` JavaScript: updated externs
 - `3c3bc7d3f` JavaScript: updated externs
 - `a28771a30` JavaScript: updated regexp result handling and casting
 - `a2e4c9a83` Litedown: eliminated superfluous variable in Blocks pass
 - `0ddf1936c` Litedown: made variable non-nullable in Emphasis pass
 - `f5f10524a` Parser: ensure that regexpLimit is set before executing a plugin's regexp
 - `fea82fedf` PipeTables: handle tables inside of spoiler blocks
 - `ef703b9b1` Reorganized DOM diffing in live preview


2.1.1 (2019-08-05)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/6902c1dc06fccea08565b8966c5a2cf01e3bc85f...decd84340b57554f78763ce1441c1a24944a2573)

### Added

 - `28a405d9e` MediaEmbed: added Trending Views

### Changed

 - `e83bf910c` BBCodes: updated the algorithm that handles backslashes
 - `decd84340` BBCodes: updated the regexp that captures unquoted attributes
 - `58835aa86` Litedown: exclude ASCII punctuation from inline scripts' short form


2.1.0 (2019-07-24)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#210) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/9bd8b56ebbb9a93eb99c05141e31fded4149de0e...3da711fed8f9d7662b1ecd7e34ca9551c51b1f2b)

### Added

 - `aed44ffd6` Added FilterHelper and short syntax
 - `c8e3025c5` Added RecursiveParser, to be used internally
 - `357c9c773` DisallowUnsafeDynamicURL: added support for xsl:choose statements
 - `3da711fed` Litedown: added support for block spoilers and inline spoilers

### Removed

 - `a59c76606` MediaEmbed: removed defunct site Healthguru
 - `c7a59a846` MediaEmbed: removed defunct site LiveCap
 - `8d4310813` MediaEmbed: removed defunct site Yahoo Screen
 - `6f7aa1ab2` MediaEmbed: removed discontinued site HumorTV

### Fixed

 - `1b79cd300` Fixed default sortPriority value in Tag.js
 - `8d6c6327b` Fixed handling of ignored attributes in live preview

### Changed

 - `3c63ecc4a` BBCode: properly reject colons in BBCode names during configuration
 - `531c0e963` FilterSyntaxMatcher: renamed ArrayKey, ArrayValue to Scalar, Literal
 - `13cfdfa2e` Litedown: set all blockquote tags to the same priority
 - `27f70904e` Litedown: skip the next auto line break when a forced line break is used
 - `42d410db1` MediaEmbed: updated BBC News
 - `903568b8a` MediaEmbed: updated Bleacher Report
 - `81239be11` MediaEmbed: updated CNBC
 - `f2d0b1cb1` MediaEmbed: updated Internet Archive
 - `69d0bfeb4` MediaEmbed: updated MRCTV
 - `1a78a1e62` MediaEmbed: updated MSNBC
 - `58874c6f0` MediaEmbed: updated İzlesene
 - `872421b92` OptimizeChoose: switch the logic of when/otherwise if it eliminates a branch
 - `34b2c5369` Parser: do not needlessly fix self-closing tags with an autoClose rule
 - `f7eb4688d` Parser: improved the tag sorting algorithm
 - `9641a2721` RecursiveParser: use the match name as a tiebreaker when sorting
 - `7cd28aa6a` XPathConvertor: simplified tokenizeXPathForExport()
 - `a3e52ece8` XPathConvertor: updated to use RecursiveParser
 - `d43da72ea` XPathConvertor: use a PCRE MARK to identify the match


2.0.1 (2019-06-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/f15541ce19e9af7eeb48b8b91b3ee93bec5f2f9b...021b68b5b13ae0ebfb2fe495b00f6e920a9cbd86)

### Changed

 - `021b68b5b` Emoji: fixed Twemoji filenames for ZWJ sequences


2.0.0 (2019-05-31)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#200) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/641bdfc5516c4e0da10df19ae33d59837f08db41...1f917de4221ea8bf03c19ce083ea3a0c65504539)

### Added

 - `2868b21d6` Added new named parameters to tag filters
 - `cfb9bb738` Fatdown: added mailto to the list of allowed schemes
 - `bec1c0810` MediaEmbed: added Sporcle
 - `ab956458c` MediaEmbed: added support for Google Sheets charts
 - `40adb246e` TemplateNormalizer: added support for custom normalizations list in constructor
 - `27716cd91` Utils\Http\Client: added returnHeaders option

### Removed

 - `7bb7842f8` AbstractNormalization: removed the $onlyOnce property
 - `5d443634e` Emoji: removed attribute name from configurator
 - `4d6998300` RegexpBuilder: removed CharacterClassBuilder
 - `07a289f23` Removed deprecated APIs
 - `571a82ce8` Renderers\XSLT: removed unreachable code
 - `c2e519208` TemplateHelper: removed deprecated API

### Fixed

 - `f018f9da3` Fixed rejection of templates that can only be partially rendered by the Quick renderer
 - `234c8df36` Utils\Http\Client: fixed custom headers not being reset between requests

### Changed

 - `1f917de42` AVTHelper: improved handling of escaped braces
 - `6f827c274` Autolink: prevent partial replacements inside of a URL
 - `ffd66f8b8` Bumped PHP requirements to 7.1
 - `f237bd07c` DisallowUnsafeDynamicURL: reorganized code
 - `bf09f813a` Emoji: changed default template to use Twemoji assets
 - `407f5208e` JavaScript\FunctionProvider: prefill cache and keep it in sync
 - `40a9f8e66` MediaEmbed: updated BBC News
 - `cb7888d3d` RegexpBuilder: refactored to use s9e\RegexpBuilder
 - `bf24e0d72` RendererGenerators\XSLT: reorganized code
 - `47c32647d` RendererGenerators\XSLT: replaced optimizer
 - `6a71b5f29` Reorganized Configurable trait
 - `9f029995b` Tag: reorganized invalidate()
 - `b18ba8205` TemplateLoader: replace CDATA on load
 - `5c18a0de1` UrlFilter: allow non-HTTP, non-FTP URLs with no authority part
 - `e70e95de4` Utils\Http\Client: updated API
 - `212389923` XPathConvertor: identify translate() as a string function
 - `b9aa4c8e3` XPathConvertor: refactored and rewritten
 - `234de3584` XPathConvertor: updated Runner constructor


1.4.3 (2019-04-26)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/181b70563fdb083022c6b6c7eb3f59b6502487ff...af875b2e85d510a31616e814f18c78e1700b2449)

### Added

 - `5b4420b4c` MediaEmbed: added Sendvid

### Changed

 - `d3860f3a5` MediaEmbed: updated XboxDVR


1.4.2 (2019-03-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/bfba51bbce7d18e0c2686f075b8910e8afa5d51a...639a92db4ec39ca6df3ef5dba94814701eea5877)

### Added

 - `35cd6f3bd` MediaEmbed: added 247Sports
 - `9312daef7` MediaEmbed: added BitChute

### Changed

 - `639a92db4` Censor: do not use atomic grouping in JavaScript regexp
 - `3e576cd3d` MediaEmbed: updated 247Sports
 - `5f26c13a5` MediaEmbed: updated Amazon
 - `78d31f3f0` MediaEmbed: updated Gfycat
 - `ae5ac62af` MediaEmbed: updated MLB


1.4.1 (2019-03-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/f03c717ff37e732e595eb2afb4a777c4180732e3...c164ec1163f943840e71bbdbc01ca92fa462c38b)

### Added

 - `01d8b015f` Autovideo: added controls to the player
 - `496320829` MediaEmbed: added TikTok

### Changed

 - `2fb37c77a` Emoji: updated to Unicode 12.0
 - `c164ec116` MediaEmbed: register variables on setup and update them in real time
 - `c35ed70dd` MediaEmbed: updated Spotify


1.4.0 (2019-02-01)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/77cd528dd79ff7a873f407fc76dbef8c0ec1973b...020ea657d2203a68d06e935a7e238b0efe80fafd)

### Added

 - `63574b76c` UninlineAttributes: added a fast path for static attributes

### Changed

 - `d2d21bae3` AVTHelper: simplified parse()
 - `5644c5f88` Configurator: do not implicitly call finalize() in asConfig()
 - `3440c0225` Emoji: updated EmojiOne URL
 - `fc470bbd9` FoldArithmeticConstants: updated regexps
 - `3a2d4d3c0` MediaEmbed: updated Gfycat
 - `446c4295e` MediaEmbed: updated Gist
 - `ea5d59768` MediaEmbed: updated Google Sheets
 - `4be095e3e` MediaEmbed: updated Google Sheets
 - `c216c71b5` MediaEmbed: updated Pinterest
 - `020ea657d` MediaEmbed: updated The Guardian
 - `582dcb793` MediaEmbed: updated Tumblr
 - `1fc93878f` NodeLocator: reorganized internal API
 - `c61d88266` NormalizeAttributeNames: replaced PHP conditional with XPath predicate
 - `76f73f2ab` PHP Serializer: cache the value of all "void" attributes
 - `8ca414940` Quick: improved the regexp used to match string literals in source
 - `a24789833` TemplateLoader: remove redundant namespace declarations on load/save
 - `192081983` TemplateNormalizations: improved the handling of whitespace in text nodes
 - `1c805a2ee` TemplateNormalizer: reduced the number of passes that run on uninlined attributes
 - `facc8d79b` TemplateParser: use XPath queries instead of DOM methods
 - `f944f8789` UninlineAttributes: use a document fragment when creating xsl:attribute elements
 - `4e355e608` XPathHelper: reorganized parseEqualityExpr()


1.3.2 (2018-12-23)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8aa52c780b9d3818d9f0760e21a54c8a864d7db5...6b7d65548ee1dd7ef83f2c46748c179f2ebadedd)

### Added

 - `640eed514` ColorFilter: added support for alpha channel

### Changed

 - `7651e0c8a` MediaEmbed: updated Gfycat
 - `9341727fd` MediaEmbed: updated Google Sheets
 - `62b26960b` MediaEmbed: updated Instagram
 - `3eb59f3e9` MediaEmbed: updated MSNBC and Team Coco
 - `1ba5d6685` MediaEmbed: updated Medium
 - `397e9f640` MediaEmbed: updated Twitter
 - `2d1755cbd` MediaEmbed: updated Twitter
 - `9dc6edfb2` MediaEmbed: updated dynamically-sized sites
 - `21a52e55d` Parser: reject invalid UTF-8 input
 - `ec51bb0b9` Parser: remove control characters from attribute values


1.3.1 (2018-10-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/1e165c0b6c7aabe24bea62680e6c2d8c94c64745...8aa52c780b9d3818d9f0760e21a54c8a864d7db5)

### Added

 - `8c9bfcbf2` AbstractDynamicContentCheck: added support for multiple named attributes in XPath expressions
 - `c3c8c86ea` DisallowUnsafeCopyOf: added support for multiple attributes in xsl:copy-of
 - `e6668ae95` MediaEmbed: added support for /embed/ URLs in Internet Archive
 - `a2bd8c7b0` MediaEmbed: added support for YouTube playlists with no ID
 - `e4db3c75d` TemplateParser: added support for multiple attributes in xsl:copy-of

### Removed

 - `119cd186e` MediaEmbed: removed defunct sites Kiss Video and Videomega

### Changed

 - `9a1107012` AbstractDynamicContentCheck: reorganized checkExpression() code
 - `f1f24c6e9` Censor: simplified Helper's regexp
 - `8aa52c780` MediaEmbed: updated Hudl
 - `3f5de94b9` MediaEmbed: updated Internet Archive
 - `cdb0984ad` MediaEmbed: updated example URLs to use HTTPS
 - `23aadcb92` TemplateHelper: updated highlightNode()


1.3.0 (2018-09-20)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#130) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/3df7e018c9c613ac8968e8d6e8440a4605b9ff26...5654c323067a48ff9a9931d74a804d927bfe05cb)

### Added

 - `2be2114b4` BBCodes: added colspan/rowspan to TD and TH
 - `b1f9068e9` ClosureCompilerApplication: added support for user-defined invocation
 - `77cd86307` Emoji: added support for Twemoji-style filenames
 - `f73f8c737` Emoji: added support for Unicode 11.0
 - `d64c68848` MediaEmbed: added getSites()
 - `51a901525` MediaEmbed: added support for /presentation/ URLs in Scribd

### Removed

 - `4473762e5` MediaEmbed: removed Fora.tv

### Fixed

 - `684350c16` MediaEmbed: fixed a potential issue with cached HTTP client using the wrong cache dir

### Changed

 - `e2abcec27` Emoji: capture trailing U+FE0F even when it's superfluous
 - `89d0da364` Emoji: merged default shortnames into custom aliases
 - `5b705ab2d` Emoji: renamed JavaScript hint
 - `71c4369a3` JavaScript: renamed $exportMethods to $exports
 - `5baaab747` JavaScript: sort exports in lexical order
 - `072c87d2d` MediaEmbed: updated Getty Images
 - `70c62091a` MediaEmbed: updated Global News
 - `7d42f039d` MediaEmbed: updated Rutube
 - `e29597a06` MediaEmbed: updated VK
 - `5a42834b2` RegexpConvertor: updated Unicode properties
 - `6ccd4a31b` Remove e/i/s tags in live preview to emulate the PHP renderer's algorithm


1.2.2 (2018-07-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8af61dce9a28079036fcdc0af897280931c4919a...cf9b031930efbb833d42585af4fd0987efe9441f)

### Added

 - `cf9b03193` Autoimage: added support for .svg, .svgz and .webp

### Changed

 - `a656b9159` EnforceHTMLOmittedEndTags: reorganized code
 - `6e3039f2e` PHP Quick renderer: detect truncated XML with a "r" root tag
 - `fd5e9f35a` Renderer: detect truncated XML with a "t" root tag
 - `806df83ad` Renderer: throw an exception when loading invalid XML


1.2.1 (2018-07-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/7b909f51a8dbaff75e8a50038d8a8c3001a59da1...8af61dce9a28079036fcdc0af897280931c4919a)

### Added

 - `23906f738` MediaEmbed: added Streamja
 - `149e28792` MediaEmbed: added support for Instagram's IGTV
 - `8af61dce9` TemplateNormalizations: added EnforceHTMLOmittedEndTags

### Removed

 - `972a5a2fa` MediaEmbed: removed defunct site Blab
 - `9cc11283d` MediaEmbed: removed defunct site Viagame

### Fixed

 - `c0b22b293` ElementInspector: fixed incorrect results in closesParent()

### Changed

 - `fd66aa6f2` MediaEmbed: updated Amazon
 - `8d2e3c683` MediaEmbed: updated Amazon (.in)
 - `4ccf4531f` MediaEmbed: updated Brightcove
 - `0d813046d` MediaEmbed: updated Gamespot
 - `142e4f953` MediaEmbed: updated Kickstarter
 - `25db4ecb8` MediaEmbed: updated MSNBC
 - `e152afe5a` Parser: prevent tags from starting or ending in the middle of a UTF sequence


1.2.0 (2018-06-10)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#120) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/7565cbb8d4ddd7650b6bc52a74a16fc915dc8d89...7b909f51a8dbaff75e8a50038d8a8c3001a59da1)

### Changed

 - `70e7d0631` MediaEmbed: updated Youku
 - `7b909f51a` NodeLocator: simplified code
 - `8b647bafc` Split TemplateHelper into separate components
 - `ec48771b3` TemplateHelper: simplified some code


1.1.7 (2018-06-03)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2e99b01043cf5ea2d126489281f50114faadc5be...a7a43dec54df0e2bdda790569761128b4e58b4c9)

### Added

 - `50be65408` MediaEmbed: added support for custom headers when scraping

### Changed

 - `a7a43dec5` MediaEmbed: updated NPR
 - `fcaa772d5` Reorganized PHP serializer code


1.1.6 (2018-05-28)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4ae20a30fb8feeb4d267fd274c7421fac07862f5...d16774a0e30e6a35270ef5edde06c13dc38006eb)

### Fixed

 - `d16774a0e` TemplateParser: fixed an issue with empty elements being incorrectly removed from default case


1.1.5 (2018-05-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/22b9429ed9b208b96313ce35c410bd73995af599...d973a468f048f05c4df6010b9bda4905556888d7)

### Removed

 - `1052a19e0` BBCodes: removed useless code from repository

### Fixed

 - `a8174fafa` Fixed a potential issue with DOMNode::$childNodes returning empty text nodes
 - `cf1069519` Fixed a potential issue with whitespace passed to the PHP renderer generator's serializer
 - `21ef901d2` TemplateParser: fixed a potential issue with DOMNode::$childNodes

### Changed

 - `588c7e272` BBCodes: reorganized Repository internals
 - `2aabcb262` TemplateNormalizations: avoid creating empty text nodes
 - `cb2ddd1df` TemplateParser: improved compatibility with Libxml 2.7
 - `6e1ddead0` TemplateParser: replaced PHP conditionals with XPath predicates


1.1.4 (2018-05-20)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e00b876f49f38328ee27eb7521ffe7f0323649fe...8d5ece9fef9225bf7ba44f7e86b84131ec61155a)

### Added

 - `65c1fedf6` MediaEmbed: added support for custom user-agent value in scrapes

### Changed

 - `5d97a666a` ElementInspector: updated with HTML 5.2 changes
 - `8d5ece9fe` HTMLComments: remove trailing dashes to conform to HTML 5.2
 - `a213d7403` MediaEmbed: updated GoFundMe
 - `2a1977596` MediaEmbed: updated Imgur
 - `fd5a06765` MediaEmbed: updated Team Coco
 - `76a71ed88` MediaEmbed: updated Twitter
 - `5a558c1da` MediaEmbed: updated VK
 - `9e6255edc` MediaEmbed: updated VK's dimensions


1.1.3 (2018-04-14)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/60fbdf7d6315e06143e0a1405c66b812d3a6d8a0...c50c7525bcbf72ec983badf31e53a542d90af234)

### Added

 - `692a5157f` TemplateHelper: added support for select attribute in passthrough replacements

### Fixed

 - `4096d3523` MediaEmbed: fixed legacy Imgur embeds

### Changed

 - `a6b9a107b` BBCodes\Configurator\Repository: improved error message on bad repository file
 - `c50c7525b` XmlFileDefinitionCollection: load XML files using native PHP streams


1.1.2 (2018-03-31)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a907d8dab55639b426a8a531616203e99d1a8b27...eee99ce5110e319bee5f68c4313a8e0c292dac66)

### Fixed

 - `dce9241bb` FoldConstantXPathExpressions: fixed an issue with function calls without arguments being incorrectly replaced
 - `95ea73acb` XPathConvertor: fixed boolean conversion of parameters set to '0' in conditionals
 - `49330b7fd` XPathConvertor: fixed handling of negative numbers that start with a zero
 - `815cef8b5` XPathConvertor: fixed handling of numbers that start with a zero

### Changed

 - `1d8456c11` MediaEmbed: updated Liveleak
 - `eee99ce51` MediaEmbed: updated MLB
 - `8cfcbe30c` XPathConvertor: refactored exportXPath()
 - `6d69d0ee0` XPathConvertor: simplified matchXPathForExport()
 - `57c7a5d71` XPathConvertor: simplified translate()


1.1.1 (2018-03-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b2dd04095e975cd2009bff0f3f83520105be09de...d5cafb5e1aa56900d4152f7d7526fdb991e1232e)

### Added

 - `8e6388310` MediaEmbed: added support for "allow" attributes in iframes
 - `26cad57a6` XPathConvertor: added support for current()

### Removed

 - `d5cafb5e1` MediaEmbed: removed Oddshot

### Changed

 - `556b72ff8` MediaEmbed: updated Gfycat
 - `83b6bfa5d` MediaEmbed: updated Spotify
 - `aa5532afc` TemplateHelper: reorganized replaceTokens()
 - `dcef175d3` XPathConvertor: refactored the way XPath expressions are tokenized


1.1.0 (2018-02-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/7817e0b0fe8bec571f801a029bc932464f38309f...2b30e75237c8ec532f0c72e6cdb100401f0cec3f)

### Added

 - `ac38f54cf` MediaEmbed: added support for Telegram
 - `5f4d01827` TemplateParser: added support for disable-output-escaping="yes"

### Removed

 - `08cb4d52a` MediaEmbed: removed support for Imgur's /t/ URLs

### Changed

 - `86f0c7ad7` BBCodes: updated SPOILER BBCode to prevent it from submitting forms
 - `7a4633b98` FixUnescapedCurlyBracesInHtmlAttributes: escape the first left brace of a function declaration
 - `74c90041a` InlineTextElements: do not inline text with disable-output-escaping="yes"
 - `58b2a389f` MediaEmbed: embed Imgur links to static images
 - `87671bcb8` MediaEmbed: updated BBC News
 - `95ed3fc0d` MediaEmbed: updated CBS News
 - `2b30e7523` MediaEmbed: updated Vevo
 - `25559caf4` MediaEmbed: updated Vevo
 - `289b1e26e` Simplified Quick renderer generator
 - `472c568b7` TemplateParser: refactored and split into subcomponents
 - `8a323795f` TemplateParser: simplified node removal


1.0.1 (2018-01-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/307ac0e9c69fe71b280652fe40f9da03def9d4dc...1b7e7a387d83bb048e4ca309e9e2e314624ab8d8)

### Added

 - `b72c04884` MediaEmbed: added FOX Sports

### Fixed

 - `1b7e7a387` Parser: fixed an issue with malformed XML in places where text is ignored


1.0.0 (2018-01-04)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#100) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/15b61f63ad4e0694c28fd84f5d65fecf6f2d4842...e821032b862a5b75c602901be7fbdd2ef45e5a5b)

### Added

 - `7577e4584` Added caching HTTP client
 - `a107e470a` Emoji: added support for textual codepoint sequences

### Removed

 - `147c773ea` BBCodes: removed $predefinedAttributes
 - `06920f1fb` MediaEmbed: removed $captureURLs
 - `ffde0c243` MediaEmbed: removed $createIndividualBBCodes
 - `ac33fb0a2` MediaEmbed: removed Vidme
 - `24d5ae989` MediaEmbed: removed Zippyshare
 - `def39285e` MediaEmbed: removed appendTemplate()
 - `7e343bf2d` MediaEmbed: removed support for custom schemes
 - `5921a6ad1` MediaEmbed: removed unused code
 - `21a9fce71` Parser: removed implicit invalidation in tag filters
 - `1c136a634` Removed InvalidTemplateException and InvalidXslException classes
 - `80ec0e641` Removed support for attribute generators and the {RANDOM} token in BBCodes

### Changed

 - `0c3bcb2c2` ElementInspector: updated for HTML 5.2
 - `ff6da69aa` HTMLElements: updated configurator for HTML 5.2
 - `0bc92a880` HTMLElements: updated the list of URL attributes
 - `dace43e40` Litedown: restricted the characters allowed in link references' URLs
 - `8092ea038` MediaEmbed: refactored plugin
 - `6557a14ae` MediaEmbed: updated 8tracks
 - `112b7457c` MediaEmbed: updated Dailymotion and Twitch
 - `488a15907` MediaEmbed: updated Facebook
 - `9079e50c3` MediaEmbed: updated Flickr
 - `b7ee53bf5` MediaEmbed: updated Imgur
 - `c0f863da8` MediaEmbed: updated Reddit
 - `eacf0e0f8` MediaEmbed: updated Spotify
 - `a5647eb14` MediaEmbed: updated Vimeo
 - `3d5cfef45` Parser: reorganized filter processing
 - `3de8c6af8` RegexpConvertor: updated Unicode properties to latest version
 - `1987a0ba4` TemplateHelper: updated for HTML 5.2
 - `788152faf` Updated live preview algorithm


0.13.1 (2017-12-10)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/9c1e4cebcb61535d14423263553b6414b42f456c...492c1d8e7d97b43614dc258d9b701ce9c21dc296)

### Added

 - `8aef6ff75` Added RemoveLivePreviewAttributes template normalization to non-JavaScript renderer generators
 - `badcf2a35` Added live preview attribute data-s9e-livepreview-ignore-attrs
 - `fa352dc5a` MediaEmbed: added live preview hints to dynamically-resized embeds

### Fixed

 - `500d43a51` RegexpBuilder: fixed infinite recursion during remerge

### Changed

 - `69332cc50` JavaScript: updated Closure Compiler externs
 - `62ed3073f` MediaEmbed: keep all optional fields in cached definitions
 - `1e56d2a60` MediaEmbed: moved attribute creation out of add()
 - `805b1ae9a` MediaEmbed: normalize site definitions as they are created
 - `ee4b1bdd4` MediaEmbed: updated MSNBC
 - `6e5b6c405` Ruleset: refactored internals to be more scalable
 - `d3fde3969` TemplateInspector: split individual elements' inpection into ElementInspector
 - `2d242d8a7` Updated live preview code and JavaScript externs
 - `f6551bb4d` Updated live preview to return the last node modified
 - `a75aef0cb` Updated the live preview algorithm


0.13.0 (2017-11-27)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0130) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/d50adf87a92d5c793f5a06df7af089bdbaa749e4...459dddf5bc5f81f78564e3562231b6f01f2f2791)

### Added

 - `fa9e938a4` Added MinifyInlineCSS template normalization
 - `d471649ce` Added timestamp attribute filter

### Removed

 - `0ef891332` Censor: removed unused method

### Fixed

 - `feeeac930` HashmapFilter: fixed an issue where hash keys were not preserved during JavaScript minification
 - `803d3c5b9` RegexpConvertor: fixed the conversion of empty regexps

### Changed

 - `9ccb7597a` BuiltInFilters: reorganized filters into separate classes
 - `66b1d77be` BuiltInFilters: simplified regexp-based filters
 - `4f998c818` Logger: renamed get() to getLogs()
 - `d2df9701b` MediaEmbed: updated MSNBC
 - `47b00f1c8` MediaEmbed: updated YouTube
 - `017c8f77c` MediaEmbed: updated cached definitions


0.12.0 (2017-11-11)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0120) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/f8d9f670a609d147573117d2e33ffb7ee6844eb8...87dd6663f0f514822b258968441301b9be24f58e)

### Added

 - `cff256ec2` Added OptimizeChooseText template normalization
 - `f7b88b2d8` Added UninlineAttributes template normalization
 - `8dfdedf97` BBCodeMonkey: added support for short-form optional attributes
 - `e92549522` Litedown: added support for subscript
 - `8e751a5a1` MediaEmbed: added Orfium

### Removed

 - `193dec933` Emoji: removed set configuration and hardcoded default template
 - `4744c5950` OptimizeChoose: removed redundant method
 - `232bb4e03` PHP renderer: removed source from renderer instance
 - `433651b7b` Removed HostedMinifier and RemoteCache
 - `cf79c62f4` Renderer: removed $metaElementsRegexp
 - `d37d1912a` TemplateInspector: removed getDOM()

### Changed

 - `0c6bc3331` AVTHelper: preserve whitespace in toXSL()
 - `4ea55275c` BBCodeMonkey: reorganized some code
 - `761bc3e35` ClosureCompilerService: updated service's URL
 - `34ff96f14` Detect unexpected input that cannot be rendered by the Quick renderer
 - `1fc40d19d` Emoji: use EmojiOne 3.1's PNG assets
 - `e6f58045d` Litedown: do not require a blank line before lists
 - `be2618c1d` Litedown: refactored Superscript pass
 - `b6368c235` Litedown: refactored parser into separate passes
 - `9ebe42bea` Litedown: reorganized Emphasis parser
 - `c941be98a` Litedown: simplified configurator setup
 - `f4be8e77d` Litedown: updated emphasis syntax to check for whitespace
 - `92ba7928c` Litedown: updated superscript syntax
 - `8d8e3cde3` MediaEmbed: updated Google Sheets
 - `cb942255a` MediaEmbed: updated Mail.Ru
 - `cb0c4d6dc` MediaEmbed: updated YouTube
 - `283087b99` MediaEmbed: updated YouTube to remove deprecated option
 - `e06cc7079` Moved static code from dynamically generated PHP renderers into a separate class
 - `713cc2e29` OptimizeChoose: moved generic methods to an abstract class
 - `21f63889d` Preg: replaced detection of invalid regexps
 - `d5bfb22a4` Quick: bypass entity decoding/encoding when multiple XML attribute values are output in an HTML attribute
 - `50d1bf55e` Quick: simplified boilerplate code
 - `3755ed483` Refactored and simplified template normalization classes
 - `f1a382413` Replaced custom branch tables with native switch statements in PHP renderers
 - `844df511a` TemplateInspector: reorganized code for readability
 - `d184254b7` TemplateInspector: simplified bitfield analysis
 - `bdcfec4fc` TemplateNormalizer: increased the maximum number of iterations
 - `e20a5c8b0` Updated JavaScript parsers to use bracket notation for character access
 - `f6a3dfdb6` Updated PHP requirements to PHP 5.4.7


0.11.2 (2017-10-02)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0fb5ad37aa380cba7d8e4ae795a8f4c24ede057c...166118701944908429ecd2d94bbe461f1981c931)

### Fixed

 - `b82ab12b4` Litedown: fixed a bug triggered by empty code blocks
 - `77e81cfba` Quick: fixed an issue with @* in conditionals
 - `ac2353ba6` XmlFileDefinitionCollection: fixed an issue with atypical but valid definitions

### Changed

 - `14c4846e4` MediaEmbed: identify [media] tag pairs as markup
 - `aeaa1c8f1` MediaEmbed: made ->MediaEmbed->captureURLs accessible
 - `744f5a436` MediaEmbed: updated NHL
 - `ee5e8e13b` MediaEmbed: updated Podbean
 - `91fa461a0` Preg: normalize custom tag names
 - `955c8e78c` RulesGenerator: improved code's readability
 - `ba76dd0fd` XmlFileDefinitionCollection: cast known config values to the appropriate type


0.11.1 (2017-09-12)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ad2f29a3453d112f75f9603be6190c6f4434ea02...d84102711ee361c84a5b2cc5fe93e2e1828071f8)

### Added

 - `eebac1ad4` MediaEmbed: added support for parameters metadata in site config

### Removed

 - `8eb826344` MediaEmbed: removed default dimensions from definitions

### Changed

 - `d84102711` BBCodes: updated default [img] BBCode to accept dimensions
 - `9e917849f` MediaEmbed: updated Gfycat
 - `57bff33ca` MediaEmbed: updated Imgur
 - `7e7aa3b23` MediaEmbed: updated Vidme
 - `d75174d02` MediaEmbed: updated YouTube


0.11.0 (2017-08-04)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0110) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/6266e40850301ddc0e40808e7abe3e507a3ca347...ad7afe67b971cc76c43f5d6445cc06ca12ea2502)

### Added

 - `0239f7e06` Added AllowAll rules generator
 - `76c01d93b` MediaEmbed: added support for Amazon India
 - `afe63fc05` RulesGenerator: added BlockElementsCloseFormattingElements to default generators

### Removed

 - `d94ef970f` Configurator: removed addHTML5Rules() and integrated it in finalize()
 - `3e9a48fe1` Configurator: removed the finalizeParser and finalizeRenderer options from finalize()
 - `599a21548` Configurator: removed the optimizeConfig option from finalize()
 - `c20153268` Configurator: removed the returnParser and returnRenderer options from finalize()
 - `77f96dfe8` RulesGenerator: removed support for parentHTML option
 - `4d3370389` Ruleset: removed defaultChildRule() and defaultDescendantRule()
 - `65c910a20` TemplateInspector: removed isIframe()

### Fixed

 - `f5f176c8c` Utils: fixed removeTag() failing with arbitrarily high  values

### Changed

 - `4b05c5233` Autoimage: reversed tag priority to allow Autolink to linkify the image
 - `a4862b55e` Autovideo: explicitly allow URL tags to be used as fallback
 - `9970de5da` Internals: increased the default limits for tags and other resources
 - `c31a802d1` MediaEmbed: explicitly allow URL tags to be used as fallback
 - `2d3cc9ba9` MediaEmbed: limit the width of dynamically-sized embeds to 100%
 - `a2dba6ee0` MediaEmbed: reorganized template generation for readability
 - `a58a52a34` MediaEmbed: updated Amazon
 - `a7088bd48` MediaEmbed: updated Amazon
 - `4fc5fbda2` MediaEmbed: updated Getty
 - `2074eeabc` MediaEmbed: updated Google Drive
 - `6b26c91cd` MediaEmbed: updated Vidme
 - `608840ec2` MediaEmbed: use span as responsive wrapper
 - `15f65327a` Rules: the default rule for tags is now `deny`. `allowDescendant` and `denyDescendant` now only apply to non-child descendant


0.10.1 (2017-07-03)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/691c181e0313eccca750f90b9471cf7d1540fea7...98f1ec319bc6943fb3bb00cd1915398d3b34ac41)

### Fixed

 - `98f1ec319` Censor: fixed an issue where Helper::censorHtml() would not use the correct replacement


0.10.0 (2017-07-03)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0100) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/d8617a1858407057ff7edd368bcd550075ae903e...023d2f1a70bbfd8a72ce95d086c60c0eb6073908)

### Added

 - `438497a86` MediaEmbed: added support for new Twitch Clips URLs
 - `e12a63653` TemplateParser: added support for namespaces

### Removed

 - `f0a1b6589` Censor: removed Helper::reparse()
 - `023d2f1a7` Tag: removed setSortPriority()

### Fixed

 - `51fce5c9d` Censor: fixed an issue with special characters in censorHtml()

### Changed

 - `5390c3af6` BBCodes: updated default spoiler BBCode
 - `80e813320` MediaEmbed: updated Audiomack
 - `af3c2e758` MediaEmbed: updated audioBoom
 - `3e32c86eb` MediaEmbed: updated vidme


0.9.6 (2017-05-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/da98196db2b0490c62d4f5ba4ce559a0921e3200...7d7e16ebfaa3ecc59093970f2a3a6ed72a9c2f46)

### Added

 - `dc754f8d9` BBCodeMonkey: added support for setting nestingLimit and tagLimit in definitions
 - `c6edaf190` BuiltInFilters: added support for PHP 7.2
 - `739fa2ee9` HotnameList: added support for PHP 7.2
 - `bd9a5d697` XPathConvertor: added support for PHP 7.2

### Removed

 - `07abb3413` MediaEmbed: removed the authoritative part of SoundCloud URLs used as id

### Fixed

 - `ed780e3b9` FixUnescapedCurlyBracesInHtmlAttributes: fixed an issue where properly escaped braces would get incorrectly escaped again

### Changed

 - `7d7e16ebf` FixUnescapedCurlyBracesInHtmlAttributes: improved support for HTML attributes that end with an odd number of left braces
 - `76b806e92` HTMLElements: updated list of URL attributes
 - `6aba111ff` MediaEmbed: systematically scrape track_id from SoundCloud URLs
 - `935381522` MediaEmbed: updated BBC News
 - `d8f6c0573` MediaEmbed: updated Pinterest to exclude "explore" links


0.9.5 (2017-04-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b8210d933e4f10955de62d4252258736678cdbaa...e0de18cb68298028d56fd1a34e0f2b2bd3645d23)

### Added

 - `3159fe96a` XPathHelper: added support for ints and floats in export()

### Fixed

 - `284b4e08e` FoldArithmeticConstants: fixed an issue with number formatting in non-C locales
 - `65f5721d4` Parser: fixed an unbounded loop that can occur when a tag closes an ancestor with a worse priority

### Changed

 - `cd0a160b3` FoldArithmeticConstants: improved the parsing of decimal and negative numbers
 - `82abeb20c` Parser: do not apply closeAncestor/closeParent rules if maxFixingCost has been reached to mitigate against unbounded loops
 - `a8abcf6dd` Parser: tweaked regexp for performance
 - `b7ba9e117` XPathHelper: improved detection of numerical expressions


0.9.4 (2017-04-07)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8175223a3c663c5dc9d8625a03a94cc1a4534e21...4ebc635ce9f57db7f3f586b1fac24721bbd307d5)

### Fixed

 - `a1b60abf9` TemplateInspector: fixed an issue where templates with a mixed content model would not allow any children

### Changed

 - `4ebc635ce` FixUnescapedCurlyBracesInHtmlAttributes: escape left braces that are not followed by a right brace
 - `3a3577129` MediaEmbed: updated Youku
 - `9e1197ae1` TemplateNormalizer: replaced the way iterations are counted


0.9.3 (2017-03-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/5e583aae68e5fe32faefe0a4efa474c0debeaaa8...ea80c9deb417008712ba93f9e68e9273a9c19966)

### Added

 - `10d67eec1` MediaEmbed: added livestreaming tag
 - `5e9de5cda` MediaEmbed: added support for ABC News embed URLs

### Fixed

 - `ea80c9deb` OptimizeChoose: fixed an issue where xsl:choose elements could be improperly repositioned

### Changed

 - `f44d2dede` MediaEmbed: accept uppercase schemes in IGN
 - `1356d05e8` MediaEmbed: accept uppercase schemes in SoundCloud
 - `231d1bc0e` MediaEmbed: updated Google Sheets
 - `1041b3e88` MediaEmbed: updated Oddshot
 - `d8ed8bf40` MediaEmbed: updated TinyPic
 - `b14e5abb4` MediaEmbed: updated Twitch


0.9.2 (2017-02-11)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/04b5ae0a14b3e4a5b1a85e7cfd1b2d0618861b84...d3f9ae4242e057ba5a9cae0b2b0c4e22db5cd67a)

### Added

 - `72091c2c4` Autoimage: added support for more punctuations
 - `4f9aa8bcd` Autovideo: added support for more punctuations
 - `18c63cea7` MediaEmbed: added support for attribution links from YouTube
 - `8bbb9b089` MediaEmbed: added support for new share links from YouTube
 - `4f5475ffe` TemplateInspector: added isIframe()

### Changed

 - `d3f9ae424` BBCodeMonkey: allow newlines in {TEXT} used in composite attributes
 - `e52087874` EnforceContentModels: limit content fallback to single iframes
 - `31f46d6cb` MediaEmbed: updated Twitch
 - `8d0d4a82a` MediaEmbed: updated vidme
 - `6d678d75b` TemplateInspector: elements with "display:none" won't be considered block elements


0.9.1 (2017-01-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/299ce48c0ebbfe05e0123fd55d5f2daa94026ddf...a8114878c43a0eb419f237bdb22bf773723dcc67)

### Added

 - `1f4c52189` BBCodeMonkey: added support for flexible whitespace in composite attributes
 - `22566a0a1` BBCodes: added TBODY and THEAD to the repository
 - `a8114878c` PHP renderer generators: added support for @* in conditions

### Changed

 - `f36db2024` BBCodeMonkey: TEXT and ANYTHING tokens don't have to consume any text in composite attributes
 - `3d7542c72` Parser: create tags generated by a fosterParent rule at the first available non-whitespace position
 - `b241fbd2d` Parser: remove empty tag pairs, including those with attributes
 - `0031b5fa0` Parser: trim whitespace when adding a magic end tag if the current tag ignores whitespace
 - `e8bcaed06` Parser: trim whitespace when closing multiple tags if any of them ignores whitespace


0.9.0 (2017-01-15)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#090) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/8fefdbf95ab97316172d0f49d83c9547d0998795...d8623b088dd5ab71250fa9d842c513628bca4a89)

### Added

 - `8a25af9fa` FixUnescapedCurlyBracesInHtmlAttributes: added more JavaScript-related replacements
 - `acdd16962` MediaEmbed: added support for Gifs.com
 - `42f549035` MediaEmbed: added support for Pinterest

### Removed

 - `25f2c231a` Removed the custom autoloader

### Fixed

 - `382b31df3` RegexpParser: fixed incorrect unanchored pattern
 - `ecf1d71e4` Utils: fixed raw newlines in replaceAttributes()

### Changed

 - `bd3e3edce` Autolink: simplified parentheses matching
 - `842b02716` JavaScript: do not replace the s9e object if it already exists
 - `19c33a310` JavaScript\ConfigOptimizer: do not deduplicate empty arrays and dictionaries
 - `caf68b1cf` MediaEmbed: updated Podbean
 - `9deb2f725` MediaEmbed: updated WSHH
 - `d8623b088` RegexpParser: anchored bracket matching pattern as a precaution
 - `f5f494fc7` TemplateForensics: renamed to TemplateInspector
 - `2f57d0383` TemplateInspector: replaced "self" type hints with "TemplateInspector"
 - `20c5ed80f` Utils: normalize control characters in replaceAttributes()


0.8.5 (2016-12-11)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b2e101e62b114684d2b096167590d330fc93a7c6...92f61dc74effb788028d5055e04c2dfa60d5bc62)

### Added

 - `e2910c4b1` FancyPants: added =/= as an alias for the not-equal sign
 - `a9a2f2fa2` FancyPants: added support for toggling groups of replacements
 - `d8644729c` FancyPants: added support for vulgar fractions
 - `5bc811858` PipeTables: added support for empty cells

### Changed

 - `ff0e5cf03` Emoji: updated aliases and changed JavaScript regexp to lowercase
 - `fcbe4f120` MediaEmbed: updated IMDb
 - `e7ad9f604` PipeTables: improved the priority of table tags
 - `9c4eff9ab` Updated composer.json with dev dependencies
 - `92f61dc74` XSLT: force HTML attributes to use double quotes


0.8.4 (2016-11-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e3356dc6f68453ac814339a9cc1f301e13aa2c04...c5d2dbece918263151ba10d8566f4d3feeedfc01)

### Added

 - `29dd2ca78` Added #fontfamily attribute filter
 - `008025df4` Added PipeTables plugin
 - `d62d30a89` BBCodes: added align attribute to TD and TH
 - `f7da8cd9c` Fatdown: added PipeTables
 - `1b2a9b28f` MediaEmbed: added support for MLB.com
 - `ba6ce5e2d` MediaEmbed: added support for Twitch clips
 - `fffd95cde` MediaEmbed: added support for the new NHL video site

### Fixed

 - `3b3731fe6` FontfamilyFilter: fixed consecutive quoted strings

### Changed

 - `88bbafc3f` AttributeFilters: reorganized how each filter sets its own safeness
 - `f911748d3` BBCodes: disabled paragraphs inside [TD] and [TH]
 - `71c0fe828` BBCodes: updated default [font]
 - `8620a49e3` ClosureCompiler: updated externs
 - `85f6f41bb` HashmapFilter: reorganized code. No functional change
 - `5d2eea951` Litedown: improved the priority of QUOTE start tags
 - `ffde5aa2e` MediaEmbed: updated BBC News
 - `f4c750adc` MediaEmbed: updated Facebook
 - `06658f1b3` MediaEmbed: updated IMDb
 - `ce1b411d7` MediaEmbed: updated SoundCloud. No functional change
 - `507fcbddd` MediaEmbed: updated Spotify
 - `8a486df0d` Parser: addTagPair() now sets the priority of the end tag to minus the given value
 - `7db413b19` Parser: invalidate tags that are skipped
 - `c5d2dbece` RegexpFilter: reorganized code slightly
 - `f72332402` Ruleset: clear() should reset defaultChildRule and defaultDescendantRule rather than remove them


0.8.3 (2016-11-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8bb0409ae0f5daf0edeecfbbe0fbe18060f628e2...83f2797599180ca4223264d3f003ed7d910f2a24)

### Added

 - `f83eb28` FancyPants: added support for not equal sign and guillemets

### Fixed

 - `6d5797c` Censor\Helper: fixed an issue with text in quotes being ignored
 - `83f2797` Quick renderer: fixed an issue where attribute values would not be saved

### Changed

 - `6dced78` FancyPants: cached tagName/attrName in a local variable for better JS minification
 - `7293db8` FancyPants: reorganized code. No functional change
 - `04a6539` FancyPants: simplified parseSymbolsAfterDigits()
 - `0300282` MediaEmbed: made the default [media] tag's name configurable


0.8.2 (2016-10-16)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ea6e4e1fac53527769c19b81bed8aac94b59c562...de73726c19a13d17f48216ba013094b23b221a5f)

### Added

 - `4048a68` BBCodes: added [table], [tr], [th] and [td] to repository
 - `1e44987` Forum bundle: added [table], [tr], [th] and [td]
 - `90fcbce` MediaEmbed: added support for NBC News videos
 - `de73726` MediaEmbed: added support for Washington Post Video
 - `426e103` MediaEmbed: added support for new Pastebin URLs
 - `fd1094a` TemplateForensics: added support for more optional end tags

### Changed

 - `2805fba` Emoji: updated aliases
 - `915a281` Fatdown: disabled paragraphs inside of <td> and <th>
 - `3b8b047` FoldConstantXPathExpressions: replaced the blacklist of unsupported functions with a whitelist of supported functions


0.8.1 (2016-10-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/97125c831c206f6b0c597a5ed1c8df7a3510da6c...d26e62b0369ef7bfedb94a26708a1f1f87bbd050)

### Fixed

 - `e82316c` FoldConstantXPathExpressions: fixed detection of XPath node tests in uppercase

### Changed

 - `d26e62b` FoldConstantXPathExpressions: improved detection of nonfoldable expressions


0.8.0 (2016-10-09)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#080) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/7d08b19a0573b0b459a1206a0142da370bccda42...dc20ca832b5abbac7f24b65912b897b1a37f9197)

### Added

 - `f3ce808` FoldArithmeticConstants: added support for folding substractions
 - `83a3468` Litedown: added support for spaces in inline links' info
 - `7cb65e0` MediaEmbed: added 'name' and 'tags' metadata to CachedDefinitionCollection items
 - `4602feb` TemplateNormalizer: added FoldConstantXPathExpressions pass

### Removed

 - `8b2e036` Parser: removed manual garbage collection

### Changed

 - `dc20ca8` BBCodes: updated default [CODE]
 - `75b28f9` MediaEmbed: replaced the SiteDefinitionProvider API
 - `6d1d7c0` MediaEmbed: simplified the XML configuration reader


0.7.1 (2016-09-24)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/64c95e0c84183a46a9bffaa85bea546671b85b21...3c2ff8143055f7998d1cdab428157eaa67a44645)

### Added

 - `3c2ff81` Added Autovideo plugin
 - `8608895` Autoimage: added support for fallback link via Autolink
 - `ca3cc4e` MediaEmbed: added Steam store
 - `64bc419` MediaEmbed: added support for fallback link via Autolink
 - `2a7b9ff` MediaEmbed: added support for uppercase scheme

### Fixed

 - `0693ad7` BuiltInFilters.js: fixed unchecked access to undefined var
 - `dfa0e66` HTMLElements: fixed exception's message
 - `ba79789` TemplateForensics: fixed allowsChildElements() on a template with no xsl:apply-templates element
 - `8371057` Utils: fixed an issue with removeFormatting() where some tags were skipped

### Changed

 - `411f753` Autolink: excluded fullwidth and halfwidth punctuation from links
 - `02360fa` Emoji: simplified UTF-8 to codepoint algorithm
 - `a0a2546` Emoji: store hexadecimal codepoints padded to 4 characters
 - `31b88d0` Emoji: updated JavaScript regexp. No functional change
 - `b3b9e7b` EnforceContentModels: allow for some fallback content in templates with no xsl:apply-templates element
 - `f1fb0c3` HTMLElements: updated the list of URL attributes
 - `7f67345` MediaEmbed: updated Amazon
 - `9388645` RegexpConvertor: updated Unicode properties
 - `2023bfb` Replaced the default behaviour on duplicates in AttributeCollection, BBCodeCollection, EmoticonCollection and TagCollection
 - `e66f291` TemplateForensics: updated specs to HTML 5.1
 - `2c8bbf0` Utils: simplified the UTF-8 to codepoint algorithm


0.7.0 (2016-09-12)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#070) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/66605667be735e06ccbdf2b14c344c2b8bb84100...7cd1dfea62d9d35a40c206997e266918b9f1dfa1)

### Added

 - `7cd1dfe` Emoji: added support for UTR #51 Unicode Emoji, Version 3.0
 - `e1701fd` Forum bundle: added support for emoji
 - `646c85b` JavaScript: added support for urldecode() as a callback
 - `3626b16` MediaEmbed: added support for non-ASCII usernames in Google+

### Changed

 - `f654a16` BBCodes: replaced stripos() calls for performance
 - `3352264` Emoji: made EmojiOne the default image set
 - `aee1add` Emoji: updated path to Twemoji assets
 - `27d1b6b` MediaEmbed: improved support for embeds whose dimensions are fully dynamic
 - `76e4641` MediaEmbed: updated ESPN
 - `8a77784` MediaEmbed: updated Imgur
 - `ce36a47` MediaEmbed: updated TED Talks
 - `f3a0f52` MediaEmbed: updated VK
 - `2fc4807` Parser: improved the performance of out-of-order insertions in the tag stack
 - `4d3b0b0` Parser: simplified out-of-order insertion algorithm
 - `2507bfc` Parser: tweaked addTagPair() to reduce the need for additional sorting
 - `36c544e` Preg: set a higher priority to replacements over default plugins
 - `d0543dd` StylesheetCompressor: improved heuristics
 - `cda5b1f` Tag: setSortPriority() has been deprecated and will emit a warning


0.6.2 (2016-08-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a2d2f3c33695ccabc1c5779abfd44736c592a6c2...6e32a09afa2a1676a81109b6fe31dd63db209e42)

### Added

 - `3d68336` Autoimage: added support for uppercased URLs
 - `c8253c9` BBCodeMonkey: added support for putting BBCode attributes in quotes


0.6.1 (2016-07-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/d841d6be7838c25b586a669285c3b03bc185c498...7f3031c419ed748181a123a8b01c6945f2ae821b)

### Fixed

 - `7f3031c` Litedown: fixed catastrophic backtracking when matching inline links/images


0.6.0 (2016-07-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/3e76dc3b0ce5acad0860eb3f77dada48f6cfffc1...e3c010a09ae5236ef145e5728b8cdd7665d9dbc2)

### Added

 - `5e6c18a` Litedown: added support for separating blockquotes with two blank lines
 - `636bd77` Litedown: added support for separating lists with two blank lines
 - `d77de85` MediaEmbed: added support for Plays.tv shortlinks

### Removed

 - `bf77ead` BuiltInFilters: removed public access to parseUrl()
 - `39a41bc` Configurator: removed getParser() and getRenderer()
 - `b005a40` Removed the Variant class and related API

### Fixed

 - `915226a` BuiltInFilters: fixed an issue with empty query/fragment in URLs

### Changed

 - `52e41b9` ClosureCompilerService: updated externs
 - `07317a1` Code: ensure that __toString() always returns a string
 - `e3c010a` Code: filterConfig('JS') should return the instance itself to preserve its content from being encoded as a string
 - `d4c9eca` Emoticons: customized exception message to be more meaningful
 - `584562c` Litedown: expanded escaping to include the left parenthesis and single quote
 - `a365537` Litedown: replaced the algorithm handling links and images
 - `ec2f000` Litedown: simplified ignoreEmphasis()
 - `0f533ba` MediaEmbed: updated Scribd
 - `af8ddab` MediaEmbed: updated Straw Poll
 - `78ca63b` MediaEmbed: updated YouTube
 - `2ded572` Moved JS parser loading to PluginBase::getBaseProperties()
 - `0f34747` PluginBase: getBaseProperties() should not return a 'js' element if there is no JS parser
 - `4ecb7f2` Quick renderer: replaced the export() algorithm
 - `fa99034` Regexp: replaced JavaScript-related API
 - `99fbcd1` RegexpConvertor: updated toJS() to return a string rather than an instance of Code


0.5.4 (2016-07-08)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/3122029dcaf12dc95ce584d43fa84ad92ad9619a...96a5a67eb3681b9f89d15dc6affd5896c3d877c6)

### Added

 - `d081f95` Added new rule type: createChild
 - `413fbeb` MediaEmbed: added support for Google Drive links that contain a domain name
 - `bfad6cc` MediaEmbed: added support for JW Platform

### Changed

 - `8a53870` Litedown: suspended escaping inside inline code spans
 - `afbbcd5` MediaEmbed: ignore the hash part of URLs when scraping
 - `2683928` MediaEmbed: updated Hudl
 - `0aff031` MediaEmbed: updated Hudl
 - `38ad33f` MediaEmbed: updated Reddit
 - `41967d0` MediaEmbed: updated SoundCloud
 - `d7526a4` MediaEmbed: updated XboxClips to exclude screenshots
 - `e141f0b` TemplateHelper: refactored some code and reformatted sources
 - `96a5a67` TemplateHelper: remove invalid attributes in loadTemplate()


0.5.3 (2016-06-14)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4a7bda3e0ecd74b2d67106695ff9d292a0afa2ff...65d80c1ff686489dfc57a29a18bae1edfca9890a)

### Removed

 - `a55119c` Litedown: removed excessive slash stripping from attributes
 - `722763d` Removed unused variable from PHP renderers

### Fixed

 - `7ea7d29` Fixed an issue with newlines in attribute values in Quick rendering
 - `efd1ce3` Litedown: fixed an issue with quote markup inside of multiline attributes

### Changed

 - `65d80c1` Litedown: refactored inline code spans matching
 - `665444d` Parser: output LF characters as HTML entities in attribute values


0.5.2 (2016-06-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/702e6fdd6d7874ac25a721173c22435eb03fdc08...c66c9404a0d9ac29bc71d26df0f9c89d31679739)

### Fixed

 - `0c704d7` Litedown: fixed an issue with backticks inside inline code spans
 - `6e536e6` Litedown: fixed an issue with unbalanced inline code markers
 - `c66c940` TemplateHelper: fixed an issue with UTF-8 characters in HTML-to-XML conversion

### Changed

 - `bad5c74` BBCodes: updated Highlight.js to 9.4.0
 - `6e8c811` MediaEmbed: updated Getty Images


0.5.1 (2016-05-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b89e5b2db384a14a164db66871eac2ceab1e2832...253fbb59fbdbf0d664c93128b399fd157e955d7a)

### Added

 - `ff775b7` Emoji: added support for more aliases
 - `b573086` XPathConvertor: added support for less-than and greater-then comparisons

### Fixed

 - `558f34e` JavaScript\Encoder: fixed a potential issue with property names that start with a digit
 - `448e167` RegexpConvertor: fixed an issue with incorrect ranges used for \P properties

### Changed

 - `81106df` Emoji: updated EmojiOne's template to use lowercase filenames
 - `50e85d6` MediaEmbed: prevent division by zero in variable-sized embeds
 - `43acb03` MediaEmbed: updated Facebook
 - `3912aa9` MediaEmbed: updated Getty
 - `253fbb5` MediaEmbed: updated Internet Archive
 - `a579d85` RegexpConvertor: precompute the regexp that matches Unicode properties for performance
 - `e22a808` Updated Tinypic


0.5.0 (2016-04-28)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/efd5915ca5bf74f938fce40c8837d7fc781b642d...fe3dec1725bf870e4437b20e6be0d17652be87ea)

### Added

 - `64f3ee0` Added OL and UL to the Forum bundle
 - `794d664` BBCodes: added OL and UL to the default repository
 - `794127a` MediaEmbed: added NBC Sports
 - `8c6aa1e` MediaEmbed: added The Guardian
 - `7714ae9` MediaEmbed: added Veoh
 - `630ad0a` MediaEmbed: added a background image to YouTube and Twitter

### Removed

 - `c2a3a1c` Litedown: removed support for unquoted titles in links and images

### Fixed

 - `a13416c` Fatdown: fixed missing title in links

### Changed

 - `c032937` Autolink: made ->Autolink->matchWww public
 - `11633f8` Autolink: remove most non-letters at the end of the URL
 - `2a7de09` Autolink: replaced manual check with word boundary assertion
 - `90eaf86` BBCodes: updated Highlight.js to 9.3.0
 - `1608c79` Litedown: made the decodeHtmlEntities property public
 - `94f07bd` MediaEmbed: replaced all HTTP URLs with protocol-relative URLs in src attributes
 - `8b0a31d` MediaEmbed: updated Brightcove
 - `76638c6` MediaEmbed: updated Gfycat
 - `16038df` MediaEmbed: updated Gist
 - `ab22f0b` MediaEmbed: updated VBOX7
 - `b0b717a` MediaEmbed: updated Youku
 - `d9f1608` MediaEmbed: updated dumpert
 - `e49d6c9` OnlineMinifier: instantiate the HTTP client in the constructor
 - `15e3223` OnlineMinifier: renamed HTTP client property
 - `4f10e67` OptimizeChoose: streamlined conditional
 - `c6fd225` TemplateForensics: inspect an element's style to determine whether it's a block-level element


0.4.12 (2016-03-20)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ac3fc397cfbbf1083da75c9df5fcc3eac38fbbfb...acb855967860a9c095cd338f7383c247f88aa928)

### Added

 - `266ccf8` Added support for named parameter 'text' in tag filters
 - `e5d1b85` MediaEmbed: added Brightcove
 - `5c5206c` MediaEmbed: added Healthguru
 - `34a49c1` MediaEmbed: added MRCTV
 - `04d14f1` MediaEmbed: added Video Detective
 - `ae8cc70` MediaEmbed: added support for FORA.tv
 - `9a741c3` MediaEmbed: added support for Livestream short links and old site
 - `bdc62e8` TemplateNormalizations: added SetRelNoreferrerOnTargetedLinks (enabled by default)

### Removed

 - `3b0682f` Litedown: removed support for space in inline links markup

### Fixed

 - `71064bb` Litedown: fixed an issue with Setext headers causing the next line to be ignored

### Changed

 - `f038f28` Litedown: prefix the class name used to identify the language of a code block
 - `7606ab6` Litedown: require code fences' length to match
 - `d2ff237` MediaEmbed: updated Dailymotion
 - `55ca396` MediaEmbed: updated NYTimes


0.4.11 (2016-02-21)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b455eb19d389a67f576ab4cb8e9e25aae0609cb3...ec154ed850e8665afd1df50dc2a11bb78247e178)

### Added

 - `eb234d7` MediaEmbed: added support for LiveCap

### Fixed

 - `7e8fb2a` OptimizeChoose: fixed an issue when removing nested conditionals


0.4.10 (2016-02-11)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/98836fa362a0ba24a8a3ff6be1db484067d8d8f2...0eeecb35d9934a454250e367b9eb3e5916494b88)

### Fixed

 - `9ca5270` Parser: fixed an issue with fosterParent and high-priority tags
 - `0eeecb3` Parser: fixed an issue with fosterParent and low-priority tags

### Changed

 - `11bdbe7` BlockElementsFosterFormattingElements: do not create a fosterParent rule if the template is not passthrough
 - `5bb3b77` Parser: adjusted the cost of fixing tags in fosterParent


0.4.9 (2016-02-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/350bb8c63660d888e62796567a3b6479f34a68dc...7c279935ae7e3360776dca6c5f512fa320433f6c)

### Added

 - `8a13fb4` RegexpBuilder: added support for non-Unicode strings

### Removed

 - `7bafcde` MediaEmbed: removed support for discontinued Mixcloud short links

### Fixed

 - `9f2b080` Litedown: fixed quote markup interpreted inside of fenced code blocks
 - `e56f871` OptimizeChoose: fixed an issue with node iteration over a live tree

### Changed

 - `09bc408` Http: prefer native stream to cURL if safe mode is on (PHP 5.3 only)
 - `9398ee9` MediaEmbed: updated ComedyCentral
 - `93a6a9b` MediaEmbed: updated Imgur
 - `5de2c70` MediaEmbed: updated Straw Poll
 - `bbabbb9` MediaEmbed: updated Tumblr
 - `5ac0294` Updated externs files for Closure Compiler v20160208


0.4.8 (2016-01-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a7a66e4afae0ccebe729e8ea4b4b60c48bb41dfc...1050f78b5b2ebc4fe09a17b21d49d2d100a4f0ca)

### Added

 - `e98b8a0` Added an HTTP helper with support for cURL and native streams
 - `19ecb28` Added support for customisable timeout in HTTP clients
 - `88562c2` Added support for toggling SSL peer verification in HTTP clients
 - `c4e0a99` ClosureCompilerService: added support for configurable HTTP client
 - `ff15e3a` HostedMinifier: added support for configurable HTTP client
 - `8509ded` MediaEmbed: added Blab
 - `5feeae6` MediaEmbed: added support for HTTP client used for scraping
 - `e2cae50` RemoteCache: added support for configurable HTTP client

### Changed

 - `39139c3` ClosureCompilerService: reorganized code
 - `0c2b5aa` Curl: reset the request body when doing POST requests
 - `48d08f6` MediaEmbed: moved all GitHub iframes to RawGit
 - `c73392e` MediaEmbed: moved hosted iframes back to GitHub
 - `4e55063` MediaEmbed: updated Indiegogo
 - `6444043` Moved Http helper to the Utils namespace
 - `9973077` OnlineMinifier: automatically set timeout in getHttpClient()
 - `ab2c9f1` RemoteCache: updated for new API
 - `b2481cd` Reorganized native HTTP client's code
 - `0489bb8` Set headers unconditionally in native HTTP client


0.4.7 (2016-01-08)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2b837825cac2da9ddf06867038c19096ffacc4dd...932f6f16738fad1d32e44a96a37347a0aa12635e)

### Added

 - `fd08fc1` Added support for MatthiasMullie\Minify
 - `8eda0fb` JavaScript: added support for converting \x{....} in regexps
 - `932f6f1` Minifiers: added experimental minifiers HostedMinifier and RemoteCache

### Removed

 - `e06bc52` MediaEmbed: removed Rdio

### Changed

 - `2c76bb3` BBCodes: updated Highlight.js to 9.0.0
 - `4be9a3f` ClosureCompilerApplication: cache the binary's hash for performance
 - `b730287` ClosureCompilerApplication: made constructor argument optional
 - `43b9f74` Minifier: use a default constant used as cache differentiator


0.4.6 (2015-12-21)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4a6c4967cc506397d08053213dc1075d5bca9ec8...4dab11faae6382607c3b12612b274261ba110cc8)

### Added

 - `8e4706d` Added support for negative offsets in NormalizedList
 - `faeafd6` JavaScript: added FirstAvailable minifier
 - `1ec2d62` MediaEmbed: added Plays.tv
 - `853e63b` MediaEmbed: added support for timestamps in Twitch videos

### Fixed

 - `4dab11f` Litedown: fixed incorrect indentation in fenced code blocks

### Changed

 - `8bc9340` Litedown: trim whitespace around the language name in fenced code blocks
 - `19ee41c` Litedown: updated inline code syntax to bring it closer to Markdown's
 - `1f6e375` MediaEmbed: updated Pastebin
 - `299996e` MediaEmbed: updated Twitch to use their new player


0.4.5 (2015-12-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/c60a76996bd97733b2fe8ca14289237e43ce94f9...eb8ca6fc21770129b9244f6c544111f64f1a47f8)

### Added

 - `c40350b` Censor: added JavaScript hint
 - `650adb0` Censor: added JavaScript hint
 - `68fe6d6` Emoji: added JavaScript hint
 - `491042c` Emoji: added JavaScript hint
 - `23e84ac` Emoticons: added JavaScript hint
 - `7ec97e6` HTMLElements: added JavaScript hint
 - `5b5a6c4` JavaScript: added support for custom hints set by plugins
 - `fe484da` Litedown: added JavaScript hint for skipping HTML entity decoding
 - `0971ef0` Litedown: added support for decoding HTML entities in attribute values
 - `993f5cc` Preg: added JavaScript hint

### Removed

 - `3484b60` BBCodes: removed duplicate condition

### Fixed

 - `d0b6396` BBCodes: fixed improper pairing during parsing
 - `080d7aa` HTMLElements: fixed detection of empty elements

### Changed

 - `58dd013` HTMLEntities: ignore control characters encoded as HTML entities
 - `ffcdd48` MediaEmbed: replaced protocol-relative iframe URLs from GitHub to use HTTPS
 - `95b72d7` MediaEmbed: updated CNN
 - `ec41c55` MediaEmbed: updated GameTrailers
 - `2209d3c` Updated emoji script
 - `4ce201a` utils.js: cache the element used in html_entity_decode()


0.4.4 (2015-11-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/1a6cb0f14f0e368c973976afb7d0ac015c635d80...63dae49a256a8f3f263809c1a3e1f29e2b86a994)

### Added

 - `b6d4c3e` BBCodes: added support for lists that start at an arbitrary number
 - `72d8236` Litedown: added support for empty links
 - `c466adf` Litedown: added support for ordered lists that start at an arbitrary number
 - `54b40b8` Litedown: added support for reference links and images
 - `d26f829` Litedown: added support for single quoted and unquoted titles in links and images
 - `22426da` Litedown: added support for unescaped brackets in link text and image alt text
 - `76c7f6a` MediaEmbed: added support for country-specific Facebook links
 - `58d8541` XPathConvertor: added support for conditions with more than 3 boolean operations

### Fixed

 - `d1ce37e` MediaEmbed: fixed CSS overflow on iOS Safari
 - `10cfa29` XPathConvertor: fixed comparison to 0

### Changed

 - `845279a` Escaper: reverted to using a custom tag
 - `a94a625` MediaEmbed: updated Facebook
 - `13c9015` MediaEmbed: updated Facebook
 - `c742fa7` MediaEmbed: updated Imgur
 - `d7edfed` MediaEmbed: updated Imgur
 - `ff60c99` MediaEmbed: updated Instagram
 - `4e138d7` MediaEmbed: updated Xbox DVR
 - `048e433` MediaEmbed: updated vidme
 - `bb56400` Parser: automatically correct the length of ignore tags
 - `0676a8d` Updated Fatdown


0.4.3 (2015-10-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4ac91c44d353e8fb1b2ce5639a9ee71d92bad27d...d7547abecab3ac9d1b91f5e4c5e15e04883299f1)

### Added

 - `3abcb7d` Bundles: added getCachedParser() and getCachedRenderer()

### Removed

 - `5925a64` MediaEmbed: removed redundant code

### Changed

 - `b0ab098` Emoji: simplified template generation
 - `f624086` Litedown: allow lists to start immediately after a header or horizontal rule
 - `a871f19` Litedown: replaced the way block boundaries are set
 - `3c8c61d` MediaEmbed: updated Comedy Central
 - `ce982d7` MediaEmbed: updated Imgur
 - `df9e944` MediaEmbed: updated Imgur to not transform links to static images
 - `e5c72df` MediaEmbed: updated NPR
 - `cd24826` NormalizedCollection: save items in the lexical order of their keys


0.4.2 (2015-10-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/50c790c1bcb80d8ed5a5cb9b81c6a6fbb6a1d0f4...a8d84fb8a2d6150862531ed8622d6c1a64b1fb90)

### Added

 - `73e14ab` BBCodes: ensure that end tags added by lookahead are not duplicated
 - `fd6bd30` Emoji: added draggable="false" to Emoji One images
 - `d32e93d` MediaEmbed: added support for private tracks in SoundCloud
 - `0dd8a61` PHP renderer generator: added support for raw output

### Changed

 - `ce681ae` BBCodes: updated the default CODE definition
 - `627d41c` JavaScript\ConfigOptimizer: do not attempt to deduplicate simple variables
 - `7afa5f9` MediaEmbed: updated wget() to send a User-agent header
 - `31e3b26` TemplateParser: set the escape value of literal text in script elements to "raw"


0.4.1 (2015-09-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/15b0005595cc38c76355f376cc5576968db79de6...30ca6acb15a467777f6229bacf4158a8199a38cd)

### Fixed

 - `b6dd56b` MediaEmbed: fixed malformed XSL when an attribute value contains an angle bracket but is not XSL
 - `0d4675c` MediaEmbed: fixed responsive embeds alignment

### Changed

 - `30ca6ac` MediaEmbed: updated Medium
 - `7887f79` MediaEmbed: updated SoundCloud


0.4.0 (2015-09-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/5a50dab7662d4083129287bd1c895e2aaf884e9a...5863c4cb5df880d315188b17145410105ba71773)

### Added

 - `7abbe26` Added AVTHelper::toXSL()
 - `5863c4c` Added JavaScript\StylesheetCompressor
 - `257a413` MediaEmbed: added support for Google Drive
 - `a6f99bb` MediaEmbed: added support for multiple-choice templates
 - `82f06e3` TemplateNormalizations: added OptimizeChoose
 - `bc8e764` TemplateNormalizations: added additive identity optimization to FoldArithmeticConstants
 - `9548cee` TemplateNormalizations: added support for decimal values in InlineXPathLiterals
 - `0a6ff7a` TemplateNormalizations: added support for multiplications, divisions and sub expressions in FoldConstants
 - `7a439ff` XPathConvertor: added support for parenthesized math expressions

### Removed

 - `dc8a261` Emoji: removed unnecessary parentheses in JavaScript regexp
 - `edacfa8` MediaEmbed: removed ESPN Deportes
 - `e20df9f` MediaEmbed: removed enableResponsiveEmbeds() and disabledResponsiveEmbeds()
 - `4e7f087` MediaEmbed: removed the embed element from Flash templates
 - `b1500b6` PHP renderer generator: removed constant math evaluation which was made redundant by the FoldArithmeticConstants template normalization pass

### Fixed

 - `9c8eaa0` MediaEmbed: fixed the MEDIA tag filter to not create a tag if it does not match a known site
 - `a5feb09` Quick renderer: fixed a potential issue with string comparison against single quotes
 - `80f2c9a` Quick renderer: fixed incorrect comparison against literals that contain a single quote

### Changed

 - `e1809c3` BBCodes: updated Highlight.js in CODE BBCode
 - `c3f79fb` Censor: do not escape single quotes in Helper::reparse()
 - `050b051` MediaEmbed: overhauled responsive embeds
 - `2ac29f1` MediaEmbed: reorganized filterTag()
 - `5ff9c1e` MediaEmbed: reorganized template generation
 - `7ede2a7` MediaEmbed: updated Audiomack and SoundCloud
 - `1b8956f` MediaEmbed: updated Google Drive
 - `493ac00` MediaEmbed: updated IMDb
 - `455723e` MediaEmbed: updated Imgur
 - `4680bb5` MediaEmbed: updated Ustream
 - `5552810` Moved JavaScript callbacks deduplication to ConfigOptimizer
 - `2bc612c` TemplateNormalizations: improved parentheses removal in FoldArithmeticConstants
 - `361212f` TemplateNormalizations: preserve strings content in FoldArithmeticConstants
 - `679f0d3` TemplateNormalizations: renamed FoldConstants to FoldArithmeticConstants
 - `470c1da` Updated docblock
 - `0765165` Utils: updated serializeAttributes() to escape quotes in a manner consistent with the parser
 - `caf1dc2` XPathHelper: updated minify() to remove more space around the div operator
 - `38a9c89` XPathHelper: updated minify() to remove spaces after a div operator


0.3.2 (2015-09-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/c6fad331e64aeea041ce2da4c5f0e0521bc76afc...dd3fe8c881856bb6279302932912cc8ab957efd1)

### New

 - `dd3fe8c` Added CharacterClassBuilder helper

### Changed

 - `c3f08fb` JavaScript: use returnFalse and returnTrue callbacks as-is
 - `0f5051d` MediaEmbed: simplified the regexp that matches text links


0.3.1 (2015-09-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/24a4d8a4ac4aea751428e788ff94cf298646f342...f1ef4fce3431c5ddd59fb345a9e2a839331ea8ad)

### New

 - `51bce03` ClosureCompilerService: added read timeout with a default value of 10s
 - `348e1de` JavaScript: added returnTrue() in utils.js

### Changed

 - `957ada3` ClosureCompilerApplication: updated command line options for v20150901 and turned off warnings
 - `d105dbc` JavaScript: created different externs for ClosureCompilerApplication and ClosureCompilerService
 - `3cab461` MediaEmbed: moved template generation out of add()
 - `c497eb3` MediaEmbed: normalized the order of characters in regexps' character classes
 - `ed7aaaf` MediaEmbed: replaced the 'unresponsive' attribute in site definitions with a 'responsive' attribute
 - `95007fb` MediaEmbed: simplified the generation of Flash templates
 - `a75dd32` Refreshed bundles
 - `d4e81cb` Regexp: avoid adding non-capturing subpatterns to regexps generated by getNamedCaptures() where they are not needed
 - `cd1f119` Regexp: simplified getNamedCaptures()

### Fixed

 - `d363f8b` MediaEmbed: fixed responsive Flash objects
 - `f1ef4fc` Parser: fixed incorrect tag removal


0.3.0 (2015-09-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/eab904365c31bb87a016d2c0876e0c149faf93a3...ce1d116ccbc15868856cb2fd65dc9897afd38360)

### Changed

 - `ce1d116` MediaEmbed: moved template generation to its own class
 - `be478e7` MediaEmbed: moved the 'unresponsive' attribute into the iframe/flash definition
 - `3f5e499` MediaEmbed: removed support for custom templates
 - `15e8ece` MediaEmbed: updated Vine

### Fixed

 - `c089b4a` XPathHelper: fixed false negative in isExpressionNumeric()


0.2.1 (2015-08-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/90a396d1d63d4c8f69b96a2450a35573b8d676c6...042d1779e37c4f1720042845e1842a1e1d9b5383)

### New

 - `6c181a7` MediaEmbed: added Oddshot.tv
 - `48d03be` TemplateNormalizations: added FoldConstants pass

### Changed

 - `de996ae` Litedown: gave inline links a slightly better priority to give them precedence over BBCodes

### Fixed

 - `6a44f9f` Litedown: fixed incorrect indentation inside fenced code blocks


0.2.0 (2015-08-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0.1.0...0.2.0)


0.1.0 (2015-08-12)
==================

Initial release
