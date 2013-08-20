<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="QUOTE">
		<xsl:choose>
			<xsl:when test="$STYLE_ID=2">
				<xsl:choose>
					<xsl:when test="@author">xxx</xsl:when>
					<xsl:otherwise>yyy</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="@author">aaa</xsl:when>
					<xsl:otherwise>bbb</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>