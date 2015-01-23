<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

abstract class Presentation {

	/*
	 * @var string
	 */
	public static $_class_prefix = 'Presentation';

	/*
	 * @var string
	 */
	public static $_context = '';

	/*
	 * @var string
	 */
	public static $_type = '';

	/*
	 * @var string
	 */
	protected $_data_key = '_presentation';

	/*
	 * @var string
	 */
	protected $_model_key = '_model';

	/*
	 * @var string
	 */
	protected $_field_method_prefix = 'field_';

	/*
	 * @var array
	 */
	protected $_values_cache = array();

	/*
	 * @var array
	 */
	protected $_calculated_cache = array();

	/*
	 * @return boolean
	 */
	public function __isset($field)
	{
		return method_exists($this, $this->_field_method_prefix . $field);
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation
	 */
	public function clear_cache()
	{
		$this->_values_cache = array();
		$this->_calculated_cache = array();

		return $this;
	}

	protected static function full_class_prefix()
	{
		return implode('_', array_filter(array(static::$_class_prefix, static::$_context, static::$_type))) . '_';
	}

	/*
	 * @param  string $callback
	 * @return mixed
	 */
	protected function execute_callback($callback, $params = array())
	{
		if ($callback instanceof \Closure)
		{
			return call_user_func_array($callback, $params);
		}
		if (strpos($callback, '::') > 0)
		{
			list($class_alias, $method) = explode('::', $callback, 2);

			if (in_array(strtolower($class_alias), array('self', 'this')))
			{
				$class_name = get_class($this);
			}
			else
			{
				$class_name = $class_alias;
			}

			$reflection = new \ReflectionMethod($class_name, $method);
			if (in_array(strtolower($class_alias), array('self', 'this')))
			{
				$reflection->setAccessible(TRUE);
			}
			if (strtolower($class_alias) === 'this')
			{
				return $reflection->invokeArgs($this, $params);
			}
			return $reflection->invokeArgs(NULL, $params);
		}
		else
		{
			$reflection = new \ReflectionFunction($callback);

			return $reflection->invokeArgs($params);
		}
	}

	/*
	 * @param  mixed  $value
	 * @param  string $callback
	 * @param  array  $params
	 * @return mixed
	 */
	protected function execute_value_callback($value, $callback, $params = array())
	{
		array_unshift($params, $value);

		return $this->execute_callback($callback, $params);
	}

	/*
	 * @param  mixed $callback
	 * @return mixed
	 */
	public function calculate_value($callback)
	{
		if (is_array($callback))
		{
			return $this->execute_callback(array_shift($callback), $callback);
		}
		return $this->execute_callback($callback);
	}

	/*
	  * @param  string $field
	  * @return mixed
	  */
	protected function fetch_value($field)
	{
		if (array_key_exists($field, $this->_calculated_cache))
		{
			return $this->_calculated_cache[$field];
		}
		if (method_exists($this, $method = $this->_field_method_prefix . $field))
		{
			$this->_calculated_cache[$field] = $this->$method();
			return $this->_calculated_cache[$field];
		}
		return $this->value($field);
	}

	/*
	 * @param  string $field
	 * @param  mixed $value
	 * @return mixed
	 */
	protected function translate_value($field, $value)
	{
		foreach ((array) \Arr::get($this->rules(), $field) as $rule)
		{
			if (is_array($rule))
			{
				$value = $this->execute_value_callback($value, array_shift($rule), $rule);
			}
			else
			{
				$value = $this->execute_value_callback($value, $rule);
			}
		}

		return $value;
	}

	/*
	 * @param  array $value
	 * @return boolean
	 */
	protected function is_presentable_array(array $value)
	{
		return \Arr::is_assoc($value);
	}

	/*
	 * @param  string $field
	 * @param  mixed $value
	 * @return mixed
	 */
	protected function translate_field($field, $value)
	{
		if ($value instanceof Presentation)
		{
			return $value;
		}
		if ($value instanceof ORM)
		{
			return Presentation_Model::factory($value);
		}
		if (is_array($value))
		{
			if ($model_name = \Arr::get($value, $this->_model_key))
			{
				unset($value[$this->_model_key]);
				return Presentation_Model::factory($model_name)->make($value);
			}
			if ($this->is_presentable_array($value))
			{
				$presentation_name = \Arr::get($value, $this->_data_key);
				unset($value[$this->_data_key]);
				return Presentation_Data::factory($presentation_name)->set_source($value);
			}
		}

		return $this->translate_value($field, $value);
	}

	/*
	 * @param  string $field
	 * @return mixed
	 */
	public function get($field)
	{
		if (array_key_exists($field, $this->_values_cache))
		{
			return $this->_values_cache[$field];
		}
		$this->_values_cache[$field] = $this->translate_field($field, $this->fetch_value($field));

		return $this->_values_cache[$field];
	}

	/*
	 * @param  string $field
	 * @return mixed
	 */
	public function __get($field)
	{
		return $this->get($field);
	}

	/*
	 * @param  string $field
	 * @return mixed
	 */
	public function raw($field)
	{
		return $this->fetch_value($field);
	}

	/*
	 * @return array
	 */
	public function as_array()
	{
		$result = $this->get_basic_array();
		$reflection = new \ReflectionClass($this);
		foreach ($reflection->getMethods() as $method)
		{
			if (substr($method->name, 0, strlen($this->_field_method_prefix)) === $this->_field_method_prefix)
			{
				$field = substr($method->name, strlen($this->_field_method_prefix));
				$result[$field] = $this->fetch_value($field);
			}
		}
		return $result;
	}
	
	/*
	 * @param  array $replacements
	 * @return mixed
	 */
	protected function replace(array $replacements)
	{
        return function($value) use ($replacements)
        {
            if (array_key_exists($value, $replacements))
            {
                return $replacements[$value];
            }
            return $value;
        };
	}

	/*
	 * @param $field string
	 * @return mixed
	 */
	abstract protected function value($field);

	/*
	 * @return array
	 */
	abstract protected function get_basic_array();

	/*
	 * Override this method
	 *
	 * @return array
	 */
	public function rules()
	{
		return array();
	}
}
