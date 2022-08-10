<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayAccess;
use Metabolism\WordpressBundle\Helper\ACFHelper;
use Metabolism\WordpressBundle\Helper\DataHelper;
use Metabolism\WordpressBundle\Helper\MetaHelper;
use ReflectionObject;
use ReflectionProperty;
use ReflectionMethod;

/**
 * Class Entity
 *
 * @package Metabolism\WordpressBundle\Entity
 */
abstract class Entity implements ArrayAccess
{
	public $ID;
	public $entity;

	public static $date_format = false;

    /**
     * @var bool|ACFHelper
     */
	public $custom_fields = false;

    /**
     * @var bool|MetaHelper
     */
	public $meta = false;

	/**
	 * Magic method to load properties
	 */
    public function __toArray(): array {

        $data = [];

        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property){

            $name = $property->name;
            $data[$name] = $this->$name;
        }

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method){

            $name = $method->name;

            if( substr($name,0,3) == 'get'){

                $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', preg_replace('/get(.*)/', '$1', $name)));
                $data[$key] = new DataHelper($this, $name, $key);
            }
            elseif( substr($name,0,2) == 'is' && ctype_upper(substr($name,2,1)) ){

                $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
                $data[$key] = new DataHelper($this, $name, $key);
            }
        }

        return $data;
	}

	/**
	 * @param $id
	 * @return string
	 */
	private function getMethodName($id): string {

        return 'get'.str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $id))));
    }


	/**
	 * Magic method to load properties
     * todo: to be deprecated
	 *
	 * @param $id
	 * @return string
	 */
	public function __get($id) {

        $method = $this->getMethodName($id);

		if( method_exists($this, $method) )
			return call_user_func([$this, $method]);
        elseif( $this->custom_fields && $this->custom_fields->has($id) )
            return $this->custom_fields->getValue($id);

		return null;
	}


	/**
	 * Magic method to load properties from call
     * todo: to be deprecated
	 *
	 * @param $id
	 * @param $args
	 * @return string
	 */
	public function __call($id, $args) {

        $method = $this->getMethodName($id);

		if( method_exists($this, $method) )
			return call_user_func_array([$this, $method], $args);

		return null;
	}


	/**
	 * Magic method to check properties
     * todo: to be deprecated
	 *
	 * @param $id
	 * @return bool
     */
	public function __isset($id) {

        $method = $this->getMethodName($id);

		return method_exists($this, $method) || ($this->custom_fields && $this->custom_fields->has($id));
	}


    /**
     * Return true if id exists
     * @return bool
     */
    public function exist(){

		return is_int( $this->ID );
	}

	/**
	 * @return mixed
	 */
	public function getID(){

		return $this->ID;
	}


    /**
     * load custom fields data
     * @param $id
     * @param $type
     */
	protected function loadMetafields($id, $type)
	{

        if( class_exists('ACF') && !$this->custom_fields )
	        $this->custom_fields = new ACFHelper( $id, $type );

        if( !$this->meta )
	        $this->meta = new MetaHelper( $id, $type );
	}

    /**
     * @param $date
     * @return mixed|void
     */
    protected function formatDate($date)
    {
        if( !self::$date_format )
            self::$date_format = get_option('date_format');

		$date = (string) mysql2date( self::$date_format, $date);

        return apply_filters('get_the_date', $date, self::$date_format);
	}

	/**
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset){

        return $this->__isset($offset);
    }

	/**
	 * @param $offset
	 * @return mixed
	 */
	public function offsetGet($offset){

        return $this->__get($offset);
    }

	/**
	 * @param $offset
	 * @param $value
	 * @return void
	 */
	public function offsetSet($offset, $value){}

	/**
	 * @param $offset
	 * @return void
	 */
	public function offsetUnset($offset){}
}
