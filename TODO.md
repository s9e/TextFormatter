Current things
--------------
- ~~Add RegexpParser::getCaptureNames()~~
- ~~Modify the Preg plugin to use getCaptureNames()~~
- ~~Modify AttributePreprocessor to use getCaptureNames()~~
- ~~Move AttributePreprocessor::getAttributes() functionality to Regexp. Make AttributePreprocessor extend Regexp~~
- Modify NormalizedCollection::asConfig() to order items in lexical order
- Move AttributeProcessor instances to Attribute
- Replace AttributePreprocessorCollection with AttributePreprocessorList
- Refactor BBCodeMonkey, create a token class
- Add MergeConsecutiveCopyOf to the XSLT renderer's optimizations
- Optimize the JS config by deduplicating the tagConfig.allowed, tagConfig.rules.closeAncestor, tagConfig.rules.closeParent, tagConfig.rules.fosterParent and tagConfig.rules.requireAncestor arrays.
- Add PluginBase::getJavaScriptHints()

Long-term goals (?)
-------------------
- Extend Litedown: perhaps reference links
- Have a piece of utilities that converts a parsed text to BBCodes or Markdown
- RTE


Random stuff that may never happen
----------------------------------
- Create BBCodesConfig::getBBCodeTemplate() that returns the definition of a BBCode, e.g. [URL={URL}]{TEXT}[/URL]
- Look into properly implementing urlencode() and rawurlencode() in JS
- Fix the path in "// Start of content generated by .." comments
- Sort the "isTransparent" situation wrt `<video>`. isTransparent makes a tag inherit the list of disallowed children from its parent, but it cannot currently allow tags that aren't allowed by its parent. In most cases, it doesn't matter, but it prevents using `<track>` as the child of <video> through the HTMLElements plugin
- Test how conditional comments in templates are rendered
- Conditional comments can create IE-specific exploits. Consider removing them
- Consider adding one callback opportunity before and after rendering. The callback would receive the XML (before) or HTML (after) and the Renderer instance
- One cheap way to filter a CSS value would be to set an attribute preprocessor for every supported CSS attribute, e.g. `/(?<!\w)color\s*:\s*(?<color>#[0-9a-f]+|\w+)/` - could be mentionned in cookbook
- MediaEmbed: add an oEmbed helper(?)
- JavaScript: check whether registeredVars's keys are preserved. convertCallback() should always use the bracket notation when accessing registeredVars, otherwise registerVar() would not work
- Some characters such as [ and ] should always be URL-encoded to pass W3C validation. Others are recommended to be escaped as per RFC 2396 2.4.3. XSLT systematically encode even more characters. Consider adding a mechanism in the PHP renderer generator to escape dynamic values. Consider adding a "url" flag in the representation generated by the TemplateParser. Could be called "context" and be extended to JS and CSS.
- Consider adding a way to apply RulesGenerators on a per-tag basis. For instance, it makes sense to have BlockElementsFosterFormattingElements on a CENTER tag, but not so much on QUOTE or CODE
- Create an exception used in security checks so that an implementor can log security exceptions differently from other exceptions
- Add hints to the PHP parser. Makes hints take the form of a bitfield, e.g. "if ($this->hints & self::HAS_ATTRIBUTE_GENERATORS)"
- Force a paragraph break when a formatting element is open and two newlines are found?
- Litedown: consider handling URLs in brackets http://six.pairlist.net/pipermail/markdown-discuss/2007-May/000626.html
- Add an option for Tag's position to be flexible; It can start anywhere within its consumed text. IOW, if the parser's current position is past the tag's position, its position is adjusted up to pos+len (which is already the case with ignore tags, although ignore tags can't be 0 length)
- Consider a rule that defines whether a tag should be excluded from the createParagraphs rule. For instance, a tag that is rendered as an empty string, or a HTML comment does not require a paragraph to be created