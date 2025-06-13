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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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


 
  
        #[Route('/payer/{id1}/{id2}', name: 'payer', methods: 'POST')]  

         public function payer(Request $request, int $id1,int $id2,ValidatorInterface $validator): JsonResponse
    {       
        $personne1=$this->userrepository->findOneBy(['id' => $id1]);
        $personne2=$this->userrepository->findOneBy(['id' =>$id2 ]);
    if ($personne1 && $personne2 ) { 
        $operation1 = $this->serializer->deserialize($request->getContent(),Operation::class, 'json');
        $errors = $validator->validate($operation);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);}
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

            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
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