<?php

namespace freefair\RestBundle\Converters;

use freefair\RestBundle\Parsing\ClassParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
		$result = null;
		if($configuration->getClass() == 'Symfony\Component\HttpFoundation\File\UploadedFile') {
			$result = $this->parseFile($configuration->getName(), $request);
		} else {
			$result = $this->parser->parseClass($configuration->getClass(), $request->getContent());
		}
		$request->attributes->set($configuration->getName(), $result);
		return true;
	}

	public function supports(ParamConverter $configuration)
	{
		return true;
	}

	private function parseFile($varname, Request $request)
	{
		if(!$request->files->has($varname)) return null;
		$file = $request->files->get($varname);
		return $file;
	}
}
