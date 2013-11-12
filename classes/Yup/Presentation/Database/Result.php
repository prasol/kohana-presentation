<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class Presentation_Database_Result implements \Countable, \Iterator, \SeekableIterator, \ArrayAccess {

	/* @var array */
	protected $_object_cache = array();

	/* @var array */
	protected $_as_array = array();

	/* @var \Database_Result */
	protected $_rows = FALSE;

	/*
	 * @chainable
	 * @param  \Database_Result $rows
	 * @return \Yup\Presentation_Database_Result
	 */
	public static function factory(\Database_Result $rows)
	{
		$obj = new static();
		return $obj->set_rows($rows);
	}

	/*
	 * @chainable
	 * @param  \Database_Result $value
	 * @return \Yup\Presentation_Database_Result
	 */
	public function set_rows(\Database_Result $value)
	{
		$this->_rows = $value;

		return $this;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_Database_Result
	 */
	protected function clear_cache()
	{
		$this->_object_cache = array();
		$this->_as_array = array();

		return $this;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_Database_Result
	 */
	public function check_rows()
	{
		if (! $this->_rows instanceof \Database_Result)
		{
			throw new \Kohana_Exception('Rows can not be empty and must be instance of Database_Result');
		}
		return $this;
	}

	/*
	 * @return mixed
	 */
	public function current()
	{
		$this->check_rows();
		$value = $this->_rows->current();
		if ($value instanceof ORM)
		{
			if (! isset($this->_object_cache[$this->_rows->key()]))
			{
				$this->_object_cache[$this->_rows->key()] = Presentation_Model::factory($value);
			}
			return $this->_object_cache[$this->_rows->key()];
		}
		return $value;
	}

	/**
	 * @param   string  $name
	 * @param   mixed   $default
	 * @return  mixed
	 */
	public function get($name, $default = NULL)
	{
		$row = $this->current();

		if (isset($row->$name))
		{
			return $row->$name;
		}

		return $default;
	}

	/*
	 * @param   string  $key    column for associative keys
	 * @param   string  $value  column for values
	 * @return  array
	 */
	public function as_array($key = NULL, $value = NULL)
	{
		if (isset($this->_as_array[$key][$value]))
		{
			return $this->_as_array[$key][$value];
		}

		$result = $this->_rows->as_array($key, $value);
		foreach ($result as &$row)
		{
			if ($row instanceof ORM)
			{
				$row = Presentation_Model::factory($row);
			}
		}
		$this->_as_array[$key][$value] = $result;

		return $result;
	}

	/*
	 * @param   string $method
	 * @param   array  $args
	 * @return  mixed
	 */
	public function __call($method, $args)
	{
		$this->check_rows();

		return call_user_func_array(array($this->_rows, $method), $args);
	}

	// Ghost functions to interfaces matching

	public function count()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function offsetExists($offset)
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function offsetGet($offset)
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	final public function offsetSet($offset, $value)
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	final public function offsetUnset($offset)
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function key()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function next()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function prev()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function rewind()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function valid()
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function seek($position)
	{
		return $this->__call(__FUNCTION__, func_get_args());
	}
}
