<?php

namespace App\Repository;

use App\Entity\Breakdown;
use App\Entity\Maintenance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Breakdown>
 */
class BreakdownRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Breakdown::class);
    }

    //    /**
    //     * @return Breakdown[] Returns an array of Breakdown objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Breakdown
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getTotalBreakdowns(): int
    {
        $qb = $this->createQueryBuilder('b')
               ->select('COUNT(b.id)')
               ->where('b.status IS NOT NULL')
               ->andWhere('b.status != :repare')
               ->setParameter('repare', 'repare');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getUrgentBreakdowns(): int
    {
        $qb = $this->createQueryBuilder('b')
               ->select('COUNT(b.id)')
               ->where('b.status = :status')
               ->andWhere('b.status IS NOT NULL')
               ->andWhere('b.status != :empty')
               ->setParameter('status', 'Urgent')
               ->setParameter('empty', '');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getNonUrgentBreakdowns(): int
    {
        $qb = $this->createQueryBuilder('b')
               ->select('COUNT(b.id)')
               ->where('b.status = :status')
               ->andWhere('b.status IS NOT NULL')
               ->andWhere('b.status != :empty')
               ->setParameter('status', 'Non Urgent')
               ->setParameter('empty', '');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTodayBreakdowns(): int
    {
         $qb = $this->createQueryBuilder('b')
               ->select('COUNT(b.id)')
               ->where('b.date = :today')
               ->andWhere('b.status IS NOT NULL')
               ->andWhere('b.status != :repare')
               ->setParameter('today', new \DateTime('today'))
               ->setParameter('repare', 'repare');
         return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getBreakdownsByMaintenance(int $idMaintenance): array
    {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.id')
                   ->where('b.maintenance= :maintenance')
                   ->setParameter('maintenance', $idMaintenance);

        return $qb->getQuery()->getArrayResult();
    }

    public function getUnscheduledBreakdownsCount(): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.maintenance IS NULL');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getUnscheduledUrgentBreakdownsCount(): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.maintenance IS NULL')
            ->andWhere('b.status = :urgentStatus')
            ->setParameter('urgentStatus', 'urgent'); 

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

public function getUrgentBreakdownsByCabinet(): array
{
    $qb = $this->createQueryBuilder('b')
               ->select('c.name as cabinetName, COUNT(b.id) as urgentBreakdowns')
               ->join('b.machine', 'm')
               ->join('m.cabinet', 'c')
               ->where('b.status = :urgentStatus')
               ->setParameter('urgentStatus', 'Urgent')
               ->groupBy('c.name');
    
    $results = $qb->getQuery()->getResult();
    
    $urgentBreakdownsByCabinet = [];
    foreach ($results as $result) {
        $urgentBreakdownsByCabinet[$result['cabinetName']] = $result['urgentBreakdowns'];
    }
    
    return $urgentBreakdownsByCabinet;
}

public function getNonUrgentBreakdownsByCabinet(): array
{
    $qb = $this->createQueryBuilder('b')
               ->select('c.name as cabinetName, COUNT(b.id) as nonUrgentBreakdowns')
               ->join('b.machine', 'm')
               ->join('m.cabinet', 'c')
               ->where('b.status = :nonUrgentStatus')
               ->setParameter('nonUrgentStatus', 'Non Urgent')
               ->groupBy('c.name');
    
    $results = $qb->getQuery()->getResult();
    
    $nonUrgentBreakdownsByCabinet = [];
    foreach ($results as $result) {
        $nonUrgentBreakdownsByCabinet[$result['cabinetName']] = $result['nonUrgentBreakdowns'];
    }
    
    return $nonUrgentBreakdownsByCabinet;
}

}
