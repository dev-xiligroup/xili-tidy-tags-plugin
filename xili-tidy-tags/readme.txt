=== xili-tidy-tags ===
Contributors: michelwppi, MS dev.xiligroup
Donate link: http://dev.xiligroup.com/
Tags: tag,tags,theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary,widget,CMS, multisite, wpmu, bbPress, shortcode
Requires at least: 4.0
Tested up to: 4.3
Stable tag: 1.11.2
License: GPLv2

xili-tidy-tags is a tool for grouping tags by semantic groups or by language and for creating tidy tag clouds. 

== Description ==

= on monolingual website (blog or CMS) =
xili-tidy-tags is a tool for grouping tags by semantic groups and sub-groups.
This tags aggregator can also, by instance, be used to group tags according two or more main parts of the CMS website. It is also possible to create group of tags in parallel of category and display a ‘sub’ tag cloud only depending of the displayed category.

= on multilingual website =
xili-tidy-tags is a tool for grouping tags by language with xili-language plugin for multilingual site and for creating tidy tag clouds. By instance to present only tags in english when the theme is in english because the post or the current category present texts in english. Technically, as xili-language, this plugin don't create tables in wordpress db. He only use (rich) taxonomy features. So, with or without the plugin, the base structure is not modified. 

= Why xili-tidy-tags versus / against included parent property of terms ? =

With default parent feature, a tag (term) can have only one parent. The default taxonomy (see file taxonomy.php in folder wp-includes) is very poweful but don't include the queries to group tags under one another tag. Is is the purpose of this plugin xili-tidy-tags created since WP 2.7 ! Initially created to group tags by language, he structurally contains all functions to group tags by semantic groups AND one tag can belong to one or more groups.

= Why xili-tidy-tags introduces grouping features since version 1.9 ? =
**RESERVED for DEVELOPERS using template-tags**
Using nice feature "alias of" and "group" of WP core taxonomy.php, xili-tidy-tags offers now a way to "link" tags of different languages (red, rouge, rot,…). So when displaying list of french posts associated with a french tag (*rouge*), it is now possible to show a list of tags in other languages (*red, rouge, rot,…*) and visitor is now able to click on *red* and show the webpage of list of posts tagged with *red*. Only taxonomy wpdb tables are used, no new tables, no new lines in options table.

= Widget to insert Tags cloud =
The powerful widget is easy to setup and to choose what and when group of tags to display.

