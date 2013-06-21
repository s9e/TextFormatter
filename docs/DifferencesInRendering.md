### Empty tags

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