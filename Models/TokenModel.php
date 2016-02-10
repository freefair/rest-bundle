<?php

namespace freefair\RestBundle\Models;

use freefair\RestBundle\Annotations\Serialize;

class TokenModel
{
	/**
	 * @Serialize
	 */
	public $code;
	/**
	 * @Serialize
	 */
	public $client_id;
	/**
	 * @Serialize
	 */
	public $client_secret;
	/**
	 * @Serialize
	 */
	public $redirect_uri;
	/**
	 * @Serialize
	 */
	public $scope;
	/**
	 * @Serialize
	 */
	public $grant_type;
	/**
	 * @Serialize
	 */
	public $username;
	/**
	 * @Serialize
	 */
	public $password;
}