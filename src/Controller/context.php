<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Credit;
use App\Entity\Marque;
use App\Entity\Operation;
use App\Entity\Parametre;
use App\Entity\Reponse;
use App\Entity\RoleEntity;
use App\Entity\User;
use App\Entity\Voiture;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class Context
{
    public static function Context()
    {
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if ($object instanceof User) {
                    return $object->getNom();
                } elseif ($object instanceof Covoiturage) {
                    return $object->getId();
                } elseif ($object instanceof Voiture) {
                    return $object->getId();
                } elseif ($object instanceof Parametre) {
                    return $object->getId();
                } elseif ($object instanceof Covoiturage) {
                    return $object->getId();
                } elseif ($object instanceof Marque) {
                    return $object->getId();
                } elseif ($object instanceof Reponse) {
                    return $object->getId();
                } elseif ($object instanceof Avis) {
                    return $object->getId();
                } elseif ($object instanceof Credit) {
                    return $object->getId();
                } elseif ($object instanceof Operation) {
                    return $object->getId();
                } elseif ($object instanceof RoleEntity) {
                    return $object->getId();
                } else {
                    throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
            },
        ];

        return $context;
    }
}
