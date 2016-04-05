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

		$config = $this->container->getParameter("rest.config")["authentication"];

		if($authorize != null && $config["enabled"]) {
			$authHeader = $event->getRequest()->headers->get("authorization", "null null");
			$explode = explode(" ", $authHeader);
			$type = $explode[0];
			$token = $explode[1];

			if ($authHeader == "null null") $this->unauth();
			if (strtolower($type) != "bearer") $this->unauth();

			$type = $config["oauth_type"];
			if($type == "own") {
				/** @var OAuthService $oauthService */
				$oauthService = $this->container->get("rest.oauth_service");

				$authToken = $oauthService->getAuthToken($token);

				if ($authToken == null) $this->unauth();

				$session = new Session(new MockArraySessionStorage());
				$session->set("token", $authToken->getAuthToken());
				$session->set("consumer", $authToken->getConsumer());
				$session->set("user", $authToken->getUser());
				$event->getRequest()->setSession($session);
			}
			else if ($type == "static"){
				$tokens = $config["oauth"]["static_tokens"];
				if(!in_array($token, $tokens)) $this->unauth();
			}
		}
	}

	private function unauth()
	{
		throw new UnauthorizedHttpException("authorization_token");
	}
}