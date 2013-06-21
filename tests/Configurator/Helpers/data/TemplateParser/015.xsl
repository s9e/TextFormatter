<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="IE">
		<xsl:comment>[if IE 6]&gt;&lt;p&gt;You are using Internet Explorer 6.&lt;/p&gt;&lt;![endif]</xsl:comment>
		<xsl:comment>[if !IE]&gt;&lt;!</xsl:comment>
		<p>You are not using Internet Explorer.</p>
		<xsl:comment>&lt;![endif]</xsl:comment>
	</xsl:template>

</xsl:stylesheet>