<?php

namespace freefair\RestBundle\Formatters;

class JsonFormatter extends FormatterBase
{
	public function serialize($obj)
	{
		return json_encode($obj);
	}

	public function parse($content)
	{
		return json_decode($content, true);
	}

	public function getType()
	{
		return "application/json";
	}
}