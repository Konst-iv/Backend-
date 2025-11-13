<?php

namespace App\Command;

use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-test-houses',
    description: 'Add test houses to database',
)]
class AddTestHousesCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $houses = [
            ['Домик у моря', 2, 'санузел, душевая кабина', 50, true, '2500.00'],
            ['Лесной домик', 4, 'никаких', 300, true, '1800.00'],
            ['Эконом вариант', 2, 'санузел', 100, true, '1200.00'],
            ['Премиум вилла', 6, 'санузел, душевая кабина, кухня', 30, false, '5000.00'],
            ['Семейный домик', 5, 'санузел, душевая кабина', 80, true, '3200.00']
        ];

        foreach ($houses as $houseData) {
            $house = new House();
            $house->setName($houseData[0]);
            $house->setBeds($houseData[1]);
            $house->setAmenities($houseData[2]);
            $house->setDistanceToSea($houseData[3]);
            $house->setIsAvailable($houseData[4]);
            $house->setPricePerNight($houseData[5]);

            $this->entityManager->persist($house);
        }

        $this->entityManager->flush();

        $io->success('5 test houses added to database!');

        return Command::SUCCESS;
    }
}