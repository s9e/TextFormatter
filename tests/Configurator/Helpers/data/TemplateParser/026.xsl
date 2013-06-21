<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="FOO">
		<div>
			<xsl:choose>
				<xsl:when test="@a"><xsl:attribute name="title">foo</xsl:attribute></xsl:when>
				<xsl:otherwise>foo</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

</xsl:stylesheet>