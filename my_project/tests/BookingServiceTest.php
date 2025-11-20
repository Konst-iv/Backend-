<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Service\BookingService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BookingServiceTest extends TestCase
{
    private BookingService $bookingService;
    private $bookingRepository;
    private $houseRepository;
    private $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        // Создаем моки
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->houseRepository = $this->createMock(HouseRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->bookingService = new BookingService(
            $this->bookingRepository,
            $this->houseRepository,
            $this->entityManager
        );
    }

    public function testCreateBookingSuccess(): void
    {
        // Arrange - Подготовка данных
        $user = new User();
        $user->setEmail('test@example.com');

        $house = new House();
        $house->setName('Test House');
        $house->setIsAvailable(true);

        // Используем Reflection для установки ID
        $reflection = new ReflectionClass($house);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($house, 1);

        $this->houseRepository->method('find')
            ->with(1)
            ->willReturn($house);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Booking::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $checkIn = new DateTime('2024-01-20');
        $checkOut = new DateTime('2024-01-25');

        $booking = $this->bookingService->createBooking(
            $user,
            1,
            'Test comment',
            $checkIn,
            $checkOut
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($user, $booking->getCustomer());
        $this->assertEquals($house, $booking->getHouse());
        $this->assertEquals('Test comment', $booking->getComment());
        $this->assertEquals('confirmed', $booking->getStatus());
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $user = new User();

        $this->houseRepository->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('House with ID 999 not found');

        $checkIn = new DateTime('2024-01-20');
        $checkOut = new DateTime('2024-01-25');

        $this->bookingService->createBooking($user, 999, 'Comment', $checkIn, $checkOut);
    }

    public function testCreateBookingHouseNotAvailable(): void
    {
        $user = new User();

        $house = new House();
        $house->setName('Unavailable House');
        $house->setIsAvailable(false);

        $this->houseRepository->method('find')
            ->with(1)
            ->willReturn($house);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('House with ID 1 is not available');

        $checkIn = new DateTime('2024-01-20');
        $checkOut = new DateTime('2024-01-25');

        $this->bookingService->createBooking($user, 1, 'Comment', $checkIn, $checkOut);
    }

    public function testCreateBookingInvalidDates(): void
    {
        $user = new User();

        $house = new House();
        $house->setName('Test House');
        $house->setIsAvailable(true);

        $this->houseRepository->method('find')
            ->with(1)
            ->willReturn($house);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Check-in date must be before check-out date');

        $checkIn = new DateTime('2024-01-25');
        $checkOut = new DateTime('2024-01-20');

        $this->bookingService->createBooking($user, 1, 'Comment', $checkIn, $checkOut);
    }

    public function testUpdateBookingCommentSuccess(): void
    {
        $user = new User();
        $house = new House();

        $booking = new Booking();
        $booking->setCustomer($user);
        $booking->setHouse($house);
        $booking->setComment('Old comment');

        $this->bookingRepository->method('find')
            ->with(1)
            ->willReturn($booking);

        $result = $this->bookingService->updateBookingComment(1, 'New comment');

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertEquals('New comment', $result->getComment());
        $this->assertNotNull($result->getUpdatedAt());
    }

    public function testUpdateBookingNotFound(): void
    {
        $this->bookingRepository->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->bookingService->updateBookingComment(999, 'New comment');

        $this->assertNull($result);
    }
}
