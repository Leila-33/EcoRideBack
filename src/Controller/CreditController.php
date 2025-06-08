<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
use App\Entity\Operation;
use App\Entity\Credit;
use App\Repository\CreditRepository;
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

#[Route('/api/credit', name: 'app_api_credit_')]
class CreditController extends AbstractController
{
    public function __construct(
        private UserRepository $userrepository,
        private EntityManagerInterface $manager,
        private CreditRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,

    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/credit",
     *     summary="Créer un credit",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du credit",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du credit",
     *          @OA\Property(property="name", type="string", example="Nom du credit"),
     *          @OA\Property(property="description", type="string", example="Description du credit")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Credit créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du credit"),
     *             @OA\Property(property="description", type="string", example="Description du credit"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request): JsonResponse
    {
        $operation = $this->serializer->deserialize($request->getContent(),Operation::class, 'json');
        $this->getUser()->addOperation($operation);
        $this->getUser()->getCredit()->setTotal($operation->getOperation());
        $this->manager->persist($operation);
        $this->manager->flush();
        $responseData = $this->serializer->serialize($this->getUser()->getCredit(), 'json',$Context::context() );
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}


   #[Route('/payerChauffeur/{id}', name: 'payerChauffeur', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/credit",
     *     summary="Créer un credit",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du credit",
     *         @OA\JsonContent(
     *         type="object",
     *         description="Données du credit",
     *          @OA\Property(property="name", type="string", example="Nom du credit"),
     *          @OA\Property(property="description", type="string", example="Description du credit")
     * )
     * ),
     *     @OA\Response(
     *         response=201,
     *         description="Credit créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du credit"),
     *             @OA\Property(property="description", type="string", example="Description du credit"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function payerChauffeur(Request $request, int $id): JsonResponse
    {       
        $chauffeur=$this->userrepository->findOneBy(['id' => $id]);   
    if ($chauffeur) { 
        $operation = $this->serializer->deserialize($request->getContent(),Operation::class, 'json');
        $chauffeur->addOperation($operation);
        $chauffeur->getCredit()->setTotal($operation->getOperation());
        $this->manager->persist($operation);
        $this->manager->flush();
        $responseData = $this->serializer->serialize($chauffeur->getCredit(),  'json',Context::context());
        return new jsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
        }
      
        #[Route('/payer/{id1}/{id2}', name: 'payer', methods: 'POST')]  

         public function payer(Request $request, int $id1,int $id2): JsonResponse
    {       
        $personne1=$this->userrepository->findOneBy(['id' => $id1]);
        $personne2=$this->userrepository->findOneBy(['id' =>$id2 ]);
    if ($personne1 && $personne2 ) { 
        $operation1 = $this->serializer->deserialize($request->getContent(),Operation::class, 'json');
       
        $operation2=$this->serializer->deserialize($request->getContent(),Operation::class, 'json');
        if ($id1==9){$operation2->setOperation(-$operation2->getOperation() +2);}
        else{$operation2->setOperation(-$operation2->getOperation());}
        $personne1->addOperation($operation2);
        $personne1->getCredit()->setTotal($operation2->getOperation());
        $personne2->addOperation($operation1);
        $personne2->getCredit()->setTotal($operation1->getOperation());
        $this->manager->persist($operation1);
        $this->manager->persist($operation2);
        $this->manager->flush();
        $reponse = $this->serializer->serialize($operation2, 'json',Context::context());

        return new jsonResponse($reponse, Response::HTTP_OK, [], true);
            } 

            return new jsonResponse(1, Response::HTTP_NOT_FOUND);
        }
 
      #[Route('/nombreCreditsTotal', name: 'nombreCreditsTotal', methods: 'GET')]  
    
public function nombreCreditsTotal(Request $request): JsonResponse
    {       
        $credits=$this->getUser()->getCredit()->getTotal();   
    if ($credits) { 
        $responseData = $this->serializer->serialize($credits,  'json',Context::context());
        return new jsonResponse($responseData, Response::HTTP_OK, [], true);
            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
        }
 
    }