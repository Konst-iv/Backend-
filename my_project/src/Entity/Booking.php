<?php

namespace App\Entity;

class Booking
{
    public function __construct(
        public int $id,
        public string $phone,
        public int $houseId,
        public string $comment,
        public string $createdAt,
        public ?string $updatedAt = null
    ) {}
}