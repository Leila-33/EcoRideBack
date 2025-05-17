<?php

namespace App\Controller;
use OpenApi\Annotations as OA;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Entity\Marque;
use App\Entity\User;
use App\Entity\Configuration;

use App\Repository\CovoiturageRepository;
use App\Repository\VoitureRepository;
use Datetime;
use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/covoiturage', name: 'app_api_covoiturage_')]
class CovoiturageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CovoiturageRepository $repository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/covoiturage",
     *     summary="Créer un covoiturage",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du covoiturage",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du covoiturage",
     *          @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *          @OA\Property(property="description", type="string", example="Description du covoiturage")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Covoiturage créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *             @OA\Property(property="description", type="string", example="Description du covoiturage"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $covoiturage = $this->serializer->deserialize($request->getContent(), Covoiturage::class, 'json');
        $voiture = $this->getUser()->getVoitures()[intval($covoiturage->getVoiture()->getImmatriculation())];
        $voiture->addCovoiturage($covoiturage);
        $this->getUser()->addCovoiturage($covoiturage);
        $covoiturage->addUser($this->getUser());
        $this->manager->persist($covoiturage);
        $this->manager->flush();
// serialize the nested Organization with only the name (and not the members)
$context = [ AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if (!$object instanceof Voiture) {
                    throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
            return $object->getId();
              }, AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if (!$object instanceof Marque) {
                throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
                        return $object->getId();
               }, AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if (!$object instanceof User) {
                throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
                        return $object->getId();
                                                },
                AbstractNormalizer::CALLBACKS => [
                    // all callback parameters are optional (you can omit the ones you don't use)
                    'voiture' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue : '';
                    },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Marque ? $attributeValue : '';
                    },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : get_class($attributeValue);
        },'user' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue : '';
                    },
                'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';

                }

                ],
            ];
        $responseData = $this->serializer->serialize($covoiturage, 'json',$context );


        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    
#[Route('/allCovoiturages', name: 'allCovoiturages', methods: 'GET')]
public function allCovoiturages(): JsonResponse
{
    $covoiturages = $this->getUser()->getCovoiturages();
 
   
    $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
        AbstractNormalizer::CALLBACKS => [
            // all callback parameters are optional (you can omit the ones you don't use)
            'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Voiture ? $attributeValue: '';
            },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Marque ? $attributeValue : '';
            },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },  'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Covoiturage ? $attributeValue : '';
        },
        
        ]
];
$responseData = $this->serializer->serialize($covoiturages, 'json',$context);
return new JsonResponse($responseData, Response::HTTP_OK, [], true);
}
    #[Route('/Covoiturages/{lieuDepart}/{lieuArrivee}/{dateDepart}', name: 'Covoiturages', methods: 'GET')]
public function Covoiturages(string $lieuDepart,string $lieuArrivee,string $dateDepart): JsonResponse
{
            $covoiturage = $this->repository->findBy(['lieu_depart' => $lieuDepart,'lieu_arrivee' => $lieuArrivee,'date_depart' => new Datetime($dateDepart)]);
 
   
    $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
        AbstractNormalizer::CALLBACKS => [
            // all callback parameters are optional (you can omit the ones you don't use)
            'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Voiture ? $attributeValue: '';
            },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Marque ? $attributeValue : '';
            },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },  'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Covoiturage ? $attributeValue : '';
        },
        
        ]
];

            if ($covoiturage) {
                $responseData = $this->serializer->serialize($covoiturage,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

}
        #[Route('/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/covoiturage/{id}",
     *     summary="Afficher un covoiturage par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du covoiturage à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Covoiturage trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *             @OA\Property(property="description", type="string", example="Description du covoiturage"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Covoiturage non trouvé"
     *     )
     * )
     */
        public function show(int $id): JsonResponse
        {
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
       
            $context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
             
                    if ($object instanceof User) {return $object->getNom();}
                    else if ($object instanceof Configuration) {return $object->getId();}
                    else if ($object instanceof Voiture) {return $object->getId();}
                    else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
                    },
                AbstractNormalizer::CALLBACKS => [
                    // all callback parameters are optional (you can omit the ones you don't use)
                    'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue: '';
                    },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Marque ? $attributeValue : '';
                    },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof User ? $attributeValue : '';
                },  'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                },
                
                ]
];

            if ($covoiturage) {
                $responseData = $this->serializer->serialize($covoiturage,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/covoiturage/{id}",
     *     summary="Modifier un covoiturage par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du covoiturage à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du covoiturage",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du covoiturage",
     *          @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *          @OA\Property(property="description", type="string", example="Description du covoiturage")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Covoiturage trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *             @OA\Property(property="description", type="string", example="Description du covoiturage"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Covoiturage non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) { 
            $covoiturage->setStatut($data['statut']);  
            /*if ($covoiturage) { 
                $covoiturage = $this->serializer->deserialize(
                    $request->getContent(),
                     Covoiturage::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $covoiturage]
                );*/  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        
   
        #[Route('/reponses/{id}', name: 'editReponses', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/covoiturage/{id}",
     *     summary="Modifier un covoiturage par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du covoiturage à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du covoiturage",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du covoiturage",
     *          @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *          @OA\Property(property="description", type="string", example="Description du covoiturage")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Covoiturage trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *             @OA\Property(property="description", type="string", example="Description du covoiturage"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Covoiturage non trouvé"
     *     )
     * )
     */
         public function editReponses(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) {
                if(array_key_exists('reponse',$data)){$covoiturage->setReponse($this->getUser()->getId(),$data['reponse']);}          
                else{$covoiturage->setReponse1($this->getUser()->getId(),$data['reponse1']);}           
            $this->manager->flush();

            return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) {
                $this->manager->remove($covoiturage);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
