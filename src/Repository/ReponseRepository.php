<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 *
 * @method Reponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reponse[]    findAll()
 * @method Reponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    public function countByCovoiturages(int $id): int
    {
        return $this->createQueryBuilder('r')
        ->select('COUNT(r.id)')
        ->where('r.covoiturage=:id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function findReponsesNon(): array
    {
        return $this->createQueryBuilder('r')
        ->leftJoin('r.covoiturage', 'cov')
        ->leftJoin('cov.commentaires', 'c', 'WITH', 'c.auteur = r.user')
        ->addSelect('cov', 'c')
        ->where('r.reponse1 = :non')
        ->andWhere('r.statut = :statut')
        ->setParameter('non', 'non')
        ->setParameter('statut', 'en attente')
        ->getQuery()
        ->getResult();
    }
}
//    /**
//     * @return Reponse[] Returns an array of Reponse objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reponse
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
