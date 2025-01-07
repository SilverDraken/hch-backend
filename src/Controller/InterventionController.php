<?php

namespace App\Controller;

use App\Document\Intervention;
use App\Document\Zone;
use App\Document\User;
use App\Document\Client;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InterventionController extends AbstractController
{
    /**
     * @Route("/interventions", name="get_interventions", methods={"GET"})
     */
    public function getInterventions(DocumentManager $dm): JsonResponse
    {
        $interventions = $dm->getRepository(Intervention::class)->findAll();

        $data = [];
        foreach ($interventions as $intervention) {
            $data[] = [
                'id' => $intervention->getId(),
                'type' => $intervention->getType(),
                'createdDate' => $intervention->getCreatedDate()->format('Y-m-d'),
                'dateOfIntervention' => $intervention->getDateOfIntervention()->format('Y-m-d'),
                'time' => $intervention->getTime(), // Include the time field
                'length' => $intervention->getLength(),
                'bikeModel' => $intervention->getBikeModel(),
                'client' => [
                    'name' => $intervention->getClient()->getName(),
                    'address' => $intervention->getClient()->getAddress(),
                    'email' => $intervention->getClient()->getEmail(),
                    'phoneNumber' => $intervention->getClient()->getPhoneNumber(),
                ],
                'zone' => $intervention->getZone() ? $intervention->getZone()->getName() : null,
                'technician' => $intervention->getTechnician() ? $intervention->getTechnician()->getUsername() : null,
            ];
        }

        return $this->json($data);
    }

    /**
     * @Route("/interventions/{id}", name="get_intervention", methods={"GET"})
     */
    public function getIntervention(string $id, DocumentManager $dm): JsonResponse
    {
        $intervention = $dm->getRepository(Intervention::class)->find($id);

        if (!$intervention) {
            return new JsonResponse(['message' => 'Intervention not found'], 404);
        }

        $data = [
            'id' => $intervention->getId(),
            'type' => $intervention->getType(),
            'dateOfIntervention' => $intervention->getDateOfIntervention()->format('Y-m-d'),
            'time' => $intervention->getTime(),
            'length' => $intervention->getLength(),
            'bikeModel' => $intervention->getBikeModel(),
            'client' => [
                'name' => $intervention->getClient()->getName(),
                'address' => $intervention->getClient()->getAddress(),
                'email' => $intervention->getClient()->getEmail(),
                'phoneNumber' => $intervention->getClient()->getPhoneNumber(),
            ],
            'zone' => $intervention->getZone() ? $intervention->getZone()->getName() : null,
            'technician' => $intervention->getTechnician() ? $intervention->getTechnician()->getUsername() : null,
        ];

        return new JsonResponse($data);
    }

     /**
     * @Route("/interventions/{id}", name="update_intervention", methods={"PUT"})
     */
    public function updateIntervention(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $intervention = $dm->getRepository(Intervention::class)->find($id);

        if (!$intervention) {
            return new JsonResponse(['message' => 'Intervention not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Update intervention fields
        $intervention->setType($data['type']);
        $intervention->setDateOfIntervention(new \DateTime($data['dateOfIntervention']));
        $intervention->setTime($data['time']);
        $intervention->setLength($data['length']);
        $intervention->setBikeModel($data['bikeModel']);

        // Update client details
        $client = $intervention->getClient();
        $client->setName($data['client']['name']);
        $client->setAddress($data['client']['address']);
        $client->setEmail($data['client']['email']);
        $client->setPhoneNumber($data['client']['phoneNumber']);
        $intervention->setClient($client);

        // Update zone and technician if provided
        $zone = $dm->getRepository(Zone::class)->find($data['zoneId']);
        $intervention->setZone($zone);

        $technician = $dm->getRepository(User::class)->find($data['technicianId']);
        $intervention->setTechnician($technician);

        // Save changes
        $dm->flush();

        return new JsonResponse(['message' => 'Intervention updated successfully']);
    }

    /**
     * @Route("/interventions", name="create_intervention", methods={"POST"})
     */
    public function createIntervention(Request $request, DocumentManager $dm): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $intervention = new Intervention();
    $intervention->setType($data['type']);
    $intervention->setCreatedDate(new \DateTime());
    $intervention->setDateOfIntervention(new \DateTime($data['dateOfIntervention']));

    // Parse and validate the time
    $time = $data['time'];
    if (!preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $time)) {
        return new JsonResponse(['error' => 'Invalid time format'], 400);
    }
    $intervention->setTime($time);

    $intervention->setLength($data['length']);

    $client = new Client();
    $client->setName($data['client']['name']);
    $client->setAddress($data['client']['address']);
    $client->setEmail($data['client']['email']);
    $client->setPhoneNumber($data['client']['phoneNumber']);
    $intervention->setClient($client);

    if (isset($data['zoneId'])) {
        $zone = $dm->getRepository(Zone::class)->find($data['zoneId']);
        $intervention->setZone($zone);
    }

    if (isset($data['technicianId'])) {
        $technician = $dm->getRepository(User::class)->find($data['technicianId']);
        $intervention->setTechnician($technician);
    }

    $dm->persist($intervention);
    $dm->flush();

    return $this->json(['message' => 'Intervention created successfully'], 201);
}


    /**
     * @Route("/interventions/{id}", name="delete_intervention", methods={"DELETE"})
     */
    public function deleteIntervention(string $id, DocumentManager $dm): JsonResponse
    {
        $intervention = $dm->getRepository(Intervention::class)->find($id);
        if (!$intervention) {
            return $this->json(['message' => 'Intervention not found'], 404);
        }

        $dm->remove($intervention);
        $dm->flush();

        return $this->json(['message' => 'Intervention deleted successfully']);
    }

    /**
 * @Route("/interventions/technician/{technicianId}", name="get_technician_interventions", methods={"GET"})
 */
public function getInterventionsByTechnician(string $technicianId, DocumentManager $dm): JsonResponse
{
    $technician = $dm->getRepository(User::class)->find($technicianId);
    if (!$technician) {
        return $this->json(['message' => 'Technician not found'], 404);
    }

    $interventions = $dm->getRepository(Intervention::class)
        ->findBy(['technician' => $technician]);

    $data = [];
    foreach ($interventions as $intervention) {
        $data[] = [
            'id' => $intervention->getId(),
            'type' => $intervention->getType(),
            'dateOfIntervention' => $intervention->getDateOfIntervention()->format('Y-m-d'),
            'zone' => $intervention->getZone() ? $intervention->getZone()->getName() : null,
        ];
    }

    return $this->json($data);
}
}
