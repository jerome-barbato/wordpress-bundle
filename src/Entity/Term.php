<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\TaxonomyFactory;

/**
 * Class Term
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Term extends Entity
{
	public $entity = 'term';

	public $excerpt;
	public $link;
	public $ID;
	public $current;
	public $slug;
	public $taxonomy;
	public $parent;
	public $count;
	public $order;
	public $title;
	public $thumbnail;
	public $template;

	/** @var bool|Term[] $children */
	public $children;

	protected $term_id;
	protected $term_taxonomy_id;


	/**
	 * Post constructor.
	 *
	 * @param null $id
	 * @param array $args
	 */
	public function __construct($id, $args = [])
	{
		if( is_array($id) )
		{
			if( empty($id) || isset($id['invalid_taxonomy']) )
				return;

			$id = $id[0];
		}

		if( $term = $this->get($id) )
		{
			$this->import($term, false, 'term_');

			if( !empty($term->taxonomy) ){

				if( !isset($args['depth']) || $args['depth'] )
					$this->addCustomFields($term->taxonomy.'_'.$id);
			}
		}
	}


	/**
	 * Validate class
	 * @param \WP_Term $term
	 * @return bool
	 */
	protected function isValidClass($term){

		$class = explode('\\', get_class($this));
		$class = end($class);
		return $class == "Term" || Factory::getClassname($term->taxonomy) == $class;

	}


	/**
	 * @param $pid
	 * @return array|bool|\WP_Error|\WP_Term|null
	 */
	protected function get($pid ) {

		$term = false;

		if( $term = get_term($pid) )
		{
			if( !$term || is_wp_error($term) )
				return false;
			
			$term->excerpt = strip_tags(term_description($pid),'<b><i><strong><em><br>');
			$term->template = get_term_meta($term->term_id, 'template', true);

			if( WP_FRONT )
				$term->link = get_term_link($pid);

			$term->ID = $term->term_id;
			$term->term_order = intval($term->term_order);
			$term->current = get_queried_object_id() == $pid;

			// load thumbnail if set to optimize loading by preventing full acf load
			if( class_exists('ACF') && $thumbnail_id = get_field('thumbnail', $term->taxonomy.'_'.$term->ID) )
				$term->thumbnail = Factory::create($thumbnail_id, 'image');
			else
				$term->thumbnail = false;
		}

		return $term;
	}
}
