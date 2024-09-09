<?php
// src/Controller/ApiController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sensor;
use App\Entity\Wine;
use App\Entity\Measurement;
use Swagger\Annotations as SWG;


class ApiController extends AbstractController
{
    /**
     * @Route("/api/sensor", name="api_register_sensor", methods={"POST"})
     * @SWG\Post(
     *     summary="Register a new sensor",
     *     description="Creates a new sensor",
     *     @SWG\Response(
     *         response=201,
     *         description="Sensor created",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="name", type="string")
     *         )
     *     )
     * )
     */
    #[Route('api/sensor', methods: ['POST'])]
    public function registerSensor(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Decode JSON data from the request body
        $data = json_decode($request->getContent(), true);

        // Check if 'name' field exists in the decoded JSON data
        $name = $data['name'] ?? null;
        if (!$name) {
            return new JsonResponse(['error' => 'Name field is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Create and persist the new Sensor entity
        $sensor = new Sensor();
        $sensor->setName($name);
        $em->persist($sensor);
        $em->flush();

        return new JsonResponse(['status' => 'Sensor created!'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/api/sensors", name="api_get_sensors", methods={"GET"})
     * @SWG\Get(
     *     summary="Get sorted sensors",
     *     description="Returns a list of sensors sorted by name",
     *     @SWG\Response(
     *         response=200,
     *         description="Successful response",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="name", type="string")
     *             )
     *         )
     *     )
     * )
     */
    #[Route('api/sensors', methods: 'GET')]
    public function getSensors(EntityManagerInterface $em): JsonResponse
    {
        $sensors = $em->getRepository(Sensor::class)->findBy([], ['name' => 'ASC']);
        $data = [];

        $data = array_map(fn($sensor) => [
            'id' => $sensor->getId(),
            'name' => $sensor->getName(),
        ], $sensors);

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/wines/measurements", name="api_get_wines_with_measurements", methods={"GET"})
     * @SWG\Get(
     *     summary="Get wines with measurements",
     *     description="Returns a list of wines with their measurements",
     *     @SWG\Response(
     *         response=200,
     *         description="Successful response",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="name", type="string"),
     *                 @SWG\Property(property="year", type="integer"),
     *                 @SWG\Property(
     *                     property="measurements",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="year", type="integer"),
     *                         @SWG\Property(property="sensor", type="string"),
     *                         @SWG\Property(property="color", type="string"),
     *                         @SWG\Property(property="temperature", type="number"),
     *                         @SWG\Property(property="alcoholContent", type="number"),
     *                         @SWG\Property(property="ph", type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    #[Route('api/wines/measurements', methods: 'GET')]

    public function getWinesWithMeasurements(EntityManagerInterface $em): JsonResponse
    {
        $wines = $em->getRepository(Wine::class)->findAll();
        $data = [];

        foreach ($wines as $wine) {
            $measurements = [];
            foreach ($wine->getMeasurements() as $measurement) {
                $measurements[] = [
                    'year' => $measurement->getYear(),
                    'sensor' => $measurement->getSensor()->getName(),
                    'color' => $measurement->getColor(),
                    'temperature' => $measurement->getTemperature(),
                    'alcoholContent' => $measurement->getAlcoholContent(),
                    'ph' => $measurement->getPh(),
                ];
            }

            $data[] = [
                'id' => $wine->getId(),
                'name' => $wine->getName(),
                'year' => $wine->getYear(),
                'measurements' => $measurements,
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/measurement", name="api_register_measurement", methods={"POST"})
     * @SWG\Post(
     *     summary="Register a new measurement",
     *     description="Creates a new measurement for a wine",
     *     @SWG\Response(
     *         response=201,
     *         description="Measurement recorded",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="year", type="integer"),
     *             @SWG\Property(property="sensor_id", type="integer"),
     *             @SWG\Property(property="wine_id", type="integer"),
     *             @SWG\Property(property="color", type="string"),
     *             @SWG\Property(property="temperature", type="number"),
     *             @SWG\Property(property="alcoholContent", type="number"),
     *             @SWG\Property(property="ph", type="number")
     *         )
     *     )
     * )
     */
    #[Route('api/measurement', methods: 'POST')]
    public function registerMeasurement(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $measurement = new Measurement();
        $measurement->setYear($request->request->get('year'));
        $measurement->setSensor($em->getRepository(Sensor::class)->find($data['sensor_id']));
        $measurement->setWine($em->getRepository(Wine::class)->find($data['wine_id']));
        $measurement->setColor($data['color']);
        $measurement->setTemperature($data['temperature']);
        $measurement->setAlcoholContent($data['alcoholContent']);
        $measurement->setPh($data['ph']);
        $em->persist($measurement);
        $em->flush();

        return new JsonResponse(['status' => 'Measurement recorded!'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/api", name="app_api", methods={"GET"})
     * @SWG\Get(
     *     summary="Index",
     *     description="API Index",
     *     @SWG\Response(
     *         response=200,
     *         description="API Index",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="controller_name", type="string")
     *         )
     *     )
     * )
     */
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }
}
