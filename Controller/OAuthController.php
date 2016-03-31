<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 10.02.16
 * Time: 01:10
 */

namespace freefair\RestBundle\Controller;


use DateTime;
use freefair\RestBundle\Entity\AuthCodeInterface;
use freefair\RestBundle\Entity\ConsumerInterface;
use freefair\RestBundle\Models\TokenModel;
use freefair\RestBundle\Services\OAuthService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthController extends RestController
{
	/**
	 * @return OAuthService
	 */
	private function getService()
	{
		return $this->get('rest.oauth_service');
	}

	/**
	 * @ParamConverter(name="model", converter="rest_converter")
	 */
	public function tokenAction(TokenModel $model)
	{
		if($model->grant_type == "authorization_code") {
			$validateClient = $this->getService()->validateClient($model->client_id, $model->client_secret, $model->redirect_uri);
			if(!$validateClient) return $this->invalidClient();
			/** @var AuthCodeInterface $authCode */
			$authCode = $this->getService()->getAuthCode($model->code);
			if($authCode->getValidTill() < (new DateTime())->getTimestamp()) return $this->invalidClient();
			$authTokenInterface = $this->getService()->createAuthTokenFromCode($authCode);
			$accessToken = $authTokenInterface->getAuthToken();
			$validTill = $authTokenInterface->getValidTill();
		} else if ($model->grant_type == "password") {
			return $this->authPassword($model);
		} else {
			return $this->invalidClient();
		}

		return $this->createAccessTokenResponse($accessToken, $validTill);
	}

	private function invalidClient()
	{
		return $this->restResult(array("error" => "invalid_client"), 403);
	}

	private function authPassword(TokenModel $model)
	{
		$validateClient = $this->getService()->validateClient($model->client_id, $model->client_secret, $model->redirect_uri);
		if(!$validateClient) return $this->invalidClient();

		$persistence = $this->getParameter("rest.config")["authentication"]["oauth"]["persistence"];
		$user_entity = $persistence["user_entity"];
		$client_entity = $persistence["consumer_entity"];

		/** @var UserInterface $user */
		$user = $this->getDoctrine()->getRepository($user_entity)->findOneBy(array("username" => $model->username));
		if($user == null) return $this->invalidClient();

		$encoder_service = $this->get('security.encoder_factory');
		$encoder = $encoder_service->getEncoder($user_entity);
		$encoded_pass = $encoder->encodePassword($model->password, $user->getSalt());

		if($encoded_pass != $user->getPassword()) return $this->invalidClient();

		/** @var ConsumerInterface $client */
		$client =$this->getDoctrine()->getRepository($client_entity)->findOneBy(array('client_id' => $model->client_id));

		$authCodeInterface = $this->getService()->createAuthCode(explode(" ", $model->scope), $client, $model->redirect_uri);
		$authTokenInterface = $this->getService()->createAuthTokenFromCode($authCodeInterface);

		return $this->createAccessTokenResponse($authTokenInterface->getAuthToken(), $authTokenInterface->getValidTill());
	}

	/**
	 * @param $accessToken
	 * @param $validTill
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function createAccessTokenResponse($accessToken, $validTill)
	{
		return $this->restResult(array("access_token" => $accessToken, "expires_in" => $validTill - (new DateTime())->getTimestamp()));
	}
}