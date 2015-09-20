<?php

namespace Tom32i\Phpillip\PropertyHandler;

use Tom32i\Phpillip\Behavior\PropertyHandlerInterface;
use Tom32i\Phpillip\Service\ContentRepository;

/**
 * Slug property handler
 */
class SlugPropertyHandler implements PropertyHandlerInterface
{
    /**
     * Content repository
     *
     * @var ContentRepository
     */
    protected $contentRepository;

    /**
     * {@inheritdoc}
     */
    public function getProperty()
    {
        return 'slug';
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
        return !isset($data[$this->getProperty()]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        return ContentRepository::getName($context['file']);
    }
}
