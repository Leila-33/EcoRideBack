<?php

namespace App\Controller;
use OpenApi\Annotations as OA;

use App\Entity\Voiture;
use App\Repository\VoitureRepository;

use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\AbstractNormalizer;

use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/voiture', name: 'app_api_voiture_')]
class VoitureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private VoitureRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/voiture",
     *     summary="Créer un voiture",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du voiture",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du voiture",
     *          @OA\Property(property="name", type="string", example="Nom du voiture"),
     *          @OA\Property(property="description", type="string", example="Description du voiture")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Voiture créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du voiture"),
     *             @OA\Property(property="description", type="string", example="Description du voiture"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $voiture = $this->serializer->deserialize($request->getContent(), Voiture::class, 'json');

        $this->manager->persist($voiture);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($voiture, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_voiture_show',
            ['id' => $voiture->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);

 
}
    

    
        #[Route('/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/voiture/{id}",
     *     summary="Afficher un voiture par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du voiture à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Voiture trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du voiture"),
     *             @OA\Property(property="description", type="string", example="Description du voiture"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voiture non trouvé"
     *     )
     * )
     */
        public function show(int $id): JsonResponse
        {
            $voiture = $this->repository->findOneBy(['immatriculation' => $id]);
    
            if ($voiture) {
                $responseData = $this->serializer->serialize($voiture,  'json');
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse($responseData, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/voiture/{id}",
     *     summary="Modifier un voiture par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du voiture à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du voiture",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du voiture",
     *          @OA\Property(property="name", type="string", example="Nom du voiture"),
     *          @OA\Property(property="description", type="string", example="Description du voiture")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Voiture trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du voiture"),
     *             @OA\Property(property="description", type="string", example="Description du voiture"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voiture non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {
            $voiture = $this->repository->findOneBy(['immatriculation' => $id]);
        
            if ($voiture) { 
                $voiture = $this->serializer->deserialize(
                    $request->getContent(),
                     Voiture::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $voiture]
                );  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        

     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $voiture = $this->repository->findOneBy(['id' => $id]);
            if ($voiture) {
                $this->manager->remove($voiture);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
