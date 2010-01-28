=== Plugin Name ===
Contributors: seanbarton
Tags: page, parent, child, child list, sb_child_list, cms, hierarchy, breadcrumbs, links
Requires at least: 2.3
Tested up to: 2.9.1

A Plugin to introduce some shortcodes to use on parent pages which give dynamic information on it's children

== Description ==

This plugin lets you turn Wordpress into a proper easily navigable CMS. Having a site made up mostly of pages in the form of a tree (an information site for example) was always a pain until I wrote this to help me out. I have had lots of feedback about it over the last few months and more and more people are finding ways they can use it to improve the navigation and usability of their sites

I wanted to show some sort of hierarchy in the pages inside Wordpress. I decided to call one of my pages "articles" and have my articles use it as their parent.

Logically you would expect to see a list of the child pages on the articles page along with a pretty picture and some intro text. This doesn't seem possible natively with Wordpress so I decided to make it happen.

Adding the hook [sb_child_list] to any post or page will by default show an unordered list showing the children and links to them. If you prefer to style it yourself then don't worry because you can do that too using the templating system on the settings page.

There is also add another tag, [sb_parent], that allows you to provide a back to parent link from any child. This enables you (in the articles example) to add a "click here to read more on this subject" link. It is also template based so it can say anything or look however you see fit

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `sb_child_list.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `[sb_child_list] in a parent page and optionally [sb_parent] to any child pages`
4. In the settings menu under `SB Child List`, you will find some config options

== Screenshots ==

Screenshots available at: http://www.sean-barton.co.uk/sb-child-list/