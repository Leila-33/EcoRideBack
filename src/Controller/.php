<?php

namespace App\Controller;
use OpenApi\Annotations as OA;

use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Entity\Marque;
use App\Entity\User;
use App\Entity\Configuration;

use App\Repository\AvisRepository;
use App\Repository\VoitureRepository;
use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/avis', name: 'app_api_avis_')]
class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/avis",
     *     summary="Créer un avis",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du avis",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du avis",
     *          @OA\Property(property="name", type="string", example="Nom du avis"),
     *          @OA\Property(property="description", type="string", example="Description du avis")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Avis créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du avis"),
     *             @OA\Property(property="description", type="string", example="Description du avis"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $avis = $this->serializer->deserialize($request->getContent(), Avis::class, 'json');
        $this->getUser()->addAvi($avis);
        $avis->setUser($this->getUser());
        $this->manager->persist($avis);
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
                'avis' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Avis ? $attributeValue : '';
                },'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                },  'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';

                }

                ],
            ];
        $responseData = $this->serializer->serialize($avis, 'json',$context );


        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    
#[Route('/allAvis/{id}', name: 'allAvis', methods: 'GET')]
public function allAvis(int $id): JsonResponse
{
    $avis = $this->repository->findBy(['idChauffeur' => $id]);

 
   
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
        },  'avis' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Avis ? $attributeValue : '';
        },'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                }, 
        
        ]
];
            if ($avis) {
                $responseData = $this->serializer->serialize($avis,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

}
    
        #[Route('/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/avis/{id}",
     *     summary="Afficher un avis par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du avis à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avis trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du avis"),
     *             @OA\Property(property="description", type="string", example="Description du avis"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Avis non trouvé"
     *     )
     * )
     */
        public function show(int $id): JsonResponse
        {
            $avis = $this->repository->findOneBy(['id' => $id]);
       
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
                },  'aviss' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Avis ? $attributeValue : '';
                },
                
                ]
];

            if ($avis) {
                $responseData = $this->serializer->serialize($avis,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 
     #[Route('/avisAVerifier', name: 'avisAVerifier', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/avis/{id}",
     *     summary="Afficher un avis par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du avis à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avis trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du avis"),
     *             @OA\Property(property="description", type="string", example="Description du avis"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Avis non trouvé"
     *     )
     * )
     */
        public function avisAVerifier(): JsonResponse
        {
            $avis = $this->repository->findBy(['statut' => 'en attente']);
       
     $context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                   if ($object instanceof User) {return $object->getNom();}
                    else if ($object instanceof Covoiturage) {return $object->getId();}
                    else if ($object instanceof Voiture) {return $object->getId();}
                    else if ($object instanceof Parametre) {return $object->getId();}
                    else if ($object instanceof Covoiturage) {return $object->getId();}
                    else if ($object instanceof Marque) {return $object->getId();}
                    else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
                    },
        ];
            if ($avis) {
                $responseData = $this->serializer->serialize($avis,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/avis/{id}",
     *     summary="Modifier un avis par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du avis à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du avis",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du avis",
     *          @OA\Property(property="name", type="string", example="Nom du avis"),
     *          @OA\Property(property="description", type="string", example="Description du avis")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Avis trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du avis"),
     *             @OA\Property(property="description", type="string", example="Description du avis"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Avis non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $avis = $this->repository->findOneBy(['id' => $id]);
            if ($avis) { 
            $avis->setStatut($data['statut']);  
            /*if ($avis) { 
                $avis = $this->serializer->deserialize(
                    $request->getContent(),
                     Avis::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $avis]
                );*/  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
                
    
   
        #[Route('/reponses/{id}', name: 'editReponses', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/avis/{id}",
     *     summary="Modifier un avis par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du avis à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du avis",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du avis",
     *          @OA\Property(property="name", type="string", example="Nom du avis"),
     *          @OA\Property(property="description", type="string", example="Description du avis")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Avis trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du avis"),
     *             @OA\Property(property="description", type="string", example="Description du avis"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Avis non trouvé"
     *     )
     * )
     */
         public function editReponses(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $avis = $this->repository->findOneBy(['id' => $id]);
            if ($avis) {
                if(array_key_exists('reponse',$data)){$avis->setReponse($this->getUser()->getId(),$data['reponse']);}          
                else{$avis->setReponse1($this->getUser()->getId(),$data['reponse1']);}           
            $this->manager->flush();

            return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $avis = $this->repository->findOneBy(['id' => $id]);
            if ($avis) {
                $this->manager->remove($avis);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
