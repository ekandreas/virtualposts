<?php

class VirtualPostsFeeds {

	function __construct() {
		add_action( 'wp_ajax_virtualposts_feed', array( &$this, 'feed' ) );
		add_action( 'wp_ajax_virtualposts_feeds', array( &$this, 'feeds' ) );
	}

	function feed(){

		include_once ABSPATH . WPINC . '/class-simplepie.php';
		include_once ABSPATH . WPINC . '/feed.php';

		$result = array();

		if( wp_verify_nonce( $_REQUEST['nonce'], 'virtualposts_feed' ) ){

			$feeds = VirtualPostsSettings::get( 'feeds' );

			$id = $_REQUEST['id'];

			foreach( $feeds as $key => $feed ){

				if( $id == $feed['id'] ){

					$general_settings = VirtualPostsSettings::get( 'general' );

					$url = $feed['url'];
					$max = (int)$feed['max'];
					$name = $feed['name'];
					$feed_id = $feed['id'];

					$feed = new SimplePie();

					$upload = wp_upload_dir();
					$feed->cache_location = $upload['basedir'];

					$feed->cache = false;
					$feed->set_output_encoding();
					$feed->timeout = $general_settings[ 'timeout' ];
					$feed->set_timeout( $general_settings[ 'timeout' ] );

					$feed->set_feed_url( $url );

					$feed->init();
					$feed->handle_content_type();

					$found = 0;

					$feeds[ $key ][ 'fetched' ] = current_time( 'mysql' );

					if ( !$feed->error() ) {

						$maxitems = $feed->get_item_quantity( $max );
						$rss_items = $feed->get_items( 0, $maxitems );
						foreach ( $rss_items as $item ) {

							//$item->get_permalink();
						 	//$item->get_date('j F Y | g:i a');

							$headline = esc_html( $item->get_title() );
							$title = sanitize_title( $item->get_title() );
							$link = $feed_id . '/' . $title;

							$rss_item = array();
							$rss_item[ 'headline' ] = $headline;
							$rss_item[ 'content' ] = $item->get_content();
							$rss_item[ 'date' ] = $item->get_date();
							$rss_item[ 'feed' ] = $name;
							$rss_item[ 'link' ] = $link;

							$result[] = $rss_item;

							$cache = phpFastCache::get( 'virtualposts' );
							if( !is_array( $cache ) ) $cache = array();
							$cache[ $link ] = $rss_item;
							phpFastCache::set( 'virtualposts', $cache, 3600 );

							$found++;

						}

						$feeds[ $key ][ 'found' ] = $found;

					}
					else{

						$feeds[ $key ][ 'error' ] = $feed->error();
						$feeds[ $key ][ 'found' ] = (int)$found;

					}

					VirtualPostsSettings::update( 'feeds', $feeds );

				}


			}

		}

		echo json_encode( $result );
		exit;

	}

	function feeds(){

		$result = VirtualPostsSettings::get( 'feeds' );

		echo json_encode( $result );
		exit;

	}


}

$_virtualposts_feeds = new VirtualPostsFeeds();