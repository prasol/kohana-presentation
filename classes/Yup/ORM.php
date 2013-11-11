<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class ORM extends \ORM {

    /*
     * @var array
     */
    protected $_enums = array();

    /*
     * @return array
     */
    public function related()
    {
        return $this->_related;
    }

    /*
     * @chainable
	 * @return \Yup\ORM
	 */
    public function force_values(array $values)
    {
        return $this->values($values, array_keys($values));
    }

    /*
     * @param  string $column
	 * @return boolean
	 */
    public function __isset($column)
    {
        return parent::__isset($column) || isset($this->_enums[$column]);
    }

    /*
     * @param  string $column
	 * @return mixed
	 */
    public function get($column)
    {
        if (isset($this->_enums[$column]['values']))
        {
            return array_combine($this->_enums[$column]['values'], $this->_enums[$column]['values']);
        }
        return parent::get($column);
    }

    /*
	 * @return array
	 */
    public function enums()
    {
        return $this->_enums;
    }

    /**
     * @chainable
     * @param   boolean $force
     * @return  \Yup\ORM
     */
    public function reload_columns($force = FALSE)
    {
        $reloaded = ($force === TRUE || empty($this->_table_columns));

        parent::reload_columns($force);

        if ($reloaded && ($force || empty($this->_enums)))
        {
            $this->reload_enums();
        }

        return $this;
    }

    /**
     * @chainable
     * @return  \Yup\ORM
     */
    protected function reload_enums()
    {
        $this->_enums = array();
        foreach ($this->_table_columns as $name => $column)
        {
            if (! empty($column['options']))
            {
                $this->_enums[\Inflector::plural($name)] = array(
                    'column' => $name,
                    'values' => $column['options']
                );
            }
        }

        return $this;
    }
}
