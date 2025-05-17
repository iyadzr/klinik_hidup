<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/appointments')]
class AppointmentController extends AbstractController
{
    #[Route('', name: 'app_appointment_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $appointments = $entityManager->getRepository(Appointment::class)->findAll();
            
            $formattedAppointments = array_map(function($appointment) {
                $patient = $appointment->getPatient();
                $doctor = $appointment->getDoctor();
                
                if (!$patient || !$doctor) {
                    // Skip appointments with missing relations
                    return null;
                }
                
                return [
                    'id' => $appointment->getId(),
                    'patient' => [
                        'id' => $patient->getId(),
                        'firstName' => $patient->getFirstName(),
                        'lastName' => $patient->getLastName(),
                    ],
                    'doctor' => [
                        'id' => $doctor->getId(),
                        'firstName' => $doctor->getFirstName(),
                        'lastName' => $doctor->getLastName(),
                    ],
                    'appointmentDateTime' => $appointment->getAppointmentDateTime()->format('Y-m-d\TH:i:s'),
                    'reason' => $appointment->getReason(),
                    'status' => $appointment->getStatus(),
                    'notes' => $appointment->getNotes(),
                ];
            }, $appointments);
            
            // Filter out any null values from appointments with missing relations
            $formattedAppointments = array_filter($formattedAppointments);
            
            return $this->json([
                'appointments' => array_values($formattedAppointments)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch appointments',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/today', name: 'app_appointment_today', methods: ['GET'])]
    public function today(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $today = new \DateTime();
            $appointments = $entityManager->getRepository(Appointment::class)
                ->createQueryBuilder('a')
                ->where('DATE(a.appointmentDateTime) = :today')
                ->setParameter('today', $today->format('Y-m-d'))
                ->getQuery()
                ->getResult();

            return $this->json(['count' => count($appointments)]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch today\'s appointments',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'app_appointment_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['patientId'], $data['doctorId'], $data['appointmentDateTime'])) {
                return $this->json([
                    'error' => 'Missing required fields',
                    'message' => 'patientId, doctorId, and appointmentDateTime are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $patient = $entityManager->getRepository(Patient::class)->find($data['patientId']);
            $doctor = $entityManager->getRepository(Doctor::class)->find($data['doctorId']);

            if (!$patient || !$doctor) {
                return $this->json([
                    'error' => 'Not found',
                    'message' => 'Patient or Doctor not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $appointment = new Appointment();
            $appointment->setPatient($patient);
            $appointment->setDoctor($doctor);
            $appointment->setAppointmentDateTime(new \DateTime($data['appointmentDateTime']));
            $appointment->setStatus($data['status'] ?? 'scheduled');
            
            if (isset($data['reason'])) {
                $appointment->setReason($data['reason']);
            }
            if (isset($data['notes'])) {
                $appointment->setNotes($data['notes']);
            }

            $entityManager->persist($appointment);
            $entityManager->flush();

            return $this->json([
                'message' => 'Appointment created successfully',
                'appointment' => [
                    'id' => $appointment->getId(),
                    'patient' => [
                        'id' => $patient->getId(),
                        'firstName' => $patient->getFirstName(),
                        'lastName' => $patient->getLastName(),
                    ],
                    'doctor' => [
                        'id' => $doctor->getId(),
                        'firstName' => $doctor->getFirstName(),
                        'lastName' => $doctor->getLastName(),
                    ],
                    'appointmentDateTime' => $appointment->getAppointmentDateTime()->format('Y-m-d\TH:i:s'),
                    'reason' => $appointment->getReason(),
                    'status' => $appointment->getStatus(),
                    'notes' => $appointment->getNotes(),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create appointment',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): JsonResponse
    {
        try {
            $patient = $appointment->getPatient();
            $doctor = $appointment->getDoctor();

            if (!$patient || !$doctor) {
                return $this->json([
                    'error' => 'Invalid appointment',
                    'message' => 'Appointment has missing patient or doctor'
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'appointment' => [
                    'id' => $appointment->getId(),
                    'patient' => [
                        'id' => $patient->getId(),
                        'firstName' => $patient->getFirstName(),
                        'lastName' => $patient->getLastName(),
                    ],
                    'doctor' => [
                        'id' => $doctor->getId(),
                        'firstName' => $doctor->getFirstName(),
                        'lastName' => $doctor->getLastName(),
                    ],
                    'appointmentDateTime' => $appointment->getAppointmentDateTime()->format('Y-m-d\TH:i:s'),
                    'reason' => $appointment->getReason(),
                    'status' => $appointment->getStatus(),
                    'notes' => $appointment->getNotes(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch appointment',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_appointment_update', methods: ['PUT'])]
    public function update(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['patientId'])) {
                $patient = $entityManager->getRepository(Patient::class)->find($data['patientId']);
                if (!$patient) {
                    return $this->json([
                        'error' => 'Not found',
                        'message' => 'Patient not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                $appointment->setPatient($patient);
            }

            if (isset($data['doctorId'])) {
                $doctor = $entityManager->getRepository(Doctor::class)->find($data['doctorId']);
                if (!$doctor) {
                    return $this->json([
                        'error' => 'Not found',
                        'message' => 'Doctor not found'
                    ], Response::HTTP_NOT_FOUND);
                }
                $appointment->setDoctor($doctor);
            }

            if (isset($data['appointmentDateTime'])) {
                $appointment->setAppointmentDateTime(new \DateTime($data['appointmentDateTime']));
            }

            if (isset($data['status'])) {
                $appointment->setStatus($data['status']);
            }

            if (isset($data['reason'])) {
                $appointment->setReason($data['reason']);
            }

            if (isset($data['notes'])) {
                $appointment->setNotes($data['notes']);
            }

            $entityManager->flush();

            return $this->json([
                'message' => 'Appointment updated successfully',
                'appointment' => [
                    'id' => $appointment->getId(),
                    'patient' => [
                        'id' => $appointment->getPatient()->getId(),
                        'firstName' => $appointment->getPatient()->getFirstName(),
                        'lastName' => $appointment->getPatient()->getLastName(),
                    ],
                    'doctor' => [
                        'id' => $appointment->getDoctor()->getId(),
                        'firstName' => $appointment->getDoctor()->getFirstName(),
                        'lastName' => $appointment->getDoctor()->getLastName(),
                    ],
                    'appointmentDateTime' => $appointment->getAppointmentDateTime()->format('Y-m-d\TH:i:s'),
                    'reason' => $appointment->getReason(),
                    'status' => $appointment->getStatus(),
                    'notes' => $appointment->getNotes(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update appointment',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['DELETE'])]
    public function delete(Appointment $appointment, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($appointment);
            $entityManager->flush();

            return $this->json([
                'message' => 'Appointment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete appointment',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
