=== Plugin Name ===
Contributors: seanbarton
Tags: page, parent, child, child list, sb_child_list, cms, hierarchy, breadcrumbs, links, category listings, sub pages, sub page
Requires at least: 2.3
Tested up to: 3.1.4

A Plugin to introduce some shortcodes to use on parent pages which give dynamic information on it's children. Additionally gives a category post listing shortcode

== Description ==

This plugin lets you turn Wordpress into a proper easily navigable CMS. Having a site made up mostly of pages in the form of a tree (an information site for example) was always a pain until I wrote this to help me out. I have had lots of feedback about it over the last few months and more and more people are finding ways they can use it to improve the navigation and usability of their sites

I wanted to show some sort of hierarchy in the pages inside Wordpress. I decided to call one of my pages "articles" and have my articles use it as their parent.

Logically you would expect to see a list of the child pages on the articles page along with a pretty picture and some intro text. This doesn't seem possible natively with Wordpress so I decided to make it happen.

Adding the hook [sb_child_list] to any post or page will by default show an unordered list showing the children and links to them. If you prefer to style it yourself then don't worry because you can do that too using the templating system on the settings page.

There is also add another tag, [sb_parent], that allows you to provide a back to parent link from any child. This enables you (in the articles example) to add a "click here to read more on this subject" link. It is also template based so it can say anything or look however you see fit

You can also use this shortcode: [sb_cat_list category=CatName] which does what it says on the tin, simply give it a category name and it will show the posts in that category for you with optional 'limit' argument

The latest version incorporates some crude templating to allow multiple templates for [sb_child_list] and [sb_cat_list] with the argument 'template' ([sb_child_list template="2"]). I shall make it more elegant in the future but for now it does the job. Also added excerpt support and support for the SB Uploader Plugin custom fields.

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