<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<!-- NYH -->
	<xsl:template match="FOO">
		<xsl:element name="div"><xsl:attribute name="id">foo</xsl:attribute></xsl:element>
	</xsl:template>

</xsl:stylesheet>