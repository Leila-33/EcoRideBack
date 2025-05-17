<?php
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
                },
            ]];