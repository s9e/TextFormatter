<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:html="urn:s9e:TextFormatter:html">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="html:*">
		<xsl:element name="{local-name()}">
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>

</xsl:stylesheet>