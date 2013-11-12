<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class Presentation_Model extends Presentation {

	/*
	 * @var string
	 */
	public static $_type = 'Model';

	/*
	 * @var \Yup\ORM
	 */
	protected $_model = NULL;

	/*
	 * @chainable
	 * @param  mixed $model
	 * @return \Yup\Presentation_Model
	 */
	public static function factory($model = NULL)
	{
		if ($model === NULL)
		{
			return new static();
		}
		if (is_string($model))
		{
			$class_name = NS::add_class_prefix($model, static::full_class_prefix());
			if (class_exists($class_name))
			{
				return new $class_name();
			}
			return new self();
		}
		$class_name = self::detect_class_name($model);
		$instance = new $class_name();

		return $instance->set_model($model);
	}

	/*
	 * @param  \Yup\ORM $model
	 * @return string
	 */
	protected static function detect_class_name(ORM $model = NULL)
	{
		$class_name = NS::add_class_prefix(NS::remove_class_prefix($model, 'Model_'), static::full_class_prefix());
		if (class_exists($class_name))
		{
			return $class_name;
		}

		return __CLASS__;
	}

	/*
	 * @chainable
	 * @return \Yup\Presentation_Model
	 */
	protected function check_model()
	{
		if (! $this->_model instanceof ORM)
		{
			throw new \Kohana_Exception('Model can not be empty and must be instance of \Yup\ORM');
		}
		return $this;
	}

	/*
	 * @chainable
	 * @param  \Yup\ORM $model
	 * @return \Yup\Presentation_Model
	 */
	public function set_model(ORM $model)
	{
		$this->clear_cache();
		$this->_model = $model;

		return $this;
	}

	/*
	 * @return \Yup\ORM
	 */
	public function model()
	{
		$this->check_model();
		return $this->_model;
	}

	/*
	 * @param  string $field
	 * @return boolean
	 */
	public function __isset($field)
	{
		return isset($this->model()->$field) || parent::__isset($field);
	}

	/*
	 * @param  string $field
	 * @param  array $list
	 * @return array
	 */
	protected function translate_enums($field, array $list)
	{
		$result = array();
		if ($column = \Arr::path($this->model()->enums(), "$field.column"))
		{
			foreach ($list as $key => $value)
			{
				$result[$key] = $this->translate_value($column, $value);
			}
		}
		return $result;
	}

	/*
	 * @param  string $field
	 * @param  mixed $value
	 * @return mixed
	 */
	protected function translate_field($field, $value)
	{
		if (is_array($value))
		{
			return $this->translate_enums($field, $value);
		}
		return parent::translate_field($field, $value);
	}

	/*
	 * @param $field string
	 * @return mixed
	 */
	public function original($field)
	{
		$value = parent::original($field);
		if ($value instanceof ORM)
		{
			throw new \Kohana_Exception('No direct access to original model :model from presentation', array(
				':model' => get_class($value)
			));
		}
		return $value;
	}

	/*
	 * @param $field string
	 * @return mixed
	 */
	protected function value($field)
	{
		return $this->model()->get($field);
	}

	/*
	 * @return array
	 */
	protected function _as_array()
	{
		$object = array();

		foreach ($this->model()->object() as $column => $value)
		{
			$object[$column] = $this->get($column);
		}

		foreach ($this->model()->related() as $column => $model)
		{
			$object[$column] = self::factory($model)->as_array();
		}

		return $object;
	}

	/*
	 * @return string
	 */
	protected function get_model_name()
	{
		return NS::remove_class_prefix($this, static::full_class_prefix());
	}

	/*
	 * @chainable
	 * @param  array $fields
	 * @return \Yup\Presentation_Model
	 */
	public function make(array $fields)
	{
		if ($this->_model instanceof ORM)
		{
			throw new \Kohana_Exception('Model already exists');
		}
		$this->set_model(ORM::factory($this->get_model_name()));
		foreach ($fields as $name => $value)
		{
			if ($value instanceof Presentation)
			{
				$this->_values_cache[$name] = $value;
				unset($fields[$name]);
			}
		}
		$this->model()->force_values($fields);

		return $this;
	}
}
