<?php

namespace freefair\RestBundle\Parsing;

use Doctrine\Common\Annotations\AnnotationReader;
use freefair\RestBundle\Annotations\Serialize;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassParser
{
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

	public function serializeObject($obj) {
		$request = Request::createFromGlobals();
		$contentType = $request->getAcceptableContentTypes()[0];

		$formatter = $this->getFormmatter($contentType);
		$array = $obj;
		if(!is_array($array))
			$array = $this->buildArray($obj);
		$parse = $formatter->serialize($array);

		return array("type" => $formatter->getType(), "result" => $parse);
	}

	public function parseClass($class, $content) {
		$class = new \ReflectionClass($class);

		$request = Request::createFromGlobals();
		$contentType = $request->getContentType();

		$formatter = $this->getFormmatter($contentType);
		$parse = $formatter->parse($content);

		$result = $this->buildObject($parse, $class);

		return $result;
	}

	private function getFormmatter($type) {
		$config = $this->container->getParameter("rest.config");
		$formatters = $config["formatters"];
		$default = null;
		$result = null;
		foreach($formatters as $formatter) {
			if($formatter["default"])
				$default = $formatter;
			if($formatter["type"] == $type)
				$result = $formatter;
		}
		if($result == null)
			return $this->container->get($default["id"]);
		return $this->container->get($result["id"]);
	}

	private function buildArray($obj) {
		$result = array();
		$reflectionObj = new \ReflectionObject($obj);
		$reflectionProperties = $reflectionObj->getProperties();
		foreach($reflectionProperties as $property)
		{
			$serializeAnnotation = $this->reader->getPropertyAnnotation($property, Serialize::class);
			$name = $property->getName();
			if($serializeAnnotation->getName() != null)
				$name = $serializeAnnotation->getName();
			if($serializeAnnotation->getClassName() != null) {
				$result[$name] = $this->buildArray($property->getValue($obj));
			}
			else
			{
				$result[$name] = $property->getValue($obj);
			}
		}
		return $result;
	}

	private function buildObject($parse, ReflectionClass $class)
	{
		$result = $class->newInstance();
		if($parse != null) {
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
					$property->setValue($result, $this->buildObject($parse[$name], new ReflectionClass($className)));
				} else {
					$property->setValue($result, $parse[$name]);
				}
			}
		}
		return $result;
	}
}