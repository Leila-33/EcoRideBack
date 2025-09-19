<?php

namespace App\Repository;

use App\Entity\Parametre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parametre>
 *
 * @method Parametre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parametre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parametre[]    findAll()
 * @method Parametre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParametreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parametre::class);
    }

    public function findByUser(int $id): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM parametre p WHERE (p.user_id = :val)';
        $resultSet = $conn->executeQuery($sql, ['val' => $id]);

        return $resultSet->fetchAllAssociative();
    }

    public function findOneByUserAndProperty(User $user, string $propriete)
    {
        return $this->createQueryBuilder('p')
         ->innerJoin('p.users', 'u')
         ->where('u = :user')
         ->andWhere('p.propriete = :propriete')
         ->setParameters(['user' => $user,
             'propriete' => $propriete])
         ->getQuery()
         ->getOneOrNullResult();
    }
}

//    /**
//     * @return Parametre[] Returns an array of Parametre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Parametre
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
