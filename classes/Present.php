<?php

/**
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

abstract class Present {

	/*
	 * @param  \Yup\ORM $model
	 * @return \Yup\Presentation_Model
	 */
	public static function model(\Yup\ORM $model)
	{
		return \Yup\Presentation_Model::factory($model);
	}

	/*
	 * @param  string $name
	 * @param  array $data
	 * @return \Yup\Presentation_Data
	 */
	public static function data($name, array $data)
	{
		return \Yup\Presentation_Data::factory($name)->set_source($data);
	}

	/*
	 * @param  \Database_Result $db_result
	 * @return \Yup\Presentation_Database_Result
	 */
	public static function db_result(\Database_Result $db_result)
	{
		return \Yup\Presentation_Database_Result::factory($db_result);
	}

	/*
	 * @param  array $list
	 * @return \Yup\Presentation_List
	 */
	public static function data_list(array $list)
	{
		return \Yup\Presentation_List::factory($list);
	}
}
