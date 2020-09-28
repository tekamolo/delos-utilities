<?php

namespace Delos\Utilities\Converter;

use Exception;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

class JsonToObjectValueAssigner
{
    static $counter = 0;
    const TYPE_OBJECT = "object";
    const TYPE_ARRAY_OF_OBJECTS = "array_of_objects";
    const TYPE_ARRAY = "array";
    const TYPE_STRING = "string";
    const TYPE_INTEGER = "integer";
    const TYPE_FLOAT = "float";
    const TYPE_BOOLEAN = "boolean";


    /**
     * @var PHPClass
     */
    private $baseClass;

    /**
     * @param $baseClass
     */
    public function setBaseClass($baseClass)
    {
        $this->baseClass = $baseClass;
    }

    /**
     * @throws Exception
     */
    private function checkBaseClassIsSet()
    {
        if (empty($this->baseClass)) {
            throw new Exception("The base class is not set, we need that class to map all the properties we are going to demand from the response");
        }
    }

    /**
     * @param stdClass $response
     * @return PHPClass
     * @throws Exception
     */
    public function assignResponseToSchema(stdClass $response)
    {
        $this->checkBaseClassIsSet();
        $responseReflection = new ReflectionObject($response);
        $responseProperties = $responseReflection->getProperties();

        $mainReflection = new ReflectionObject($this->baseClass);
        $mainReflectionProperties = $mainReflection->getProperties();

        foreach ($responseProperties as $respReflectionProperty) {
            foreach ($mainReflectionProperties as $mainReflectionProperty) {
                if ($respReflectionProperty->getName() == $mainReflectionProperty->getName()) {
                    if ($this->getType($mainReflectionProperty) === self::TYPE_OBJECT) {
                        $propertyName = $respReflectionProperty->getName();
                        $instance = $this->scanSubNodesAndAssign($mainReflectionProperty, $response->$propertyName);
                        $mainReflectionProperty->setValue($this->baseClass, $instance);
                    } elseif ($this->getType($mainReflectionProperty) === self::TYPE_ARRAY_OF_OBJECTS) {
                        if (is_array($respReflectionProperty->getValue($response))) {
                            $arrayResult = array();
                            foreach ($respReflectionProperty->getValue($response) as $element) {
                                $result = $this->scanSubNodesAndAssign($mainReflectionProperty, $element);
                                $arrayResult[] = $result;
                            }
                            $mainReflectionProperty->setValue($this->baseClass, $arrayResult);
                        }
                    } else {
                        $mainReflectionProperty->setValue($this->baseClass, $respReflectionProperty->getValue($response));
                    }
                }
            }
        }

        return $this->baseClass;
    }

    /**
     * @param ReflectionProperty $property
     * @return string
     */
    private function getType(ReflectionProperty $property)
    {
        if (preg_match("#@var (boolean|bool)#", $property->getDocComment(), $match)) {
            return self::TYPE_BOOLEAN;
        }
        if (preg_match("#@var (integer|int)#", $property->getDocComment(), $match)) {
            return self::TYPE_INTEGER;
        }
        if (preg_match("#@var float#", $property->getDocComment(), $match)) {
            return self::TYPE_FLOAT;
        }
        if (preg_match("#@var string#", $property->getDocComment(), $match)) {
            return self::TYPE_STRING;
        }
        /**
         * Checking it is an array of objects otherwise a common array.
         * If we have an array that is mixed (this does not happen usually) we would have to think of something else
         * because nor here it is handled but either in phpstorm, in that case it will return the whole thing along with the stdClass from the response
         */
        if (preg_match("#@var (?!array)([a-zA-Z_/]+)#", $property->getDocComment(), $match)) {
            return self::TYPE_OBJECT;
        }
        if (preg_match("#@var \\$[a-zA-Z0-9_$]* [a-zA-Z0-9_\\\]*\[]#", $property->getDocComment(), $match)) {
            return self::TYPE_ARRAY_OF_OBJECTS;
        }
        if (preg_match("#@var array#", $property->getDocComment(), $match)) {
            return self::TYPE_ARRAY;
        }
    }

    private function getClassToInstantiate(ReflectionProperty $property)
    {
        if (preg_match("#@var [a-zA-Z0-9_$]* ([a-zA-Z0-9_\\\]*)\[]#", $property->getDocComment(), $match)) {
            return $match[1];
        }
        if (preg_match("#@var ([a-zA-Z_/]+)#", $property->getDocComment(), $match)) {
            return $match[1];
        }
    }

    /**
     * @description this
     * @param ReflectionProperty $mainReflectionProperty
     * @param stdClass $responseObject
     * @return mixed
     */
    private function scanSubNodesAndAssign(ReflectionProperty $mainReflectionProperty, stdClass $responseObject)
    {
        $classToInstantiate = $this->getClassToInstantiate($mainReflectionProperty);
        $classToInstantiate = $mainReflectionProperty->getDeclaringClass()->getName().'\\'.$classToInstantiate;
        $instance = new $classToInstantiate();

        $responseSubPropertyReflection = new ReflectionObject($responseObject);
        $mainSubNodeReflection = new ReflectionObject($instance);


        foreach ($responseSubPropertyReflection->getProperties() as $responseSubPropertyReflection) {
            foreach ($mainSubNodeReflection->getProperties() as $mainSubNodeProperty) {
                if ($responseSubPropertyReflection->getName() == $mainSubNodeProperty->getName()) {
                    if ($this->getType($mainSubNodeProperty) === self::TYPE_OBJECT) {
                        $propertyName = $responseSubPropertyReflection->getName();
                        $instanceResult = $this->scanSubNodesAndAssign($mainSubNodeProperty, $responseObject->$propertyName);
                        $mainSubNodeProperty->setValue($instance, $instanceResult);
                    } elseif ($this->getType($mainSubNodeProperty) === self::TYPE_ARRAY_OF_OBJECTS) {
                        if (is_array($responseSubPropertyReflection->getValue($responseObject))) {
                            $arrayResult = array();
                            foreach ($responseSubPropertyReflection->getValue($responseObject) as $element) {
                                $result = $this->scanSubNodesAndAssign($mainSubNodeProperty, $element);
                                $arrayResult[] = $result;
                            }
                            $mainSubNodeProperty->setValue($instance, $arrayResult);
                        }
                    } else {
                        $mainSubNodeProperty->setValue($instance, $responseSubPropertyReflection->getValue($responseObject));
                    }
                }
            }
        }
        return $instance;

    }
}