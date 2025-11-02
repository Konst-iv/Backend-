<?php

namespace App\Controller;   
use App\Service\BookingService;

class BookingController
{
    private BookingService $bookingService;
    
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    
    public function getAvailableHouses(): string
    {
        try {
            $houses = $this->bookingService->getAvailableHouses();
            
            $result = [];
            foreach ($houses as $house) {
                $result[] = [
                    'id' => $house->id,
                    'name' => $house->name,
                    'beds' => $house->beds,
                    'amenities' => $house->amenities,
                    'distanceToSea' => $house->distanceToSea
                ];
            }
            
            header('Content-Type: application/json');
            return json_encode(['success' => true, 'data' => $result]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function createBooking(): string
    {
        try {
            // Получаем данные из POST запроса
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['phone']) || !isset($input['houseId']) || !isset($input['comment'])) {
                http_response_code(400);
                return json_encode(['success' => false, 'error' => 'Missing required fields']);
            }
            
            $booking = $this->bookingService->createBooking(
                $input['phone'],
                (int)$input['houseId'],
                $input['comment']
            );
            
            header('Content-Type: application/json');
            return json_encode([
                'success' => true, 
                'data' => [
                    'id' => $booking->id,
                    'phone' => $booking->phone,
                    'houseId' => $booking->houseId,
                    'comment' => $booking->comment,
                    'createdAt' => $booking->createdAt
                ]
            ]);
            
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function updateBooking(): string
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id']) || !isset($input['comment'])) {
                http_response_code(400);
                return json_encode(['success' => false, 'error' => 'Missing required fields']);
            }
            
            $booking = $this->bookingService->updateBookingComment(
                (int)$input['id'],
                $input['comment']
            );
            
            if (!$booking) {
                http_response_code(404);
                return json_encode(['success' => false, 'error' => 'Booking not found']);
            }
            
            header('Content-Type: application/json');
            return json_encode([
                'success' => true, 
                'data' => [
                    'id' => $booking->id,
                    'phone' => $booking->phone,
                    'houseId' => $booking->houseId,
                    'comment' => $booking->comment,
                    'updatedAt' => $booking->updatedAt
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}