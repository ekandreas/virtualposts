<?php

class VirtualPostsVirtual{

	private $slug = NULL;
	private $title = NULL;
	private $content = NULL;
	private $author = NULL;
	private $date = NULL;
	private $type = NULL;

	function __construct( $args ) {

		if (!isset($args['slug']))
			throw new Exception('No slug given for virtual page');

		$this->slug = $args['slug'];
		$this->title = isset($args['title']) ? $args['title'] : '';
		$this->content = isset($args['content']) ? $args['content'] : '';
		$this->author = isset($args['author']) ? $args['author'] : 1;
		$this->date = isset($args['date']) ? $args['date'] : current_time('mysql');
		$this->dategmt = isset($args['date']) ? $args['date'] : current_time('mysql', 1);
		$this->type = isset($args['type']) ? $args['type'] : 'page';

		add_filter( 'the_posts', array( &$this, 'the_posts') );
	}

	static function init(){

		$url = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
		if ( strpos( $url, 'virtualposts/' ) !== false ) {

			$cache = phpFastCache::get( 'virtualposts' );

			$rss_post = $cache[ str_replace( 'virtualposts/', '', $url ) ];

			if( !$rss_post['headline'] ) return;

			$args = array(
				'slug' => 'virtualposts',
				'title' => $rss_post['headline'],
				'content' => $rss_post['content'],
				'date' => $rss_post['date']
			);
			$pg = new VirtualPostsVirtual( $args );

		}
	}

	function the_posts( $posts ){

		global $wp, $wp_query;

		$post = new stdClass;
		$post->ID = -1;
		$post->post_author = $this->author;
		$post->post_date = $this->date;
		$post->post_date_gmt = $this->dategmt;
		$post->post_content = $this->content;
		$post->post_title = $this->title;
		$post->post_excerpt = '';
		$post->post_status = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->post_password = '';
		$post->post_name = $this->slug;
		$post->to_ping = '';
		$post->pinged = '';
		$post->modified = $post->post_date;
		$post->modified_gmt = $post->post_date_gmt;
		$post->post_content_filtered = '';
		$post->post_parent = 0;
		$post->guid = get_home_url('/' . $this->slug);
		$post->menu_order = 0;
		$post->post_tyle = $this->type;
		$post->post_mime_type = '';
		$post->comment_count = 0;

		$posts = array($post);

		$wp_query->is_page = TRUE;
		$wp_query->is_singular = TRUE;
		$wp_query->is_home = FALSE;
		$wp_query->is_archive = FALSE;
		$wp_query->is_category = FALSE;
		unset($wp_query->query['error']);
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404 = FALSE;

		return ($posts);

	}

}

add_action( 'init', 'VirtualPostsVirtual::init' );

