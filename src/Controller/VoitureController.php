<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
include_once 'Context.php';

use App\Entity\Voiture;
use App\Repository\VoitureRepository;
use App\Repository\MarqueRepository;

use DateTimeImmutable ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/voiture', name: 'app_api_voiture_')]
class VoitureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private VoitureRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private MarqueRepository $marquerepository
    ){}



  
    
     
    
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
   
    #[Route('/addVoiture', name: 'addVoiture', methods: 'POST')]
        public function addVoiture(Request $request, ValidatorInterface $validator): JsonResponse
        {
         $voiture = $this->serializer->deserialize($request->getContent(), Voiture::class, 'json');
        $errors = $validator->validate($voiture);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);}
        $errors = $validator->validate($voiture->getMarque());
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);
    }      
        $voiture->setImmatriculation(strip_tags($voiture->getImmatriculation()));
        $voiture1 = $this->repository->findOneBy(['immatriculation' => $voiture->getImmatriculation()]);
        if (!$voiture1){
           $voiture->setDatePremiereImmatriculation(strip_tags($voiture->getDatePremiereImmatriculation()));
        if ($voiture->getMarque()){
        $voiture->getMarque()->setLibelle(strip_tags($voiture->getMarque()->getLibelle()));}      
        $voiture->setModele(strip_tags($voiture->getModele()));
        $voiture->setCouleur(strip_tags($voiture->getCouleur()));  
            $libelle=$voiture->getMarque()->getLibelle();
            $marque = $this->marquerepository->findOneBy(['libelle' => $libelle]);
            if ($marque) {$marque->addVoiture($voiture);}
            else{$voiture->getMarque()->addVoiture($voiture);
                $this->manager->persist($voiture->getMarque());}
            $this->getUser()->addVoiture($voiture);
            $this->manager->persist($voiture);
            $this->manager->flush();
            $responseData = $this->serializer->serialize($voiture,'json', Context::context());      
            return new JsonResponse($responseData, Response::HTTP_CREATED, [] ,true);    }
        return new JsonResponse(['error'=>'Cette voiture est déjà enregistrée'], Response::HTTP_CONFLICT);
  
}

#[Route('/allVoitures', name: 'allVoitures', methods: 'GET')]
public function allVoitures(): JsonResponse
{
    $voitures = $this->getUser()->getVoitures();

$responseData = $this->serializer->serialize($voitures, 'json',Context::context());
return new JsonResponse($responseData, Response::HTTP_OK, [], true);
}}

   
