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

        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $id            = (int)$data[0];
            $name          = $data[1];
            $beds          = (int)$data[2];
            $amenities     = $data[3];
            $distanceToSea = (int)$data[4];
            $isAvailable   = (bool)$data[5];

            $houses[] = new House(
                id: $id,
                name: $name,
                beds: $beds,
                amenities: $amenities,
                distanceToSea: $distanceToSea,
                isAvailable: $isAvailable
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