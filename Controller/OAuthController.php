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
use freefair\RestBundle\Models\TokenModel;
use freefair\RestBundle\Services\OAuthService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class OAuthController extends RestController
{
	/**
	 * @return OAuthService
	 */
	private function getService() {
		return $this->container->get('rest.oauth_service');
	}

	/**
	 * @ParamConverter(name="model", converter="rest_converter")
	 */
	public function tokenAction(TokenModel $model) {
		$accessToken = "";
		$validTill = "";

		if($model->grant_type == "authorization_code") {
			$validateClient = $this->getService()->validateClient($model->client_id, $model->client_secret, $model->redirect_uri);
			if(!$validateClient) return $this->invalidClient();
			/** @var AuthCodeInterface $authCode */
			$authCode = $this->getService()->getAuthCode($model->code);
			if($authCode->getValidTill() < (new DateTime())->getTimestamp()) return $this->invalidClient();
			$authTokenInterface = $this->getService()->createAuthTokenFromCode($authCode);
			$accessToken = $authTokenInterface->getAuthToken();
			$validTill = $authTokenInterface->getValidTill();
		}

		return $this->restResult(array("access_token" => $accessToken, "expires_in" => $validTill - (new DateTime())->getTimestamp()));
	}

	private function invalidClient()
	{
		return $this->restResult(array("error" => "invalid_client"), 403);
	}
}