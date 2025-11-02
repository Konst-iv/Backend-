<?php


namespace App\Repository;

use App\Entity\House;

class HouseRepository
{
    private string $csvFile;
    
    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;
    }
    
    public function findAll(): array
    {
        if (!file_exists($this->csvFile)) {
            return [];
        }
        
        $houses = [];
        $handle = fopen($this->csvFile, 'r');
        
        // Пропускаем заголовок
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $houses[] = new House(
                (int)$data[0],
                $data[1],
                (int)$data[2],
                $data[3],
                (int)$data[4],
                (bool)$data[5]
            );
        }
        
        fclose($handle);
        return $houses;
    }
    
    public function findAvailable(): array
    {
        return array_filter($this->findAll(), fn($house) => $house->isAvailable);
    }
    
    public function findById(int $id): ?House
    {
        $houses = $this->findAll();
        foreach ($houses as $house) {
            if ($house->id === $id) {
                return $house;
            }
        }
        return null;
    }
}