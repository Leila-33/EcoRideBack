<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
use App\Entity\Voiture;
use App\Entity\Covoiturage;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;

use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/configuration', name: 'app_api_configuration_')]
class ConfigurationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ConfigurationRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/configuration",
     *     summary="Créer un configuration",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du configuration",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du configuration",
     *          @OA\Property(property="name", type="string", example="Nom du configuration"),
     *          @OA\Property(property="description", type="string", example="Description du configuration")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuration créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du configuration"),
     *             @OA\Property(property="description", type="string", example="Description du configuration"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $configurationFound = $this->repository->findAll(); 
        if ($configurationFound){
        for ($i=0; $i<count($configurationFound);$i++){ 
            if ($configurationFound[$i]->getUser()->getId()==$this->getUser()->getId()){
                $configuration=$configurationFound[$i];}}}
        
        else{$configuration = new Configuration;
        $this->getUser()->addConfiguration($configuration);
        $configuration->setUser($this->getUser());
        $this->manager->persist($configuration);
        }
        $this->manager->flush();
             
$context = [  AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
    if (!$object instanceof Voiture) {
        throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
    }

    // serialize the nested Organization with only the name (and not the members)
    return $object->getId();
 },
    AbstractNormalizer::CALLBACKS => [
        // all callback parameters are optional (you can omit the ones you don't use)
        'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Voiture ? $attributeValue : get_class($attributeValue);
        },
        'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },
        'configurations' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Configuration ? $attributeValue : '';
        }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Parametre ? $attributeValue : '';
        }, 'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Covoiturage ? $attributeValue : '';
        },
        
    ],
];
        $responseData = $this->serializer->serialize($configuration, 'json',$context);


        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    

    
        #[Route('/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/configuration/{id}",
     *     summary="Afficher un configuration par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du configuration à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du configuration"),
     *             @OA\Property(property="description", type="string", example="Description du configuration"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuration non trouvé"
     *     )
     * )
     */
        public function show(int $id): JsonResponse
        {
            $configuration = $this->repository->findOneBy(['immatriculation' => $id]);
    
            if ($configuration) {
                $responseData = $this->serializer->serialize($configuration,  'json');
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse($responseData, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/configuration/{id}",
     *     summary="Modifier un configuration par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du configuration à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du configuration",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du configuration",
     *          @OA\Property(property="name", type="string", example="Nom du configuration"),
     *          @OA\Property(property="description", type="string", example="Description du configuration")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Configuration trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du configuration"),
     *             @OA\Property(property="description", type="string", example="Description du configuration"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuration non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {
            $configuration = $this->repository->findOneBy(['immatriculation' => $id]);
        
            if ($configuration) { 
                $configuration = $this->serializer->deserialize(
                    $request->getContent(),
                     Configuration::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $configuration]
                );  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        

     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $configuration = $this->repository->findOneBy(['id' => $id]);
            if ($configuration) {
                $this->manager->remove($configuration);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
