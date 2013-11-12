<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class Presentation_List implements \Countable, \Iterator, \SeekableIterator, \ArrayAccess {

	/* @var array */
	protected $_object_cache = array();

	/* @var array */
	protected $_rows = FALSE;

	/* @var int */
	protected $_current_row = 0;

	/*
	 * @chainable
	 * @param  array $rows
	 * @return \Yup\Presentation_List
	 */
	public static function factory(array $rows)
	{
		$obj = new static();
		return $obj->set_rows($rows);
	}

	/*
	 * @chainable
	 * @param  array $rows
	 * @return \Yup\Presentation_List
	 */
	public function set_rows(array $value)
	{
		$this->clear_cache();
		$this->_rows = $value;

		return $this;
	}

	/*
	 * @chainable
	 * @param  array $rows
	 * @return \Yup\Presentation_List
	 */
	protected function clear_cache()
	{
		$this->_object_cache = array();

		return $this;
	}

	/*
	 * @return mixed
	 */
	public function current()
	{
		if (! $this->valid())
		{
			return NULL;
		}
		if (! isset($this->_object_cache[$this->key()]))
		{
			$value = $this->_rows[$this->key()];
			if (is_array($value))
			{
				$this->_object_cache[$this->key()] = Presentation_Data::factory()->set_source($value);
			}
			elseif ($value instanceof ORM)
			{
				$this->_object_cache[$this->key()] = Presentation_Model::factory($value);
			}
			else
			{
				$this->_object_cache[$this->key()] = $value;
			}
		}
		return $this->_object_cache[$this->key()];
	}

	/*
	 * @param  string $key
	 * @return array
	 */
	public function as_array($key = NULL)
	{
		$results = array();

		foreach ($this as $row)
		{
			if ($key === NULL)
			{
				$results[] = $row->as_array();
			}
			else
			{
				$results[$row->$key] = $row->as_array();
			}
		}

		$this->rewind();

		return $results;
	}

	/*
	 * @param  string $key
	 * @return array
	 */
	public function as_indexed_array($key)
	{
		$results = array();

		foreach ($this as $row)
		{
			$results[$row->$key] = $row;
		}

		$this->rewind();

		return $results;
	}

	/*
	 * @return  integer
	 */
	public function count()
	{
		return count($this->_rows);
	}

	/*
	 * @param   int $offset
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 && $offset < $this->count());
	}

	/*
	 * @param   int $offset
	 * @return  mixed
	 */
	public function offsetGet($offset)
	{
		if (! $this->seek($offset))
		{
			return NULL;
		}

		return $this->current();
	}

	/*
	 * @param   int     $offset
	 * @param   mixed   $value
	 * @return  void
	 * @throws  \Kohana_Exception
	 */
	final public function offsetSet($offset, $value)
	{
		throw new \Kohana_Exception('Calculated results are read-only');
	}

	/*
	 * @param   int     $offset
	 * @param   mixed   $value
	 * @return  void
	 * @throws  \Kohana_Exception
	 */
	final public function offsetUnset($offset)
	{
		throw new \Kohana_Exception('Calculated results are read-only');
	}

	/*
	 * @return integer
	 */
	public function key()
	{
		return $this->_current_row;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_List
	 */
	public function next()
	{
		++$this->_current_row;
		return $this;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_List
	 */
	public function prev()
	{
		--$this->_current_row;
		return $this;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_List
	 */
	public function rewind()
	{
		$this->_current_row = 0;
		return $this;
	}

	/*
	 * @return boolean
	 */
	public function valid()
	{
		return $this->offsetExists($this->_current_row);
	}

	/*
	 * @param int $position
	 * @return boolean
	 */
	public function seek($position)
	{
		if ($this->offsetExists($position))
		{
			$this->_current_row = $position;

			return TRUE;
		}
		return FALSE;
	}
}
