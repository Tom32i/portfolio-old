<?php

namespace Tom32i\Portfolio\Controller;

use Phpillip\Application;
use Symfony\Component\HttpFoundation\Request;

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

        return [
            'languages' => $content->getContents('language', 'weight'),
            'methods'   => $content->getContents('method', 'weight'),
            'agencies'  => $content->getContents('agency', 'slug'),
            'projects'  => $content->getContents('project', 'date', false),
            'badges'    => $content->getContents('badge', 'weight'),
            'tools'     => $content->getContents('tool', 'weight'),
            'links'     => $content->getContents('link', 'weight'),
        ];
    }
}
