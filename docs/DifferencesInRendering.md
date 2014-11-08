### Empty elements

What happens when a template outputs an element with no content, such as `<FOO></FOO>`?
```xslt
<xsl:template match="FOO">
	<div><xsl:apply-templates/></div>
</xsl:template>
```
HTML: ```<div></div>```
XML:  ```<div/>```

Same thing with an empty template:
```xslt
<xsl:template match="FOO">
	<div/>
</xsl:template>
```
HTML: ```<div></div>```
XML:  ```<div/>```

### Void elements

What happens when a template outputs a void element with no content?
```xslt
<xsl:template match="br">
	<br/>
</xsl:template>
```
HTML: ```<br>```
XML:  ```<br/>```

What happens when a template outputs a void element *with* content?
```xslt
<xsl:template match="br">
	<br>Not supposed to happen</br>
</xsl:template>
```
HTML: ```<br>```
XML:  ```<br>Not supposed to happen</br>```

Arguably, the XML rendering is wrong in that it's not valid XHTML but whatever the template says. If the content comes from user input (e.g. `<xsl:apply-templates/>`) this can easily be remedied by using `addHTML5Rules()` which automatically creates rules that prevent the tag from having any content.

### How to handle void elements in each mode

Based on XSLTProcessor's output:

| Void | Empty | HTML | XML |
|:----:|:-----:|------|-----|
| Yes  | Yes   | No end tag | Use a self-closing tag |
| Yes  | Maybe | Remove content, no end tag | Check for emptiness at runtime (self-closing tag) |
| Yes  | No    | Remove content, no end tag | |
| Maybe| Yes   | Check for voidness at runtime (no end tag) | Use a self-closing tag |
| Maybe| Maybe | Check for voidness at runtime (no end tag/ignore content) | Check for emptiness at runtime (self-closing tag)
| Maybe| No    | Check for voidness at runtime (no end tag/ignore content) | |
| No   | Yes   |      | Use a self-closing tag |
| No   | Maybe |      | Check for emptiness at runtime (self-closing tag) |
| No   | No    |      |     |

In short, the XML mode does not treat void elements differently. An empty element (no content) gets a self-closing tag.
In HTML mode, a void element will never have any content or an end tag. If possible, its content can be removed in advance from its template. For dynamic elements, the content/end tag should only be output if the element is verified not to be a void element.
