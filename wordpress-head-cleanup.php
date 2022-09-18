<?php
/**
 * Plugin Name: WordPress head cleanup
 * Description: Remove RSS feeds, emoji support, WP generator and other unnecessary things from the WordPress `head` tag.
 * Version: 1.0.1
 * Author: Jeffrey Barke
 * License: GPLv2 or later
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace wordpressCleanup;

// @NOTE--Looks like WordPress is no longer adding the `type` attribute to
// `script` and `link` tags, so we no longer need to strip them out.

/**
 * Remove WordPress generator from the head element, RSS and
 * scripts and styles. The idea here is security through obscurity.
 * Let’s not advertise we’re WordPress and what version we’re running.
 */
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

/**
 * Disable XML-RPC.
 * @see https://kinsta.com/blog/xmlrpc-php/
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Clean up WordPress head (YAGNI).
 */
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

/**
 * ----------------------------------------------------------------------------
 * Remove RSS feeds.
 *
 * Breaks my heart, but it appears RSS is dead.
 */
function disableFeeds ()
{
    wp_redirect(home_url());
    die;
}

add_action('do_feed', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_rdf', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_rss', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_rss2', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_atom', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_rss2_comments', __NAMESPACE__ . '\\disableFeeds', 1);
add_action('do_feed_atom_comments', __NAMESPACE__ . '\\disableFeeds', 1);

remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);

/**
 * ----------------------------------------------------------------------------
 * Disable WordPress emoji support.
 */
add_action('init', function ()
{
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('admin_print_scripts', 'print_emoji_detection_script');
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('admin_print_styles', 'print_emoji_styles');
  remove_filter('the_content_feed', 'wp_staticize_emoji');
  remove_filter('comment_text_rss', 'wp_staticize_emoji');
  remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

  add_filter('tiny_mce_plugins', __NAMESPACE__ . '\\disableEmojisTinymce');
  add_filter('wp_resource_hints',
      __NAMESPACE__ . '\\disableEmojisRemoveDnsPrefetch', 10, 2);
});

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @param array $plugins
 * @return array Difference betwen the two arrays
 */
function disableEmojisTinymce ($plugins)
{
  if (is_array($plugins)) {
    return array_diff($plugins, [
      'wpemoji',
    ]);
  } else {
    return [];
  }
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @param array $urls URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Difference betwen the two arrays.
 */
function disableEmojisRemoveDnsPrefetch ($urls, $relation_type)
{
  if ('dns-prefetch' == $relation_type) {
    // This filter is documented in wp-includes/formatting.php.
    $emoji_svg_url = apply_filters('emoji_svg_url',
        'https://s.w.org/images/core/emoji/2/svg/');

    $urls = array_diff($urls, [
      $emoji_svg_url,
    ]);
  }

  return $urls;
}
