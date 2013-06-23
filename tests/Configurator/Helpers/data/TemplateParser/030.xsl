<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" encoding="utf-8" />

	<!--
		Test that the element is marked as empty="yes" because no branches produce output
	-->
	<xsl:template match="FOO">
		<xsl:element name="div">
			<xsl:choose>
				<xsl:when test="@foo"><xsl:attribute name="id">foo</xsl:attribute></xsl:when>
				<xsl:when test="@bar"><xsl:attribute name="id">bar</xsl:attribute></xsl:when>
				<xsl:otherwise><xsl:attribute name="id">baz</xsl:attribute></xsl:otherwise>
			</xsl:choose>
		</xsl:element>
	</xsl:template>

</xsl:stylesheet>