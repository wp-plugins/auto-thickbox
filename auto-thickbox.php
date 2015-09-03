<?php
/*
Plugin Name: Auto Thickbox
Plugin URI: http://www.semiologic.com/software/auto-thickbox/
Description: Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).
Author: Denis de Bernardy, Mike Koepke
Version: 3.5
Author URI: https://www.semiologic.com
Text Domain: auto-thickbox
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.

**/



/**
 * auto_thickbox
 *
 * @package Auto Thickbox
 **/

class auto_thickbox {

	protected $anchor_utils;

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			dirname(plugin_basename(__FILE__)) . '/lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */
	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'auto-thickbox' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );
    } #auto_thickbox

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( !is_admin() && isset($_SERVER['HTTP_USER_AGENT']) &&
      	strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') === false) {

			add_action('wp_enqueue_scripts', array($this, 'scripts'));
			add_action('wp_enqueue_scripts', array($this, 'styles'));

			add_filter('wp_head', array($this, 'localize_scripts'));

			add_filter('the_content', array($this, 'process_content'), 1000000);
			add_filter('the_excerpt', array($this, 'process_content'), 1000000);
			add_filter('comment_text', array($this, 'process_content'), 1000000);
			$inc_text_widgets = true;
			if ( $inc_text_widgets )
				add_filter('widget_text', array($this, 'process_content'), 1000000);
		}
	}

	/**
	 * process_content()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function process_content($text) {

		// short circuit if there's no anchors at all in the text
		if ( false === stripos($text, '<a ') )
			return($text);

		global $escape_anchor_filter;
		$escape_anchor_filter = array();

		$text = $this->escape($text);

		// find all occurrences of anchors and fill matches with links
		preg_match_all("/
					<\s*a\s+
					([^<>]+)
					>
					(.*?)
					<\s*\/\s*a\s*>
					/isx", $text, $matches, PREG_SET_ORDER);

		$raw_links = array();
		$processed_links = array();

		foreach ($matches as $match)
		{
			$updated_link = $this->process_link($match);
			if ( $updated_link ) {
				$raw_links[]     = $match[0];
				$processed_links[] = $updated_link;
			}
		}

		if ( !empty($raw_links) && !empty($processed_links) )
			$text = str_replace($raw_links, $processed_links, $text);

		$text = $this->unescape($text);

		return $text;
	} # process_content()


	/**
	 * escape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function escape($text) {
		global $escape_anchor_filter;

		if ( !isset($escape_anchor_filter) )
			$escape_anchor_filter = array();

		foreach ( array(
			'head' => "/
				.*?
				<\s*\/\s*head\s*>
				/isx",
			'blocks' => "/
				<\s*(script|style|object|textarea)(?:\s.*?)?>
				.*?
				<\s*\/\s*\\1\s*>
				/isx",
			) as $regex ) {
			$text = preg_replace_callback($regex, array($this, 'escape_callback'), $text);
		}

		return $text;
	} # escape()


	/**
	 * escape_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function escape_callback($match) {
		global $escape_anchor_filter;

		$tag_id = "----escape_auto_thickbox:" . md5($match[0]) . "----";
		$escape_anchor_filter[$tag_id] = $match[0];

		return $tag_id;
	} # escape_callback()


	/**
	 * unescape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function unescape($text) {
		global $escape_anchor_filter;

		if ( !$escape_anchor_filter )
			return $text;

		$unescape = array_reverse($escape_anchor_filter);

		return str_replace(array_keys($unescape), array_values($unescape), $text);
	} # unescape()


	/**
	 * filter_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function process_link($match) {
		# parse anchor
		$anchor = $this->parse_anchor($match);

		if ( !$anchor )
			return false;

		# filter anchor
		$anchor = $this->filter_anchor( $anchor );

		if ( $anchor )
			$anchor = $this->build_anchor($match[0], $anchor);

		return $anchor;
	} # process_link()


	/**
	 * parse_anchor()
	 *
	 * @param array $match
	 * @return array $anchor
	 **/

	function parse_anchor($match) {
		$anchor = array();
		$anchor['attr'] = $this->parseAttributes( $match[1] );

		if ( !is_array($anchor['attr']) || empty($anchor['attr']['href']) # parser error or no link
			|| trim($anchor['attr']['href']) != esc_url($anchor['attr']['href'], null, 'db') ) # likely a script
			return false;

		foreach ( array('class', 'rel') as $attr ) {
			if ( !isset($anchor['attr'][$attr]) ) {
				$anchor['attr'][$attr] = array();
			} else {
				$anchor['attr'][$attr] = explode(' ', $anchor['attr'][$attr]);
				$anchor['attr'][$attr] = array_map('trim', $anchor['attr'][$attr]);
			}
		}

		$anchor['body'] = $match[2];

		$anchor['attr']['href'] = @html_entity_decode($anchor['attr']['href'], ENT_COMPAT, get_option('blog_charset'));

		return $anchor;
	} # parse_anchor()

	/**
	 * build_anchor()
	 *
	 * @param $link
	 * @param array $anchor
	 * @return string $anchor
	 */

	function build_anchor($link, $anchor) {
		$attrs = array( 'class', 'rel', 'target');

		foreach ( $attrs as $attr ) {
			if ( isset($anchor['attr'][$attr]) ) {
				$new_attr_value = null;
				$values = $anchor['attr'][$attr];
				if ( is_array($values) ) {
					$values = array_unique($values);
					if ( $values )
						$new_attr_value = implode(' ',  $values );
				} else {
					$new_attr_value = $values;
				}

				if ( $new_attr_value )
					$link = $this->update_attribute($link, $attr, $new_attr_value);
			}
		}

		return $link;
	} # build_anchor()


	/**
	 * Updates attribute of an HTML tag.
	 *
	 * @param $html
	 * @param $attr_name
	 * @param $new_attr_value
	 * @return string
	 */
	function update_attribute($html, $attr_name, $new_attr_value) {

		$attr_value     = false;
		$quote          = false; // quotes to wrap attribute values

		preg_match('/(<a.*>)/iU', $html, $match);

		$link_str = $match[1];
		if ($link_str == "")
			return $html;

		$re = '/' . preg_quote($attr_name) . '=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/is';
		if (preg_match($re, $link_str, $matches)
		) {
			// two possible ways to get existing attributes
			$attr_value = $matches[2];

			$quote = false !== stripos($html, $attr_name . "='") ? "'" : '"';
		}

		if ($attr_value)
		{
			//replace current attribute
			$html = str_ireplace("$attr_name=" . $quote . "$attr_value" . $quote,
				$attr_name . '="' . esc_attr($new_attr_value) . '"', $html);
		}
		else {
			// attribute does not currently exist, add it
			$pos = strpos( $html, '>' );
			if ($pos !== false) {
				$html = substr_replace( $html, " $attr_name=\"" . esc_attr($new_attr_value) . '">', $pos, strlen('>') );
			}
		}

		return $html;
	} # update_attribute()


	/**
	 * filter_anchor()
	 *
	 * @param $anchor
	 * @return string
	 */

	function filter_anchor($anchor) {
		# disable in feeds
		if ( is_feed() )
			return null;

		// if we don't have a href or find a ? only obviously this some illformed or temp link
		if ( empty( $anchor['attr']['href'] ) || (substr($anchor['attr']['href'], 0, 1) == '?' ) )
			return null;

		$updated = false;
		if ( preg_match("/\.(?:jpe?g|gif|png|bmp)\b/i", $anchor['attr']['href']) ) {
			$anchor = $this->image($anchor);
			$updated = true;
		}
		elseif ( !empty($anchor['attr']['class']) && in_array('thickbox', $anchor['attr']['class']) ) {
			$anchor = $this->iframe($anchor);
			$updated = true;
		}

		if ( $updated )
			return $anchor;
		else
			return null;
	} # filter_anchor()


	/**
	 * image()
	 *
	 * @param array $anchor
	 * @return array $anchor
	 **/

	function image($anchor) {
		if ( !preg_match("/^\s*<\s*img\s.+?>\s*$/is", $anchor['body']) )
			return $anchor;
		
		if ( !$anchor['attr']['class'] ) {
			$anchor['attr']['class'][] = 'thickbox';
			$anchor['attr']['class'][] = 'no_icon';
		} else {
			if ( !in_array('thickbox', $anchor['attr']['class']) && !in_array('nothickbox', $anchor['attr']['class']) && !in_array('no_thickbox', $anchor['attr']['class']) )
				$anchor['attr']['class'][] = 'thickbox';
			if ( !in_array('no_icon', $anchor['attr']['class']) && !in_array('noicon', $anchor['attr']['class']) )
				$anchor['attr']['class'][] = 'no_icon';
		}
		
		if ( in_the_loop() && !$anchor['attr']['rel'] )
			$anchor['attr']['rel'][] = 'gallery-' . get_the_ID();
		
		if ( empty($anchor['attr']['title']) ) {
			if ( preg_match("/\b(?:alt|title)\s*=\s*('|\")(.*?)\\1/i", $anchor['body'], $title) ) {
				$anchor['attr']['title'] = end($title);
			}
		}
		
		return $anchor;
	} # image()


    /**
     * iframe()
     *
     * @param $anchor
     * @return string
     */
	
	function iframe($anchor) {
		if ( strpos($anchor['attr']['href'], 'TB_iframe=true') !== false || strpos($anchor['attr']['href'], '#TB_inline') !== false )
			return $anchor;

		# strip anchor ref
		$href = explode('#', $anchor['attr']['href']);
		$anchor['attr']['href'] = array_shift($href);
		
		$anchor['attr']['href'] .= ( ( strpos($anchor['attr']['href'], '?') === false ) ? '?' : '&' )
			. 'TB_iframe=true&width=720&height=540';
		
		return $anchor;
	} # iframe()
	
	function parseAttributes($text) {
	    $attributes = array();
	    $pattern = '#(?(DEFINE)
	            (?<name>[a-zA-Z][a-zA-Z0-9-:]*)
	            (?<value_double>"[^"]+")
	            (?<value_single>\'[^\']+\')
	            (?<value_none>[^\s>]+)
	            (?<value>((?&value_double)|(?&value_single)|(?&value_none)))
	        )
	        (?<n>(?&name))(=(?<v>(?&value)))?#xs';

	    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
	        foreach ($matches as $match) {
	            $attributes[$match['n']] = isset($match['v'])
	                ? trim($match['v'], '\'"')
	                : null;
	        }
	    }

	    return $attributes;
	}

	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		// use our forked version of thickbox
		wp_deregister_script('thickbox');
		$thickbox_js = ( ! WP_DEBUG ? 'auto-thickbox.min.js' : 'auto-thickbox.js' );
		wp_register_script('thickbox', plugins_url( '/js/' . $thickbox_js, __FILE__), array('jquery'), '20150514', true);
		wp_enqueue_script('thickbox');
	} # scripts()


	/**
	 * localize_scripts()
	 *
	 * @return void
	 **/

	function localize_scripts() {
/*		wp_localize_script($script_handle, 'thickboxL10n', array(
			'next' => __('Next &gt;', 'auto-thickbox'),
			'prev' => __('&lt; Prev', 'auto-thickbox'),
			'image' => __('Image', 'auto-thickbox'),
			'of' => __('of', 'auto-thickbox'),
			'close' => __('Close', 'auto-thickbox'),
			'l10n_print_after' => 'try{convertEntities(thickboxL10n);}catch(e){};',
			'loadingAnimation' => plugins_url( 'images/loadingAnimation.gif',__FILE__ )
		));
*/

		$next =  __('Next &gt;', 'auto-thickbox');
		$prev =   __('&lt; Prev', 'auto-thickbox');
		$image =  __('Image', 'auto-thickbox');
		$of =  __('of', 'auto-thickbox');
		$close =  __('Close', 'auto-thickbox');
		$l10n_print_after =   'try{convertEntities(thickboxL10n);}catch(e){};';
		$loadingAnimation =   plugins_url( 'images/loadingAnimation.gif',__FILE__ );
		$loadingAnimation = str_replace( '/', '\/', $loadingAnimation );

echo <<<EOS

<script type='text/javascript'>
/* <![CDATA[ */
var thickboxL10n = {"next":"$next","prev":"$prev","image":"$image","of":"$of","close":"$close","loadingAnimation":"$loadingAnimation"};
$l10n_print_after;
/* ]]> */
</script>

EOS;

	} # localize_scripts()

	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		wp_enqueue_style('thickbox');

		// intro in 3.9
		if ( class_exists('WP_Customize_Widgets' ) )
			wp_enqueue_style('auto-thickbox', plugins_url( 'css/styles.css',__FILE__ ), 'thickbox', '20140420');
		else
			wp_enqueue_style('auto-thickbox', plugins_url( 'css/styles-pre39.css',__FILE__ ), 'thickbox', '20140420');


	} # styles()
} # auto_thickbox

$auto_thickbox = auto_thickbox::get_instance();