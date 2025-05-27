<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Voiture;
use App\Entity\Marque;
use App\Entity\Credit;

use App\Repository\MarqueRepository;
use App\Repository\VoitureRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\Exception;

use OpenApi\Annotations as OA;



#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private SerializerInterface $serializer, private UserPasswordHasherInterface $passwordHasher, private MarqueRepository $repository,private VoitureRepository $voiturerepository
    )
    {
    }
    
 
  #[Route('/registration', name: 'registration', methods: 'POST')]
  /** @OA\Post(
     *     path="/api/registration",
     *     summary="Inscription d'un nouvel utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de l'utilisateur à inscrire",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="prenom", type="string", example="pseudo"),
     *             @OA\Property(property="nom", type="string", example="adresse@email.com"),            
     *             @OA\Property(property="pseudo", type="string", example="pseudo"),
     *             @OA\Property(property="email", type="string", example="adresse@email.com"),
     *             @OA\Property(property="password", type="string", example="Mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur inscrit avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="string", example="Nom d'utilisateur"),
     *             @OA\Property(property="apiToken", type="string", example="31a023e212f116124a36af14ea0c1c3806eb9378"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $credit=new Credit;
        $credit->setTotal(20);
        $credit->setUser($user);
        $this->manager->persist($credit);
        $this->manager->persist($user);
        $this->manager->flush();
        return new JsonResponse(
            ['user'  => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED
        );
    }


   #[Route('/login', name: 'login', methods: 'POST')]
    /** @OA\Post(
     *     path="/api/login",
     *     summary="Connecter un utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de l’utilisateur pour se connecter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="username", type="string", example="adresse@email.com"),
     *             @OA\Property(property="password", type="string", example="Mot de passe")
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Connexion réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="string", example="Nom d'utilisateur"),
     *             @OA\Property(property="apiToken", type="string", example="31a023e212f116124a36af14ea0c1c3806eb9378"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
     *          )
     *      )
     *   )
     */
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'  => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

#[Route('/account/me', name: 'me', methods: 'GET')]
 /** @OA\Post(
     *     path="/api/account/me",
     *     summary="Connecter un utilisateur",
    
     *      @OA\Response(
     *          response=200,
     *          description="Connexion réussie",
     *  
     *      )
     *   )
     */
    public function me(): JsonResponse
    {
        $user = $this->getUser();
       
$context = [
    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
        if (!$object instanceof User) {
            throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
        }

        // serialize the nested Organization with only the name (and not the members)
        return $object->getNom();
    },
    AbstractNormalizer::CALLBACKS => [
        
        // all callback parameters are optional (you can omit the ones you don't use)
        'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Voiture ? $attributeValue : '';
        },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Marque ? $attributeValue : '';
        },
        'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof User ? $attributeValue : '';
        }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Parametre ? $attributeValue : '';
        }, 'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Covoiturage ? $attributeValue : '';
        },
        
    ],
];

        $responseData = $this->serializer->serialize($user, 'json',$context);
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);

    }
    #[Route('/account/edit', name: 'edit', methods: 'PUT')]
    public function edit(Request $request): JsonResponse
    {  $data = json_decode($request->getContent(), true);
        if (!empty($data['photo'])){
        $photoB64=base64_decode($data['photo']);
        $stream=fopen('php://memory','r+');
        fwrite($stream,$photoB64);
        rewind($stream);
    $mime=null;
$imgInfo=@getimagesizefromstring($photoB64);
if ($imgInfo && isset($imgInfo['mime'])){$mime=$imgInfo['mime'];}}
        else{$stream=null;}
        $user = $this->getUser();
        $user->setPhoto($stream);
        $user->setPhotoMime($mime);
        $user->setNom($data['nom']??'');
        $user->setPrenom($data['prenom']??'');
        $user->setDateNaissance($data['date_naissance']??'');
        $user->setTelephone($data['telephone']??'');
        $user->setAdresse($data['adresse']??'');
        $credit=new Credit;
        $credit->setTotal(20);
        $user->setCredit($credit);

        if (!empty($data['password'])){
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));}
            
        $this->manager->flush();
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if (!$object instanceof User) {
                    throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
        
                // serialize the nested Organization with only the name (and not the members)
                return $object->getNom();
            },
            AbstractNormalizer::CALLBACKS => [
                
                // all callback parameters are optional (you can omit the ones you don't use)
                'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Voiture ? $attributeValue : '';
                },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Marque ? $attributeValue : '';
                },
                'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof User ? $attributeValue : '';
                }, 'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Parametre ? $attributeValue : '';
                }, 'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                },
                
            ],
        ];
        
                $responseData = $this->serializer->serialize($user, 'json',$context);
      
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
 
        }


        #[Route('/addVoiture', name: 'addVoiture', methods: 'POST')]
        public function addVoitures(Request $request): JsonResponse
        {
            $voiture = $this->serializer->deserialize($request->getContent(), Voiture::class, 'json');
            /*$voiture1 = $this->voiturerepository->findOneBy(['immatriculation' => $voiture->getImmatriculation()]);
            if ($voiture1){}*/
            $plaque = $this->voiturerepository->findOneBy(['immatriculation' => $voiture->getImmatriculation()]);

            $libelle=$voiture->getMarque()->getLibelle();
            $marque = $this->repository->findOneBy(['libelle' => $libelle]);
            if ($marque) {$marque->addVoiture($voiture);}
            else{$voiture->getMarque()->addVoiture($voiture);
                $this->manager->persist($voiture->getMarque());}
            $this->getUser()->addVoiture($voiture);
            $this->manager->persist($voiture);
            $this->manager->flush();
            $context = [ AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {
                if (!$object instanceof User) {
                    throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }
        
                // serialize the nested Organization with only the name (and not the members)
                return $object->getNom();
            },
                AbstractNormalizer::CALLBACKS => [
                    // all callback parameters are optional (you can omit the ones you don't use)
                    'voitures' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Voiture ? $attributeValue : '';
                    },'marque' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof Marque ? $attributeValue : '';
                    },'users' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof User ? $attributeValue : '';
                    }, 'user' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                        return $attributeValue instanceof User ? $attributeValue : get_class($attributeValue);
                    },'configuration' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Configuration ? $attributeValue : '';
                }, 'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
                    return $attributeValue instanceof Covoiturage ? $attributeValue : '';
                },


                ],
            ];
            $responseData = $this->serializer->serialize($voiture,'json', $context );
       

            return new JsonResponse($responseData, Response::HTTP_CREATED, [] ,true);
    
    

        
}
#[Route('/allVoitures', name: 'allVoitures', methods: 'GET')]
public function allVoitures(): JsonResponse
{
    $voitures = $this->getUser()->getVoitures();
 
   
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
        }, 'configurations' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Parametre ? $attributeValue : '';
        },  'covoiturages' => function (object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            return $attributeValue instanceof Covoiturage ? $attributeValue : '';
        },
        ]
];
$responseData = $this->serializer->serialize($voitures, 'json',$context);
return new JsonResponse($responseData, Response::HTTP_OK, [], true);
}}