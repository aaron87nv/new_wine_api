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

    #[Route('/api', methods: 'GET')]
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }
}
