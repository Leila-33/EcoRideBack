<?php

namespace App\Controller;
use OpenApi\Annotations as OA;

include_once 'Context.php';

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
use Symfony\Component\Validator\Validator\ValidatorInterface;


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
    public function new(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $parametre = $this->serializer->deserialize($request->getContent(), Parametre::class, 'json');
         $errors = $validator->validate($parametre);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString); }
        $parametre->setPropriete(strip_tags($parametre->getPropriete()));
        $parametre->setValeur(strip_tags($parametre->getValeur()));
        $parametreFound = $this->repository->findOneBy(['user' => $this->getUser(), 'propriete' =>$parametre->getPropriete()]);
  
       if  ($parametreFound ){
            if ($parametre->getValeur()!=$parametreFound->getValeur()){
                $parametreFound->setValeur($parametre->getValeur());
                $this->manager->flush();
            }
             $parametre=$parametreFound;}
        else{$this->getUser()->addParametre($parametre);
            $this->manager->persist($parametre);
            $this->manager->flush();}
        
        $responseData = $this->serializer->serialize($parametre, 'json',Context::context() );
    

        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    

    
        #[Route( name: 'show', methods: 'GET')]       
     
        public function show(): JsonResponse
            {
            $parametre = $this->repository->findByUser($this->getUser()->getId());
            if ($parametre) {
            $responseData = $this->serializer->serialize($parametre, 'json',Context::context() );
            return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);

            
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
   
