<?php

namespace App\Controller;
use OpenApi\Annotations as OA;
use App\Entity\Operation;

use App\Entity\Credit;
use App\Entity\Voiture;
use App\Entity\Marque;
use App\Entity\User;
use App\Entity\Configuration;
use App\Entity\Covoiturage;

use App\Repository\CreditRepository;
use App\Repository\VoitureRepository;
use App\Repository\AvisRepository;

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
        private EntityManagerInterface $manager,
        private CreditRepository $repository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private AvisRepository $avisrepository,

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

                 $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else if ($object instanceof Covoiturage) {return $object->getId();}
            else if ($object instanceof Credit) {return $object->getId();}

            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
                                   
                AbstractNormalizer::CALLBACKS => [
                    // all callback parameters are optional (you can omit the ones you don't use)
                    'voiture' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue : '';
                    },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Marque ? $attributeValue : '';
                    },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : get_class($attributeValue);
        },'user' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue : '';
                    },
                'credits' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Credit ? $attributeValue : '';
                }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';

                }

                ],
            ];
                
        $responseData = $this->serializer->serialize($this->getUser()->getCredit(), 'json',$context );


        return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

 
}
    
#[Route('/allCredits', name: 'allCredits', methods: 'GET')]
public function allCredits(): JsonResponse
{
    $credits = $this->getUser()->getCredits();
 
   
    $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
        AbstractNormalizer::CALLBACKS => [
            // all callback parameters are optional (you can omit the ones you don't use)
            'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Voiture ? $attributeValue: '';
            },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Marque ? $attributeValue : '';
            },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },  'credits' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Credit ? $attributeValue : '';
        },
        
        ]
];
$responseData = $this->serializer->serialize($credits, 'json',$context);
return new JsonResponse($responseData, Response::HTTP_OK, [], true);
}

   
 
   
     #[Route('/Credits/{lieuDepart}/{lieuArrivee}/{dateDepart}', name: 'Credits', methods: 'GET')]
