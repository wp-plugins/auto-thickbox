<?php
/*
Plugin Name: Auto Thickbox
Plugin URI: http://www.semiologic.com/software/publishing/auto-thickbox/
Description: Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).
Author: Denis de Bernardy
Version: 1.2
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


/**
 * auto_thickbox
 *
 * @package Auto Thickbox
 **/

if ( !is_admin() && strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') === false ) {
	add_action('wp_print_scripts', array('auto_thickbox', 'add_scripts'));
	add_action('wp_print_styles', array('auto_thickbox', 'add_css'));
	
	add_action('wp_head', array('auto_thickbox', 'add_thickbox_images'), 20);
	
	add_filter('the_content', array('auto_thickbox', 'add_thickbox'), 100);
	add_filter('the_excerpt', array('auto_thickbox', 'add_thickbox'), 100);
}

class auto_thickbox {
	/**
	 * add_thickbox()
	 *
	 * @param string $content
	 * @return string $content
	 **/

	function add_thickbox($content) {
		$content = preg_replace_callback("/
			<\s*a\s					# an achnor...
				(.*\s)?
				href\s*=\s*?(.+)	# (catch href)
				(\s.*)?
				>
			\s*
			(.*)
			\s*
			<\s*\/\s*a\s*>
			/isUx", array('auto_thickbox', 'add_thickbox_callback'), $content);
		
		return $content;
	} # add_thickbox()
	
	
	/**
	 * add_thickbox_callback()
	 *
	 * @param array $match Regexp match
	 * @return string $link
	 **/
	
	function add_thickbox_callback($match) {
		# trim surrounding quotes
		$href = trim(trim($match[2]), '\'"');
		
		# return if link isn't pointing to an image
		if ( !preg_match("/\.(jpe?g|gif|png)$/i", $href) )
			return $match[0];
		
		$img = $match[4];
		
		# return if link isn't wrapping an image (lets us work around backtrack limit)
		if ( !preg_match("|^<\s*img\s[^>]+>$|i", $img) )
			return $match[0];
		
		# link attribute
		$attr = ' ' . $match[1] . $match[3] . ' ';
		
		# add thickbox class
		if ( !preg_match("/(\sclass\s*=\s*(.+?))(?:$|\s[a-z_]+\s*=)/i", $attr, $class) ) {
			$attr .= ' class="thickbox noicon"';
		} else {
			# trim surrounding quotes
			$old_class = trim(trim($class[2]), '\'"');
			
			if ( strpos($old_class, 'thickbox') !== false ) {
				$new_class = $old_class . ' thickbox noicon';

				# replace class
				$attr = str_replace($class[0], 'class="' . $new_class . '"', $attr);
			}
		}
		
		# add gallery rel if no rel is present
		if ( in_the_loop()
			&& !preg_match("/\srel\s*=\s*.+?(?:$|\s[a-z_]+\s*=)/i", $attr, $rel)
			) {
			$attr .= ' rel="gallery-' . get_the_ID() . '"';
		}
		
		# add title
		if ( !preg_match("/title\s*=/i", $attr) ) {
			if ( preg_match("/(?:alt|title)\s*=\s*('|\")(.*?)\\1/i", $img, $title) ) {
				$title = end($title);
				$attr .= ' title="' . $title . '"';
			}
		}
		
		return '<a href="' . $href . '" ' . $attr . '>' . $img . '</a>';
	} # add_thickbox_callback()
	
	
	/**
	 * add_scripts()
	 *
	 * @return void
	 **/

	function add_scripts() {
		wp_enqueue_script('thickbox');
	} # add_scripts()
	
	
	/**
	 * add_css()
	 *
	 * @return void
	 **/

	function add_css() {
		wp_enqueue_style('thickbox');
	} # add_css()
	
	
	/**
	 * add_thickbox_images()
	 *
	 * @return void
	 **/

	function add_thickbox_images() {
		$site_url = site_url();
		
		$js = <<<EOF

<script type="text/javascript">
var tb_pathToImage = "$site_url/wp-includes/js/thickbox/loadingAnimation.gif";
var tb_closeImage = "$site_url/wp-includes/js/thickbox/tb-close.png";
</script>

EOF;
		
		echo $js;
	} # add_thickbox_images()
} # auto_thickbox
?>