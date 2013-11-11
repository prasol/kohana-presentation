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
    protected static $_sub_prefix = 'Data_';

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
            $class_name = NS::add_class_prefix($name, static::$_class_prefix . static::$_sub_prefix);
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
    protected function _as_array()
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