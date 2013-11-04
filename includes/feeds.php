<?php

class VirtualPostsFeeds {

	function __construct() {
		add_action( 'wp_ajax_virtualposts_feed', array( &$this, 'feed' ) );
		add_action( 'wp_ajax_nopriv_virtualposts_feed', array( &$this, 'feed' ) );

		add_action( 'wp_ajax_virtualposts_feeds', array( &$this, 'feeds' ) );
		add_action( 'wp_ajax_virtualposts_clear_cache', array( &$this, 'clear_cache' ) );
		add_action( 'wp_ajax_virtualposts_cache', array( &$this, 'cache' ) );

		add_filter( 'cron_schedules', array( &$this, 'change_cron_interval' ) );
		add_action( 'virtualposts_cron_feeds', array( &$this, 'cron' ) );

	}

	const posts_cache_key = 'virtualposts';

	function feed( $feed_id = null, $output = true ) {

		include_once ABSPATH . WPINC . '/class-simplepie.php';
		include_once ABSPATH . WPINC . '/feed.php';

		$result = array();

		$feeds = VirtualPostsSettings::get( 'feeds' );

		$id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : $feed_id;

		foreach ( $feeds as $key => $feed ) {

			if ( $id == $feed['id'] ) {

				$general_settings    = VirtualPostsSettings::get( 'general' );
				$rss                 = new SimplePie();
				$upload              = wp_upload_dir();
				$rss->cache_location = $upload['basedir'];
				$rss->cache          = false;
				$found               = 0;
				$feed['fetched']     = current_time( 'mysql' );

				$rss->set_output_encoding();
				$rss->timeout = 10;
				$rss->set_timeout( 10 );
				$rss->set_feed_url( $feed['url'] );
				$rss->init();
				$rss->handle_content_type();


				if ( ! $rss->error() ) {

					$maxitems  = $rss->get_item_quantity( $feed['max'] );
					$rss_items = $rss->get_items( 0, $maxitems );
					foreach ( $rss_items as $item ) {

						$headline = esc_html( $item->get_title() );
						$title    = sanitize_title( $item->get_title() );
						$link     = $feed['id'] . '/' . $title;

						$rss_item             = array();
						$rss_item['title']    = $headline;
						$rss_item['content']  = $item->get_content();
						$rss_item['date']     = $item->get_date( 'Y-m-d H:i:s' );
						$rss_item['date_gmt'] = $item->get_date( 'gmt' );
						$rss_item['feed']     = $feed['name'];
						$rss_item['link']     = $link;
						$rss_item['guid']     = $rss->get_permalink();


						if ( $author = $item->get_author() ) {
							$rss_item['author'] = $author->get_name();
						}
						else {
							$rss_item['author'] = '';
						}

						$rss_item['excerpt'] = $item->get_description();

						$result[] = $rss_item;

						$cache = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );
						if ( ! is_array( $cache ) ) $cache = array();
						$cache[$link] = $rss_item;
						phpFastCache::set( VirtualPostsFeeds::posts_cache_key, $cache, 3600 );
						$found ++;
					}
					$feed['found'] = $found;
				}
				else {
					$feed['error'] = $rss->error();
					$feed['found'] = (int) $found;
				}
				$feeds[$key] = $feed;
				VirtualPostsSettings::update( 'feeds', $feeds );
			}
		}

		if ( $output ) echo json_encode( $result );

		exit;

	}

	function feeds() {
		$result = VirtualPostsSettings::get( 'feeds' );
		echo json_encode( $result );
		exit;
	}

	function change_cron_interval( $schedules ) {
		$general = VirtualPostsSettings::get( 'general' );

		if ( $general && is_array( $general ) ) {
			$schedules['virtualposts'] = array(
				'interval' => $general['interval'] * 60,
				'display'  => 'Virtual Post Fetch every ' . $general['interval'] . ' minutes',
			);
		}

		return $schedules;
	}

	function cron() {
		$feeds  = VirtualPostsSettings::get( 'feeds' );
		$vfeeds = new VirtualPostsFeeds();
		foreach ( $feeds as $feed ) {
			$vfeeds->feed( $feed['id'], false );
		}
	}

	function clear_cache() {
		phpFastCache::delete( VirtualPostsFeeds::posts_cache_key );
		echo 'Cache is cleared...';
		exit;
	}

	function cache() {
		$result = array();
		$posts  = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );

		if ( is_array( $posts ) && sizeof( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
				$result[] = $post;
			}
		}

		echo json_encode( $result );
		exit;
	}

}

$_virtualposts_feeds = new VirtualPostsFeeds();

