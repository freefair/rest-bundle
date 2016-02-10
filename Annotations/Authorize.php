<?php
namespace freefair\RestBundle\Annotations;

/**
 * @Annotation
 */
class Authorize
{
	/**
	 * @var array
	 */
	private $scopes;

	public function __construct($options)
	{
		if (isset($options['value'])) {
			$options['scopes'] = $options['value'];
			unset($options['value']);
		}

		foreach ($options as $key => $value) {
			if (!property_exists($this, $key)) {
				throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
			}

			$this->$key = $value;
		}
	}

	public function getScopes()
	{
		return $this->scopes;
	}
}