<?php

namespace freefair\RestBundle\Filters;
use Doctrine\Common\Annotations\AnnotationReader;
use freefair\RestBundle\Annotations\Authorize;
use freefair\RestBundle\Controller\RestController;
use freefair\RestBundle\Services\OAuthService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AuthenticationListener
{
	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

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
			/** @var OAuthService $oauthService */
			$oauthService = $this->container->get("rest.oauth_service");

			$authHeader = $event->getRequest()->headers->get("authorization", null);
			if($authHeader == null) $this->unauth();

			$token = explode(" ", $authHeader)[0];
			$authToken = $oauthService->getAuthToken($token);

			if($authToken == null) $this->unauth();

			$session = new Session(new MockArraySessionStorage());
			$session->set("token", $authToken->getAuthToken());
			$session->set("consumer", $authToken->getConsumer());
			$event->getRequest()->setSession($session);
		}
	}

	private function unauth()
	{
		throw new UnauthorizedHttpException("authorization_token");
	}
}