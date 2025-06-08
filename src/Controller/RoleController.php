<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
include_once 'Context.php';

use App\Entity\Role;

use App\Repository\RoleRepository;

use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;

use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/role', name: 'app_api_role_')]
class RoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RoleRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/role",
     *     summary="Créer un role",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du role",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du role",
     *          @OA\Property(property="libelle", type="string", example="Nom du role"),
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Role créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="libelle", type="string", example="Nom du role"),
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
    $role = $this->serializer->deserialize($request->getContent(), Role::class, 'json');  
    $libelle=$role->getLibelle();
    $roleFound = $this->repository->findOneBy(['libelle' => $libelle]);
    if ($roleFound) {$roleFound->addUser($this->getUser());
        $role=$roleFound;}
    else{ $role->addUser($this->getUser());
            $this->manager->persist($role);}
        $this->manager->flush();
        $responseData = $this->serializer->serialize($role, 'json', Context::context());
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

    }
    
        #[Route(name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/role/{id}",
     *     summary="Afficher un role par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du role à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du role"),
     *             @OA\Property(property="description", type="string", example="Description du role"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role non trouvé"
     *     )
     * )
     */
        public function show(): JsonResponse
        {
            $role = $this->getUser()->getRole(); 
            if ($role) {         
                $responseData = $this->serializer->serialize($role,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            return new jsonResponse($responseData, Response::HTTP_NOT_FOUND);    
        } 
    

   
        

     
    
        #[Route('/{libelle}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(string $libelle):JsonResponse
            {
            $role = $this->repository->findOneBy(['libelle' => $libelle]);
            if ($role) {            
                $role->removeUser($this->getUser());
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }
}
   
