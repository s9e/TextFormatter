## Block elements

### Blockquotes

A line that starts with a right angle bracket > (optionally followed by a space) is a blockquote. Blockquotes can be nested but they can't be used inside of lists.

```md
> > Deep blockquote
>
> Shallower blockquote

No blockquote
```
```html
<blockquote><blockquote><p>Deep blockquote</p></blockquote>

<p>Shallower blockquote</p></blockquote>

<p>No blockquote</p>
```

### Lists

`*`, `-` and `+` for unordered lists, or any number of digits followed by a dot such as `1.` for an ordered list. The list item must be followed by a space then at least one characters. Lists can be nested and they can be used inside of blockquotes.

The indentation inside of nested lists emulates the behaviour of the original Markdown, meaning that sublists after the first should be indented by 4 spaces or a tab.

If a list has any of its text content or list items separated with a blank line, each of its items' content is wrapped in a paragraph.

```md
1. Collect underpants
2. **?**
3. Profit
```
```html
<ol><li>Collect underpants</li>
<li><strong>?</strong></li>
<li>Profit</li></ol>
```

### Indented code blocks

A series of lines indented by at least 4 spaces or a tab, preceded with an empty line.


## Formatting elements

### Emphasis

A pair of `*` or `_` marks emphasis (`<em>`) while a pair of `**` or `__` marks strong emphasis (`<strong>`). One exception: a single `_` between two ASCII alphanumerical character is kept as-is.

```md
un*frigging*believable

perform_complicated_task
```
```html
<p>un<em>frigging</em>believable</p>

<p>perform_complicated_task</p>
```

When a block of three `*` or `_` is found, the order of strong/em depends on the next series of `*` or `_` characters.

```md
***foo* bar**

***foo** bar*
```
```html
<p><strong><em>foo</em> bar</strong></p>

<p><em><strong>foo</strong> bar</em></p>
```

### Strikethrough

Any text between two `~~` markers.

```md
90s haircuts are ~~cool~~ ~~lame~~ cool again.
```
```html
<p>90s haircuts are <del>cool</del> <del>lame</del> cool again.</p>
```

### Inline code

Any text between two `\`` or `\`\`` markers.

```md
Single `print("``")` or double ``print("`")``
```
```html
<p>Single <code class="inline">print("``")</code> or double <code class="inline">print("`")</code></p>
```
