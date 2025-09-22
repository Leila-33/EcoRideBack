<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Repository\AvisRepository;
use App\Repository\CovoiturageRepository;
use App\Repository\UserRepository;
use App\Repository\VilleRepository;
use App\Repository\VoitureRepository;
use App\Utilis\Sanitizer;
use App\Utilis\Validator;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/covoiturage', name: 'app_api_covoiturage_')]
class CovoiturageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CovoiturageRepository $repository,
        private UserRepository $userRepository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private AvisRepository $avisrepository,
        private VilleRepository $villeRepository,
    ) {
    }

    private function formatCovoiturage($covoiturage, $reponseNon = false): array
    {
        $chauffeur = $covoiturage->getVoiture()->getUser();
        $voiture = $covoiturage->getVoiture();

        return [
            'id' => $covoiturage->getId(),
            'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
            'heureDepart' => $covoiturage->getHeureDepart(),
            'lieuDepart' => $covoiturage->getLieuDepart(),
            'dateArrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
            'heureArrivee' => $covoiturage->getHeureArrivee(),
            'lieuArrivee' => $covoiturage->getLieuArrivee(),
            'statut' => $covoiturage->getStatut(),
            'nbPlaces' => $covoiturage->getNbPlaces(),
            'prixPersonne' => $covoiturage->getPrixPersonne(),
            'energie' => $covoiturage->getVoiture()->getEnergie(),
            'chauffeur' => [
                'id' => $chauffeur->getId(),
                'pseudo' => $chauffeur->getPseudo(),
                'photo' => $chauffeur->getPhoto(),
                'parametres' => array_map(fn ($p) => [
                    'id' => $p->getId(),
                    'propriete' => $p->getPropriete(),
                    'valeur' => $p->getValeur(),
                ], $chauffeur->getParametres()->toArray())],
            'voiture' => [
                'id' => $voiture->getId(),
                'marque' => $voiture->getMarque()->getLibelle(),
                'modele' => $voiture->getModele(),
                'couleur' => $voiture->getCouleur(),
            ],
            'users' => array_map(fn ($p) =>['id' => $p->getId()], $covoiturage->getUsers()->toArray()),
            'noteMoyenne' => $this->avisrepository->getMoyenneChauffeur($covoiturage->getVoiture()->getUser()->getId())];
    }

    #[Route('/{id}', name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/covoiturage/{id}',
        summary: 'Enregistrer un nouveau covoiturage',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Index de la voiture du covoiturage dans la collection',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du covoiturage à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/CovoiturageCreateDto'),
            ]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Covoiturage enregistré avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/CovoiturageResponseDto'),
                ]),
            new OA\Response(
                response: 400,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Choisisssez une autre ville de départ'),
                    ])),
            new OA\Response(
                response: 404,
                description: 'Voiture introuvable pour cet utilisateur',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ]
    )]
    public function new(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $covoiturage = $this->serializer->deserialize($request->getContent(), Covoiturage::class, 'json');

        if (!$this->villeRepository->ville($covoiturage->getLieuDepart())) {
            return $this->json(['error' => 'Choisisssez une autre ville de départ'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->villeRepository->ville($covoiturage->getLieuArrivee())) {
            return $this->json(['error' => 'Choisisssez une autre ville d\'arrivée'], Response::HTTP_BAD_REQUEST);
        }
        if ($this->villeRepository->normalizeString($covoiturage->getLieuDepart()) === $this->villeRepository->normalizeString($covoiturage->getLieuArrivee())) {
            return new JsonResponse(['error' => 'La ville de départ et la ville d\'arrivée ne peuvent pas être identiques'], Response::HTTP_BAD_REQUEST);
        }
        $voiture = $this->voiturerepository->find($id);
        if (!$voiture) {
            return new JsonResponse(['error' => 'Voiture introuvable pour cet utilisateur'], Response::HTTP_BAD_REQUEST);
        }
        $voiture = $this->voiturerepository->find($id);
        $covoiturage->setLieuDepart(Sanitizer::sanitizeText($covoiturage->getLieuDepart()));
        $covoiturage->setLieuArrivee(Sanitizer::sanitizeText($covoiturage->getLieuArrivee()));
        $covoiturage->setStatut('en attente');
        if ($errorResponse = Validator::validateEntity($covoiturage, $validator)) {
            return $errorResponse;
        }
        $voiture->addCovoiturage($covoiturage);
        $this->manager->persist($covoiturage);
        $this->manager->flush();
        $location = $this->generateUrl('app_api_covoiturage_show', ['id' => $covoiturage->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($this->formatCovoiturage($covoiturage), Response::HTTP_CREATED, ['Location' => $location]);
    }

    #[Route('/allCovoiturages/{idChauffeur?}', name: 'allCovoiturages_chauffeur', methods: ['GET'])]
    #[OA\Get(
        path: '/api/covoiturage/allCovoiturages/{idChauffeur}',
        summary: "Récupère la liste de tous les covoiturages. Si idChauffeur est fourni, récupère ceux dont l'utilisateur d'identifiant idChauffeur est chauffeur; sinon, ceux de l'utilisateur courant.",
        parameters: [new OA\Parameter(
            name: 'idChauffeur',
            in: 'path',
            required: false,
            description: 'Identifiant du chauffeur dont on récupère la liste des covoiturages',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des covoiturages',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'actifs', type: 'array', items: new OA\Items(ref: '#/components/schemas/CovoiturageResponseDto')),
                        new OA\Property(property: 'termines', type: 'array', items: new OA\Items(ref: '#/components/schemas/CovoiturageResponseDto')),
                    ])),
            new OA\Response(
                response: 404,
                description: 'Chauffeur non trouvé',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ]
    )]
    public function allCovoiturages(?int $idChauffeur = null): JsonResponse
    {
        if (null !== $idChauffeur) {
            $chauffeur = $this->userRepository->find($idChauffeur);
            if (!$chauffeur) {
                return new JsonResponse(['error' => 'Chauffeur non trouvé.'], Response::HTTP_NOT_FOUND);
            }
        }
        $covoiturages = $idChauffeur ? $this->repository->findByChauffeur($idChauffeur) : $this->getUser()->getCovoiturages();
        if (empty($covoiturages)) {
            return new JsonResponse([], status: Response::HTTP_OK);
        }
        $covoituragesActifs = [];
        $covoituragesTermines = [];
        foreach ($covoiturages as $covoiturage) {
            if (in_array($covoiturage->getStatut(), ['en attente', 'en cours'], true)) {
                $covoituragesActifs[] = $this->formatCovoiturage($covoiturage);
            } else {
                $covoituragesTermines[] = $this->formatCovoiturage($covoiturage);
            }
        }

        return new JsonResponse(['actifs' => $covoituragesActifs, 'termines' => $covoituragesTermines], Response::HTTP_OK);
    }

    #[Route('/Covoiturages/{lieuDepart}/{lieuArrivee}/{dateDepart}/{strictDate?}', name: 'Covoiturages', methods: ['GET'])]
    #[OA\Get(
        path: '/api/covoiturage/Covoiturages/{lieuDepart}/{lieuArrivee}/{dateDepart}/{strictDate}',
        summary: 'Récupère la liste de tous les covoiturages correspondant aux critères de recherche. 
        Si stricDate, récupère ceux dont la date de départ correspond exactement à dateDepart, sinon le jour de l\'itinéraire le plus proche de celui correspond aux critères de recherche.',
        parameters: [new OA\Parameter(
            name: 'lieuDepart',
            in: 'path',
            required: true,
            description: 'Ville de départ du covoiturage recherché',
            schema: new OA\Schema(type: 'string', example: 'Bordeaux')
        ),
            new OA\Parameter(
                name: 'lieuArrivee',
                in: 'path',
                required: true,
                description: "Ville d'arrivée du covoiturage recherché",
                schema: new OA\Schema(type: 'string', example: 'Paris')
            ),
            new OA\Parameter(
                name: 'dateDepart',
                in: 'path',
                required: true,
                description: 'Date de départ du covoiturage recherché',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-09-02')
            ),
            new OA\Parameter(
                name: 'strictDate',
                in: 'path',
                required: false,
                description: 'Si true, ne retourne que les covoiturages dont la date de départ correspond exactement à dateDepart.',
                schema: new OA\Schema(type: 'string', example: true)
            )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste de tous les covoiturages correspondant aux critères de recherche',
                content: ['application/json' => new OA\JsonContent(
                    type: 'object',
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                // strictDate = true
                                new OA\Property(
                                    property: 'resultats',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/CovoiturageCreateDto')),
                                new OA\Property(property: 'prixEtDureeMaximumEtMinimum', type: 'object', properties: [
                                    new OA\Property(property: 'prixMin', type: 'string', format: 'decimal', example: '7.00'),
                                    new OA\Property(property: 'prixMax', type: 'string', format: 'decimal', example: '15.00'),
                                    new OA\Property(property: 'dureeMin', type: 'string', example: '07:00'),
                                    new OA\Property(property: 'dureeMax', type: 'string', example: '15:00'),
                                ])]),
                        // strictDate = false
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'date_proche', type: 'string', format: 'date', example: '2025-09-17'),
                            ]
                        )]
                )]),
            new OA\Response(
                response: 400,
                description: 'La ville de départ est requise',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'La ville de départ est requise'),
                    ])),
        ]
    )]
    public function Covoiturages(string $lieuDepart, string $lieuArrivee, string $dateDepart, ?string $strictDate = null): JsonResponse
    {
        $strictDate = ('true' === $strictDate);
        if (!$lieuDepart) {
            return $this->json(['error' => 'La ville de départ est requise'], Response::HTTP_BAD_REQUEST);
        }
        if (!$lieuArrivee) {
            return $this->json(['error' => 'La ville d\'arrivée est requise'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->villeRepository->ville($lieuDepart)) {
            return $this->json(['error' => 'Choisissez une autre ville de départ'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->villeRepository->ville($lieuArrivee)) {
            return $this->json(['error' => 'Choisissez une autre ville d\'arrivée'], Response::HTTP_BAD_REQUEST);
        }
        if ($this->villeRepository->normalizeString($lieuDepart) === $this->villeRepository->normalizeString($lieuArrivee)) {
            return $this->json(['error' => 'La ville de départ et la ville d\'arrivée ne peuvent pas être identiques'], Response::HTTP_BAD_REQUEST);
        }
        if (empty($dateDepart)) {
            return $this->json(['error' => 'La date de départ est requise.'], Response::HTTP_BAD_REQUEST);
        }
        $date = \DateTime::createFromFormat('Y-m-d', $dateDepart);
        $errors = \DateTime::getLastErrors();
        if (!$date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return $this->json(['error' => "La date de départ n'existe pas."], Response::HTTP_BAD_REQUEST);
        }
        $now = new \DateTime('today');
        if ($date < $now) {
            return $this->json(['error' => "La date de départ doit être aujourd'hui ou dans le futur."], Response::HTTP_BAD_REQUEST);
        }

        $coordinates = $this->villeRepository->findCoordinates($lieuDepart);
        $prochesDep = $this->villeRepository->findVillesProches($coordinates);
        $coordinates = $this->villeRepository->findCoordinates($lieuArrivee);
        $prochesArr = $this->villeRepository->findVillesProches($coordinates);
        $prochesDep = array_map(fn ($ville) => $ville->getNom(), $prochesDep);
        $prochesArr = array_map(fn ($ville) => $ville->getNom(), $prochesArr);

        $covoiturages = $this->repository->findCovoiturages($lieuDepart, $lieuArrivee, $prochesDep, $prochesArr, $dateDepart, $strictDate);
        $resultats = [];
        if ($covoiturages) {
            if ($strictDate) {
                foreach ($covoiturages as $covoiturage) {
                    $resultats[] = $this->formatCovoiturage($covoiturage);
                }
                $prixEtDureeMaximumEtMinimum = $this->repository->findStats($lieuDepart, $lieuArrivee, $dateDepart, $prochesDep, $prochesArr, true);
                $responseData = ['resultats' => $resultats, 'prixEtDureeMaximumEtMinimum' => $prixEtDureeMaximumEtMinimum];

                return new JsonResponse($responseData, Response::HTTP_OK);
            } else {
                return new JsonResponse(['date_proche' => $covoiturages->format('Y-m-d')], Response::HTTP_OK);
            }
        }

        return new JsonResponse([], Response::HTTP_OK);
    }

    #[Route('/NotRespondedCovoiturages', name: 'NotRespondedCovoiturages', methods: ['GET'])]
    #[OA\Get(
        path: '/api/covoiturage/NotRespondedCovoiturages',
        summary: "Récupère la liste de tous les covoiturages auxquels a participé l'utilisateur et pour lesquels il n'a pas encore répondu à la question de fin de trajet.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste de tous les covoiturages non répondus',
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CovoiturageCreateDto')),
                ]),
        ]
    )]
    public function NotRespondedCovoiturages(): JsonResponse
    {
        $covoiturages = $this->repository->findNotRespondedCovoiturages($this->getUser());
        $resultats = array_map(fn ($c) => $this->formatCovoiturage($c), $covoiturages);

        return new JsonResponse($resultats, Response::HTTP_OK);
    }

    // Fonction qui convertit une chaine en :
    // - convertissant les caractères accentués ou spéciaux en leur équivalent ASCII,
    // - mettant le texte en minuscule,
    // - supprimant les caractères non alphanumériques sauf tirets et espaces
    // - réduisant les espaces multiples à un seul espace
    // - supprimant les espaces en début/fin
    private function normalizeString(string $str): string
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^a-z0-9\- ]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);

        return trim($str);
    }

    #[Route('/nombreDeparts', name: 'nombreDeparts', methods: ['GET'])]
    #[OA\Get(
        path: '/api/covoiturage/nombreDeparts',
        summary: 'Récupère le nombre de covoiturages regroupés par date de départ.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des dates avec le nombre de covoiturages',
                content : ['application/json' => new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'dateDepart', type: 'string', format: 'date', example: '2025-09-02'),
                            new OA\Property(property: 'nombre', type: 'integer', example: 1),
                        ]))])])]
    public function nombreDeparts(): JsonResponse
    {
        $nombreDeparts = $this->repository->findByDay();

        return new JsonResponse($nombreDeparts ?: [], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/covoiturage/{id}',
        summary: "Récupere le covoiturage d'identifiant id.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: "Covoiturage d'identifiant id.",
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/CovoiturageCreateDto'),
                ]),
            new OA\Response(
                response: 404,
                description: 'Covoiturage non trouvé',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            )]
    )]
    public function show(int $id): JsonResponse
    {
        $covoiturage = $this->repository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->formatCovoiturage($covoiturage, true), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/covoiturage/{id}',
        summary: "Changer le statut d'un covoiturage.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Nouveau statut du covoiturage',
            content : ['application/json' => new OA\JsonContent(
                type: 'object',
                required: ['statut'],
                properties: [
                    new OA\Property(property: 'statut', type: 'string', example: 'en cours', enum: ['en attente', 'en cours', 'terminé']),
                ])]),
        responses: [
            new OA\Response(
                response: 204,
                description: 'Statut changé avec succès',
            ),
            new OA\Response(
                response: 400,
                description: 'Le statut est requis.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Le statut est requis.'),
                    ]
                )),
            new OA\Response(
                response: 404,
                description: 'Covoiturage introuvable',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            )]
    )]
    public function edit(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $covoiturage = $this->repository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (!isset($data['statut']) || empty($data['statut'])) {
            return $this->json(['error' => 'Le statut est requis.'], Response::HTTP_BAD_REQUEST);
        }
        $covoiturage->setStatut(Sanitizer::sanitizeText($data['statut']));
        $violations = $validator->validateProperty($covoiturage, 'statut');
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/addUser/{id}', name: 'addUser', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/covoiturage/addUser/{id}',
        summary: "Ajouter l'utilisateur courant à un covoiturage d'identifiant id.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Utilisateur ajouté avec succès',
            ),
            new OA\Response(
                response: 400,
                description: 'Aucune place disponible',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Aucune place disponible.'),
                    ]
                )),
            new OA\Response(
                response: 404,
                description: 'Covoiturage introuvable',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
            new OA\Response(
                response: 409,
                description: 'Utilisateur déjà inscrit au covoiturage.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Vous participez déjà à ce covoiturage'),
                    ]
                ))]
    )]
    public function addUser(int $id): JsonResponse
    {
        $covoiturage = $this->repository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        if ($covoiturage->getUsers()->contains($this->getUser())) {
            return new JsonResponse(['error' => 'Vous participez déjà à ce covoiturage'], Response::HTTP_CONFLICT);
        }
        if (0 === $covoiturage->getNbPlaces()) {
            return new JsonResponse(['error' => 'Aucune place disponible'], Response::HTTP_BAD_REQUEST);
        }
        $covoiturage->addUser($this->getUser());
        $covoiturage->setNbPlaces($covoiturage->getNbPlaces() - 1);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/removeUser/{id}', name: 'removeUser', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/covoiturage/removeUser/{id}',
        summary: "Supprime l'utilisateur courant des participants du covoiturage d'identifiant id.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Utilisateur supprimé avec succès',
            ), new OA\Response(
                response: 404,
                description: 'Covoiturage introuvable',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ), new OA\Response(
                response: 409,
                description: "L'utilisateur ne participe pas à ce covoiturage.",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Vous ne participez pas à ce covoiturage.'),
                    ]
                ))]
    )]
    public function removeUser(int $id): JsonResponse
    {
        $covoiturage = $this->repository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (!$covoiturage->getUsers()->contains($this->getUser())) {
            return new JsonResponse(['error' => 'Vous ne participez pas à ce covoiturage.'], Response::HTTP_CONFLICT);
        }
        $covoiturage->removeUser($this->getUser());
        $covoiturage->setNbPlaces($covoiturage->getNbPlaces() + 1);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/covoiturage/{id}',
        summary: 'Supprimer un covoiturage.',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage à supprimer',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Covoiturage supprimé avec succès.',
            ), new OA\Response(
                response: 404,
                description: 'Covoiturage introuvable',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ]),
    ]
    public function delete(int $id): JsonResponse
    {
        $covoiturage = $this->repository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->manager->remove($covoiturage);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
