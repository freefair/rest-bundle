<?php

namespace freefair\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('RestBundle:Default:index.html.twig');
    }
}
