<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
use App\Entity\Operation;
use App\Repository\OperationRepository;
use App\Repository\UserRepository;
include_once 'Context.php';


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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/operation', name: 'app_api_operation_')]
class OperationController extends AbstractController
{
    public function __construct(
        private UserRepository $userrepository,
        private EntityManagerInterface $manager,
        private OperationRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,

    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/operation",
     *     summary="Créer un operation",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du operation",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du operation",
     *          @OA\Property(property="name", type="string", example="Nom du operation"),
     *          @OA\Property(property="description", type="string", example="Description du operation")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Operation créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du operation"),
     *             @OA\Property(property="description", type="string", example="Description du operation"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $operation = $this->serializer->deserialize($request->getContent(),Operation::class, 'json');
        $errors = $validator->validate($operation);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);}
        $this->getUser()->addOperation($operation);
        $this->getUser()->getCredit()->setTotal($operation->getOperation());
        $this->manager->persist($operation);
        $this->manager->flush();
        $responseData = $this->serializer->serialize($this->getUser()->getOperation(), 'json',$Context::context() );
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}


   #[Route('/nombreCreditsParJour', name: 'nombreCreditsParJour', methods: 'GET')]  
    /** @OA\Post(
     *     path="/api/operation",
     *     summary="Créer un operation",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du operation",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du operation",
     *          @OA\Property(property="name", type="string", example="Nom du operation"),
     *          @OA\Property(property="description", type="string", example="Description du operation")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Operation créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du operation"),
     *             @OA\Property(property="description", type="string", example="Description du operation"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function nombreCreditsParJour(Request $request): JsonResponse
    {       
        $credits=$this->repository->findByDay();   
    if ($credits) { 
        $responseData = $this->serializer->serialize($credits,  'json',Context::context());
        return new jsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
        }





 }