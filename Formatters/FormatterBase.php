<?php

namespace freefair\RestBundle\Formatters;

abstract class FormatterBase
{
	public abstract function serialize($obj);
	public abstract function parse($content);
	public abstract function getType();
}