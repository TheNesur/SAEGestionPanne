<?php

namespace App\DataFixtures;

use App\Entity\Breakdown;
use App\Entity\Cabinet;
use App\Entity\Machine;
use App\Entity\Maintenance;
use App\Entity\User;
use Composer\XdebugHandler\Status;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use function DateTime;
use function PHPUnit\Framework\containsEqual;
use function Symfony\Component\Clock\now;

class AppFixtures extends Fixture
{ private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }


    public function load(ObjectManager $manager): void
    {
        // Cree une liste de cabinet

        $manager->clear();

        /*
         *  CABINET
         */
        $listCabinet = array();
        for ($i = 0; $i < 5; $i++) {
            $cabinet = new Cabinet();
            $cabinet
                ->setName($this->faker->company())
                ->setAddress($this->faker->address())
                ->setCreationDate(new DateTimeImmutable());
            $listCabinet[] = $cabinet;

            $manager->persist($cabinet);
        }


        /*
         *  MACHINE
         */
        $listUser = array();
        $userTech = array();
        $listMachines = array();
        for ($i = 0; $i < 15; $i++) {
            // create machine
            $machine = new Machine();
            $machine
                ->setSerialNumber($this->faker->creditCardType())
                ->setReference($this->faker->creditCardType())
                ->setCreationDate(new DateTimeImmutable())
                ->setStatus("Active")
                ->setProductionDate(new DateTimeImmutable())
                ->setCabinet($listCabinet[rand(0, count($listCabinet) - 1)]);
            $manager->persist($machine);
            $listMachines[] = $machine;
        }


        /*
         *  USER
         */
        $role = array('ROLE_USER', 'ROLE_TECHNICIAN', 'ROLE_ADMIN');
        for ($i = 0; $i < 10; $i++) {

            $rand = rand(0,2);
            $grade = array('ROLE_USER');

            if ($rand >= 1) $grade[] = \USER_ROLES::TECH->value;
            if ($rand >= 2) $grade[] = \USER_ROLES::ADMIN->value;


            $lastName = $this->faker->lastName();
            $firstName = $this->faker->firstName();

            $user = new User();
            $user
                ->setUsername($lastName . " " . $firstName)
                ->setRoles($grade)
                ->setPassword($this->faker->password())
                ->setName($lastName)
                ->setFirstName($firstName)
                ->setEmail($this->faker->email())
                ->setCreationDate(new DateTimeImmutable())
                ->setCabinet($listCabinet[array_rand($listCabinet)])
                ->setVerified(true);

            if ($rand >= 1) $userTech[] = $user;

            $listUser[] = $user;

            $manager->persist($user);

        }


        /*
         *  MAINTENANCE
         */
        $listMaintenance = array();
        $randTech = rand(0, 2);
        for ($i = 0; $i < 3; $i++) {
            $maintenance = new Maintenance();
            $maintenance
                ->setDate(new DateTimeImmutable("now"))
                ->setComment($this->faker->realText())
                ->setStatus("en attente")
                ->setCreationDate(new DateTimeImmutable())
                ->setCreator($listUser[array_rand($listUser)]);
            if ($i == $randTech ) $maintenance->setTechnician($userTech[array_rand($userTech)]);
            $manager->persist($maintenance);
            $listMaintenance[] = $maintenance;
        }


        /*
         *  PANNE
         */
        for ($i = 0; $i < 5; $i++) {
            $panne = new Breakdown();
            $panne
                ->setDate(new DateTimeImmutable("now"))
                ->setDescription($this->faker->realText())
                ->setComment($this->faker->realText())
                ->setStatus("en attente")
                ->setCreationDate(new DateTimeImmutable())
                ->setCreator($listUser[array_rand($listUser)])
                ->setMaintenance($listMaintenance[array_rand($listMaintenance)])
                ->setMachine($listMachines[array_rand($listMachines)]);
            $manager->persist($panne);
        }



        $manager->flush();
    }
}
