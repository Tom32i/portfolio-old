<?php

namespace Tom32i\Portfolio\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * App Controller
 */
class AppController
{
    /**
     * Index
     *
     * @param Request $request
     * @param Application $app
     *
     * @return Response
     */
    public function index(Request $request, Application $app)
    {
        $content = $app['content_repository'];

        return $app['twig']->render('index.html.twig', [
            'language' => $content->getContents('language'),
        ]);
    }
}
