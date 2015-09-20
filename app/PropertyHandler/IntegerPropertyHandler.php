<?php

namespace Tom32i\Phpillip\PropertyHandler;

use Tom32i\Phpillip\Behavior\PropertyHandlerInterface;

/**
 * Integer property handler
 */
class IntegerPropertyHandler implements PropertyHandlerInterface
{
    /**
     * Property name
     *
     * @var string
     */
    protected $property;

    /**
     * Constructor
     *
     * @param string $property
     */
    public function __construct($property)
    {
        $this->property = $property;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Is data supported?
     *
     * @param array $data
     *
     * @return boolean
     */
    public function isSupported(array $data)
    {
        return isset($data[$this->getProperty()]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        return intval($value);
    }
}
