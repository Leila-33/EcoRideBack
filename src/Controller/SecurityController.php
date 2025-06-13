<?php

namespace App\Controller;
use App\Entity\User;
include_once 'Context.php';
use App\Entity\Credit;
use App\Repository\UserRepository;

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
use Symfony\Component\Validator\Validator\ValidatorInterface;

use OpenApi\Annotations as OA;



#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, 
    private SerializerInterface $serializer, 
    private UserPasswordHasherInterface $passwordHasher, 
    private UserRepository $repository

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
    public function register(Request $request,ValidatorInterface $validator): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');  $errors = $validator->validate($voiture);
         $errors = $validator->validate($user);
        if (count($errors) > 0) {
        $errorsString = (string) $errors;
        return new Response($errorsString);
    }
        $user->setPseudo(strip_tags($user->getPseudo));
        $userFound = $this->repository->findOneBy(['pseudo' => $user->getPseudo()]);
        if (!$userFound){        
            $user->setNom(strip_tags($user->getNom));
            $user->setPrenom(strip_tags($user->getPrenom));
            $user->setAdresse(strip_tags($user->getAdresse));
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
        );}
        return new JsonResponse(['error'=>'Cet email est déjà enregistré'], Response::HTTP_CONFLICT);

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
    public function login(#[CurrentUser] ?User $user,Request $request): JsonResponse
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
        $responseData = $this->serializer->serialize($user, 'json', Context::context());
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    
#[Route('/personnes', name: 'personnes', methods: 'GET')]
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
    public function personnes(): JsonResponse
    {
        $users = $this->repository->findByName();     
        $responseData = $this->serializer->serialize($users, 'json', Context::context());
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/account/edit', name: 'edit', methods: 'PUT')]
    public function edit(Request $request, ValidatorInterface $validator): JsonResponse
    {  $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $photoB64=$data['photo']??null;
        $mime=null;
        $stream=null;

        if (!empty($photoB64)){
            $decoded=base64_decode($photoB64,true);
            if($decoded===false){return new JsonResponse(['error'=>'Image non valide']);}
           
            $mime=null;
            $imgInfo=@getimagesizefromstring($decoded);
            if (!$imgInfo || !in_array($imgInfo['mime'],['image/jpeg','image/png']) || strlen($decoded)>300000){return new JsonResponse(['error'=>'Type d\'image interdit']);}
            $mime=$imgInfo['mime'];
            $stream=fopen('php://memory','r+');
            fwrite($stream,$decoded);
            rewind($stream);
            $user->setPhoto($stream);
            $user->setPhotoMime($mime);
        }
            $user->setNom(strip_tags($data['nom']));
            $user->setPrenom(strip_tags($data['prenom']));
            $user->setAdresse(strip_tags($data['adresse']));
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new Response($errorsString);
    }
        $credit=new Credit;
        $credit->setTotal(20);
        $credit->setUser($user);

        if (!empty($data['password'])){
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));} 
            $this->manager->persist($credit);           
            $this->manager->flush(); 
      
        $responseData = $this->serializer->serialize($user, 'json', Context::context());
      
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
 
        }


    
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]       
     
        public function delete(int $id): JsonResponse
            {
            $user = $this->repository->findOneBy(['id' => $id]);
            if ($user) {
                $this->manager->remove($user);
                $this->manager->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);

            
                }


}