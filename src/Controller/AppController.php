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
            'languages' => $content->getContents('language', 'weight'),
            'methods'   => $content->getContents('method', 'weight'),
            'agencies'  => $content->getContents('agency', 'slug'),
            'projects'  => $content->getContents('project', 'date', false),
            'badges'    => $content->getContents('badge', 'weight'),
            'tools'     => $content->getContents('tool'),
            'links'     => $content->getContents('link', 'weight'),
        ]);
    }
}
