<?php

namespace App\Repository;

use App\Entity\Machine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Machine>
 */
class MachineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Machine::class);
    }

//    /**
//     * @return Machine[] Returns an array of Machine objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Machine
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function getPercentageOfBrokenMachinesByCabinet(): array
    {
        $qb = $this->createQueryBuilder('m')
                   ->select('c.id as cabinetId, c.name as cabinetName, COUNT(m.id) as totalMachines, 
                  SUM(CASE WHEN m.status = :enPanne THEN 1 ELSE 0 END) as brokenMachines')
                  ->join('m.cabinet', 'c')
                  ->groupBy('c.id, c.name')
                  ->setParameter('enPanne', 'en panne');
        
        $results = $qb->getQuery()->getResult();

        $percentages = [];
        foreach ($results as $result) {
            $percentages[$result['cabinetName']] = ($result['totalMachines'] > 0) ? ($result['brokenMachines'] / $result['totalMachines']) * 100 : 0;
        }

        return $percentages;
    }

}