public function Credits(string $lieuDepart,string $lieuArrivee,string $dateDepart): JsonResponse
{
           // $credit = $this->repository->findBy(['lieu_depart' => $lieuDepart,'lieu_arrivee' => $lieuArrivee,'date_depart' => new Datetime($dateDepart), 'statut' => 'en attente', 'nb_places'=> 'nb_places'>0]);
        $credits= $this->repository->findByPrice($lieuDepart,$lieuArrivee,$dateDepart);
        $credits1=[];
        foreach($credits as $credit){
        $credit1 = $this->repository->findOneBy(['id' => $credit['id']]);
        $idChauffeur=$credit1->getVoiture()->getUser()->getId();
        $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]);   
        $noteChauffeur=0;
        foreach($avis as $avi){
        $noteChauffeur+=$avi->getNote();}
        $noteChauffeur/=count($avis);
        $credit1=$credit1->setNoteChauffeur($noteChauffeur);
        $credits1[]=$credit1;}
    $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
        AbstractNormalizer::CALLBACKS => [
            // all callback parameters are optional (you can omit the ones you don't use)
            'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Voiture ? $attributeValue: '';
            },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Marque ? $attributeValue : '';
            },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },  'credits' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Credit ? $attributeValue : '';
        },
        
        ]
];

            if ($credits1) {
                $responseData = $this->serializer->serialize($credits1,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

} 
     #[Route('/CreditsSansDate/{lieuDepart}/{lieuArrivee}', name: 'CreditsSansDate', methods: 'GET')]
public function CreditsSansDate(string $lieuDepart,string $lieuArrivee): JsonResponse
{
        $credits= $this->repository->findByPlace($lieuDepart,$lieuArrivee);
        $credits1=[];
        foreach($credits as $credit){
        $credit1 = $this->repository->findOneBy(['id' => $credit['id']]);
        $idChauffeur=$credit1->getVoiture()->getUser()->getId();
        $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]);   
        $noteChauffeur=0;
        foreach($avis as $avi){
        $noteChauffeur+=$avi->getNote();}
        $noteChauffeur/=count($avis);
        $credit1=$credit1->setNoteChauffeur($noteChauffeur);
        $credits1[]=$credit1;}
    $context = [
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
     
            if ($object instanceof User) {return $object->getNom();}
            else if ($object instanceof Configuration) {return $object->getId();}
            else if ($object instanceof Voiture) {return $object->getId();}
            else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
            },
        AbstractNormalizer::CALLBACKS => [
            // all callback parameters are optional (you can omit the ones you don't use)
            'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Voiture ? $attributeValue: '';
            },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                return $attributeValue instanceof Marque ? $attributeValue : '';
            },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        },  'credits' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Credit ? $attributeValue : '';
        },
        
        ]
];

            if ($credits1) {
                $responseData = $this->serializer->serialize($credits1,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

} 
        #[Route('/c/{id}', name: 'show', methods: 'GET')]
        /** @OA\Get(
     *     path="/api/credit/{id}",
     *     summary="Afficher un credit par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du credit à afficher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credit trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du credit"),
     *             @OA\Property(property="description", type="string", example="Description du credit"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credit non trouvé"
     *     )
     * )
     */
        public function show(int $id): JsonResponse
        {
            $credit = $this->repository->findOneBy(['id' => $id]);
            $idChauffeur=$credit->getVoiture()->getUser()->getId();
            $avis=$this->avisrepository->findBy(['idChauffeur' => $idChauffeur]);   
            if ($avis){$noteChauffeur=0;
            foreach($avis as $avi){
            $noteChauffeur+=$avi->getNote();}
            $noteChauffeur/=count($avis);
            $credit=$credit->setNoteChauffeur($noteChauffeur);}
       
            $context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
             
                    if ($object instanceof User) {return $object->getNom();}
                    else if ($object instanceof Configuration) {return $object->getId();}
                    else if ($object instanceof Voiture) {return $object->getId();}
                    else{throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');}
                    },
                AbstractNormalizer::CALLBACKS => [
                    // all callback parameters are optional (you can omit the ones you don't use)
                    'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue: '';
                    },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Marque ? $attributeValue : '';
                    },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof User ? $attributeValue : '';
                },  'credits' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Credit ? $attributeValue : '';
                },
                
                ]
];

            if ($credit) {
                $responseData = $this->serializer->serialize($credit,  'json',$context);
                return new jsonResponse($responseData, Response::HTTP_OK, [], true);

            }
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);

         
        } 
    

        
        #[Route('/{id}', name: 'edit', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/credit/{id}",
     *     summary="Modifier un credit par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du credit à modifier",
     *         @OA\Schema(type="integer")
     *     ),
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
     *         response=204,
     *         description="Credit trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du credit"),
     *             @OA\Property(property="description", type="string", example="Description du credit"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credit non trouvé"
     *     )
     * )
     */
         public function edit(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $credit = $this->repository->findOneBy(['id' => $id]);
            if ($credit) { 
            $credit->setStatut($data['statut']);  
            /*if ($credit) { 
                $credit = $this->serializer->deserialize(
                    $request->getContent(),
                     Credit::class,
                     'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $credit]
                );*/  
                $this->manager->flush();
                return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
        
   
        #[Route('/reponses/{id}', name: 'editReponses', methods: 'PUT')]
        /** @OA\Put(
     *     path="/api/credit/{id}",
     *     summary="Modifier un credit par ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du credit à modifier",
     *         @OA\Schema(type="integer")
     *     ),
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
     *         response=204,
     *         description="Credit trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du credit"),
     *             @OA\Property(property="description", type="string", example="Description du credit"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credit non trouvé"
     *     )
     * )
     */
         public function editReponses(int $id, Request $request): JsonResponse
        {   
            $data = json_decode($request->getContent(), true); 
            $credit = $this->repository->findOneBy(['id' => $id]);
            if ($credit) {
                if(array_key_exists('reponse',$data)){$credit->setReponse($this->getUser()->getId(),$data['reponse']);}          
                else{$credit->setReponse1($this->getUser()->getId(),$data['reponse1']);}           
            $this->manager->flush();

            return new jsonResponse(null, Response::HTTP_NO_CONTENT);
                }

            return new jsonResponse( null, Response::HTTP_NOT_FOUND);

            }
     
    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $credit = $this->repository->findOneBy(['id' => $id]);
            if ($credit) {
                $this->manager->remove($credit);
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
            return new jsonResponse(null, status: Response::HTTP_NOT_FOUND);
        }
    
    
 }