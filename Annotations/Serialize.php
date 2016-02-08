<?php
namespace freefair\RestBundle\Annotations;

/**
 * @Annotation
 */
class Serialize
{
	private $className;
	private $name;

	public function __construct($options)
	{
		if (isset($options['value'])) {
			$options['name'] = $options['value'];
			unset($options['value']);
		}

		foreach ($options as $key => $value) {
			if (!property_exists($this, $key)) {
				throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
			}

			$this->$key = $value;
		}
	}

	public function getClassName()
	{
		return $this->className;
	}

	public function getName() {
		return $this->name;
	}
}