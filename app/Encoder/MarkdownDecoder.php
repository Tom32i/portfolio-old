<?php

namespace Tom32i\Phpillip\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Yaml\Yaml;
use Tom32i\Phpillip\Service\Parsedown;

/**
 * Encodes Markdown data
 */
class MarkdownDecoder implements DecoderInterface
{
    /**
     * Supported format
     */
    const FORMAT = 'markdown';

    /**
     * Head separator
     */
    const HEAD_SEPARATOR = '---';

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = array())
    {
        $separator = static::HEAD_SEPARATOR;
        $start     = strpos($data, $separator);
        $stop      = strpos($data, $separator, 1);
        $length    = strlen($separator) + 1;

        if ($start === 0 && $stop) {
            return array_merge(
                $this->parseYaml(substr($data, $start + $length, $stop - $length)),
                ['content' => $this->markdownify(substr($data, $stop + $length))]
            );
        }

        return ['content' => $this->markdownify($data)];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * Parse YAML
     *
     * @param string $data
     *
     * @return array
     */
    private function parseYaml($data)
    {
        return Yaml::parse($data, true);
    }

    /**
     * Parse Mardown to return HTML
     *
     * @param string $data
     *
     * @return string
     */
    private function markdownify($data)
    {
        $parser = new Parsedown();

        return $parser->parse($data);
    }
}
