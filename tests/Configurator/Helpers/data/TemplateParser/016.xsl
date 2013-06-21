<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="A">
		<a href="{@href}">
			<xsl:copy-of select="@title"/>
			<xsl:apply-templates/>
		</a>
	</xsl:template>

</xsl:stylesheet>