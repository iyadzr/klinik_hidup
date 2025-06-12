<?php

namespace App\Controller;

use App\Entity\Medication;
use App\Entity\PrescribedMedication;
use App\Repository\MedicationRepository;
use App\Repository\PrescribedMedicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/medications')]
class MedicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MedicationRepository $medicationRepository,
        private PrescribedMedicationRepository $prescribedMedicationRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_medication_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $category = $request->query->get('category');

        if ($search) {
            $medications = $this->medicationRepository->findByNameLike($search);
        } elseif ($category) {
            $medications = $this->medicationRepository->findByCategory($category);
        } else {
            $medications = $this->medicationRepository->findBy([], ['name' => 'ASC']);
        }

        $data = array_map(function (Medication $medication) {
            return [
                'id' => $medication->getId(),
                'name' => $medication->getName(),
                'unitType' => $medication->getUnitType(),
                'unitDescription' => $medication->getUnitDescription(),
                'description' => $medication->getDescription(),
                'category' => $medication->getCategory(),
            ];
        }, $medications);

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_medication_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $medication = new Medication();
        $medication->setName($data['name']);
        $medication->setUnitType($data['unitType']);
        $medication->setUnitDescription($data['unitDescription'] ?? null);
        $medication->setDescription($data['description'] ?? null);
        $medication->setCategory($data['category'] ?? null);

        $errors = $this->validator->validate($medication);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($medication);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $medication->getId(),
            'name' => $medication->getName(),
            'unitType' => $medication->getUnitType(),
            'unitDescription' => $medication->getUnitDescription(),
            'description' => $medication->getDescription(),
            'category' => $medication->getCategory(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_medication_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $medication = $this->medicationRepository->find($id);

        if (!$medication) {
            return new JsonResponse(['error' => 'Medication not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $medication->getId(),
            'name' => $medication->getName(),
            'unitType' => $medication->getUnitType(),
            'unitDescription' => $medication->getUnitDescription(),
            'description' => $medication->getDescription(),
            'category' => $medication->getCategory(),
        ]);
    }

    #[Route('/{id}', name: 'api_medication_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $medication = $this->medicationRepository->find($id);

        if (!$medication) {
            return new JsonResponse(['error' => 'Medication not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $medication->setName($data['name']);
        if (isset($data['unitType'])) $medication->setUnitType($data['unitType']);
        if (isset($data['unitDescription'])) $medication->setUnitDescription($data['unitDescription']);
        if (isset($data['description'])) $medication->setDescription($data['description']);
        if (isset($data['category'])) $medication->setCategory($data['category']);

        $errors = $this->validator->validate($medication);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $medication->getId(),
            'name' => $medication->getName(),
            'unitType' => $medication->getUnitType(),
            'unitDescription' => $medication->getUnitDescription(),
            'description' => $medication->getDescription(),
            'category' => $medication->getCategory(),
        ]);
    }

    #[Route('/{id}', name: 'api_medication_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $medication = $this->medicationRepository->find($id);

        if (!$medication) {
            return new JsonResponse(['error' => 'Medication not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if medication is being used in prescriptions
        $prescriptions = $this->prescribedMedicationRepository->findBy(['medication' => $medication]);
        if (count($prescriptions) > 0) {
            return new JsonResponse([
                'error' => 'Cannot delete medication that is used in prescriptions',
                'message' => 'This medication is referenced in ' . count($prescriptions) . ' prescription(s)'
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($medication);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Medication deleted successfully']);
    }

    #[Route('/usage-stats', name: 'api_medication_usage_stats', methods: ['GET'])]
    public function usageStats(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', 'first day of this month'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));

        $stats = $this->prescribedMedicationRepository->getMedicationUsageStats($startDate, $endDate);

        return new JsonResponse($stats);
    }
} 