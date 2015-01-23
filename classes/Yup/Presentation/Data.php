<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class Presentation_Data extends Presentation {

	/*
	 * @var string
	 */
	public static $_type = 'Data';

	/*
	 * @var array
	 */
	protected $_source = array();

	/*
	 * @chainable
	 * @param  string $name
	 * @return \Yup\Presentation_Data
	 */
	public static function factory($name = NULL)
	{
		if ($name !== NULL)
		{
			$class_name = NS::add_class_prefix($name, static::full_class_prefix());
			if (class_exists($class_name))
			{
				return new $class_name();
			}
		}
		return new static();
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_Data
	 */
	public function set_source(array $source)
	{
		$this->clear_cache();
		$this->_source = $source;

		return $this;
	}

	/*
	 * @return array
	 */
	public function source()
	{
		return $this->_source;
	}

	/*
	 * @param $field string
	 * @return mixed
	 */
	protected function value($field)
	{
		return \Arr::get($this->source(), $field);
	}

	/*
	 * @return array
	 */
	protected function get_basic_array()
	{
		$result = array();

		foreach ($this->source() as $field => $value)
		{
			$result[$field] = $this->get($field);
			if ($result[$field] instanceof Presentation)
			{
				$result[$field] = $result[$field]->as_array();
			}
		}

		return $result;
	}
}
