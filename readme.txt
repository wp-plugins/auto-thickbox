=== Auto Thickbox ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: http://www.semiologic.com/partners/
Tags: lightbox, thickbox, shadowbox, gallery, semiologic, images
Requires at least: 3.1
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).


== Description ==

The Auto Thickbox plugin for WordPress automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up), through the use of WordPress' built-in thickbox library.

In the event you'd like to override this for an individual image, you can disable the behavior by adding the 'nothickbox' class to its anchor tag.


= Thickbox Galleries =

By default, the auto thickbox plugin will bind all images within a post into a single thickbox gallery. That is, next image and previous image links will appear so you can navigate from an image to the next.

The behavior is particularly interesting when you create galleries using WordPress' image uploader. Have the images link to the image file rather than the attachment's post, and you're done.

On occasion, you'll want to split a subset of images into a separate gallery. Auto Thickbox lets you do this as well: add an identical rel attribute to each anchor you'd like to group, and you're done.

(Note: To set the rel attribute using WordPress' image uploader, start by inserting the image into your post. Then, edit that image, browse its advanced settings, and set "Link Rel" in the Advanced Link Attributes.)

= Thickbox Anything =

Note that thickbox works on any link, not merely image links. To enable thickbox on an arbitrary link, set that link's class to thickbox.

= No thickbox =

In the event you want to disable thickbox on some links to images, assign it a nothickbox class.

= Keyboard support =

Thickbox supports the following keys:
- Next Image: Greater Than (>) or Left Arrow
- Previous Image: Less Than (<) or Right Arrow
- First Image: Home
- Last Image: End
- Close Popup: Esc or Enter/Return


= Hat Translators =

- German: hakre
- Portuguese/Brazil: Henrique Schiavo

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.semiologic.com).


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 3.4.2 =

- Tweak to handle image paired with link classes better.

= 3.4.1 =

- Additional tweak to benefit performance

= 3.4 =

- Fix some performance issues in the link parsing.   New algorithm used.
- Incorporate WP core fix #29346 - Disable background page scrolling when modals are open.

= 3.3 =

- Set default thickbox javascript parameters as fallback to avoid error
- WP 4.0 compat

= 3.2.2 =

- Remove html comments used for some troubleshooting
- changed hook priorities

= 3.2.1 =

- Additional tweak to global callback processing

= 3.2 =

- Fix Thickbox pop failing to show with some caching plugins that concat javascript files.
- Use own custom version of the anchor_utils class

= 3.1.1 =

- Handle nested parenthesis in javascript event attributes on links and images

= 3.1 =

- Fix handling of the #TB_Inline attribute used for popping up forms in iframes.  (props to the Auto Thickbox Plus plugin)
- Use more full proof WP version check to alter plugin behavior instead of relying on $wp_version constant.

= 3.0.2 =

- Fix broken translations

= 3.0 =

- Added new keyboard support: Left arrow, right arrow, home, end
- Next and Prev don't always advance correctly with Thickbox.js 3.1.   Plugin now has own custom thickbox.js.
- WP 3.9 eliminated some of the default thickbox styling.   Plugin now has own custom thickbox styling
- Changed ordering of Prev, Next and Image of
- Photo title is now centered.
- Now support .bmp image extensions
- Custom thickbox.js uses a minified version for better performance.
- WP 3.9 compat
- Code refactoring

= 2.4.1 =

- WP 3.8 compat

= 2.4 =

- Further updates to the link attribute parsing code
- Fixed bug where img was not processed if it was preceded by an empty text anchor link.

= 2.3 =

- WP 3.7 compat
- New link attribute parsing code to handle various image link configurations.

= 2.2 =

- WP 3.6 compat
- PHP 5.4 compat
- Fixed issue with parsing of links with non-standard (class, href, rel, target) attributes included in the <img> tag.  This caused Twitter Widgets to break.
- Fixed issue with images containing onLoad (or other javascript event) attributes with embedded javascript code possibly corrupting js code

= 2.1.1 =

- Removed Auto Thickbox Plus fixes applied in previous version.  Seemed to cause issue on some site upgrades

= 2.1 =

- WP 3.5 compat
- Backport bug fixes from Auto Thickbox Plus

= 2.0.3 =

- Fix conflict with wp cron.
- Use esc_url() / Require WP 2.8.

= 2.0.2 =

- Actually load the text domain for i18n support...

= 2.0.1 =

- Restore the nothickbox functionality
- German and Brazilian Translation (requires WP 2.9 for the js part)
- Force a higher pcre.backtrack_limit and pcre.recursion_limit to avoid blank screens on large posts

= 2.0 =

- Full iFrame support
- Code enhancements and optimizations
- Localization support
