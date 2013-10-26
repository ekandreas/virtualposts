<?php

class VirtualPostsFeeds {

	function __construct() {
		add_action( 'wp_ajax_virtualposts_feed', array( &$this, 'feed' ) );
	}

	function feed(){

		include_once( ABSPATH . WPINC . '/feed.php' );

		$result = array();

		if( wp_verify_nonce( $_REQUEST['nonce'], 'virtualposts_feed' ) ){

			$feeds = VirtualPostsSettings::get( 'feeds' );

			$id = $_REQUEST['id'];

			foreach( $feeds as $feed ){

				if( $id == $feed['id'] ){

					add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 1;' ) );
					$rss = fetch_feed( $feed['url'] );

					if ( ! is_wp_error( $rss ) ) {

						$maxitems = $rss->get_item_quantity( (int)$feed['max'] );
						$rss_items = $rss->get_items( 0, $maxitems );
						foreach ( $rss_items as $item ) {

							//$item->get_permalink();
						 	//$item->get_date('j F Y | g:i a');

							$row = array();
							$row['headline'] = esc_html( $item->get_title() );
							$row['feed'] = $feed['name'];
							$row['link'] = '#';
							$result[] = $row;

						}
					}
				}


			}

		}

		echo json_encode( $result );
		exit;

	}


}

$_virtualposts_feeds = new VirtualPostsFeeds();