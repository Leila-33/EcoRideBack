<?php

namespace App\Utilis;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    public static function validateEntity(object $entity, ValidatorInterface $validator): ?JsonResponse
    {
        $violations = $validator->validate($entity);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
