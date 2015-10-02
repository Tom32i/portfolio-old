<?php

use Phpillip\Application as BaseApplication;

/**
 * Your Phpillip application
 */
class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->get('/', 'Tom32i\\Portfolio\\Controller\\AppController::index')
            ->template('index.html.twig')
            ->bind('homepage');

        $this->get('/blog', 'Tom32i\\Portfolio\\Controller\\BlogController::index')
            ->paginate('article', 'date', false)
            ->bind('blog');

        $this->get('blog-latest', 'Tom32i\\Portfolio\\Controller\\BlogController::latest')
            ->contents('article', 'date', false)
            ->hide()
            ->bind('blog_latest');

        $this->get('/blog/feed.rss', 'Tom32i\\Portfolio\\Controller\\BlogController::feed')
            ->contents('article', 'date', false)
            ->template('@phpillip/rss.xml.twig')
            ->hideFromSitemap()
            ->bind('blog_rss');

        $this->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')
            ->content('article')
            ->bind('article');
    }
}
