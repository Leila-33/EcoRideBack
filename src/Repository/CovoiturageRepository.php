<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Covoiturage>
 *
 * @method Covoiturage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Covoiturage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Covoiturage[]    findAll()
 * @method Covoiturage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CovoiturageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covoiturage::class);
    }

    //    /**
    //     * @return Covoiturage[] Returns an array of Covoiturage objects
    //     */

    public function findStats(string $lieuDepart, string $lieuArrivee, string $dateDepart, array $prochesDep, array $prochesArr, bool $strictDate = true): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $dateOperator = $strictDate ? '=' : '>=';
        if (!$strictDate) {
            $dateDepart = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }
        $sql = "SELECT
        MAX(c.prix_personne) AS prix_max,
        MIN(c.prix_personne) AS prix_min,
        MAX(TIMEDIFF(CONCAT(c.date_arrivee,' ',c.heure_arrivee),CONCAT(c.date_depart,' ',c.heure_depart))) AS duree_max,
        MIN(TIMEDIFF(CONCAT(c.date_arrivee,' ',c.heure_arrivee),CONCAT(c.date_depart,' ',c.heure_depart))) AS duree_min
        FROM covoiturage c
        WHERE c.lieu_depart IN (:prochesDep)
          AND c.lieu_arrivee IN (:prochesArr) 
          AND c.date_depart {$dateOperator} :dateDepart
          AND c.nb_places > 0
          AND c.statut = :statut";

        return $conn->executeQuery($sql, [
            'prochesDep' => $prochesDep,
            'prochesArr' => $prochesArr,
            'dateDepart' => $dateDepart,
            'statut' => 'en attente'],
            ['prochesDep' => Connection::PARAM_STR_ARRAY,
                'prochesArr' => Connection::PARAM_STR_ARRAY])->fetchAssociative() ?: null;
    }

    public function findCovoiturages(string $lieuDepart, string $lieuArrivee, array $prochesDep, array $prochesArr, string $dateDepart, bool $strictDate = true)
    {
        $qb = $this->createQueryBuilder('c')
        ->addSelect('CASE WHEN c.lieuDepart = :val AND c.lieuArrivee = :val1 THEN 0 
         WHEN c.lieuDepart = :val OR c.lieuArrivee = :val1 THEN 1
        ELSE 2 END AS HIDDEN tri')
            ->andWhere('c.lieuDepart IN (:val2)')
            ->andWhere('c.lieuArrivee IN (:val3)')
            ->andWhere('c.nbPlaces > :val4')
            ->andWhere('c.statut = :val5')
            ->setParameter('val', $lieuDepart)
            ->setParameter('val1', $lieuArrivee)
            ->setParameter('val2', $prochesDep)
            ->setParameter('val3', $prochesArr)
            ->setParameter('val4', 0)
            ->setParameter('val5', 'en attente');

        if ($strictDate) {
            $qb->andWhere('c.dateDepart = :dateDepart')
            ->setParameter('dateDepart', $dateDepart);
        } else {
            $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
            $qb->andWhere('c.dateDepart >= :today')
            ->setParameter('today', $today);

            $qb->addSelect('ABS(DATE_DIFF(c.dateDepart,:dateRef)) AS HIDDEN deltaDays')
       ->setParameter('dateRef', $dateDepart)
            ->addOrderBy('deltaDays', 'ASC');
        }
        $qb->orderBy('tri', 'ASC')
        ->addOrderBy('c.heureDepart', 'ASC');

        if (!$strictDate) {
            $qb->setMaxResults(1);
            $result = $qb->getQuery()->getOneOrNullResult();

            return $result ? $result->getDateDepart() : null;
        }

        return $qb->getQuery()->getResult();
    }

    public function findNotRespondedCovoiturages(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
        ->innerJoin('c.users', 'u')
        ->leftJoin('c.reponses', 'r', 'WITH', 'r.user = :user')
        ->andWhere('r.id IS NULL')
        ->andWhere('u = :user')
        ->andWhere('c.statut= :val')
        ->setParameter('user', $user)
        ->setParameter('val', 'terminé');

        return $qb->getQuery()->getResult();
    }

    public function findByChauffeur(int $chauffeurId): array
    {
        $qb = $this->createQueryBuilder('c')
        ->innerJoin('c.voiture', 'v')
        ->innerJoin('v.user', 'u')
        ->andWhere('u.id = :chauffeurId')
        ->setParameter('chauffeurId', $chauffeurId);

        return $qb->getQuery()->getResult();
    }

    public function findByDay(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT c.date_depart,COUNT(*) As nombre FROM covoiturage c GROUP BY c.date_depart ORDER BY c.date_depart ASC';
        $resultSet = $conn->executeQuery($sql);

        return $resultSet->fetchAllAssociative();
    }
    //    public function findOneBySomeField($value): ?Covoiturage
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
