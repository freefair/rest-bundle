<?php
namespace freefair\RestBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use freefair\RestBundle\Entity\AuthCodeInterface;
use freefair\RestBundle\Entity\AuthTokenInterface;
use freefair\RestBundle\Entity\ConsumerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Validator\Constraints\DateTime;

class OAuthService
{
	public function __construct($container)
	{
		$this->container = $container;
	}

	public function createAuthCode(array $scopes, ConsumerInterface $consumer, $redirect_url)
	{
		$code = $this->base64url_encode($this->random_string(16));

		$oauth = $this->container->getParameter("rest.config")["authentication"]["oauth"];
		$parameter = $oauth["persistence"];
		$auth_code_entity = $parameter["auth_code_entity"];
		/** @var AuthCodeInterface $entity */
		$entity = new $auth_code_entity();

		$entity->setAuthCode($code);
		$entity->setRedirectUrl($redirect_url);
		$entity->setConsumer($consumer);
		$entity->setScopes($scopes);
		$dt = new \DateTime();
		$entity->setValidTill($dt->getTimestamp() + $oauth["code_lifetime"]);

		$manager = $this->getDoctrine()->getManager();
		$manager->persist($entity);
		$manager->flush();

		return $entity;
	}

	public function createAuthTokenFromCode(AuthCodeInterface $authCode){
		$code = $this->base64url_encode($this->random_string(17));

		$oauth = $this->container->getParameter("rest.config")["authentication"]["oauth"];
		$parameter = $oauth["persistence"];
		$auth_token_entity = $parameter["auth_token_entity"];
		/** @var AuthTokenInterface $entity */
		$entity = new $auth_token_entity();

		$entity->setAuthToken($code);
		$entity->setScopes($authCode->getScopes());
		$entity->setConsumer($authCode->getConsumer());
		$dt = new \DateTime();
		$entity->setValidTill($dt->getTimestamp() + $oauth["token_lifetime"]);

		$manager = $this->getDoctrine()->getManager();
		$manager->persist($entity);
		$manager->remove($authCode);
		$manager->flush();

		return $entity;
	}

	public function validateClient($client_id, $client_secret, $redirect_uri)
	{
		$consumer_entity = $this->container->getParameter("rest.config")["authentication"]["oauth"]["persistence"]["consumer_entity"];
		/** @var ConsumerInterface[] $by */
		$by = $this->getDoctrine()->getManager()->getRepository($consumer_entity)->findBy(array("client_id" => $client_id));
		if($by == null || count($by) != 1) return false;
		return $by[0]->getClientSecret() == $client_secret && in_array($redirect_uri, $by[0]->getRedirectUri());
	}

	public function getAuthCode($code)
	{
		$auth_code_entity = $this->container->getParameter("rest.config")["authentication"]["oauth"]["persistence"]["auth_code_entity"];
		return $this->getDoctrine()->getManager()->getRepository($auth_code_entity)->findBy(array("authCode" => $code))[0];
	}

	/**
	 * @param $token
	 * @return AuthTokenInterface
	 */
	public function getAuthToken($token)
	{
		$auth_token_entity = $this->container->getParameter("rest.config")["authentication"]["oauth"]["persistence"]["auth_token_entity"];
		return $this->getDoctrine()->getManager()->getRepository($auth_token_entity)->findOneBy(array("authToken" => $token));
	}

	private function base64url_encode($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	private function random_string($length = 32) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $randstring = '';
	    for ($i = 0; $i < $length; $i++) {
		    $randstring .= $characters[rand(0, strlen($characters)-1)];
	    }
	    return $randstring;
	}

	/**
	 * @return Registry
	 */
	private function getDoctrine() {
		return $this->container->get('doctrine');
	}
}