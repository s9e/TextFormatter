<h2>Tag rules</h2>

Rules are contained in <code>s9e\TextFormatter\Configurator\Collections\Ruleset</code>.  
They are set on a per-tag basis, for example:

```php
$configurator = new Configurator;

$tag = $configurator->tags->add('B');
$tag->rules->autoReopen();
$tag->rules->denyChild('X');
```

Method calls can be chained for convenience. The same example can be written as:

```php
$configurator = new Configurator;

$tag = $configurator->tags->add('B');
$tag->rules->autoReopen()
           ->denyChild('X');
```

Rules can be:

 * boolean — they accept <code>true</code> or <code>false</code> as argument, with <code>true</code> being the default.
 * targeted — they accept a tag name as argument.

Rules that apply to descendants also apply to children. Rules that apply to ancestors also apply to the parent. A tag that is explicitly denied cannot be allowed by another rule.

<dl>

<dt id="allowChild">allowChild</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->allowChild('X');</code></p>
	<p>Allows tag X to be used as a child of given tag.</p>
</dd>

<dt id="allowDescendant">allowDescendant</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->allowDescendant('X');</code></p>
	<p>Allows tag X to be used as a non-child descendant of given tag.</p>
</dd>

<dt id="autoClose">autoClose</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->autoClose(true);</code></p>
	<p>Start tags of this tag are automatically closed if they are not paired with an end tag. This rule exists primarily to deal with <a href="https://www.w3.org/html/wg/drafts/html/master/single-page.html#void-elements">void elements</a> such as <code>&lt;img&gt;</code>.</p>
</dd>

<dt id="autoReopen">autoReopen</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->autoReopen(false);</code></p>
	<p>Automatically reopens this tag if it's closed by a non-matching tag. This rule helps dealing with misnested tags such as <code>&lt;B&gt;&lt;I&gt;&lt;/B&gt;&lt;/I&gt;</code>. In this case, if <code>I</code> has an autoReopen rule, it will automatically be reopened when <code>B</code> closes.</p>
</dd>

<dt id="breakParagraph">breakParagraph</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->breakParagraph();</code></p>
	<p>This tag will break current paragraph if applicable.</p>
</dd>

<dt id="closeAncestor">closeAncestor</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->closeAncestor('X');</code></p>
	<p>Forces all ancestor tags X to be closed when this tag is encountered.</p>
</dd>

<dt id="closeParent">closeParent</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->closeParent('LI');</code></p>
	<p>Forces current parent LI to be closed when this tag is encountered. Helps dealing with <a href="https://www.w3.org/html/wg/drafts/html/master/single-page.html#optional-tags">optional end tags</a>. For instance, if LI has a closeParent rule targeting LI, the following <code>&lt;LI&gt;one&lt;LI&gt;two</code> is interpreted as <code>&lt;LI&gt;one&lt;/LI&gt;&lt;LI&gt;two</code>.</p>
</dd>

<dt id="createChild">createChild</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->createChild('LI');</code></p>
	<p>Automatically creates a LI tag at the first non-whitespace position after current tag.</p>
</dd>

<dt id="createParagraphs">createParagraphs</dt>
<dd>
	<p><i>Example:</i> <code>$configurator->rootRules->createParagraphs();</code></p>
	<p>Automatically creates paragraphs (HTML element <code>&lt;p&gt;</code>) to host content. Using two consecutive new lines indicates a paragraph break in content.</p>
</dd>

<dt id="denyChild">denyChild</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->denyChild('X');</code></p>
	<p>Prevents tag X to be used as a child of this tag.</p>
</dd>

<dt id="denyDescendant">denyDescendant</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->denyDescendant('X');</code></p>
	<p>Prevents tag X to be used as a non-child descendant of this tag.</p>
</dd>

<dt id="disableAutoLineBreaks">disableAutoLineBreaks</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->disableAutoLineBreaks();</code></p>
	<p>Turns off the conversion of new lines in the scope of this tag. Conversion can be turned back on by descendants.</p>
</dd>

<dt id="enableAutoLineBreaks">enableAutoLineBreaks</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->enableAutoLineBreaks();</code></p>
	<p>Turns on the conversion of new lines to <code>&lt;br/&gt;</code>. Conversion applies to descendants as well, unless selectively disabled or suspended.</p>
</dd>

<dt id="fosterParent">fosterParent</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->fosterParent('X');</code></p>
	<p>Forces current parent X to be closed when this tag is encountered, and reopened as its child. If this tag is a self-closing tag, X is reopened as its next sibling.</p>
</dd>

<dt id="ignoreSurroundingWhitespace">ignoreSurroundingWhitespace</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->ignoreSurroundingWhitespace();</code></p>
	<p>Whether whitespace around this tag should be ignored. Useful for allowing whitespace around block elements without extra newlines being displayed. Limited to 1 newline before the template, 1 newline at the start and at the end of its content, and up to 2 newlines after it.</p>
</dd>

<dt id="ignoreTags">ignoreTags</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->ignoreTags();</code></p>
	<p>Silently ignore all tags until current tag is closed. Does not effect the automatic conversion of new lines or system tags such as line breaks, paragraphs breaks and ignore tags.</p>
</dd>

<dt id="ignoreText">ignoreText</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->ignoreText();</code></p>
	<p>Prevents plain text from being displayed as a child of this tag. Also disables line breaks. This rule deals with elements that do not allow text, such as lists. Does not apply to descendants.</p>
</dd>

<dt id="isTransparent">isTransparent</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->isTransparent();</code></p>
	<p>Indicates that this tag uses the <a href="https://www.w3.org/html/wg/drafts/html/master/single-page.html#transparent-content-models">transparent content model</a> and their allow/deny rules are inherited from its parent.</p>
</dd>

<dt id="preventLineBreaks">preventLineBreaks</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->preventLineBreaks();</code></p>
	<p>Prevent manual line breaks in this tag's context. Does not apply to descendants. Does not apply to automatic line breaks.</p>
</dd>

<dt id="requireParent">requireParent</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->requireParent('X');</code></p>
	<p>Prevents this tag from being used unless it's as a child of X. If multiple requireParent rules are set, only one has to be satisfied.</p>
</dd>

<dt id="requireAncestor">requireAncestor</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->requireAncestor('X');</code></p>
	<p>Prevents this tag from being used unless it's as a descendant of X. If multiple requireAncestor rules are set, all of them must be satisfied.</p>
</dd>

<dt id="suspendAutoLineBreaks">suspendAutoLineBreaks</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->suspendAutoLineBreaks();</code></p>
	<p>Temporarily turns off the conversion of new lines into <code>br</code> elements in this tag's text. Does not apply to descendants.</p>
</dd>

<dt id="trimFirstLine">trimFirstLine</dt>
<dd>
	<p><i>Example:</i> <code>$tag->rules->trimFirstLine();</code></p>
	<p>Removes the first character inside given tag if it's a newline.</p>
</dd>

</dl>