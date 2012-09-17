=== Plugin Name ===
Contributors: seanbarton
Tags: page, parent, child, child list, sb_child_list, cms, hierarchy, breadcrumbs, links, category listings, sub pages, sub page, siblings, page siblings
Requires at least: 3.0
Tested up to: 3.4.2

The total in-page navigation solution for Wordpress. Using the shortcodes and widgets provided you can display navigation between your parent, child and sibling items in any format you can think of. Additionally by way of shortcode allows category post functionality.

== Description ==

This plugin lets you turn Wordpress into a proper easily navigable CMS. Having a site made up mostly of pages in the form of a tree (an information site for example) was always a pain until I wrote this to help me out. I have had lots of feedback about it over the last few months and more and more people are finding ways they can use it to improve the navigation and usability of their sites

I wanted to show some sort of hierarchy in the pages inside Wordpress. I decided to call one of my pages "articles" and have my articles use it as their parent.

Logically you would expect to see a list of the child pages on the articles page along with a pretty picture and some intro text. This doesn't seem possible natively with Wordpress so I decided to make it happen.

Adding the hook [sb_child_list] to any post or page will by default show an unordered list showing the children and links to them. If you prefer to style it yourself then don't worry because you can do that too using the templating system on the settings page.

There is also add another tag, [sb_parent], that allows you to provide a back to parent link from any child. This enables you (in the articles example) to add a "click here to read more on this subject" link. It is also template based so it can say anything or look however you see fit

You can also use this shortcode: [sb_cat_list category=CatName] which does what it says on the tin, simply give it a category name and it will show the posts in that category for you with optional 'limit' argument

The plugin creates a widget for use also. This works if there is a sub page present. Much like a sub pages widget or similar.

I have added a shortcode for sibling navigation. You can use [sb_sibling_next] and [sb_sibling_prev] to show links to next and previous pages ordered by menu order followed by post title order. Handy indeed!

There is a simple templating sytstem to allow multiple templates for [sb_child_list] and [sb_cat_list] with the argument 'template' ([sb_child_list template="2"]). Also added excerpt support and support for the SB Uploader Plugin custom fields (custom field called post_image will show if necessary).

To round it all up.. Shortcode listings:

[sb_child_list] <-- Arguments allowed are: 'template', 'nest_level' and 'order'
[sb_parent]
[sb_grandparent]
[sb_cat_list]
[sb_sibling_next]
[sb_sibling_prev]

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `sb_child_list.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `[sb_child_list] in a parent page and optionally [sb_parent] to any child pages. Now with [sb_cat_list] for category items`
4. In the settings menu under `SB Child List`, you will find lots of lovely config options

== Screenshots ==

Screenshots available at: http://www.sean-barton.co.uk/sb-child-list/

== Changelog ==

 0.1:	Basic functionality.
 
 0.5:	Admin Page added.
 
 0.9:	Templating and nest limiting.
 
 1.0:	Added backlink from child to parent.
 
 1.1:	Added sb_cl_cat_list functionality
 
 1.2:	Now using get_permalink for the child list. Means the guid field is no longer relied on and links always work
 
 1.3:	Added post_thumb to the templating system. Uses the WP Post Thumbnail system. Contributed by a plugin user.
 
 1.4:	Fixed post_thumb option whereby the function didn't exist on some installs. Uses the get_the_post_thumb function to operate
 
 1.5:	Updated sb_parent permalink from guid to get_permalink
 
 1.6:	Added templating for the shortcodes (multiple instances of the shortcode in different formats now possible) and support for the_excerpt and SB Uploader output (custom fields called post_image and post_image2 will be recognised)
 
 1.7:	Forced page excerpt support in case it wasn't already added. Added tooltip for post_excerpt
 
 1.8:	Added ability to sort a child list by any field in the wp_posts table by adding order="field_name" to the shortcode
 
 1.9:	Added child list widget to show sub pages of current page or any other page of your choice.
 
 2.0: 	Fixed widget title issue whereby the title was being changed to 1,2,3 depending on the template used.
 
 2.1:	Child list and widget now shows ancestors if there are no children. Added parent link option to widget
 
 2.2:	Fixed issue with siblings showing in normal child list and then repeating themselves breaking the site.
 
 2.3:	Added two new shortcodes sb_sibling_next and sb_sibling_prev. Kind of like next and previous navigation for posts. Uses menu order for display followed by alphabetical post titles.
 
 2.4:	Added sb_grandparent so that you can feature one more level of parentage as a link back. Added getText format on "Back to" text for localisation.
 
 2.5:	When [post_class] is used and the item relates to the current page then a classname will be added: 'current_page_item sb_cl_current_page' to allow you to style individual rows using CSS making the current page stand out perhaps.
 
 2.6:	Added custom excerpt function so that when using [post_excerpt] in the template if you don't enter a manual one it will generate it from the post body as Wordpress does normally.
 
 2.7:	Minor update, added support for qTranslate
 
 2.8:	Minor update, added support for excerpt more tag if used.
  
 2.9:	Minor Update, added order parameter to sb_cat_list shortcode. Default ordering to post title.
 
 3.0:	Minor update. Added ability to fix the parent ID of a child using parent_id="" in sb_child_list shortcode
 
 3.1:	Minor update. Added template settings shortcode [post_thumb_url] and removed the default link to large image around [post_thumb]. Allows you to set up your own link around the thumb to go wherever you like.. be it larger image or the post itself
 
 3.2:	Bug Fix update. [post_image] didn't work from my 3.1 update because of the logic using the incorrect post ID. Sorted now.
 
 3.3:	Minor update. Added new option to turn off the siblings list on the lowest level. This means that when you get to the bottom of the page tree the child list will disappear if this option is utilised
 