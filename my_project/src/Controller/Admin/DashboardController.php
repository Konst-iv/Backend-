<?php
namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $userCount = $this->entityManager->getRepository(User::class)->count([]);
        $houseCount = $this->entityManager->getRepository(House::class)->count([]);
        $bookingCount = $this->entityManager->getRepository(Booking::class)->count([]);
        $activeBookings = $this->entityManager->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', 'confirmed')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'user_count' => $userCount,
            'house_count' => $houseCount,
            'booking_count' => $bookingCount,
            'active_bookings' => $activeBookings,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Админ-панель Booking System')
            ->setFaviconPath('favicon.ico')
            ->setTextDirection('ltr')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Управление');
        yield MenuItem::linkToCrud('Дома', 'fa fa-home', House::class);
        yield MenuItem::linkToCrud('Бронирования', 'fa fa-calendar', Booking::class);
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class);
        yield MenuItem::section('Выход');
        yield MenuItem::linkToLogout('Выйти', 'fa fa-sign-out');
    }
}