<?php

namespace App\Repository;

use App\Entity\Booking;  

class BookingRepository
{
    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;
        $this->initializeFile();
    }

    private function initializeFile(): void
    {
        if (!file_exists($this->csvFile)) {
            $dir = dirname($this->csvFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);  
            }
            
            $handle = fopen($this->csvFile, "w");
            fputcsv($handle, ['id', 'phone', 'house_id', 'comment', 'created_at', 'updated_at']);
            fclose($handle);
        }
    }

    public function save(Booking $booking): void
    {
        $handle = fopen($this->csvFile, 'a');
        fputcsv($handle, [
            $booking->id,
            $booking->phone,
            $booking->houseId,
            $booking->comment,
            $booking->createdAt,
            $booking->updatedAt
        ]);
        fclose($handle);
    }
    
    public function findAll(): array
    {
        if (!file_exists($this->csvFile)) {  
            return [];
        }

        $bookings = [];
        $handle = fopen($this->csvFile, 'r'); 
        
        // Пропускаем заголовок
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 6) {  
                $bookings[] = new Booking(
                    (int)$data[0],
                    $data[1],
                    (int)$data[2],
                    $data[3],
                    $data[4],
                    $data[5] ?: null
                );
            }
        }
        
        fclose($handle);
        return $bookings;
    }

    

    public function findById(int $id):?Booking{
        $bookings = $this->findAll();
        foreach($bookings as $booking) {
            if ($booking->id === $id) {
                return $booking;
            }
        }

        return null;
    } 

    public function update(Booking $updatedBooking): bool{
        $bookings = $this->findAll();
        $found = false;

        foreach ($bookings as $key => $booking) {
            if ($booking->id === $updatedBooking->id) {
                $bookings[$key] = $updatedBooking;
                $found = true;
                break;
            }
        }

        if ($found) {
            $this->saveAll($bookings);
            return true;
        }
        
        return false;
    }

    private function saveAll(array $bookings): void
    {
        $handle = fopen($this->csvFile, 'w');
        fputcsv($handle, ['id', 'phone', 'house_id', 'comment', 'created_at', 'updated_at']);
        
        foreach ($bookings as $booking) {
            fputcsv($handle, [
                $booking->id,
                $booking->phone,
                $booking->houseId,
                $booking->comment,
                $booking->createdAt,
                $booking->updatedAt
            ]);
        }
        
        fclose($handle);
    }

    public function getNextId(): int
    {
        $bookings = $this->findAll();
        if (empty($bookings)) {
            return 1;
        }
        
        $maxId = 0;
        foreach ($bookings as $booking) {
            if ($booking->id > $maxId) {
                $maxId = $booking->id;
            }
        }
        
        return $maxId + 1;
    }
    
}