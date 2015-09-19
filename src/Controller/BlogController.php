<?php

namespace Tom32i\Portfolio\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tom32i\Phpillip\Model\Paginator;

/**
 * Blog Controller
 */
class BlogController
{
    /**
     * List
     *
     * @param Request $request
     * @param Application $app
     * @param integer $page
     *
     * @return Response
     */
    public function index(Request $request, Application $app, Paginator $paginator, array $articles, $page = 1)
    {
        return [
            'articles' => $articles,
            'pages'    => $paginator->count(),
            'page'     => $page,
        ];
    }

    /**
     * Show
     *
     * @param Request $request
     * @param Application $app
     * @param array $article
     *
     * @return Response
     */
    public function article(Request $request, Application $app, array $article)
    {
        return ['article' => $article];
    }

    /**
     * List latest articles
     *
     * @param Request $request
     * @param Application $app
     * @param array $articles
     *
     * @return string
     */
    public function latest(Request $request, Application $app, array $articles)
    {
        return ['articles' => array_slice($articles, 0, 5)];
    }

    /**
     * RSS
     *
     * @param Request $request
     * @param Application $app
     * @param array $articles
     *
     * @return Response
     */
    public function feed(Request $request, Application $app, array $articles)
    {
        return [
            'title'       => $app['parameters']['meta']['blog']['title'],
            'description' => $app['parameters']['meta']['blog']['description'],
            'webmaster'   => 'thomas.jarrand@gmail.com',
            'items'       => array_map(
                function ($article) use ($app){
                    return [
                        'title'       => $article['title'],
                        'description' => $article['description'],
                        'guid'        => $article['slug'],
                        'pubDate'     => $article['date'],
                        'link'        => $app['url_generator']->generate(
                            'article',
                            ['article' => $article['slug']],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )
                    ];
                },
                $articles
            ),
        ];
    }
}
