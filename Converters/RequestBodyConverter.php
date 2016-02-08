<?php

namespace freefair\RestBundle\Converters;

use freefair\RestBundle\Parsing\ClassParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class RequestBodyConverter implements \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface
{
	/**
	 * @var ClassParser
	 */
	private $parser;

	public function __construct(ClassParser $parser)
	{
		$this->parser = $parser;
	}

	public function apply(Request $request, ParamConverter $configuration)
	{
		$request->attributes->set($configuration->getName(), $this->parser->parseClass($configuration->getClass(), $request->getContent()));
		return true;
	}

	public function supports(ParamConverter $configuration)
	{
		return true;
	}
}