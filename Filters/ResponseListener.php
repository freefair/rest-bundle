<?php
namespace freefair\RestBundle\Filters;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ResponseListener
{
	/**
	 * @var \Exception
	 */
	private static $exception;

	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function onKernelResponse(FilterResponseEvent $event) {
		$arr = $event->getRequest()->headers->get("accept");
		if(is_array($arr) && (in_array("text/html", $arr) || in_array("*/*", $arr))) return;
		$response = $event->getResponse();
		$error = $response->isServerError() || $event->getResponse()->isClientError();
		if($error) {
			$result = array();
			$result["status"] = $response->getStatusCode();
			if(self::$exception != null) {
				$result["message"] = self::$exception->getMessage();
				$result["stacktrace"] = self::$exception->getTraceAsString();
			} else {
				$result["message"] = "unknown";
				$result["stacktrace"] = "";
			}
			$classParser = $this->container->get("rest.internal_class_parser");
			$content = $classParser->serializeObject($result, true);
			$response->setContent($content["result"]);
			$response->headers->add(array("content-type" => $content["type"]));
		}
	}

	public function onException(GetResponseForExceptionEvent $event) {
		self::$exception = $event->getException();
	}
}