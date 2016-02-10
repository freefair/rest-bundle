<?php

namespace freefair\RestBundle\Filters;
use Doctrine\Common\Annotations\AnnotationReader;
use freefair\RestBundle\Annotations\Authorize;
use freefair\RestBundle\Controller\RestController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationListener
{
	public function onKernelController(FilterControllerEvent $event){
		$controller = $event->getController();

		if(!is_array($controller) || !($controller[0] instanceof RestController)) return;

		$action = $controller[1];
		$controller = $controller[0];

		$reflection = new \ReflectionObject($controller);
		$reader = new AnnotationReader();
		$authorize = $reader->getClassAnnotation($reflection, Authorize::class);

		$methodAuthorize = $reader->getMethodAnnotation($reflection->getMethod($action), Authorize::class);
		if($methodAuthorize != null)
			$authorize = $methodAuthorize;

		if($authorize != null) {
			throw new UnauthorizedHttpException("");
		}
	}
}