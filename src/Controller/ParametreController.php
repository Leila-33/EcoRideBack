<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
use App\Entity\Voiture;

use App\Entity\Parametre;
use App\Repository\ParametreRepository;

use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;


use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/parametre', name: 'app_api_parametre_')]
class ParametreController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ParametreRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/parametre",
     *     summary="Créer un parametre",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du parametre",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du parametre",
     *          @OA\Property(property="name", type="string", example="Nom du parametre"),
     *          @OA\Property(property="description", type="string", example="Description du parametre")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Parametre créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du parametre"),
     *             @OA\Property(property="description", type="string", example="Description du parametre"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $parametre = $this->serializer->deserialize($request->getContent(), Parametre::class, 'json');
        $parametreFound = $this->repository->findOneBy(['propriete' => $parametre->getPropriete()]);
          if  ($parametreFound && $parametreFound->getConfiguration()->getUser()->getId()==$this->getUser()->getId()){
            if ($parametre->getValeur()==$parametreFound->getValeur()){
                $parametre=$parametreFound;}
            else {$parametreFound->setValeur($parametre->getValeur());
                $parametre=$parametreFound;
                $this->manager->flush();
            }}
        else{$this->getUser()->getConfigurations()[0]->addParametre($parametre);
                $parametre->setConfiguration($this->getUser()->getConfigurations()[0]);
                $this->manager->persist($parametre);
            $this->manager->flush();}
            $context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                    if (!$object instanceof User) {
                        throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                    }
            
                    // serialize the nested Organization with only the name (and not the members)
                    return $object->getNom();
                }, AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                    if (!$object instanceof Voiture) {
                        throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                    }
                return $object->getId();
                  },
            AbstractNormalizer::CALLBACKS => [
                
                // all callback parameters are optional (you can omit the ones you don't use)
                'parametres' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';
                },  'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';
                }, 'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Voiture ? $attributeValue : '';
                },'configurations' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Configuration ? $attributeValue : '';
                }, 'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                },

        ], ];
        $responseData = $this->serializer->serialize($parametre, 'json', $context );
    

        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    

    
        #[Route( name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/parametre/{id}",
     *     summary="Afficher un parametre par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du parametre à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parametre trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du parametre"),
     *             @OA\Property(property="description", type="string", example="Description du parametre"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Parametre non trouvé"
     *     )
     * )
     */
        public function show(): JsonResponse
        {   $parametres = $this->getUser()->getConfigurations()[0]->getParametres();
    
            if ($parametres) {
                $context = [
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                        if (!$object instanceof User) {
                            throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                        }
                
                        // serialize the nested Organization with only the name (and not the members)
                        return $object->getNom();
                    },
                    AbstractNormalizer::CALLBACKS => [
                        // all callback parameters are optional (you can omit the ones you don't use)
                        'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                            return $attributeValue instanceof Voiture ? $attributeValue : '';
                        },
                        'role' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                            return $attributeValue instanceof Role ? $attributeValue : '';
                        }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                            return $attributeValue instanceof Parametre ? $attributeValue : '';
                        },
        
                    ],
                   
           
                ];
                $responseData = $this->serializer->serialize($parametres,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse($responseData, status: Response::HTTP_NOT_FOUND);

         
        } 
    
         
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/parametre/{id}",
     *     summary="Modifier un parametre par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du parametre à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du parametre",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du parametre",
     *          @OA\Property(property="name", type="string", example="Nom du parametre"),
     *          @OA\Property(property="description", type="string", example="Description du parametre")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Parametre trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du parametre"),
     *             @OA\Property(property="description", type="string", example="Description du parametre"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Parametre non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {
            $parametre = $this->repository->findOneBy(['immatriculation' => $id]);
        
            if ($parametre) { 
                $parametre = $this->serializer->deserialize(
                    $request->getContent(),
                     Parametre::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $parametre]
                );  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        

     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $parametre = $this->repository->findOneBy(['id' => $id]);
            if ($parametre) {
                $this->manager->remove($parametre);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
