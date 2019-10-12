<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class User
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class User extends Entity
{
	public $entity = 'user';

	public $login;
	public $nicename;
	public $email;
	public $url;
	public $registered;
	public $status;
	public $display_name;

	private $_user = null;

	/**
	 * User constructor.
	 *
	 * @param $id
	 * @param array $args
	 */
	public function __construct($id, $args = [])
	{
		if( $user = $this->get($id) ) {

			$this->import($user->data, false, 'user_');

			$this->ID = $id;
			$this->link = get_author_posts_url($id);

			if( !isset($args['depth']) || $args['depth'] )
				$this->addCustomFields('user_'.$id);
		}
	}


	/**
	 * Get user
	 *
	 * @param $pid
	 * @return bool|\WP_User
	 */
	protected function get( $pid ) {

		if( $user = get_userdata($pid) ){

			if( is_wp_error($user) )
				return false;

			$this->_user = $user;
		}

		return $user;
	}


    /**
     * Get avatar url
     *
     * @param array $args
     * @return string
     */
	public function getAvatar($args = []){

        $args = get_avatar_data( $this->ID, $args );
        return $args['url'];
    }
}