= Template tags usable in theme design = 
Template tags are provided to enrich the theme and display sub-selection of tags.
Through the settings admin UI, it is possible to assign to a tag one or more groups (by instance a french tag to the french language group. A trademark term like WordPress to a group named "trademark". You can choose different storage policies.

**NEW 2 template-tags since 1.9 :** `xili_tidy_tags_group_links` to show the group of tags containing the current tag (useful in tag.php of theme) and `xili_tidy_tag_in_other_lang` to return info (link) of one other tag of the group in an another lang. Xili-language version > 2.9.0 will use the links switching in language switching navigation menu when displaying tag.php page. (if "red" tag page is displayed, language menu for french will link to "rouge" !)

= Shortcode =
add shortcode inside a post content to include in your text a cloud of a group of tags.

**Example of shortcode :**  `[xili-tidy-tags params="tagsgroup=trademark&largest=10&smallest=10" glue=" | "]`
In this cas, the group of tags named 'trademark' will be display inside a paragraph of a post. The params are defined as in `xili_tidy_tag_cloud()` and as in `wp_tag_cloud()`. The glue is chars inserted between the tags (if omitted default is a space).

[Example of tag cloud made with shortcode here](http://dev.xiligroup.com/?xilifunctions=shortcode-xili-tidy-tags)

= TRILOGY FOR MULTILINGUAL CMS SITE =
Please verify that you have installed the latest versions of:
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)

= Translations available for admin UI =
* english, french by the author,
* spanish and serbian - contributions of [Ognjen D., firstsiteguide.com](http://www.firstsiteguide.com)

= Roadmap =
* readme.txt rewritting.
* more function for grouping new features introducted in version 1.9

= Version 1.11.2 =
* Last Updated 2015-09-24
* see [tab and chapters in changelog](http://wordpress.org/extend/plugins/xili-tidy-tags/changelog/)


== Installation ==

1. Upload the folder containing `xili-tidy-tags.php` and others files to the `/wp-content/plugins/` directory,
2. If xili-language plugin is activated, groups of languages are automatically created. If not, you can also use xili-tidy-tags to group your tags in semantic group like technical, trademark...
3. in theme, a new template tag is available : `xili_tidy_tag_cloud` Same passed values as tag_cloud but two new : tagsgroup and tagsallgroup . tagsallgroup can be the parent group slug, tagsgroup is one of the child group slug. If one or both are included, the cloud is built with sub-selected tags in this (theses) group(s). 


**Exemples of script in sidebar.php :**

= with xili-language plugin activated in multilingual website =
`
<div><h2><?php _e('Tags cloud','xilidev');?></h2>
<?php if (function_exists('xili_tidy_tag_cloud') && class_exists('xili_language')) xili_tidy_tag_cloud('tagsgroup='.the_curlang().'&tagsallgroup=tidy-languages-group&largest=18'); ?>
</div>
`

= with semantic group named as category and a group containing trademarks named trademark =
`
<h2><?php _e('Tags cloud','xilidev');?></h2><?php 
if (function_exists('xili_tidy_tag_cloud')) xili_tidy_tag_cloud('tagsgroup='.single_cat_title('',false).'&tagsallgroup=trademark&largest=18'); ?>
</div>
`
= example of a splitted tag cloud of authors group (here separated by hr) - change html tags if you want to build a table with 3 columns =
`
<div><h2><?php _e('Tags clouds','xilidev');?></h2><?php if (function_exists('xili_tidy_tag_cloud')) xili_tidy_tag_cloud('tagsgroup=authors&largest=18&&number=15'); ?>
<hr />
<?php if (function_exists('xili_tidy_tag_cloud')) xili_tidy_tag_cloud('tagsgroup=authors&largest=18&&offset=15&number=15'); ?>
<hr />
<?php if (function_exists('xili_tidy_tag_cloud')) xili_tidy_tag_cloud('tagsgroup=authors&largest=18&&offset=30&number=150'); ?>
</div>
`
= note about template tag =

If the two args tagsgroup and tagsallgroup are empty, the content is all the tags as in current tag cloud but with more features for selecting or look as soon documented.

= note about widget =
If you create the single widget since 0.9.0, with 0.9.2 (which allows more than one), you need to recreate one, two or more widget(s) in theme admin UI.


== Frequently Asked Questions ==

= What about WP 3.0 multisite mode (WPMU) and the trilogy ? =
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)

Since WP 3.0-alpha and now with 3.1, if multisite is activated, the trilogy is now compatible and will include progressively some improvements dedicaded especially for WP network context. Future specific docs will be available for registered webmasters.

= What about custom taxonomies and tidy grouping ? =

Since release 1.6.0, xili-tidy-tags is compatible with custom taxonomies. Reserved for skilled webmasters with WP, data model   and php knowledges. Multiple instantiation of this powerful plugin is possible.

= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/ "a multi-language site")

and

www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

and the first from China since plugin version 0.8.0

layabozi.com [here](http://layabozi.com) to sub select music maker name and other tags sub-groups.

and a wonderful website

[Frases de cine](http://www.frasesdecine.es) with more than 200 tags.

or
[794 point 8 - Petite bibliothèque vidéoludique](http://www.794point8.com) as a library of video games.

= Compatibility with other plugins ? =

In xiligroup plugins series, xili-tidy-tags is compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/), [xilitheme-select](http://wordpress.org/extend/plugins/xilitheme-select/) and [others](http://wordpress.org/extend/plugins/search.php?q=xili&sort=) , a set of plugins to create powerful multilingual (multisite) CMS website. 

== Screenshots ==

1. the admin settings UI : tidy tags groups.
2. the admin assign UI : table and checkboxes to set group of tags.
3. the admin settings UI : table and checkboxes to set group of tags : sub-selection of column groups.
4. widget UI : example where cloud of tags is dynamic and according categories and include group trademark.
5. widget UI : (xili-language plugin activated) example where cloud of tags is dynamic and according active language.
6. widget UI : display a sub-group of tags named Trademark.
7. the admin assign UI : with big tags list, it is now possible to select tags starting or containing char(s) or word(s).
8. the admin assign UI : here only the group “software” - a parent group -  is selected and all tags of his childs are shown.
9. the admin assign UI : here only the group “software” - a parent group -  is selected and only tags of this group are shown (No childs checked).
10. Tags grouping - same sense but in different languages.

== Changelog ==
= 1.11.2 ( 2015-09-24 ) =
* ready for XL 2.20.3
= 1.11.1 ( 2015-07-05 ) =
* Updated datatables js css
* pre-tested WP 4.3-beta1
= 1.10.2 ( 2015-03-22 ) 1.10.3 (2015-04-08) =
* Updated datatables js css
* tested WP 4.2-beta4
= 1.10.1 ( 2015-02-28 ) =
* new js inside dropdown function (updated - see end of sources)
* rewrite selected(), checked()
* pre-tested WP 4.2-alpha
= 1.10.0 ( 2014-12-19 ) =
* tested WP 4.1
* fixes editing alias tags through new or edit form one post_tag
= 1.9.3 ( 2014-12-10 ) =
* tests WP 4.1-beta - fixes news_id
= 1.9.2 ( 2014-04-13 ) =
* tests WP 3.9-rc - fixes notices
= 1.9.1 ( 2013-12-29 ) =
* tests WP 3.8 final - 
* more accurate messages on initialisation
= 1.9.0 ( 2013-09-29 ) =
* Grouping features : This version introduces the tags multilingual grouping *same sense but in different languages* (using alias feature existing in WP taxonomy) according languages.
= 1.8.6 ( 2013-09-03 ) =
* tests WP 3.6 final - 
* fixes Strict Standards message (class Walker)
* new icons
* dropdown template tag for tags : xili_tidy_tags_dropdown (see commented example in sources line #1009)
= 1.8.5 ( 2013-05-25 ) =
* tests 3.6-beta3,
* fixes Strict Standards message (class Walker), 
* fixes rare situation when one or more plugin desactivated.
= 1.8.4 ( 2013-05-08 ) =
* add capabilities removing when deactivating.
* tests 3.6-beta2
* po file updated
= 1.8.3 ( 2013-04-22 ) =
* tests 3.6
* better errors management for external instancing
= 1.8.2 ( 2013-01-27 ) = 
* tests WP 3.5.1 - fixes warning
= 1.8.0, 1.8.1 ( 2012-08-20, 2012-09-25 ) =
* fixes (constants), cloud/list echoing in template tag
* admin UI in separate class and file.
* new icons
= 1.7.0 ( 2012-05-28) =
* language info in tags list
* fixes in assign list display
* cloud of other site if multisite in widget (further dev)
= 1.6.4 (2012-04-05) =
* pre-tests with WP 3.4: fixes metaboxes columns
= 1.6.3 =
* maintenance release - warning
= 1.6.0, 1.6.2 (2011-10-08) =
* fixes custom post tags, tag edit + hierarchy
* fixes url and messages, new folder organization, fixes
* source cleaned.
* today xili-tidy-tags is ready for custom taxonomy and custom post type.
* possible multiple instantiation to permit another custom taxonomy similar to `post_tag`
* fixe group name editing.
= 1.5.4 = add two template tags : `link_for_posts_of_xili_tags_group` to return the link to show posts of a xili_tags_group. `xili_tags_group_list` to list of tags-group with link to list Posts with tags belonging to each tags-group. See end of source to read example.
= 1.5.3.1 = add option to desactivate javascript list
= 1.5.3 = add options to select unchecked tags only and to exclude one group and include unchecked.
= 1.5.2 = fixes issues when xl temporary desactivated, some cache issues fixed.
= 1.5.1 = popup for groups in cloud widget, fixes DISTINCT issue when merging two groups
= 1.5.0 (2010-11-07) = javascript in tags list assign
= 1.3.1 to 1.4.3 = pre-tests for WP3.0-beta, WP3.0, Code partially rewritten, Capabilities fixed,...
= 1.3.0 (2010-02-18) = add sub-selection by tags belonging to a group. Now uses Walker class to sort groups in settings UI.
= 1.2.1 = fix quick-edit tag function.
= 1.2 = fix `xili_tidy_tag_cloud` sort and order.
= 1.1 (2009-10-12) = In loop, the template tag `the_tags` named `xili_the_tags` is now able to show only tags of sub-group(s).
= 1.0.1 = some fixes in php code on some servers (Thanks to Giannis)
= 1.0 (2009-06-11) = 
* add shortcode to include a cloud of a group of tags inside a post,
* compatible with WP 2.8.
= 0.9.5 = Capabilities and roles, better admin menu
= 0.9.4 = when creating tags in post UI - group new tag to default lang if xili-language is active
= 0.9.3 = W3C, recover compatibility with future WP 2.8
= 0.9.2 = changing kindship, now allows multiple cloud widgets.
= 0.9.1 = with big tags list, select tags starting or containing char(s) or word(s). &offset= et &number= in `xili_tidy_tag_cloud`
= 0.9.0 = widget for compatible themes and UI actions to include group according a chosen category
= 0.8.2 = fixes php warning when tagsgroup args are empty in tidy_tag_cloud()
= 0.8.1 (2009-03-31) = some fixes - improved query - better tag_cloud()
= 0.8.0 = first public beta release.

© 2015-09-24 dev.xiligroup.com

== Upgrade Notice ==

* As recommanded, don't forget to make a backup of the database.
* Upgrading can be easily procedeed through WP admin UI or through ftp (delete previous release folder before upgrading via ftp).
* If updating via desktop ftp : erase previous version folder before uploading latest version.
* Verify you install latest version of xili-language trilogy.

== More infos ==

= Capabilities and roles : =

0.9.5 : Administrator role can create grouping or setting capabilities for editor role. 'Grouping' permits to editor to group tags in group (lang and/or semantic). 'Setting' permits to editor to create, modify or delete semantic groups. Only administrator has access to languages groups. 


The plugin post is frequently documented [dev.xiligroup.com](http://dev.xiligroup.com/)
and updated [Wordpress repository](http://wordpress.org/extend/plugins/xili-tidy-tags/download/).

See also the [dev.xiligroup plugins forum](http://dev.xiligroup.com/?forum=xili-tidy-tags-plugin).


© 2009-2015 MS - dev.xiligroup.com
