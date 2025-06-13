<?php

namespace App\Controller;
use OpenApi\Annotations as OA;

use App\Entity\Covoiturage;
include_once 'Context.php';

use App\Repository\CovoiturageRepository;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/covoiturage', name: 'app_api_covoiturage_')]
class CovoiturageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CovoiturageRepository $repository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private AvisRepository $avisrepository,

    ){}


    #[Route(name: 'new', methods: 'POST')]  
    /** @OA\Post(
     *     path="/api/covoiturage",
     *     summary="Créer un covoiturage",
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
     *         response=201,
     *         description="Covoiturage créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du covoiturage"),
     *             @OA\Property(property="description", type="string", example="Description du covoiturage"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function new(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $covoiturage = $this->serializer->deserialize($request->getContent(), Covoiturage::class, 'json');
         $errors = $validator->validate($covoiturage);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);}
        $covoiturage->setLieuDepart(strip_tags($covoiturage->getLieuDepart()));
        $covoiturage->setLieuArrivee(strip_tags($covoiturage->getLieuArrivee()));
        $covoiturage->setStatut('en attente');
        $voiture = $this->getUser()->getVoitures()[strip_tags($covoiturage->getVoiture()->getImmatriculation())];
        $covoiturage->setIdChauffeur($this->getUser()->getId());
        $voiture->addCovoiturage($covoiturage);
        $this->manager->persist($covoiturage);
        $this->manager->flush();
        $responseData = $this->serializer->serialize($covoiturage, 'json',Context::context() );
        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);
}


#[Route('/allCovoituragesChauffeur/{idChauffeur}', name: 'allCovoituragesChauffeur', methods: 'GET')]
public function allCovoituragesChauffeur(int $idChauffeur): JsonResponse
{
        $covoiturages = $this->repository->findBy(['idChauffeur' => $idChauffeur]);    
     $covoiturages1=[[],[]];
    foreach($covoiturages as $covoiturage){
          if (($covoiturage->getStatut()=="en attente") ||($covoiturage->getStatut()=="en cours")){
            $covoiturages1[0][]=$covoiturage;
        }else{$covoiturages1[1][]= $covoiturage;}
 }
             if ($covoiturages1) {
                $responseData = $this->serializer->serialize($covoiturages1,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

        }

    
#[Route('/allCovoiturages', name: 'allCovoiturages', methods: 'GET')]
public function allCovoiturages(): JsonResponse
{   $covoiturages1=[[],[]];
    $covoiturages = $this->getUser()->getCovoiturages();
    foreach($covoiturages as $covoiturage){
          if (($covoiturage->getStatut()=="en attente") ||($covoiturage->getStatut()=="en cours")){
            $covoiturages1[0][]=$covoiturage;
        }else{$covoiturages1[1][]=$covoiturage;}
 }
$responseData = $this->serializer->serialize($covoiturages1, 'json',Context::context());
return new JsonResponse($responseData, Response::HTTP_OK, [], true);
}

   
 
   
     #[Route('/Covoiturages/{lieuDepart}/{lieuArrivee}/{dateDepart}', name: 'Covoiturages', methods: 'GET')]
public function Covoiturages(string $lieuDepart,string $lieuArrivee,string $dateDepart): JsonResponse
{
           // $covoiturage = $this->repository->findBy(['lieu_depart' => $lieuDepart,'lieu_arrivee' => $lieuArrivee,'date_depart' => new Datetime($dateDepart), 'statut' => 'en attente', 'nb_places'=> 'nb_places'>0]);
        $covoiturages= $this->repository->findByDate($lieuDepart,$lieuArrivee,$dateDepart);
        $covoiturages1=[];
        foreach($covoiturages as $covoiturage){
        $covoiturage1 = $this->repository->findOneBy(['id' => $covoiturage['id']]);
        $idChauffeur=$covoiturage1->getIdChauffeur();
        $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]); 
        if ($avis ){
        $noteChauffeur=0;
        foreach($avis as $avi){
            $noteChauffeur+=$avi->getNote();}
        $noteChauffeur/=count($avis);
        $covoiturage1=$covoiturage1->setNoteChauffeur($noteChauffeur);}
        $covoiturages1[]=$covoiturage1;}
    

            if ($covoiturages1) {
                $responseData = $this->serializer->serialize($covoiturages1,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

} 
     #[Route('/CovoituragesSansDate/{lieuDepart}/{lieuArrivee}', name: 'CovoituragesSansDate', methods: 'GET')]
public function CovoituragesSansDate(string $lieuDepart,string $lieuArrivee): JsonResponse
{
        $covoiturages= $this->repository->findByPlace($lieuDepart,$lieuArrivee);
        $covoiturages1=[];
        foreach($covoiturages as $covoiturage){
        $covoiturage1 = $this->repository->findOneBy(['id' => $covoiturage['id']]);
        $idChauffeur=$covoiturage1->getIdChauffeur();
        $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]);   
         if ($avis ){
        $noteChauffeur=0;
        foreach($avis as $avi){
            $noteChauffeur+=$avi->getNote();}
        $noteChauffeur/=count($avis);
        $covoiturage1=$covoiturage1->setNoteChauffeur($noteChauffeur);}
        $covoiturages1[]=$covoiturage1;}
   
            if ($covoiturages1) {
                $responseData = $this->serializer->serialize($covoiturages1,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

} 
        #[Route('/c/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/covoiturage/{id}",
     *     summary="Afficher un covoiturage par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du covoiturage à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
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
        public function show(int $id): JsonResponse
        {
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            $idChauffeur=$covoiturage->getIdChauffeur();
            $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]);   
            if ($avis){$noteChauffeur=0;
            foreach($avis as $avi){
            $noteChauffeur+=$avi->getNote();}
            $noteChauffeur/=count($avis);
            $covoiturage=$covoiturage->setNoteChauffeur($noteChauffeur);}
       


            if ($covoiturage) {
                $responseData = $this->serializer->serialize($covoiturage,  'json',Context::context());
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
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
         public function edit(int $id, Request $request,ValidatorInterface $validator): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) { 
                $covoiturage->setStatut($data['statut']);
                $errors = $validator->validate($covoiturage);
                if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return new Response($errorsString);}    
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        
   

        #[Route('/addUser/{id}', name: 'addUser', methods: 'PUT')]
        /** @OA\Get(
     *     path="/api/covoiturage/{id}",
     *     summary="Afficher un covoiturage par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du covoiturage à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
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
        public function addUser(int $id): JsonResponse
        {
            
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) {
            $covoiturage->addUser($this->getUser());
            $covoiturage->setNbPlace($covoiturage->getNbPlace()-1);
            $this->manager->flush();
            $responseData = $this->serializer->serialize($covoiturage,  'json',Context::context());
            return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 

    #[Route('/removeUser/{id}', name: 'removeUser', methods: 'DELETE')]
 public function removeUser(int $id): JsonResponse
        {           
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) {
            $covoiturage->removeUser($this->getUser());
            $covoiturage->setNbPlace($covoiturage->getNbPlace()+1);
            $this->manager->flush();
            $responseData = $this->serializer->serialize($covoiturage,  'json',Context::context());
            return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 

     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $covoiturage = $this->repository->findOneBy(['id' => $id]);
            if ($covoiturage) {
                $this->manager->remove($covoiturage);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }


    #[Route('/prixMaximumEtMinimum/{lieuDepart}/{lieuArrivee}/{dateDepart}', name: 'prixMaximum', methods: 'GET')]
public function prixMaximumEtMinimum(string $lieuDepart,string $lieuArrivee,string $dateDepart): JsonResponse
{
        $prixEtDureeMaximumEtMinimum= $this->repository->findMaximumPrice($lieuDepart,$lieuArrivee,$dateDepart);
        $prixEtDureeMaximumEtMinimum[1]= $this->repository->findMinimumPrice($lieuDepart,$lieuArrivee,$dateDepart)[0];
        $prixEtDureeMaximumEtMinimum[2]=$this->repository->findDureeMaximum($lieuDepart,$lieuArrivee,$dateDepart)[0]; 
        $prixEtDureeMaximumEtMinimum[3]=$this->repository->findDureeMinimum($lieuDepart,$lieuArrivee,$dateDepart)[0]; 
  
            if ($prixEtDureeMaximumEtMinimum) {
                $responseData = $this->serializer->serialize($prixEtDureeMaximumEtMinimum,  'json');
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);
        }
    
        #[Route('/nombreDeparts', name: 'nombreDeparts', methods: 'GET')]
public function nombreDeparts(): JsonResponse
{
        $nombreDeparts= $this->repository->findByDay();  
            if ($nombreDeparts) {
                $responseData = $this->serializer->serialize($nombreDeparts,  'json');
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, Response::HTTP_NOT_FOUND);
        }
    
    
 }