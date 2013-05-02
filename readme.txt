=== Create New Site ===
Contributors: mfkelly
Tags: create, site, multisite, blog, new, buddypress
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.1

== Description ==
Buddypress plugin. Requires Buddypress!
Allow logged in users to create new blog sites, up to a defined limit, via links in the admin bar.

== Installation ==

1. Copy the Create New Site folder to your web server's wp-content/plugins folder.

2. Set a value for a limit to the number of personal sites in the wp-config.php file, like this: 
define('WP_BLOGS_PER_USER', 5);

3. Make sure that the 'subnav' div in your Buddypress theme's members/single/blog.php file has the class 'no-ajax' defined, e.g.:
<div class="item-list-tabs no-ajax" id="subnav" role="navigation">

4. Activate the plugin for the network.

== Changelog ==

*0.1 Initial Release*