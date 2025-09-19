<?php

namespace App\Repository;

use App\Document\Ville;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;

class VilleRepository
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    // Renvoie un tableau de noms de villes correspondant à une saisie partielle de l'utilisateur ($mot), en effectuant
    // une autocomplétion sur un champ MongoDB nom_normalise
    public function autocomplete(string $mot, int $limite = 10): array
    {
        $motNorm = $this->normalizeString($mot);
        if (!preg_match('/^[a-z]/i', $motNorm)) {
            return [];
        }
        $qb = $this->dm->createQueryBuilder(Ville::class)
        ->field('nom_normalise')->equals(new Regex('^'.preg_quote($motNorm), 'i'))
        ->limit($limite);
        $villes = $qb->getQuery()->execute();

        return array_map(fn ($ville) => $ville->getNom(), iterator_to_array($villes));
    }

    // Renvoie toutes les villes proches d'un point géographiques de coordonnées $coordinates dans un rayon
    // donné grâce à la requête géospatiale geoWithinCenterSphere
    public function findVillesProches(array $coordinates, int $distance = 10): array
    {
        return $this->dm->createQueryBuilder(Ville::class)
        ->field('location')->geoWithinCenterSphere(
            $coordinates[0], $coordinates[1], $distance / 6371)
        ->getQuery()
        ->execute()
        ->toArray()
        ;
    }

    // Cherche une ville dans la base MongoDB en comparant une version normalisée de son nom et retourne son nom exact
    // si elle existe ou null sinon
    public function ville(string $ville): ?string
    {
        $ville = $this->normalizeString($ville);
        $result = $this->dm->createQueryBuilder(Ville::class)
           ->field('nom_normalise')->equals($ville)
           ->getQuery()
           ->getSingleResult();

        return $result ? $result->getNom() : null;
    }

    // Retourne les coordonnées géographiques d'une ville à partir de son nom
    public function findCoordinates(string $ville): array
    {
        $ville = $this->normalizeString($ville);
        $ville = $this->dm->createQueryBuilder(Ville::class)
           ->field('nom_normalise')->equals($ville)
           ->getQuery()
           ->getSingleResult();

        return $ville?->getLocation()['coordinates'];
    }

    // Fonction qui convertit une chaine en :
    // - convertissant les caractères accentués ou spéciaux en leur équivalent ASCII,
    // - mettant le texte en minuscule,
    // - supprimant les caractères non alphanumériques sauf tirets et espaces
    // - réduisant les espaces multiples à un seul espace
    // - supprimant les espaces en début/fin
    public function normalizeString(string $str): string
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^a-z0-9\- ]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);

        return trim($str);
    }
}
//    /**
//     * @return Ville[] Returns an array of Ville objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ville
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
