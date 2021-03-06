<?php

namespace freefair\RestBundle\Parsing;

use Countable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use freefair\RestBundle\Annotations\Serialize;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassParser
{
	private static $specialTypes = array("DateTime", "double", "integer", "boolean", "string");

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var AnnotationReader
	 */
	private $reader;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		$this->reader = new AnnotationReader();
	}

	public function serializeObject($obj, $skipArrayBuilding = false)
	{
		$request = Request::createFromGlobals();
		$contentType = $request->getAcceptableContentTypes()[0];

		$formatter = $this->getFormmatter($contentType);
		$array = $obj;
		if($obj != null)
		{
			if (!$skipArrayBuilding)
				$array = $this->buildArray($obj);
		}
		else
		{
			$array = null;
		}
		$parse = $formatter->serialize($array);

		return array("type" => $formatter->getType(), "result" => $parse);
	}

	public function parseClass($class, $content)
	{
		$request = Request::createFromGlobals();
		$contentType = $request->getContentType();

		$formatter = $this->getFormmatter($contentType);
		$result = $formatter->parse($content);

		if (!empty($class)) {
			if(strpos($class, "[]") !== false) {
				if(is_array($result)){
					$class = substr($class, 0, -2);
					$class = new \ReflectionClass($class);
					$array = array();
					foreach ($result as $value) {
						$array[] = $this->buildObject($value, $class);
					}
					$result = $array;
				}
			}
			else {
				$class = new \ReflectionClass($class);
				$result = $this->buildObject($result, $class);
			}
		}
		return $result;
	}

	private function getFormmatter($type)
	{
		$config = $this->container->getParameter("rest.config");
		$formatters = $config["formatters"];
		$default = null;
		$result = null;
		foreach ($formatters as $formatter) {
			if ($formatter["default"])
				$default = $formatter;
			if ($formatter["type"] == $type)
				$result = $formatter;
		}
		if ($result == null)
			return $this->container->get($default["id"]);
		return $this->container->get($result["id"]);
	}

	private function buildArray($obj)
	{
		$result = array();
		if($obj !== null) {
			if (is_array($obj) || $obj instanceof Countable) {
				foreach ($obj as $key => $value) {
					$result[$key] = $this->buildArray($value);
				}
			} else if (is_string($obj) || is_bool($obj) || is_integer($obj) || is_float($obj) || is_double($obj)) {
				$result = $obj;
			} else if (is_array($obj)) {
				$result[] = $this->buildArray($obj);
			} else {
				$reflectionObj = new \ReflectionObject($obj);
				$reflectionProperties = $reflectionObj->getProperties();
				foreach ($reflectionProperties as $property) {
					$serializeAnnotation = $this->reader->getPropertyAnnotation($property, Serialize::class);
					if ($serializeAnnotation == null) continue;
					$name = $property->getName();
					if ($serializeAnnotation->getName() != null)
						$name = $serializeAnnotation->getName();
					$className = $serializeAnnotation->getClassName();
					if ($className != null) {
						if (in_array($className, self::$specialTypes))
							$result[$name] = $this->buildArrayFromSpecialType($property->getValue($obj), $className);
						else
							$result[$name] = $this->buildArray($property->getValue($obj));
					} else {
						$result[$name] = $property->getValue($obj);
					}
				}
			}
		}
		return $result;
	}

	private function buildObject($parse, ReflectionClass $class)
	{
		$result = $class->newInstance();
		if ($parse !== null) {
			$reflectionProperties = $class->getProperties();
			foreach ($reflectionProperties as $property) {
				$serializeAnnotation = $this->reader->getPropertyAnnotation($property, Serialize::class);
				if ($serializeAnnotation == null) continue;
				$name = $property->getName();
				if ($serializeAnnotation->getName() != null)
					$name = $serializeAnnotation->getName();
				if (!array_key_exists($name, $parse)) {
					$property->setValue($result, null);
					continue;
				}
				if ($serializeAnnotation->getClassName() != null) {
					$className = $serializeAnnotation->getClassName();
					$val = null;
					if (strpos($className, "[]") !== false) {
						$className = substr($className, 0, -2);
						$array = $parse[$name];
						/** @var ArrayCollection $val */
						$val = new ArrayCollection();
						foreach ($array as $value) {
							if (in_array($className, self::$specialTypes))
								$val->add($this->buildSpecialObject($value, $className));
							else
								$val->add($this->buildObject($value, new ReflectionClass($className)));
						}
					} else {
						if (in_array($className, self::$specialTypes))
							$val = $this->buildSpecialObject($parse[$name], $className);
						else
							$val = $this->buildObject($parse[$name], new ReflectionClass($className));
					}
					$property->setValue($result, $val);
				} else {
					$property->setValue($result, $parse[$name]);
				}
			}
		}
		return $result;
	}

	private function buildArrayFromSpecialType($getValue, $className)
	{
		if($getValue != null) {
			if ($className == "DateTime") {
				/** @var \DateTime $dt */
				$dt = $getValue;
				$date_format = $this->container->getParameter("rest.config")["date_format"];
				if ($date_format != "php")
					$dt = $dt->format($date_format);
				return $dt;
			}
		}
		return $getValue;
	}

	private function buildSpecialObject($value, $className)
	{
		if ($className == "DateTime") {
			$date_format = $this->container->getParameter("rest.config")["date_format"];
			if ($date_format == "php")
				$result = new \DateTime($value["date"], new \DateTimeZone($value["timezone"]));
			else
				$result = \DateTime::createFromFormat($date_format, $value);
			return $result;
		}
		if ($className == "double") {
			return doubleval($value);
		}
		if ($className == "integer") {
			return intval($value);
		}
		if ($className == "boolean") {
			return boolval($value);
		}
		return $value;
	}
}
