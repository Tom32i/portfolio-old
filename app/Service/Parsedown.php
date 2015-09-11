<?php

namespace Tom32i\Phpillip\Service;

use Parsedown as BaseParsedown;
use Tom32i\Phpillip\Service\Pygments;

/**
 * Parsedown
 */
class Parsedown extends BaseParsedown
{
    /**
     * Pygment highlighter
     *
     * @var Pygment
     */
    private $pygments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pygments = new Pygments();
    }

    /**
     * {@inheritdoc}
     */
    protected function blockCodeComplete($Block)
    {
        $Block = parent::blockCodeComplete($block);

        $Block['element']['text']['text'] = $this->pygments->highlight(
            $Block['element']['text']['text'],
            $this->getLanguage($Block)
        );

        return $Block;
    }

    /**
     * {@inheritdoc}
     */
    protected function blockFencedCodeComplete($Block)
    {
        $Block = parent::blockFencedCodeComplete($Block);

        $Block['element']['text']['text'] = $this->pygments->highlight(
            $Block['element']['text']['text'],
            $this->getLanguage($Block)
        );

        return $Block;
    }

    /**
     * Get language of the given block
     *
     * @param array $Block
     *
     * @return string
     */
    private function getLanguage($Block)
    {
        return substr($Block['element']['text']['attributes']['class'], strlen('language-'));
    }
}
