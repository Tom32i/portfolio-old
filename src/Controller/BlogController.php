<?php

namespace Tom32i\Portfolio\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     *
     * @return Response
     */
    public function index(Request $request, Application $app, $page = 1)
    {
        $paginator = new Paginator($app['content_repository']->getContents('article', 'date', false));

        return $app['twig']->render('blog/index.html.twig', [
            'articles' => $paginator->get($page),
            'pages'    => $paginator->count(),
            'page'     => $page,
            'latest'   => $app['content_repository']->getContents('article', 'date', false, 5),
        ]);
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
        return $app['twig']->render('blog/article.html.twig', [
            'article' => $article,
            'latest'  => $app['content_repository']->getContents('article', 'date', false, 5),
        ]);
    }
}
