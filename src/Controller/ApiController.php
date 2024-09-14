<?php

// src/Controller/ApiController.php

namespace App\Controller;

use App\Entity\Measurement;
use App\Entity\Sensor;
use App\Entity\Wine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/sensor", name="api_register_sensor", methods={"POST"})
     *
     * @SWG\Post(
     *     summary="Register a new sensor",
     *     description="Creates a new sensor",
     *
     *     @SWG\Response(
     *         response=201,
     *         description="Sensor created",
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="name", type="string")
     *         )
     *     )
     * )
     */
    #[Route('/api/sensor', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function registerSensor(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        if (!$name) {
            return new JsonResponse(['error' => 'Name field is required.'], Response::HTTP_BAD_REQUEST);
        }

        $sensor = new Sensor();
        $sensor->setName($name);
        $em->persist($sensor);
        $em->flush();

        return new JsonResponse(['status' => 'Sensor created!'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/sensor{id}", name="api_delete_sensor", methods={"DELETE"})
     *
     * @SWG\Delete(
     *     summary="Delete a sensor",
     *     description="Delete a sensor by ID",
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Sensor deleted",
     *     @SWG\Response(
     *          response=404,
     *          description="Measurement not found",
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *
     *     @SWG\Parameter(
     *         name="id",
     *         in="body",
     *         required=true,
     *         type="integer",
     *         description="The ID of the sensor to delete"
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="name", type="string")
     *         )
     *     )
     * )
     */
    #[Route('/api/sensor/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteSensor(int $id, EntityManagerInterface $em): JsonResponse
    {
        $sensor = $em->getRepository(Sensor::class)->find($id);

        if (!$sensor) {
            return new JsonResponse(['error' => 'Sensor not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($sensor);
        $em->flush();

        return new JsonResponse(['status' => 'Sensor deleted successfully'], Response::HTTP_OK);
    }

    /**
     * @Route("/api/sensors", name="api_get_sensors", methods={"GET"})
     *
     * @SWG\Get(
     *     summary="Get sorted sensors",
     *     description="Returns a list of sensors sorted by name",
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @SWG\Schema(
     *             type="array",
     *
     *             @SWG\Items(
     *                 type="object",
     *
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="name", type="string")
     *             )
     *         )
     *     )
     * )
     */
    #[Route('/api/sensors', methods: 'GET')]
    public function getSensors(EntityManagerInterface $em): JsonResponse
    {
        $sensors = $em->getRepository(Sensor::class)->findBy([], ['name' => 'ASC']);
        $data = [];

        $data = array_map(fn ($sensor) => [
            'id' => $sensor->getId(),
            'name' => $sensor->getName(),
        ], $sensors);

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/wines/measurements", name="api_get_wines_with_measurements", methods={"GET"})
     *
     * @SWG\Get(
     *     summary="Get wines with measurements",
     *     description="Returns a list of wines with their measurements",
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @SWG\Schema(
     *             type="array",
     *
     *             @SWG\Items(
     *                 type="object",
     *
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="name", type="string"),
     *                 @SWG\Property(property="year", type="integer"),
     *                 @SWG\Property(
     *                     property="measurements",
     *                     type="array",
     *
     *                     @SWG\Items(
     *                         type="object",
     *
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
    #[Route('/api/wines/measurements', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getWinesWithMeasurements(EntityManagerInterface $em): JsonResponse
    {
        $measurements = $em->getRepository(Measurement::class)->findAll();
        $data = [];

        foreach ($measurements as $measurement) {
            $wine = $measurement->getWine();

            if ($wine) {
                $data[] = [
                    'id' => $wine->getId(),
                    'name' => $wine->getName(),
                    'year' => $wine->getYear(),
                    'measurements' => [
                        'year' => $measurement->getYear(),
                        'sensor' => $measurement->getSensor() ? $measurement->getSensor()->getName() : null,
                        'color' => $measurement->getColor(),
                        'temperature' => $measurement->getTemperature(),
                        'alcoholContent' => $measurement->getAlcoholContent(),
                        'ph' => $measurement->getPh(),
                    ],
                ];
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/measurement", name="api_register_measurement", methods={"POST"})
     *
     * @SWG\Post(
     *     summary="Register a new measurement",
     *     description="Creates a new measurement for a wine",
     *
     *     @SWG\Response(
     *         response=201,
     *         description="Measurement recorded",
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *
     *         @SWG\Schema(
     *             type="object",
     *
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
    #[Route('/api/measurement', methods: 'POST')]
    #[IsGranted('ROLE_USER')]
    public function registerMeasurement(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $measurement = new Measurement();
        $measurement->setYear($data['year']);

        // Find the sensor
        $sensor = $em->getRepository(Sensor::class)->find($data['sensor_id']);
        if (!$sensor) {
            return new JsonResponse(['error' => 'Sensor not found'], Response::HTTP_NOT_FOUND);
        }
        $measurement->setSensor($sensor);

        $wine = new Wine();
        $wine->setName($data['wine_name']);
        $wine->setYear($data['year']);

        $measurement->setWine($wine);

        $measurement->setColor($data['color']);
        $measurement->setTemperature($data['temperature']);
        $measurement->setAlcoholContent($data['alcoholContent']);
        $measurement->setPh($data['ph']);

        $em->persist($measurement);
        $em->flush();

        return new JsonResponse(['status' => 'Measurement recorded!'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/measurement/{id}", name="api_delete_measurement", methods={"DELETE"})
     *
     * @SWG\Delete(
     *     summary="Delete a measurement",
     *     description="Deletes a  measurement by ID",
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Measurement deleted",
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="status", type="string")
     *         )
     *     ),
     *
     *     @SWG\Response(
     *         response=404,
     *         description="Measurement not found",
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         type="integer",
     *         description="The ID of the measurement to delete"
     *     )
     * )
     */
    #[Route('/api/measurement/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteMeasurement(int $id, EntityManagerInterface $em): JsonResponse
    {
        $measurement = $em->getRepository(Measurement::class)->find($id);

        if (!$measurement) {
            return new JsonResponse(['error' => 'Measurement not found'], Response::HTTP_NOT_FOUND);
        }

        $wine = $measurement->getWine();

        $em->remove($measurement);
        $em->remove($wine);
        $em->flush();

        return new JsonResponse(['status' => 'Measurement and associated wine deleted successfully'], Response::HTTP_OK);
    }

    /**
     * @Route("/api", name="app_api", methods={"GET"})
     *
     * @SWG\Get(
     *     summary="Index",
     *     description="API Index",
     *
     *     @SWG\Response(
     *         response=200,
     *         description="API Index",
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="controller_name", type="string")
     *         )
     *     )
     * )
     */
    #[Route('/api', methods: 'GET')]
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }
}
