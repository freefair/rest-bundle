<?php

namespace freefair\RestBundle\Controller;

use freefair\RestBundle\Entity\AuthTokenInterface;
use freefair\RestBundle\Entity\ConsumerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;

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

	/**
	 * @return UserInterface
	 */
	protected function getUser() {
		/** @var Session $session */
		$session = $this->get("session");
		return $session->get("user");
	}

	/**
	 * @return ConsumerInterface
	 */
	protected function getConsumer() {
		/** @var Session $session */
		$session = $this->get("session");
		return $session->get("consumer");
	}

	/**
	 * @return string[]
	 */
	protected function getScopes() {
		/** @var Session $session */
		$session = $this->get("session");
		/** @var AuthTokenInterface $token */
		$token = $session->get("token");
		return $token->getScopes();
	}
}