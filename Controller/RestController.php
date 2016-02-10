<?php

namespace freefair\RestBundle\Controller;

abstract class RestController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
	protected function restResult($obj, $status = 200) {
		$response = new \Symfony\Component\HttpFoundation\Response();
		$classParser = $this->container->get("rest.internal_class_parser");
		$content = $classParser->serializeObject($obj);
		$response->setContent($content["result"]);
		$response->headers->add(array("content-type" => $content["type"]));
		$response->setStatusCode($status);
		return $response;
	}
}