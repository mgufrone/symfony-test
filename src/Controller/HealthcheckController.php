<?php


namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Mgufrone\HealthcheckBundle\Healthcheck;
use Symfony\Component\Routing\Annotation\Route;

class HealthcheckController extends AbstractController
{
    private $health;
    public function __construct()
    {
        $this->health = new Healthcheck(dirname(dirname(__DIR__)));
    }

    /**
     * @Route("/health", methods={"GET","HEAD"}, name="healthcheck")
     */
    public function health(): Response
    {
        $response = new JsonResponse();
        $response->setContent(json_encode($this->health->health()));
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
