<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findByNbPlace($lieuDepart,$lieuArrivee,$dateDepart): array
    {            
        return $this->createQueryBuilder('c')
            ->andWhere('c.lieu_depart = :val')  
            ->andWhere('c.lieu_arrivee =:val1')
            ->andWhere('c.date_depart = :val2')
            ->andWhere('c.statut = :val3')
            ->andWhere('c.nb_place > :val4')
            ->setParameter('val', $lieuDepart)
            ->setParameter('val1', $lieuArrivee)
            ->setParameter('val2', $dateDepart)
            ->setParameter('val3', 'en attente')
            ->setParameter('val4', 0)
            ->orderBy('c.heure_depart', 'ASC')
            ->getQuery()
            ->getResult()
        ;

    }  
/*public function findMaximumPrice(string $lieuDepart,string $lieuArrivee,  $dateDepart): array
    {   $entityManager=$this->getEntityManager();

        $query= $entityManager->createQuery(
            'SELECT c FROM App\Entity\Covoiturage c WHERE (c.lieu_depart= :val) ')
            ->setParameter('val', $lieuDepart);

            /*AND WHERE (c.lieu_depart = :val) 
            AND WHERE (c.lieu_arrivee =:val1) AND WHERE(c.date_depart = :val2)AND WHERE(c.statut = :val3)
            AND WHERE(c.nb_place > :val4)')*/
            //->setParameter('val', $lieuDepart);
            /*->setParameter('val1', $lieuArrivee)
            ->setParameter('val2', $dateDepart)
            ->setParameter('val3', 'en attente')
            ->setParameter('val4', 0)
            ->orderBy('c.heure_depart', 'ASC')

        return $query->getResult();
        

    }  
    public function findByNbPlace(string $lieuDepart,string $lieuArrivee,$dateDepart): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT * FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (date_depart=:val2) AND (nb_place > :val3) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0]);
        return $resultSet->fetchAllAssociative();
    }*/
       public function findMaximumPrice(string $lieuDepart,string $lieuArrivee,string $dateDepart): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT MAX(c.prix_personne) FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (c.date_depart=:val2) AND (c.nb_place > :val3) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0]);
        return $resultSet->fetchAllAssociative();
    }
        public function findMinimumPrice(string $lieuDepart,string $lieuArrivee,$dateDepart): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT MIN(c.prix_personne) FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (c.date_depart=:val2) AND (c.nb_place > :val3) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0]);
        return $resultSet->fetchAllAssociative();
    }
    
        public function findDureeMaximum(string $lieuDepart,string $lieuArrivee,$dateDepart): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT MAX(TIMEDIFF(CONCAT(c.date_arrivee," ",c.heure_arrivee),CONCAT(c.date_depart," ",c.heure_depart))) FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (c.date_depart=:val2) AND (c.nb_place > :val3) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0]);
        return $resultSet->fetchAllAssociative();
    }
    public function findDureeMinimum(string $lieuDepart,string $lieuArrivee,$dateDepart): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT MIN(TIMEDIFF(CONCAT(c.date_arrivee," ",c.heure_arrivee),CONCAT(c.date_depart," ",c.heure_depart))) FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (c.date_depart=:val2) AND (c.nb_place > :val3) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0]);
        return $resultSet->fetchAllAssociative();
    }
   public function findByPrice(string $lieuDepart,string $lieuArrivee,string $dateDepart, int $prixPersonne, string $duree): array{
        $conn=$this->getEntityManager()->getConnection();
        $sql='SELECT * FROM covoiturage c WHERE (c.lieu_depart= :val) 
        AND (c.lieu_arrivee=:val1) AND (c.date_depart=:val2) AND (c.nb_place > :val3) AND (c.prix_personne <= :val4)   
        AND (TIMEDIFF(CONCAT(c.date_arrivee," ",c.heure_arrivee),CONCAT(c.date_depart," ",c.heure_depart))< :val5) ORDER BY c.heure_depart ASC';
        $resultSet=$conn->executeQuery($sql,['val'=>$lieuDepart,'val1'=>$lieuArrivee , 'val2'=>$dateDepart, 'val3'=>0, 'val4'=>$prixPersonne, 'val5'=>$duree]);
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
