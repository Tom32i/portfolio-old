<?php

namespace Tom32i\Phpillip\PropertyHandler;

use Exception;
use DateTime;
use Tom32i\Phpillip\Behavior\PropertyHandlerInterface;

/**
 * DateTime property handler
 */
class DateTimePropertyHandler implements PropertyHandlerInterface
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
    public function __construct($property = 'date')
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
        try {
            new DateTime($data[$this->getProperty()]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        return new DateTime($value);
    }
}
