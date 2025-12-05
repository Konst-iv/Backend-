<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bookings')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house = null;

    #[ORM\Column(type: 'text')]
    private ?string $comment = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $checkIn = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $checkOut = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    // Геттеры и сеттеры
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): static
    {
        $this->house = $house;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCheckIn(): ?DateTimeInterface
    {
        return $this->checkIn;
    }

    public function setCheckIn(DateTimeInterface $checkIn): static
    {
        $this->checkIn = $checkIn;

        return $this;
    }

    public function getCheckOut(): ?DateTimeInterface
    {
        return $this->checkOut;
    }

    public function setCheckOut(DateTimeInterface $checkOut): static
    {
        $this->checkOut = $checkOut;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
