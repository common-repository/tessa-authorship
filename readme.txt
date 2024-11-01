=== Tessa Authorship ===
Contributors: ulih
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Y485CQJA5Y3PC
Tags: tessa authorship, authorship, biography, bio, bio box, related, related post, related author, link post, link author, relation, relationship, biography box, twitter, facebook, linkedin, googleplus, google+, delicious, flickr, picasa, vimeo, youtube, reddit, website, about, author, user, about author, user box, author box, contributors, author bio, author biography, user biography, avatar, gravatar, guest post, guest author, publisher, copyright, gallery, exposition, third-party content, widget, shortcode, template-tag, social, fusion, collaboration, custom post-type, post-type, custom post type, post type
Requires at least: 3.4.2
Tested up to: 4.0
Stable tag: 0.7.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show author information on post types not depending on WordPress users for galleries, expositions, content offered for/by third parties, etc.

== Description ==

"Tessa Authorship" allows to set and handle authorship and relations with every WordPress content that exists on your site. This allows you to present works by other people, think of an online gallery, for instance. You know best, where Tessa Authorship could help you with your content, once you grasped the idea behind. Tessa Authorship offers a lot for your imagination. You may use it in many other context not related to mere authorship reflection.

"Tessa Authorship" is a spin-off from the development and design work related with the Tessa theme but does not depend on it. Tessa is a gallery, exposition and portfolio theme which you can see in action on [TakeBarcelona](http://takebarcelona.com). This will be the home for the plugin as well.

"Tessa Authorship" relates authorship information back and forth, allowing to show authorship information on entries of all kind of post types, whereas the related author's entry allows you to show posts that relate with the author.

Themes may incorporate template files for the authorship post-type, e.g. archive-authorship.php, single-authorship.php and content-authorship.php if desired. The look and feel of the output in widgets and content is all up to you via css or by manipulating the results via the implemented filters.

"Tessa Authorship" offers a second thumbnail on the authorship custom post-type to add the author's image as well as a second featured image for all activated post-types. You have to enable this functionality explicitly.

Interaction with "Tessa Authorship" to establish relations can be enabled on a per post-type basis in the settings section.

A whole lot of code used in "Tessa Authorship" is based on a combination, revision, rewrite and adaptation of two great Wordpress plugins: "WooThemes Testimonials" and "BAW Manual Related Posts". Many thanks. The original authors may find improvements as well as deteriorations of their code like walkers, multiple choices in the widget for categories and tags, etc.

Nevertheless: "Tessa Authorship" is unique. For this reason you may use both, "WooThemes Testimonials" as well as "BAW Manual Related Posts" together with "Tessa Authorship" to enhance your WordPress installation.

"Tessa Authorship" comes bundled with the "Multiple Posts Thumbnails" class and uses it without activating it as a plugin. If it detects the plugin, it will fallback gracefully and use the plugin instead of the bundled class.

"Tessa Authorship" reflects on fusion, collaboration, honesty, interaction, teamwork and a lot of more things not to mention here. "Tessa Authorship" is a product arriving to you from Barcelona, Catalonia aka Spain. Give it a try and spread the word.

"Tessa Authorship" includes translations for German and Spanish.

And last but not least: You can help to further develop and maintain Tessa Authorship with your donation.

== Usage ==

= The easy way =

1. Select the post types you want to support for relationsships with authorship entries, e.g. Posts, Pages.
2. Activate Authorship Thumbnail support if necessary.
3. Activate "the_content"-filter.
4. Activate "Show Email Form".
5. Optionally activate also "Use packaged social icons" or "Use packaged css for genericons font". You cannot activate both and "Use packaged social icons" overwrites the "Use packadged css for genericons font" option. 
6. To display the social information out of the box on authorship post-type pages on top of the content, activate also "Add social author info".

= The hard way =
Bellow you find more advanced integration posibilities. For security and to avoid spam, if you enable "Show Email Form", you should try to configure the reCaptcha service as explained in "Private Email Messages".

= Private Email Messages =

From version 0.6.0 onwards you can enable private messaging for authors. This function is available after you decide not to publish emails in the settings section.
The private messaging system includes support for the recaptcha service to make private message handling more secure and to avoid spam. Instructions to enable your wordpress instance for recaptcha:

1. Apply for a reCaptcha account on: [Google Recaptcha](http://www.google.com/recaptcha) 
2. Follow instructions and configure the recaptcha service to support your website
3. Place the private and public keys in wp-config.php file

Example:
`/*reCaptcha Service */
define( 'RECAPTCHA_PUBLIC_KEY', 'your-public-key' );
define( 'RECAPTCHA_PRIVATE_KEY', 'your-private-key' );`

4. You're done. "Tessa Authorship" will use reCaptcha service for additional security to handle private email messages.

= Authorship Post Type Entries =

For authorship post type pages: Posts of type authorship are now overloaded with the custom field data and the internal image if set via a second thumbnail. You will also find attached to the post object a prepared social links object
You can fetch this data directly within your templates by $post->image or $post->social_links, always for the authorship post-type. If you like to use this in standard templates you can check with isset($post->image) if the value exists, which allows you to use your standard template files to reflect entries of type authorship.
For the predefined fields you can use one of the following: $post->headline, $post->location, $post->birthyear, $post->email_link, $post->twitter_link, $post->facebook_link, $post->linkedin_link, $post->vimeo_link, $post->googleplus_link, $post->youtube_link, $post->soundcloud_link, $post->icomposistions_link, $post->pinterest_link, $post->digg_link, $post->blog_link, $post->web_url.

If you have modified the fields using the filter tessa_authorship_custom_field_settings you have to use your keys to access the data in the post object. For instance, if you defined a field for stumbleUpon you will use a key like "stumbleupon_link" and hence you can retrieve the field data with $post->stumbleupon_link.

Sample Code to display post meta of an authorship entry in content.php or content-authorship.php in your template:

`<?php
	if ( isset($post->location) && $post->location != '' && isset($post->birthyear) && $post->birthyear != '' ) :
		echo '<div class="locationandyear">' . $post->location . ', ', $post->birthyear . '</div>';
	elseif ( isset($post->location) && $post->location != '' ) :
		echo '<div class="locationandyear">' . $post->location . '</div>';
	elseif ( isset($post->birthyear) && $post->birthyear != '' ) :
		echo '<div class="locationandyear">' . $post->birthyear . '</div>';					
	endif;
	if ( isset($post->headline) && $post->headline != '' ) :
		echo '<div class="headline">' . $post->headline . '</div>';
	endif;
	if ( isset($post->social_links) && is_array($post->social_links) && count($post->social_links) > 0 ) :
		echo '<div class="social"><ul class="social-links clear">' . implode('', $post->social_links) . '</ul></div>';
	endif;
?>`
(Note: $post->social-links is an array of links wrapped into li markup tags!)

= Related Authors References / Related Content References =
Lists for related authors on content pages and related posts on author pages can be shown automatically by activating the content filter in the settings page. If you don't want to add this automaticaly you can add related posts or authors manually using the following code:

`<?php do_action( 'tessa_authorship' ); ?>`


To add arguments to this, please use any of the following arguments, using the syntax provided below:

* 'limit' => 5 (the maximum number of items to display)
* 'per_row' => 3 (when creating rows, how many items display in a single row?)
* 'orderby' => 'menu_order' (how to order the items - accepts all default WordPress ordering options)
* 'order' => 'DESC' (the order direction)
* 'id' => 0 (display a specific item)
* 'authorship' => true (whether to display authorship of a post-type or to display works (post-types) of the author on the author's page)
* 'post-type' => '' (only if authorship is set to false, post-type will accept post-types as array or, with shortcode, a comma-separated list of post-types)
* 'strict' => true (whether to restrict display of authorships and related works bound to settings for post_id or to display lists of works or authors)
* 'display_auto' => false (context dependent auto-configuration with fallback. Not completely implemented yet! Needs additional testing. Configuration is ignored.)
* 'display_author' => true (whether or not to display the author information)
* 'display_avatar' => true (whether or not to display the author avatar)
* 'display_url' => true (whether or not to link to the corresponding author or work using the permalink)
* 'display_social => true (whether or not to display social links for authors)
* 'display_content => false (whether or not to display content)
* 'display_excerpt => false (whether or not to display excerpt or till more tag in content)
* 'echo' => true (whether to display or return the data - useful with the template tag)
* 'size' => 'authorship-tas-thumbnail' (defaults to this, but you can specify any image size identifier or put an integer value for the width of the image, the height will be set proportionally. Dimension for the default image size added by Tessa Authorship can be configured on the settings page for both width and height.)
* 'title' => '' (an optional title)
* 'before' => '&lt;div class="widget widget_tessa_authorship"&gt;' (the starting HTML, wrapping the testimonials)
* 'after' => '&lt;/div&gt;' (the ending HTML, wrapping the testimonials)
* 'before_title' => '&lt;h2&gt;' (the starting HTML, wrapping the title)
* 'after_title' => '&lt;/h2&gt;' (the ending HTML, wrapping the title)
* 'category' => 0 (the ID/slug of the category to filter by)
* 'tags' => 0 (the ID/slug of the tag to filter by. If categories and tags are specified the results will be based on logical OR between tags and categories)

The various options for the "orderby" parameter are:

* 'none'
* 'ID'
* 'title'
* 'date'
* 'menu_order'

`<?php do_action( 'tessa_authorship', array( 'limit' => 10, 'display_author' => false ) ); ?>`

The same arguments apply to the shortcode which is `[tessa_authorship]` and the template tag, which is `<?php tessa_authorship(); ?>`.

== Usage Examples ==

Adjusting the limit and image dimension, using the arguments in the three possible methods:

do_action() call:

`<?php do_action( 'tessa_authorship', array( 'limit' => 10, 'size' => 100 ) ); ?>`

tessa_authorship() template tag:

`<?php tessa_authorship( array( 'limit' => 10, 'size' => 100 ) ); ?>`

[tessa_authorship] shortcode:

`[tessa_authorship limit="10" size="100"]`

Addionally Tessa Authorship exposes several filters. Here's a short description of the most important:
 
 Filters for the manipulation of results
 * Filter: tessa_authorship_item_template_author 
 * @description: Allows to modify the template chunk used to display related authors for posts (content).
 * @description: Placeholders and html code can be modified before the template chunk gets filled.
 * @param: string $tpl
 * @param: array $args (settings)
 * @return: string $tpl (modified template)
 * @example: add_filter( 'tessa_authorship_item_template_author', 'your_tessa_authoship_item_template_author', 10, 2)

 * Filter: tessa_authorship_item_template_entry 
 * @description: Allows to modify the template chunk used to display posts (content) related to the author.
 * @description: Placeholders and html code can be modified before the template chunk gets filled.
 * @param: string $tpl
 * @param: array $args (settings)
 * @return: string $tpl (modified template)
 * @example: add_filter( 'tessa_authorship_item_template_entry', 'your_tessa_authoship_item_template_entry', 10, 2)
 
 * Filter: tessa_authorship_social_links 
 * @description: Allows to modify the social links related to the author.
 * @description: Offers complete control for html and links.
 * @param: string $social (the html generated)
 * @param: array $social_links (a complete list of links generated
 * @param: object $post (the current post in the loop)
 * @param: array $args (settings)
 * @return: string $social
 * @example: add_filter( 'tessa_authorship_social_links', 'your_tessa_authorship_social_links', 10, 4)
 
 * Filter: tessa_authorship_post_output 
 * @description: Allows to modify the result for each post in the current authorship loop.
 * @description: Offers full control for html and all content blocks.
 * @param: string $template (the html generated)
 * @param: string $author (the author content block; will contain title on post-types other than authorship)
 * @param: string $headline (the headline content block, only availale on authorship post types)
 * @param: string $social (the html generated)
 * @param: array $social_links (a complete list of links generated
 * @param: string $content (the content block; maybe empty based on settings)
 * @param: object $post (the current post in the loop)
 * @param: array $args (settings)
 * @return: string $template
 * @example: add_filter( 'tessa_authorship_post_output', 'your_tessa_authorship_post_output', 10, 7)

 Filter to modify, delete or add social profile fields for authorship entries (e.g. a second external webpage field, etc.) 
 * Filter: tessa_authorship_custom_field_settings
 * @description: Allows to modify the custom social fields configured for the authorship post-type or to add own fields
 * @param: array $fields (contains the custom fields for initialization and retrieving additional authorship post meta data)
 * @return: array $fields
 * @example: add_filter( 'tessa_authorship_custom_field_settings', 'tessa_authorship_custom_field_settings', 10, 1)
 
 
== Installation ==

= Minimum Requirements =

* WordPress 3.4.2 or greater (may work on versions below but not tested)
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater

= Manual installation on server =

1. Download
2. Upload to your '/wp-contents/plugins/' directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

= Installation on hosted site =
1. Download the plugin file to your computer, unzip preserving directory names and structure
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

= Usage: The easy way =

1. Select the post types you want to support for relationsships with authorship entries, e.g. Posts, Pages.
2. Activate Authorship Thumbnail support if necessary.
3. Activate "the_content"-filter.
4. Activate "Show Email Form".
5. Optionally activate also "Use packaged social icons" or "Use packaged css for genericons font". You cannot activate both and "Use packaged social icons" overwrites the "Use packadged css for genericons font" option. 
6. To display the social information out of the box on authorship post-type pages on top of the content, activate also "Add social author info".

(Find more instructions on usage in "Other Notes")

== Upgrade Notice ==

Automatic updates should work without problems, but, like always, backup your site and you'll feel better.

== Frequently Asked Questions ==

= Where can I find additional documentation and user guides =

Please visit the plugin homepage: [Tessa Authorship](http://takebarcelona.com/tessa-authorship/). Based on feedback and acceptance we will provide more information if we encounter demand for it.

= Will Tessa Authorship work with my theme =

For sure. Install and enjoy.

= The thumbnails (avatars) are all distorted. What can I do to fix this? =

To ensure that your thumbnail have a square format for example, use the build-in mechanism. Configure your preferred image size, save and use a plugin to regenerate all your thumbnails. This will assure that the WordPress instance disposes of thumbnail images with that size. If you specify your own size with an integer value via the size argument, please assure that you have defined in your media settings a corresponding image size. To achieve predictable results it's best to use the built-in mechanism, which allows you to use squarre formats as well as landscape and portrait formats. To achieve this nice effect of full circle avatar images (with a square format) just place this instruction into your css file: 

.avatar-link .avatar {
    border-radius: 50%;	
}

= I changed the slugs in the settings. Now I get the error page not found. What can I do to fix this? =

Go to the general settings page for permalinks. Set permalinks to default and save. Afterwards put your permlinks to your desired configuration and save again. This will reset all permalinks and make your new slug for existing entries available.

== Dependencies ==

"Tessa Authorship" does not depend on third party plugins. Nevertheless you may improve experience by using the bundled version of "Multiple Post Thumbnails". This is especially useful to establish a second post thumbnail for post-types you enabled for "Tessa Authorship" as the default featured image of a post or page may not be the best used as an avatar representation for the related post item. It may even have a purpose, different use assigned, e.g. used as the background for that content in your theme, etc.

== Screenshots ==

1. Example Authorship Entry.
2. Private Message Popup on example Authorship Entry
3. Widget Output filtering on categories and tags
4. Configuration of thumbnails and relations for an authorship entry and a post entry
5. Part of the social data configuration for an authorship entry
6. Part of the Settings page for the plugin

 == Changelog ==

 = 0.7.7 =

* Fix and improve content or author selection in WPML context
* Fix for WP 4.0 deprecated like_escape-function used in the plugin
* Get rid off pixelated menu icon, use WP 3.8 dashicon font icon instead

 = 0.7.6 =

* Fix problem if recaptchalib is already loaded by another plugin
* Fix header problem when initializing plugin

 = 0.7.5 =

* Fix problem that prevented to show search and selection window in WP 3.9 for related content

 = 0.7.4 =

* Correct compatibility problem with MultiPostThumbnails installed as plugin
* More compatible behaviour and styling support for author email form
 
 = 0.7.3 =

* Correct a compatibility problem with Manual Related Posts plugin which used the same _nonce

= 0.7.2 =

* Correct a problem with version registration on activation

= 0.7.1 =

* Partly fixed in colloboration with WPML slug translation. WPML only respects the general custom post-type slug translation for all post types. Individual setting is still not respected, for this reason we always turn on slug translation and invite the administrator to translate the slug or to unify it putting the same configuration for all languages.
* Offer a genericons font style sheet to be activated when a theme includes genericons font like the default WP theme TwentyThirteen does, to improve out of the box display

= 0.7.0 =

* Two new configuration options to make out of the box usage easier
* Make social information at the beginning of the content in authorship post-type pages available out of the box via configuration
* Include image set for social icons for WordPress administrations that have difficulties to configure social icons via css

= 0.6.1 =

* Removes unnecessary interference with query filters by eliminating remove_all_filters instructions on query filters for posts_orderby and post_limits
* Allow styling of Private Message Form by removing style attribute and adding classes

= 0.6.0 =

* New Feature: Private Email Messages whenever email is specified on an authorship entry
* ReCaptcha Service support to make Private Email Message Service secure and to avoid spam
* Better screenshots
* Updated translations for Spanish and German
* Fast and easy usage description

= 0.5.0 =

* More consistent check for possible urls in custom fields for authorship posts to offer social links only for fields containing urls.
* Overload authorship post-type objects returned from wp_query with data and internal image, if set, to make theme integration easier and to offer independent "themeability"
* Include a preconfigured social links object in the overload
* Fix error on image size not added if theme has thumbnail support already enabled

= 0.4.4 =

* Fix error when only one content or author is assigned in WPML context.

= 0.4.3 =

* Fix for error when trying to find related posts or authors in a given language in WPML context.

= 0.4.2 =

* Deactivate slug translation via WPML while slug translation settings and handling is inconsistent in WPML and WooCommerceMultilingual which may produce 404 errors on permalinks difficult to trace and to handle for the admins of WordPress instances
* Minor fixes for debug notice messages

= 0.4.1 =

* Fix post type query arg for related content for authorship entries

= 0.4.0 =

Features:

* Implement Meta Box on Authorship entries to allow to relate content as for post-types authors, as a side effect related content is now visible on the author's entry
* WPML Support, if available
* Customize our custom post-type via settings to make it more versatile and usable in more contexts than authorships

Other:

* Fix error on adding placeholder image sizes for post-types as Multiple Post Thumbnails expects them to be existent in some circumstances
* Minor fixes

= 0.3.2 =

* Revision of thumbnail support to obtain better results
* Fix checkboxes (boolean) in settings, which saved but showed up with inproper state

= 0.3.1 =

* Added support for thumbnail image size via add_image_size
* Added configuration options for thumbnail image size 
* Changed handling of size arg to allow identifiers of existing image size configuration in themes

= 0.3.0 =

* Fix inconsistencies in handling and storage of related ids
* Fix for the hook into filter the_content which was not properly initiated

= 0.2.1 =

* Fix author's profile email link

= 0.2.0 =

Features:

* Consistent support for Multiple Post Thumbnails with fallback to original plugin if installed
* Demand explicit activation of "Multiple Post Thumbnail" class bundled with "Tessa Authorship" and inform admin that he could and should install the original plugin
* More configuration options on settings page
* hook into the_content to allow display out of the box of relationships (has to be activated on settings page)

Errors:

* handle post-types correctly when specified via argument in widgets, shortcodes or actions
* fix save functions to avoid verify_nonce conflicts

= 0.1.0 =

* Initial version

== Links ==
* [TakeBarcelona](http://takebarcelona.com): the home of "Tessa Authorship" and more plugins and themes.
* [Tessa Authorship](http://takebarcelona.com/tessa-authorship/): Home of this plugin.
* [Easy Digital Downloads HTTPS](http://takebarcelona.com/easy-digital-downloads-https): a https switcher for Easy Digital Downloads to offer checkout and posts and pages of your choice via https
* [Stripe For Easy Digital Downloads](http://takebarcelona.com/downloads/stripe-for-easy-digital-downloads/): A simple, yet versatile Stripe payment gateway for Easy Digital Downloads.
* [WooCommerce Poor Guys Swiss Knife](http://takebarcelona.com/woocommerce-poor-guys-swiss-knife/): a great plugin to power-overload WooCommerce. Available on Wordpress with more than 50.000 downloads and counting
* [WooCommerce Rich Guys Swiss Knife](http://takebarcelona.com/woocommerce-rich-guys-swiss-knife/): more tools for the WooCommerce Poor Guys Swiss Knife
* [Tessa Theme](http://takebarcelona.com/tessa-theme/): Tessa maximizes content and scales from fullscreen to mobile devices. Tessa is ideal for photography, art and design presentation. "Tessa" has builtin WooCommerce Support and plays nicely with WPML as well.
* [Tessa Powerpack](http://www.takebarcelona.com/tessa_powerpack/): A Jetpack by Wordpress.com fork which does not rely on wordpress.com. Continues the discontinued SlimJetpack plugin.

== Updates ==
Updates to the plugin will be posted on the [Tessa Authorship homepage](http://takebarcelona.com/tessa-authorship/) where you will always find the newest version.

== Thanks ==
Many thanks to [Nico](http://nicestay.net/) for support and resistance.

== Collaboration ==
Whoever wants to work or share his translations, welcome. Thank you! Bugs reports, suggestions and feedback is highly appreciated. Translations for Spanish and German supplied, Catalan will follow soon.
