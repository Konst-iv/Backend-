<?php

namespace App\Entity; 

class House
{
    public function __construct(
        public int $id,
        public string $name,
        public int $beds,
        public string $amenities,
        public int $distanceToSea,
        public bool $isAvailable = true
    ) {}
}