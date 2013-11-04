<?php

class VirtualPostsVirtual {

	private $rss_item = NULL;

	function __construct( $args ) {
		$this->rss_item = $args;
		add_filter( 'the_posts', array( &$this, 'the_posts' ) );
	}

	static function init() {

		$url = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
		if ( strpos( $url, 'virtualposts/' ) !== false ) {
			$cache     = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );
			$cache_key = str_replace( 'virtualposts/', '', $url );
			$rss_post  = $cache[$cache_key];
			$feed_id   = substr( $cache_key, 0, strpos( $cache_key, '/' ) );
			if ( ! $rss_post['title'] ) {
				$url = admin_url( 'admin-ajax.php' ) . '?action=virtualposts_feed&nonce=' . wp_create_nonce( 'virtualposts_feed' ) . '&id=' . $feed_id;
				wp_remote_get( $url );
				$cache    = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );
				$rss_post = $cache[$cache_key];
				if ( ! $rss_post['title'] ) {
					return;
				}
			}
			$pg = new VirtualPostsVirtual( $rss_post );
		}
	}

	function the_posts( $posts ) {

		global $wp_query;

		$rss_item = $this->rss_item;

		$post                        = new stdClass;
		$post->ID                    = - 1;
		$post->post_author           = $rss_item['author'];
		$post->post_date             = $rss_item['date'];
		$post->post_date_gmt         = $rss_item['date_gmt'];
		$post->post_content          = $rss_item['content'];
		$post->post_title            = $rss_item['title'];
		$post->post_excerpt          = $rss_item['excerpt'];
		$post->post_status           = 'publish';
		$post->comment_status        = 'closed';
		$post->ping_status           = 'closed';
		$post->post_password         = '';
		$post->post_name             = $rss_item['link'];
		$post->to_ping               = '';
		$post->pinged                = '';
		$post->modified              = $rss_item['date'];
		$post->modified_gmt          = $rss_item['date_gmt'];
		$post->post_content_filtered = '';
		$post->post_parent           = 0;
		$post->guid                  = $rss_item['guid'];
		$post->menu_order            = 0;
		$post->post_type             = 'post';
		$post->post_mime_type        = '';
		$post->comment_count         = 0;

		$posts = array( $post );

		$wp_query->is_page     = TRUE;
		$wp_query->is_singular = TRUE;
		$wp_query->is_home     = FALSE;
		$wp_query->is_archive  = FALSE;
		$wp_query->is_category = FALSE;
		unset( $wp_query->query['error'] );
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404              = FALSE;

		return ( $posts );

	}

	static function add_rewrite_rules( $wp_rewrite )
	{
		$new_rules = array(
			'(virtualposts)/(.*?)/?(.*?)/?$' => 'index.php?pagename=virtualposts&feed_id=' . $wp_rewrite->preg_index( 2 ) . '&url=' . $wp_rewrite->preg_index( 3 ),
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

}

add_action( 'init', 'VirtualPostsVirtual::init' );
add_action( 'generate_rewrite_rules', 'VirtualPostsVirtual::add_rewrite_rules' );