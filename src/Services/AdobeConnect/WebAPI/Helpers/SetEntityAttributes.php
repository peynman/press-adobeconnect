<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StringCaseTransform as SCT;

/**
 * Set object attributes
 */
abstract class SetEntityAttributes
{
    /**
     * Iterate attributes and call the set method from object
     *
     * @param $object
     * @param mixed $attributes
     */
    public static function setAttributes(&$object, $attributes)
    {
        foreach ($attributes as $attr => $value) {
            if (is_array($value)) {
                static::setAttributes($object, $value);
                continue;
            }

            $attributeSetMethod = 'set' . SCT::toUpperCamelCase($attr);

            if (method_exists($object, $attributeSetMethod)) {
                $object->$attributeSetMethod($value);
            }
        }
    }
}
