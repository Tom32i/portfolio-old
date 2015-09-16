<?php

namespace Tom32i\Portfolio\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tom32i\Phpillip\Service\Paginator;

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
    public function index(Request $request, Application $app, $page = 1)
    {
        $paginator = new Paginator($app['content_repository']->getContents('article', 'date', false));
        $articles  = $paginator->get($page);
        $content   = $app['twig']->render('blog/index.html.twig', [
            'articles' => $articles,
            'pages'    => $paginator->count(),
            'page'     => $page,
            'latest'   => $app['content_repository']->getContents('article', 'date', false, 5),
        ]);

        return new Response($content, 200, ['Last-Modified' => $articles[0]['lastModified']]);
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
        $content = $app['twig']->render('blog/article.html.twig', [
            'article' => $article,
            'latest'  => $app['content_repository']->getContents('article', 'date', false, 5),
        ]);

        return new Response($content, 200, ['Last-Modified' => $article['lastModified']]);
    }

    /**
     * RSS
     *
     * @param Request $request
     * @param Application $app
     *
     * @return Response
     */
    public function rss(Request $request, Application $app)
    {
        $items = array_map(
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
            $app['content_repository']->getContents('article', 'date', false)
        );

        $content = $app['twig']->render('@phpillip/rss.xml.twig', [
            'title'       => $app['config']['parameters']['meta']['blog']['title'],
            'description' => $app['config']['parameters']['meta']['blog']['description'],
            'webmaster'   => 'thomas.jarrand@gmail.com',
            'items'       => $items,
        ]);

        return new Response($content, 200, ['Content-Type' => 'application/rss+xml']);
    }
}
