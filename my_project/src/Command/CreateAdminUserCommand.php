<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Создает администратора для админ-панели',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email администратора')
            ->addArgument('phone', InputArgument::OPTIONAL, 'Телефон администратора')
            ->addArgument('name', InputArgument::OPTIONAL, 'Имя администратора');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Создание администратора');

        $email = $input->getArgument('email');

        if (!$email) {
            $email = $io->ask('Введите email администратора', null, function ($value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Неверный формат email');
                }

                return $value;
            });
        }

        $phone = $input->getArgument('phone');

        if (!$phone) {
            $phone = $io->ask('Введите телефон (+7XXXXXXXXXX)', null, function ($value) {
                if (!preg_match('/^\+7\d{10}$/', $value)) {
                    throw new RuntimeException('Телефон должен быть в формате +7XXXXXXXXXX');
                }

                return $value;
            });
        }

        $name = $input->getArgument('name');

        if (!$name) {
            $name = $io->ask('Введите имя администратора');
        }

        $password = $io->askHidden('Введите пароль', function ($value) {
            if (strlen($value) < 6) {
                throw new RuntimeException('Пароль должен содержать минимум 6 символов');
            }

            return $value;
        });

        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->warning('Пользователь с таким email уже существует. Обновляю права...');
            $user = $existingUser;
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setName($name);
        }

        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Администратор успешно создан!');
        $io->table(
            ['Поле', 'Значение'],
            [
                ['ID', $user->getId()],
                ['Имя', $user->getName()],
                ['Email', $user->getEmail()],
                ['Телефон', $user->getPhone()],
                ['Роли', implode(', ', $user->getRoles())],
            ]
        );

        $io->note('Для входа в админ-панель перейдите по адресу: /admin');
        $io->note('Используйте телефон и пароль для входа');

        return Command::SUCCESS;
    }
}
