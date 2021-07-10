<?php


namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthcheckController extends AbstractController
{
    /**
     * @Route("/health", methods={"GET","HEAD"}, name="healthcheck")
     */
    public function health(): Response
    {
        $response = new Response();
        $response->setContent(json_encode(['status'=>'ok', 'date'=>date(DATE_W3C)]));
        return $response;
    }
    /**
     * @Route("/health/error", methods={"GET","HEAD"}, name="healthcheck-error")
     */
    public function intendedError(): Response
    {
        throw new \Exception("something went wrong");
    }
}
