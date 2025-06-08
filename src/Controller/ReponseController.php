<?php
namespace App\Controller;

use OpenApi\Annotations as OA;
include_once 'Context.php';
use App\Entity\Reponse;


use App\Repository\CovoiturageRepository;
use App\Repository\ReponseRepository;
use App\Repository\VoitureRepository;
use App\Repository\AvisRepository;
use App\Repository\UserRepository;

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

#[Route('/api/reponse', name: 'app_api_reponse_')]
class ReponseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ReponseRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private CovoiturageRepository $covoituragerepository,


    ){}


    #[Route('/setReponse1/{id}', name: 'setReponse1', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/reponse",
     *     summary="Créer un reponse",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du reponse",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du reponse",
     *          @OA\Property(property="name", type="string", example="Nom du reponse"),
     *          @OA\Property(property="description", type="string", example="Description du reponse")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Reponse créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du reponse"),
     *             @OA\Property(property="description", type="string", example="Description du reponse"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function setReponse1(Request $request, int $id): JsonResponse
    {
        $reponse = $this->serializer->deserialize($request->getContent(), Reponse::class, 'json');
        $covoiturage=$this->covoituragerepository->findOneBy(['id' => $id]);
        if ($covoiturage) { 
            $this->getUser()->addReponse($reponse);
            $covoiturage->addReponse($reponse);
            $this->manager->persist($reponse);
            $this->manager->flush();
            $responseData = $this->serializer->serialize($reponse, 'json',Context::context());
            return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);
        }         
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
            
 
}



    
#[Route('/show/{id}', name: 'Reponse', methods: 'GET')]
public function Reponse(int $id): JsonResponse
   {     $reponse = $this->repository->findOneBy(['user' => $this->getUser()->getId(), 'covoiturage' => $id]);

if ($reponse) { 
              $reponse = $this->serializer->serialize($reponse, 'json',Context::context());
                return new jsonResponse($reponse, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);

            }

   
     
#[Route('/reponsesNon', name: 'reponseNon', methods: 'GET')]
public function ReponsesNon(): JsonResponse
   {     $reponse = $this->repository->findBy(['reponse1' => 'non']);

if ($reponse) { 
              $reponse = $this->serializer->serialize($reponse, 'json',Context::context());
                return new jsonResponse($reponse, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);

            }
  
        
        #[Route('/editReponse2/{id}', name: 'editReponse2', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/reponse/{id}",
     *     summary="Modifier un reponse par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du reponse à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du reponse",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du reponse",
     *          @OA\Property(property="name", type="string", example="Nom du reponse"),
     *          @OA\Property(property="description", type="string", example="Description du reponse")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Reponse trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du reponse"),
     *             @OA\Property(property="description", type="string", example="Description du reponse"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reponse non trouvé"
     *     )
     * )
     */
         public function editReponse2(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $reponse = $this->repository->findOneBy(['user' => $this->getUser()->getId(), 'covoiturage' => $id]);
            if ($reponse) { 
                $reponse->setReponse2($data['reponse2']);
                $this->manager->flush();
                $reponse = $this->serializer->serialize($reponse, 'json',Context::context());
                return new jsonResponse($reponse, Response::HTTP_OK, [], true);
            }
            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        
   
        #[Route('/editCommentaire/{id}', name: 'editCommentaire', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/reponse/{id}",
     *     summary="Modifier un reponse par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du reponse à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du reponse",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du reponse",
     *          @OA\Property(property="name", type="string", example="Nom du reponse"),
     *          @OA\Property(property="description", type="string", example="Description du reponse")
     * )
     * ),
     *     @OA\Response(
     *         response=204,
     *         description="Reponse trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du reponse"),
     *             @OA\Property(property="description", type="string", example="Description du reponse"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reponse non trouvé"
     *     )
     * )
     */
         public function editCommentaire(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $reponse = $this->repository->findOneBy(['user' => $this->getUser()->getId(), 'covoiturage' => $id]);
            if ($reponse) { 
            $reponse->setCommentaire($data['commentaire']); 
                $this->manager->flush();
             $reponse = $this->serializer->serialize($reponse, 'json',Context::context());

             return new jsonResponse($reponse, Response::HTTP_OK, [], true);
            }
            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }

 }