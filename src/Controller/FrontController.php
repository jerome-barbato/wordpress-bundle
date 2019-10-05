<?php

namespace Metabolism\WordpressBundle\Controller;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class FrontController {

	/**
	 * @var string plugin domain name for translations
	 */
	public static $languages_folder;

	public static $domain_name = 'default';

	private $config;

	/**
	 * Redirect to admin
	 */
	public function redirect()
	{
		$path = rtrim($_SERVER['REQUEST_URI'], '/');

		if( !empty($path) && ($path == WP_FOLDER || $path == '/public'.WP_FOLDER) ){

			wp_redirect(is_user_logged_in() ? admin_url() : wp_login_url());

			echo "redirect...";

			exit;
		}
	}


	/**
	 * Init placeholder
	 */
	public function init(){}


	/**
	 * Add custom post type for taxonomy archive page
	 * @param \WP_Query $query
	 * @return mixed
	 */
	public function preGetPosts( $query )
	{
		if( !$query->is_main_query() || is_admin() )
			return $query;

		global $wp_query;

		$object = $wp_query->get_queried_object();

		if ( $query->is_archive )
		{
			if( get_class($object) == 'WP_Post_Type' ){

				if( $ppp= $this->config->get('post_type.'.$object->name.'.posts_per_page') )
					$query->set( 'posts_per_page', $ppp );

				if( $orderby = $this->config->get('post_type.'.$object->name.'.orderby') )
					$query->set( 'orderby', $orderby );

				if( $order = $this->config->get('post_type.'.$object->name.'.order') )
					$query->set( 'order', $order );
			}
			elseif( get_class($object) == 'WP_Term' ){

				if( $ppp = $this->config->get('taxonomy.'.$object->taxonomy.'.posts_per_page') )
					$query->set( 'posts_per_page', $ppp );

				if( $orderby = $this->config->get('taxonomy.'.$object->name.'.orderby') )
					$query->set( 'orderby', $orderby );

				if( $order = $this->config->get('taxonomy.'.$object->name.'.order') )
					$query->set( 'order', $order );
			}
		}

		if ( $query->is_tax && !get_query_var('post_type') )
		{
			global $wp_taxonomies;

			$post_type = ( isset($object->taxonomy, $wp_taxonomies[$object->taxonomy] ) ) ? $wp_taxonomies[$object->taxonomy]->object_type :[];

			$query->set('post_type', $post_type);
			$query->query['post_type'] = $post_type;
		}

		if( $query->is_search ) {

			if( $ppp = $this->config->get('search.posts_per_page') )
				$query->set( 'posts_per_page', $ppp );
		}

		return $query;
	}


	/**
	 * Load App configuration
	 */
	private function loadConfig()
	{
		global $_config;

		$this->config = $_config;

		self::$domain_name      = $this->config->get('domain_name', 'app');
		self::$languages_folder = WP_CONTENT_DIR . '/languages';
	}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

		$this->loadConfig();

		add_action( 'init', [$this, 'init']);
		add_action( 'init', [$this, 'redirect']);
		add_action( 'init', '_wp_admin_bar_init', 0 );

		add_action( 'pre_get_posts', [$this, 'preGetPosts'] );
	}
}
