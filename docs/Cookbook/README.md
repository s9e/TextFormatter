## Table of content

### **Getting started**
  * [Installation](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/00_Installation.md)
  * [Mode of operation](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/01_Mode_of_operation.md)
  * **Basic usage**
    * **Configuration**
      * [Zero-configuration: using predefined bundles](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/Basic_usage/Configuration/00_ZeroConfig.md)
      * [À la carte: make your own bundle](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/Basic_usage/Configuration/01_CustomBundle.md)
      * [Expert mode: configure everything yourself](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/Basic_usage/Configuration/02_Expert.md)
    * [Unparsing](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/Basic_usage/Unparsing.md)

### **Bundles**
  * [Fatdown, a Markdown bundle that doesn't suck too bad](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Fatdown.md)
  * [Forum, a bundle for forum software](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Forum.md)
  * [S18, a bundle compatible with SMF 2.1 markup](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/S18.md)

### **Plugins**
  * **BBCodes**
    * [Add custom BBCodes](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/BBCodes/AddCustom.md)
    * [Add BBCodes from the bundled repository](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/BBCodes/AddFromRepository.md)
    * [Localize strings in a BBCode template](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/BBCodes/Localize.md)
    * [Use template parameters in a BBCode template](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/BBCodes/Parameters.md)
  * **Censor**
    * [Using the Censor plugin in plain text and HTML](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Censor/CensorText.md)
    * [Quickly patch old parsed texts for a new list of words](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Censor/Reparse.md)
    * [Using the Censor plugin as a standalone word filter](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Censor/Standalone.md)
    * [Whitelist specific words to escape the censor](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Censor/Whitelist.md)
  * **Emoticons**
    * [Add emoticons](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Emoticons/AddEmoticons.md)
    * [How to restrict where emoticons are parsed](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Emoticons/ChangeRegexp.md)
    * [How to toggle whether emoticons should be displayed](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Emoticons/ToggleRendering.md)
  * **HTMLElements**
    * [Alias HTML elements to other tags](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/HTMLElements/Aliases.md)
  * **Keywords**
    * [Automatically link Magic: The Gathering cards](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Keywords/MTG.md)
    * [How to map keywords to IDs](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/Keywords/Map.md)
  * **MediaEmbed**
    * [Add a site from the supported list](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/MediaEmbed/AddBundled.md)
    * [Add a site manually](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/MediaEmbed/AddCustom.md)
    * [Add a link to the original URL below the embedded content](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/MediaEmbed/AppendTemplate.md)
    * [Change the template used to render embedded content](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/MediaEmbed/ChangeTemplate.md)
    * [Use a cache to improve scraping performance](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/30_Plugins/MediaEmbed/PerformanceCacheDir.md)

### **Templating**
  * [Introduction](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/00_Introduction.md)
  * [Template parameters](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/TemplateParameters.md)
  * **Template normalization**
    * [Change the default normalization](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/Template_normalization/01_ChangeDefault.md)
    * [Automatically set `rel="nofollow"` on every link](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/Template_normalization/02_SetRelLink.md)
    * [Extend the template syntax through normalization](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/Template_normalization/03_Extends.md)

### **Miscellaneous**
  * [Automatic rules generation](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/50_Miscellaneous/AutomaticRulesGeneration.md)
  * [Rendering engines](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/50_Miscellaneous/Renderers.md)
  * **URL features**
    * [Disallow links pointing to a given domain](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/50_Miscellaneous/URL_features/DisallowHosts.md)
    * [Restrict/allow schemes](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/50_Miscellaneous/URL_features/Protocols.md)
    * [Restrict links and/or images to a set of whitelisted domains](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/50_Miscellaneous/URL_features/RestrictHosts.md)
