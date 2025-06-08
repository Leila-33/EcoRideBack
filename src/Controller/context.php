<?php
namespace App\Controller;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;

use App\Entity\Credit;
use App\Entity\Voiture;
use App\Entity\Marque;
use App\Entity\User;
use App\Entity\Reponse;
use App\Entity\Avis;
use App\Entity\Parametre;
use App\Entity\Operation;
use App\Entity\Role;

use App\Entity\Covoiturage;
class Context
{
static public function Context(){
$context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                   if ($object instanceof User) {return $object->getNom();}
                    else if ($object instanceof Covoiturage) {return $object->getId();}
                    else if ($object instanceof Voiture) {return $object->getId();}
                    else if ($object instanceof Parametre) {return $object->getId();}
                    else if ($object instanceof Covoiturage) {return $object->getId();}
                    else if ($object instanceof Marque) {return $object->getId();}
                    else if ($object instanceof Reponse) {return $object->getId();}
                     else if ($object instanceof Avis) {return $object->getId();}
                    else if ($object instanceof Credit) {return $object->getId();}
                    else if ($object instanceof Operation) {return $object->getId();}
                    else if ($object instanceof Role) {return $object->getId();}

                    else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
                    },
        ];
   
   return $context;}
}