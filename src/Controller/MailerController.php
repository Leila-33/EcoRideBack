<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Repository\CovoiturageRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mailer', name: 'app_api_mailer_')]
class MailerController extends AbstractController
{
    public function __construct(
        private CovoiturageRepository $covoiturageRepository,
    ) {
    }

    #[Route('/emailCovoiturage/{id}', name: 'emailCovoiturage', methods: ['POST'])]
    #[OA\Post(
        path: '/api/mailer/emailCovoiturage/{id}',
        summary: "Envoie un email aux utilisateurs d'un covoiturage d'identifiant id ou au support.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du mail à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/EmailCovoiturageDto')]),
        responses: [
            new OA\Response(response: 204, description: 'Email envoyé avec succès',
            ),
            new OA\Response(
                response: 400,
                description: 'Sujet manquant ou invalide',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Sujet manquant ou invalide'),
                    ]
                )),
            new OA\Response(
                response: 404,
                description: 'Covoiturage introuvable ou aucune adresse email trouvée',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
            new OA\Response(
                response: 500,
                description: "Impossible d'envoyer l'email",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: "Impossible d'envoyer l'email"),
                    ]
                )),
        ])
    ]
    public function sendEmailCovoiturage(Request $request, MailerInterface $mailer, int $id): Response
    {
        $data = json_decode($request->getContent(), true);
        $covoiturage = $this->covoiturageRepository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if (empty($data['subject'])) {
            return new JsonResponse(['error' => 'Sujet manquant'], Response::HTTP_BAD_REQUEST);
        }
        $utilisateurs = $data['utilisateurs'] ?? [];
        $utilisateurs = array_filter($utilisateurs, fn ($u) => !empty($u['id']) && !empty($u['pseudo']) && !empty($u['email']));
        switch ($data['subject']) {
            case 'finTrajet':
                $emailSubject = 'Trajet terminé';
                $to = $this->getAdresses($covoiturage);
                $bodyMessage = "Bonjour,\nRendez-vous sur votre espace personnel pour valider votre trajet du covoiturage $id.";
                break;
            case 'annuler':
                $emailSubject = 'Trajet annulé';
                $to = $this->getAdresses($covoiturage);
                $bodyMessage = 'Bonjour,\nLe covoiturage $id a été annulé.';
                break;
            case 'confirmationPassager':
                $emailSubject = 'Confirmation trajet';
                if (!empty($utilisateurs)) {
                    $firstUser = array_values($utilisateurs)[0];
                    $to = [$firstUser['email']];
                    $bodyMessage = "Bonjour {$firstUser['pseudo']},\n Nous vous confirmons votre participation au covoiturage $id.";
                } else {
                    $to = [];
                }
                break;
            case 'remboursementEchoue':
                $emailSubject = 'Remboursement échoué';
                $to = ['support@ecoride.com'];
                if (!empty($utilisateurs)) {
                    $userList = array_map(fn ($u) => "ID: {$u['id']} - Pseudo : {$u['pseudo']}", $utilisateurs);
                    $bodyMessage = "Certains remboursements n'ont pas pu être effectués pour le covoiturage $id.\nUtilisateurs concernés:\n".implode("\n", $userList);
                } else {
                    $bodyMessage = "Certains remboursements n'ont pas pu être effectués pour le covoiturage $id. Aucun utilisateur fourni.";
                }
                break;
            default:
                return new JsonResponse(['error' => 'Sujet invalide'], Response::HTTP_BAD_REQUEST);
        }
        if (empty($to)) {
            return new JsonResponse(['error' => 'Aucune adresse email trouvée pour ce covoiturage.'], Response::HTTP_NOT_FOUND);
        }

        return $this->sendEmail($mailer, $to, $emailSubject, $bodyMessage);
    }

    public function sendEmail(MailerInterface $mailer, array $to, string $subject, string $message): Response
    {
        try {
            $email = (new Email())
                ->from('contact@ecoride.com')
                ->to(...$to)
                ->subject($subject)
                ->text($message);

            $mailer->send($email);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => "Impossible d'envoyer l'email",
                'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAdresses(Covoiturage $covoiturage): array
    {
        return array_map(fn ($u) => $u->getEmail(), $covoiturage->getUsers()?->toArray() ?? []);
    }

    #[Route('/emailEmploye', name: 'emailEmploye', methods: ['POST'])]
    #[OA\Post(
        path: '/api/mailer/emailEmploye',
        summary: 'Envoie un email à un utilisateur qui a répondu non à la question de fin de trajet.',
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'email à envoyer",
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/EmailEmployeDto')]),
        responses: [
            new OA\Response(response: 204, description: 'Email envoyé avec succès',
            ),
            new OA\Response(
                response: 400,
                description: 'Données manquantes ou adresse email invalide',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Données manquantes'),
                    ]
                )),
            new OA\Response(
                response: 500,
                description: "Impossible d'envoyer l'email",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: "Impossible d'envoyer l'email"),
                    ]
                )),
        ])
    ]
    public function sendEmailEmploye(Request $request, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Adresse email invalide.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->sendEmail($mailer, [$data['email']], $data['subject'], $data['message']);
    }

    #[Route('/emailContact', name: 'emailContact', methods: ['POST'])]
    #[OA\Post(
        path: '/api/mailer/emailContact',
        summary: 'Envoi un email au service client.',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du mail à envoyer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/EmailContactDto')]),
        responses: [
            new OA\Response(
                response: 204,
                description: 'Email envoyé avec succès',
            ),
            new OA\Response(
                response: 400,
                description: 'Données manquantes ou adresse email invalide',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Données manquantes'),
                    ]
                )),
            new OA\Response(
                response: 500,
                description: "Impossible d'envoyer l'email",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: "Impossible d'envoyer l'email"),
                    ]
                )),
        ])
    ]
    public function sendEmailContact(Request $request, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Email invalide.'], Response::HTTP_BAD_REQUEST);
        }
        $fullMessage = $data['message']."\n\n".$data['email']."\n".$data['nom'].' '.$data['prenom'];

        return $this->sendEmail($mailer, ['support@ecoride.com'], $data['subject'], $fullMessage);
    }
}
