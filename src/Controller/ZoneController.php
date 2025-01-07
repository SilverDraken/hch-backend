<?php

namespace App\Controller;

use App\Document\Zone; 
use App\Document\User;
use App\Document\Intervention;
use Doctrine\ODM\MongoDB\DocumentManager; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ZoneController extends AbstractController
{

    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function matchAddress(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $address = $data['address'] ?? null;

    if (!$address) {
        return new JsonResponse(['error' => 'Address is required'], 400);
    }

    $coordinates = $this->geocodeAddress($address);

    if (!$coordinates) {
        return new JsonResponse(['error' => 'Could not geocode address'], 400);
    }

    $zones = $this->dm->getRepository(Zone::class)->findAll();

    foreach ($zones as $zone) {
        if ($this->isPointInPolygon([$coordinates['lng'], $coordinates['lat']], $zone->getGeometry()['coordinates'][0])) {
            $technician = $zone->getTechnician() ?: 'Unassigned Technician'; // Handle null value
            return new JsonResponse([
                'zoneId' => $zone->getId(),
                'technicianId' => $technician instanceof User ? $technician->getId() : null,
                'zone' => $zone->getName(),
                'technician' => $technician instanceof User ? $technician->getUsername() : 'Unassigned Technician',
                'availability' => $this->getTechnicianAvailability($technician->getId(), $this->dm),
                
            ]);            
        }
    }

    return new JsonResponse(['error' => 'No matching zone found'], 404);
}


    private function geocodeAddress(string $address): ?array
{
    $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($address) . '&format=json';

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Home Cycl Home/1.0 (lucas.ducau64@gmail.com)\r\n"
        ]
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (!empty($data[0])) {
        return [
            'lat' => $data[0]['lat'],
            'lng' => $data[0]['lon']
        ];
    }

    return null;
}


    private function isPointInPolygon(array $point, array $polygon): bool
    {
        $x = $point[0]; // Longitude
        $y = $point[1]; // Latitude
        $inside = false;

        $numPoints = count($polygon);
        for ($i = 0, $j = $numPoints - 1; $i < $numPoints; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
    public function getTechnicianAvailability(string $technicianId, DocumentManager $dm): JsonResponse
    {
        // Fetch the technician from the database
        $technician = $dm->getRepository(User::class)->find($technicianId);
    
        if (!$technician) {
            return new JsonResponse(['error' => 'Technician not found'], 404);
        }
    
        // Fetch interventions assigned to this technician
        $interventions = $dm->getRepository(Intervention::class)->findBy(['technician' => $technician]);
    
        // Define working hours (optional, adjust as needed)
        $workingHours = [
            'start' => '09:00',
            'end' => '17:00',
        ];
    
        // Calculate availability
        $availability = $this->calculateTechnicianAvailability($interventions, $workingHours);
    
        return new JsonResponse($availability);
    }

    private function calculateTechnicianAvailability(array $interventions, array $workingHours): array
{
    // Define working hours
    $startTime = new \DateTime($workingHours['start']);
    $endTime = new \DateTime($workingHours['end']);

    // Define time slot length (e.g., 30 minutes)
    $slotLength = new \DateInterval('PT30M');

    // Collect busy slots from existing interventions
    $busySlots = [];
    foreach ($interventions as $intervention) {
        // Parse the "time" field (e.g., "09:00-09:30")
        $timeRange = explode('-', $intervention->getTime());
        if (count($timeRange) !== 2) {
            throw new \InvalidArgumentException('Invalid time format in intervention.');
        }

        $start = new \DateTime($timeRange[0]); // Start time
        $end = new \DateTime($timeRange[1]);   // End time

        $busySlots[] = ['start' => $start, 'end' => $end];
    }

    // Sort busy slots by start time
    usort($busySlots, fn($a, $b) => $a['start'] <=> $b['start']);

    // Generate all time slots for the working day
    $availableSlots = [];
    $currentSlot = clone $startTime;

    while ($currentSlot < $endTime) {
        $nextSlot = (clone $currentSlot)->add($slotLength);

        // Check if this slot overlaps with any busy slot
        $isAvailable = true;
        foreach ($busySlots as $busy) {
            if ($currentSlot < $busy['end'] && $nextSlot > $busy['start']) {
                $isAvailable = false;
                break;
            }
        }

        // If slot is available, add to the list
        if ($isAvailable) {
            $availableSlots[] = [
                'start' => $currentSlot->format('H:i'),
                'end' => $nextSlot->format('H:i'),
            ];
        }

        $currentSlot = $nextSlot;
    }

    return $availableSlots;
} 

    public function getZones(DocumentManager $dm): JsonResponse
    {
        // Fetch all zones from the database
        $zones = $dm->getRepository(Zone::class)->findAll();
    
        // Manually transform zones into an array
        $data = array_map(function ($zone) {
            return [
                'id' => $zone->getId(),
                'name' => $zone->getName(),
                'description' => $zone->getDescription(),
                'color' => $zone->getColor(),
                'geometry' => $zone->getGeometry(), // Include geometry
                'technician' => $zone->getTechnician() ? $zone->getTechnician()->getUsername() : null, // Fetch technician name
                'technicianId' => $zone->getTechnician() ? $zone->getTechnician()->getId() : null // Fetch technician ID
            ];
        }, $zones);
    
        return new JsonResponse($data);
    }
    

    public function createZone(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $zone = new Zone();
        $zone->setName($data['name'] ?? null);
        $zone->setDescription($data['description'] ?? null);
        $zone->setColor($data['color'] ?? '#3388ff');
        if (isset($data['technicianId']) && $data['technicianId']) {
            $technician = $dm->getRepository(User::class)->find($data['technicianId']);
            if (!$technician) {
                return new JsonResponse(['error' => 'Technician not found'], 404);
            }
            $zone->setTechnician($technician);
        }
        $zone->setGeometry($data['geometry'] ?? []);

        $dm->persist($zone);
        $dm->flush();

        return $this->json(['message' => 'Zone created successfully'], 201);
    }

    public function updateZone(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $zone = $dm->getRepository(Zone::class)->find($id);
        if (!$zone) {
            return $this->json(['message' => 'Zone not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $zone->setName($data['name'] ?? $zone->getName());
        $zone->setDescription($data['description'] ?? $zone->getDescription());
        $zone->setColor($data['color'] ?? $zone->getColor());
        if (isset($data['technicianId']) && $data['technicianId']) {
            $technician = $dm->getRepository(User::class)->find($data['technicianId']);
            if (!$technician) {
                return new JsonResponse(['error' => 'Technician not found'], 404);
            }
            $zone->setTechnician($technician);
        }
        $zone->setGeometry($data['geometry'] ?? $zone->getGeometry());

        $dm->flush();

        return $this->json(['message' => 'Zone updated successfully']);
    }

    public function deleteZone(string $id, DocumentManager $dm): JsonResponse
    {
        $zone = $dm->getRepository(Zone::class)->find($id);
        if (!$zone) {
            return $this->json(['message' => 'Zone not found'], 404);
        }

        $dm->remove($zone);
        $dm->flush();

        return $this->json(['message' => 'Zone deleted successfully']);
    }
}

