<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 10.02.16
 * Time: 15:00
 */

namespace freefair\RestBundle\Formatters;


class FormDataFormatter extends FormatterBase
{

	public function serialize($obj)
	{
		// TODO: Implement serialize() method.
	}

	public function parse($content)
	{
		$values = explode("&", $content);
		$result = array();
		foreach($values as $value){
			$explode = explode("=", $value, 2);
			$result[urldecode($explode[0])] = urldecode($explode[1]);
		}
		return $result;
	}

	public function getType()
	{
		return "application/x-www-form-urlencoded";
	}
}