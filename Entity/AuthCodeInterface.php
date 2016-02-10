<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 10.02.16
 * Time: 00:23
 */

namespace freefair\RestBundle\Entity;


interface AuthCodeInterface
{
	public function getAuthCode();
	public function setAuthCode($code);

	public function getRedirectUrl();
	public function setRedirectUrl($redirectUrl);

	public function getConsumer();
	public function setConsumer(ConsumerInterface $consumer);

	public function getScopes();
	public function setScopes(array $scopes);

	public function getValidTill();
	public function setValidTill($timestamp);
}