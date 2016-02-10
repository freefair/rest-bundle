<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 10.02.16
 * Time: 00:22
 */

namespace freefair\RestBundle\Entity;


interface AuthTokenInterface
{
	public function setAuthToken($token);
	public function getAuthToken();

	public function setScopes(array $scopes);
	public function getScopes();

	public function getConsumer();
	public function setConsumer(ConsumerInterface $consumer);

	public function getValidTill();
	public function setValidTill($timestamp);
}