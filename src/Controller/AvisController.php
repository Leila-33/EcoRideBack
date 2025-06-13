<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
include_once 'Context.php';


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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function new(Request $request,ValidatorInterface $validator): JsonResponse
    {
        $avis = $this->serializer->deserialize($request->getContent(), Avis::class, 'json');
        $errors = $validator->validate($avis);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);}
        $covoiturage->setCommentaire(strip_tags($covoiturage->getCommentaire()));
        $this->getUser()->addAvi($avis);
        $this->manager->persist($avis);
        $this->manager->flush();
        $responseData = $this->serializer->serialize($avis, 'json',Context::context() );
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);
}
    
#[Route('/allAvis/{id}', name: 'allAvis', methods: 'GET')]
public function allAvis(int $id): JsonResponse
{
    $avis = $this->repository->findBy(['idChauffeur' => $id]); 
 if ($avis) {
                $responseData = $this->serializer->serialize($avis,  'json',Context::context());
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
            if ($avis) {
                $responseData = $this->serializer->serialize($avis,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);        
        } 
             
   
        #[Route('validerAvis/{id}', name: 'validerAvis', methods: 'PUT')]
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
         public function validerAvis(int $id, Request $request): JsonResponse
        {   
            $avis = $this->repository->findOneBy(['id' => $id]);
            if ($avis) { 
                $avis->setStatut('validé');    
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
   
