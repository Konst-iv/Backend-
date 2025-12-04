<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\House;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private HouseRepository $houseRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getAvailableHouses(): array
    {
        return $this->houseRepository->findBy(['isAvailable' => true]);
    }

    public function createBooking(User $user, int $houseId, string $comment, \DateTimeInterface $checkIn, \DateTimeInterface $checkOut): Booking
    {
        // Находим дом
        $house = $this->houseRepository->find($houseId);
        
        if (!$house) {
            throw new \InvalidArgumentException("House with ID $houseId not found");
        }

        if (!$house->isAvailable()) {
            throw new \InvalidArgumentException("House with ID $houseId is not available");
        }

        if ($checkIn >= $checkOut) {
            throw new \InvalidArgumentException("Check-in date must be before check-out date");
        }

        // Создаем бронирование
        $booking = new Booking();
        $booking->setCustomer($user);
        $booking->setHouse($house);
        $booking->setComment($comment);
        $booking->setCheckIn($checkIn);
        $booking->setCheckOut($checkOut);
        $booking->setStatus('confirmed');

        // Сохраняем в базу
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    public function updateBookingComment(int $bookingId, string $newComment): ?Booking
    {
        $booking = $this->bookingRepository->find($bookingId);
        
        if (!$booking) {
            return null;
        }
        
        $booking->setComment($newComment);
        $booking->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        return $booking;
    }

    public function getUserBookings(User $user): array
    {
        return $this->bookingRepository->findBy(['customer' => $user]);
    }
}