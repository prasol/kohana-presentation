<?php

/**
 * Статический класс NS предоставляет набор методов для работы
 * с пространствами имен.
 *
 * @package    Yup Presentation
 * @author     Yanis Prasol
 */

namespace Yup;

class NS {
	
	// Разделитель сегментов пространств имен и принадлежащих им классов
	const DELIMITER = '\\';
	
	/**
	 * Возвращает пространство имен, к которому принадлежит класс,
	 * или NULL если пространство имен не указано
	 *
	 *     $foo = NS::extract_namespace('Root\Test\Foo');
	 *     Значение $foo: 'Root\Test'
	 *     
	 * @param   string|object	$class
	 * @return  string
	 */
	public static function extract_namespace($class)
	{
		$parts = static::_split(static::_class_name($class));
		array_pop($parts);
		
		return count($parts) ? implode(NS::DELIMITER, $parts) : NULL;
	}
	
	/**
	 * Возвращает имя класса без указания пространства имен
	 *
	 *     $foo = NS::extract_class_name('Root\Test\Foo');
	 *     Значение $foo: 'Foo'
	 *     
	 * @param   string|object	$class
	 * @return  string
	 */
	public static function extract_class_name($class)
	{
		$parts = static::_split(static::_class_name($class));

		return count($parts) ? array_pop($parts) : NULL;
	}
	
	/**
	 * Возвращает имя модуля, к которому принадлежит класс
	 *
	 *     $foo = NS::extract_module_name('Root\Test\Foo');
	 *     Значение $foo: 'Test'
	 *     
	 * @param   string|object	$class
	 * @return  string
	 */
	public static function extract_module_name($class)
	{
		$parts = static::_split(static::_class_name($class));
		
		return (count($parts) > 1) ? $parts[1] : NULL;
	}
	
	/**
	 * Добавляет префикс к имени класса, которое может содержать указание
	 * пространства имен
	 *
	 *     $foo = NS::add_class_prefix('Root\Test\Foo', 'Class_');
	 *     Значение $foo: 'Root\Test\Class_Foo'
	 *     
	 * @param   string|object	$class
	 * @param   string			$prefix
	 * @return  string
	 */
	public static function add_class_prefix($class, $prefix)
	{
		$result = $prefix . static::extract_class_name($class);
		if ($namespace = static::extract_namespace($class))
		{
			$result = static::add_namespace($result, $namespace);
		}
		
		return $result;
	}
	
	/**
	 * Удаляет возможный префикс из имени класса, которое может содержать указание
	 * пространства имен. Регистр символов не учитывается
	 *
	 *     $foo = NS::add_class_prefix('Root\Test\Class_Foo', 'Class_');
	 *     Значение $foo: 'Root\Test\Foo'
	 *     
	 * @param   string|object	$class
	 * @param   string			$prefix
	 * @return  string
	 */
	public static function remove_class_prefix($class, $prefix)
	{
		$result = static::extract_class_name($class);
		if (substr(strtolower($result), 0, strlen($prefix)) == strtolower($prefix))
		{
			$result = substr($result, strlen($prefix));
		}
		if ($namespace = static::extract_namespace($class))
		{
			$result = static::add_namespace($result, $namespace);
		}
		
		return $result;
	}
	
	/**
	 * Добавляет к имени класса указание пространства имен
	 *
	 *     $foo = NS::add_namespace('Foo', 'Root\Test');
	 *     Значение $foo: 'Root\Test\Foo'
	 *     
	 * @param   string|object	$class
	 * @param   string			$namespace
	 * @return  string
	 */
	public static function add_namespace($class, $namespace)
	{
		return $namespace . NS::DELIMITER . static::_class_name($class);
	}
	
	/**
	 * Определяет, содержит ли имя класса указание пространства имен
	 *
	 *     $foo = NS::have_namespace('Root\Test\Foo');
	 *     
	 * Функция вернет TRUE также и в том случае, если класс находится
	 * в глобальном пространство имен, и это явно указано, например, '\Foo'
	 *
	 * @param   string|object	$class
	 * @return  boolean
	 */
	public static function have_namespace($class)
	{
		return static::extract_namespace($class) !== NULL;
	}
	
	/**
	 * Возвращает имя класса в строковом представлении
	 *   
	 * @param   string|object	$class
	 * @return  string
	 */
	protected static function _class_name($class)
	{
		if (is_object($class))
		{
			return get_class($class);
		}
		return (string)$class;
	}
	
	/**
	 * Разделяет имя класса с пространством имен на массив сегментов
	 *   
	 * @param   string	$class_name
	 * @return  array
	 */
	protected static function _split($class_name)
	{
		return explode(NS::DELIMITER, $class_name);
	}
}
