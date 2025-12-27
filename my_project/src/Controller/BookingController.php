<?php

namespace App\Controller;

use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends AbstractController
{
    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function getAvailableHouses(): JsonResponse
    {
        try {
            $houses = $this->bookingService->getAvailableHouses();

            $result = array_map(fn($house) => [
                'id' => $house->id,
                'name' => $house->name,
                'beds' => $house->beds,
                'amenities' => $house->amenities,
                'distanceToSea' => $house->distanceToSea
            ], $houses);

            $responseData = ['success' => true, 'data' => $result];
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal Server Error');
        }

        return new JsonResponse($responseData);
    }

    public function createBooking(Request $request): JsonResponse
    {
        try {
            $input = $request->toArray();

            if (empty($input['phone']) || empty($input['houseId']) || empty($input['comment'])) {
                throw new BadRequestHttpException('Missing required fields');
            }

            $booking = $this->bookingService->createBooking(
                $input['phone'],
                (int)$input['houseId'],
                $input['comment']
            );

            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $booking->id,
                    'phone' => $booking->phone,
                    'houseId' => $booking->houseId,
                    'comment' => $booking->comment,
                    'createdAt' => $booking->createdAt
                ]
            ];
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    public function updateBooking(Request $request): JsonResponse
    {
        try {
            $input = $request->toArray();

            if (empty($input['id']) || empty($input['comment'])) {
                throw new BadRequestHttpException('Missing required fields');
            }

            $booking = $this->bookingService->updateBookingComment(
                (int)$input['id'],
                $input['comment']
            );

            if (!$booking) {
                throw new NotFoundHttpException('Booking not found');
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $booking->id,
                    'phone' => $booking->phone,
                    'houseId' => $booking->houseId,
                    'comment' => $booking->comment,
                    'updatedAt' => $booking->updatedAt
                ]
            ];
        } catch (BadRequestHttpException | NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return new JsonResponse($responseData);
    }
}