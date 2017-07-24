Rules
=====

See <code>s9e\TextFormatter\Configurator\Collections\Ruleset</code>.
Rules are set on a per-tag basis, for example:

```php
$configurator = new Configurator;

$tag = $configurator->tags->add('B');
$tag->rules->autoReopen();
$tag->rules->denyChild('X');
```

Method calls can be chained for convenience. The same example can be written as

```php
$configurator = new Configurator;

$tag = $configurator->tags->add('B');
$tag->rules->autoReopen()
           ->denyChild('X');
```

Rules can be:

 * boolean -- they accept <code>true</code> or <code>false</code> as argument, with <code>true</code> being the default.
 * targeted -- they accept a tag name as argument.

Rules that apply to descendants also apply to children. Rules that apply to ancestors also apply to the parent. A tag that is explicitly denied cannot be allowed by another rule.

<dl>

<dt>allowChild</dt>
<dd><i>Example:</i> <code>$tag->rules->allowChild('X');</code><br/>
Allows tag X to be used as a child of given tag.</dd>

<dt>allowDescendant</dt>
<dd><i>Example:</i> <code>$tag->rules->allowDescendant('X');</code><br/>
Allows tag X to be used as a descendant of given tag.</dd>

<dt>autoClose</dt>
<dd><i>Example:</i> <code>$tag->rules->autoClose(true);</code><br/>
Start tags of this tag are automatically closed if they are not paired with an end tag. This rule exists primarily to deal with <a href="http://www.w3.org/html/wg/drafts/html/master/single-page.html#void-elements">void elements</a> such as <code>&lt;img&gt;</code>.</dd>

<dt>autoReopen</dt>
<dd><i>Example:</i> <code>$tag->rules->autoReopen(false);</code><br/>
Automatically reopens this tag if it's closed by a non-matching tag. This rule helps dealing with misnested tags such as <code>&lt;B&gt;&lt;I&gt;&lt;/B&gt;&lt;/I&gt;</code>. In this case, if <code>I</code> has an autoReopen rule, it will automatically be reopened when <code>B</code> closes.</dd>

<dt>breakParagraph</dt>
<dd><i>Example:</i> <code>$tag->rules->breakParagraph();</code><br/>
This tag will break current paragraph if applicable.</dd>

<dt>closeAncestor</dt>
<dd><i>Example:</i> <code>$tag->rules->closeAncestor('X');</code><br/>
Forces all ancestor tags X to be closed when this tag is encountered.</dd>

<dt>closeParent</dt>
<dd><i>Example:</i> <code>$tag->rules->closeParent('LI');</code><br/>
Forces current parent LI to be closed when this tag is encountered. Helps dealing with <a href="http://www.w3.org/html/wg/drafts/html/master/single-page.html#optional-tags">optional end tags</a>. For instance, if LI has a closeParent rule targeting LI, the following <code>&lt;LI&gt;one&lt;LI&gt;two</code> is interpreted as <code>&lt;LI&gt;one&lt;/LI&gt;&lt;LI&gt;two</code>.</dd>

<dt>createParagraphs</dt>
<dd><i>Example:</i> <code>$configurator->rootRules->createParagraphs();</code><br/>
Automatically creates paragraphs (HTML element <code>&lt;p&gt;</code>) to host content. Using two consecutive new lines indicates a paragraph break in content.</dd>

<dt>denyChild</dt>
<dd><i>Example:</i> <code>$tag->rules->denyChild('X');</code><br/>
Prevents tag X to be used as a child of this tag.</dd>

<dt>denyDescendant</dt>
<dd><i>Example:</i> <code>$tag->rules->denyDescendant('X');</code><br/>
Prevents tag X to be used as a descendant of this tag.</dd>

<dt>disableAutoLineBreaks</dt>
<dd><i>Example:</i> <code>$tag->rules->disableAutoLineBreaks();</code><br/>
Turns off the conversion of new lines in the scope of this tag. Conversion can be turned back on by descendants.</dd>

<dt>enableAutoLineBreaks</dt>
<dd><i>Example:</i> <code>$tag->rules->enableAutoLineBreaks();</code><br/>
Turns on the conversion of new lines to <code>&lt;br/&gt;</code>. Conversion applies to descendants as well, unless selectively disabled or suspended.</dd>

<dt>fosterParent</dt>
<dd><i>Example:</i> <code>$tag->rules->fosterParent('X');</code><br/>
Forces current parent X to be closed when this tag is encountered, and reopened as its child. If this tag is a self-closing tag, X is reopened as its next sibling.</dd>

<dt>ignoreSurroundingWhitespace</dt>
<dd><i>Example:</i> <code>$tag->rules->ignoreSurroundingWhitespace();</code><br/>
Whether whitespace around this tag should be ignored. Useful for allowing whitespace around block elements without extra newlines being displayed. Limited to 1 newline before the template, 1 newline at the start and at the end of its content, and up to 2 newlines after it.</dd>

<dt>ignoreTags</dt>
<dd><i>Example:</i> <code>$tag->rules->ignoreTags();</code><br/>
Silently ignore all tags until current tag is closed. Does not effect the automatic conversion of new lines or system tags such as line breaks, paragraphs breaks and ignore tags.</dd>

<dt>ignoreText</dt>
<dd><i>Example:</i> <code>$tag->rules->ignoreText();</code><br/>
Prevents plain text from being displayed as a child of this tag. Also disables line breaks. This rule deals with elements that do not allow text, such as lists. Does not apply to descendants.</dd>

<dt>isTransparent</dt>
<dd><i>Example:</i> <code>$tag->rules->isTransparent();</code><br/>
Indicates that this tag uses the <a href="http://www.w3.org/html/wg/drafts/html/master/single-page.html#transparent-content-models">transparent content model</a> and their allow/deny rules are inherited from its parent.</dd>

<dt>preventLineBreaks</dt>
<dd><i>Example:</i> <code>$tag->rules->preventLineBreaks();</code><br/>
Prevent manual line breaks in this tag's context. Does not apply to descendants. Does not apply to automatic line breaks.</dd>

<dt>requireParent</dt>
<dd><i>Example:</i> <code>$tag->rules->requireParent('X');</code><br/>
Prevents this tag from being used unless it's as a child of X. If multiple requireParent rules are set, only one has to be satisfied.</dd>

<dt>requireAncestor</dt>
<dd><i>Example:</i> <code>$tag->rules->requireAncestor('X');</code><br/>
Prevents this tag from being used unless it's as a descendant of X. If multiple requireAncestor rules are set, all of them must be satisfied.</dd>

<dt>suspendAutoLineBreaks</dt>
<dd><i>Example:</i> <code>$tag->rules->suspendAutoLineBreaks();</code><br/>
Temporarily turns off the conversion of new lines into <code>br</code> elements in this tag's text. Does not apply to descendants.</dd>

<dt>trimFirstLine</dt>
<dd><i>Example:</i> <code>$tag->rules->trimFirstLine();</code><br/>
Removes the first character inside given tag if it's a newline.</dd>

</dl>