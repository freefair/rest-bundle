<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 10.02.16
 * Time: 00:21
 */

namespace freefair\RestBundle\Entity;


interface ConsumerInterface
{
	/**
	 * @return string
	 */
	public function getClientId();

	/**
	 * @return string
	 */
	public function getClientSecret();

	/**
	 * @return array
	 */
	public function getRedirectUri();
}