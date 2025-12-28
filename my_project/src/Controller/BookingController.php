<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BookingService;
use App\Service\UserService;
use DateTime;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    public function __construct(
        private BookingService $bookingService,
        private UserService $userService
    ) {
    }

    #[Route('/api/houses/available', name: 'available_houses', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse
    {
        try {
            $houses = $this->bookingService->getAvailableHouses();

            $result = [];

            foreach ($houses as $house) {
                $result[] = [
                    'id' => $house->getId(),
                    'name' => $house->getName(),
                    'beds' => $house->getBeds(),
                    'amenities' => $house->getAmenities(),
                    'distanceToSea' => $house->getDistanceToSea(),
                    'pricePerNight' => $house->getPricePerNight(),
                    'isAvailable' => $house->isAvailable()
                ];
            }

            return $this->json(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/bookings', name: 'create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $requiredFields = ['userId', 'houseId', 'comment', 'checkIn', 'checkOut'];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return $this->json([
                        'success' => false,
                        'error' => "Missing required field: $field"
                    ], 400);
                }
            }

            $user = $this->userService->getUserById($data['userId']);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $booking = $this->bookingService->createBooking(
                $user,
                (int)$data['houseId'],
                $data['comment'],
                new DateTime($data['checkIn']),
                new DateTime($data['checkOut'])
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $booking->getId(),
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail()
                    ],
                    'house' => [
                        'id' => $booking->getHouse()?->getId() ?? 0,
                        'name' => $booking->getHouse()?->getName() ?? '',
                    ],
                    'comment' => $booking->getComment(),
                    'checkIn' => $booking->getCheckIn()?->format('Y-m-d') ?? '',
                    'checkOut' => $booking->getCheckOut()?->format('Y-m-d') ?? '',
                    'status' => $booking->getStatus(),
                    'createdAt' => $booking->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]
            ], 201);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/bookings', name: 'update_booking', methods: ['PUT'])]
    public function updateBooking(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['id']) || !isset($data['comment'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required fields: id, comment'
                ], 400);
            }

            $booking = $this->bookingService->updateBookingComment(
                (int)$data['id'],
                $data['comment']
            );

            if (!$booking) {
                return $this->json([
                    'success' => false,
                    'error' => 'Booking not found'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $booking->getId(),
                    'comment' => $booking->getComment(),
                    'updatedAt' => $booking->getUpdatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
