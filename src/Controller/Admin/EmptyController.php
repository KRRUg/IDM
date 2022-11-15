<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmptyController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        return new Response();
    }
}
