<?php


namespace App\Service;  

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;

class BookingService{
    private BookingRepository $bookingRepository;
    private HouseRepository $houseRepository;

    public function __construct(BookingRepository $bookingRepo, HouseRepository $houseRepo)
    {
        $this->bookingRepository = $bookingRepo;
        $this->houseRepository = $houseRepo;
    }

    public function getAvailableHouses (): array{
        return $this->houseRepository->findAvailable();    
    }
    public function createBooking(string $phone, int $id, string $comment):?Booking{
        $house = $this->houseRepository->findById($id);
        if(!$house){
            throw new \InvalidArgumentException("House with ID $id not found");
        }

        if (!$house->isAvailable) {
            throw new \InvalidArgumentException("House with ID $id is not available");
        }

        $booking = new Booking(
        $this->bookingRepository->getNextId(),
            $phone,
            $id,
            $comment,
            date('Y-m-d H:i:s')
        );
        $this->bookingRepository->save($booking);
        return $booking;
    
    }

     public function updateBookingComment(int $bookingId, string $newComment): ?Booking
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            return null;
        }
        
        $booking->comment = $newComment;
        $booking->updatedAt = date('Y-m-d H:i:s');
        
        if ($this->bookingRepository->update($booking)) {
            return $booking;
        }
        
        return null;
    }
    
}