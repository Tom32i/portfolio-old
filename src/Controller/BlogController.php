<?php

namespace Tom32i\Portfolio\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


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
    public function index(Request $request, Application $app)
    {
        return $app['twig']->render('blog/index.html.twig', [
            'articles' => $app['content_repository']->getContents('article'),
        ]);
    }

    /**
     * Show
     *
     * @param Request $request
     * @param Application $app
     * @param string $article
     *
     * @return Response
     */
    public function article(Request $request, Application $app, $article)
    {
        return $app['twig']->render('blog/article.html.twig', [
            'article' => $app['content_repository']->getContent('article', $article),
        ]);
    }
}
