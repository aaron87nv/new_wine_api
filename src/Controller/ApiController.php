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
use Swagger\Annotations as SWG;
use App\Entity\Sensor;
use App\Entity\Wine;
use App\Entity\Measurement;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     * @SWG\Post(
     *     summary="Login a user",
     *     description="Authenticates a user with username and password",
     *     @SWG\Response(
     *         response=200,
     *         description="Successful login",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="username", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="error", type="string")
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="username", type="string"),
     *             @SWG\Property(property="password", type="string")
     *         )
     *     )
     * )
     */
    #[Route('api/login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            return new JsonResponse(['error' => $error->getMessageKey()], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['username' => $lastUsername]);
    }

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
        $name = $request->request->get('name');
        if (!$name) {
            return new JsonResponse(['error' => 'Name field is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }
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
    #[Route('api/sensors')]
    public function getSensors(EntityManagerInterface $em): JsonResponse
    {
        $sensors = $em->getRepository(Sensor::class)->findBy([], ['name' => 'ASC']);
        $data = [];

        foreach ($sensors as $sensor) {
            $data[] = [
                'id' => $sensor->getId(),
                'name' => $sensor->getName(),
            ];
        }

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
    #[Route('api/wines/measurements')]

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
    #[Route('api/measurement', 'test')]
    public function registerMeasurement(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $measurement = new Measurement();
        $measurement->setYear($data['year']);
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
