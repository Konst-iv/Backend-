<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\BookingController;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Service\BookingService;

// Инициализация зависимостей
$houseRepository = new HouseRepository(__DIR__ . '/../src/service/houses.csv');
$bookingRepository = new BookingRepository(__DIR__ . '/../src/service/bookings.csv');
$bookingService = new BookingService($bookingRepository, $houseRepository);
$controller = new BookingController($bookingService);

// Простой роутинг
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

switch (true) {
    case $requestUri === '/api/houses/available' && $requestMethod === 'GET':
        echo $controller->getAvailableHouses();
        break;
        
    case $requestUri === '/api/bookings' && $requestMethod === 'POST':
        echo $controller->createBooking();
        break;
        
    case $requestUri === '/api/bookings' && $requestMethod === 'PUT':
        echo $controller->updateBooking();
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        break;
}