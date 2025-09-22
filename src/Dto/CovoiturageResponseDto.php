<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CovoiturageResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'dateDepart', type: 'string', format: 'date', example: '2025-09-02'),
        new OA\Property(property: 'heureDepart', type: 'string', example: '16:30'),
        new OA\Property(property: 'lieuDepart', type: 'string', example: 'Bordeaux', maxLength: 50),
        new OA\Property(property: 'dateArrivee', type: 'string', format: 'date', example: '2025-09-02'),
        new OA\Property(property: 'heureArrivee', type: 'string', example: '23:30'),
        new OA\Property(property: 'lieuArrivee', type: 'string', example: 'Paris', maxLength: 50),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente', enum: ['en attente', 'en cours', 'terminé']),
        new OA\Property(property: 'nbPlaces', type: 'integer', example: 3),
        new OA\Property(property: 'prixPersonne', type: 'string', format: 'decimal', example: '10.00'),
        new OA\Property(property: 'chauffeur', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'pseudo', type: 'string', example: 'pseudo'),
            new OA\Property(property: 'photo', type: 'string', example: 'uploads/photos/photo.jpg'),
            new OA\Property(property: 'parametres', type: 'array', items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'propriete', type: 'string', example: 'animaux'),
                    new OA\Property(property: 'valeur', type: 'string', example: 'non'),
                ])),
        ]),
        new OA\Property(property: 'voiture', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'marque', type: 'string', example: 'pseudo'),
                new OA\Property(property: 'modele', type: 'string', example: 'uploads/photos/photo.jpg'),
                new OA\Property(property: 'couleur', type: 'string', example: 'uploads/photos/photo.jpg'),
                               ]),
        new OA\Property(property: 'users', type: 'array', items: new OA\Items(type:'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example:1)])),
        new OA\Property(property: 'noteMoyenne', type: 'float', example: 7.7)])]
class CovoiturageResponseDto
{
}
