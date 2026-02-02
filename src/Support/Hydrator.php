<?php

declare(strict_types=1);

namespace QueryList\Support;

use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use QueryList\Attributes\Selector;
use QueryList\Contracts\ParserInterface;

class Hydrator
{
    /**
     * @var array<string, ReflectionClass>
     */
    protected static array $reflectionCache = [];

    /**
     * Hydrate a DTO with data from the parser.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param ParserInterface $parser
     * @return T
     */
    public function hydrate(string $dtoClass, ParserInterface $parser): object
    {
        if (!isset(self::$reflectionCache[$dtoClass])) {
            self::$reflectionCache[$dtoClass] = new ReflectionClass($dtoClass);
        }

        $reflection = self::$reflectionCache[$dtoClass];
        
        // Create instance (skipping constructor to avoid dependency issues, 
        // assuming DTO properties are populated via this hydrator)
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Selector::class);
            if (empty($attributes)) {
                continue;
            }

            $selector = $attributes[0]->newInstance();
            $this->hydrateProperty($instance, $property, $selector, $parser);
        }

        return $instance;
    }

    protected function hydrateProperty(object $instance, ReflectionProperty $property, Selector $selector, ParserInterface $parser): void
    {
        // Use the parser's filter method to locate elements
        $selection = $parser->filter($selector->path);

        $value = null;
        $targetType = $property->getType();
        $typeName = ($targetType instanceof ReflectionNamedType) ? $targetType->getName() : null;

        // Determine if we should treat this as a list of elements.
        // If property is array, we assume multiple unless specified otherwise?
        // Actually, Selector has explicit multiple flag, so we check both.
        $isMultiple = $selector->multiple || ($typeName === 'array');

        if ($isMultiple) {
            // Handle multiple elements (list)
            
            // We expect $selection to be a collection or have map/texts/attrs methods
            if ($selector->attr === 'text') {
                if (method_exists($selection, 'texts')) {
                    $value = $selection->texts();
                } elseif (method_exists($selection, 'map')) {
                    $value = $selection->map(fn($node) => $node->text());
                } else {
                    // Fallback best effort
                     $value = [];
                }
            } else {
                if (method_exists($selection, 'attrs')) {
                    $value = $selection->attrs($selector->attr);
                } elseif (method_exists($selection, 'map')) {
                    $value = $selection->map(fn($node) => $node->attr($selector->attr));
                } else {
                    $value = [];
                }
            }

            // Convert Collection objects to array
            if (is_object($value)) {
                if (method_exists($value, 'all')) {
                    $value = $value->all();
                } elseif (method_exists($value, 'toArray')) {
                    $value = $value->toArray();
                }
            }
        } else {
            // Handle single element
            if ($selector->attr === 'text') {
                $value = $selection->text();
            } else {
                $value = $selection->attr($selector->attr);
            }
        }

        // Apply filter if defined
        if ($selector->filter) {
            $filter = $selector->filter;
            if (is_callable($filter)) {
                if ($isMultiple && is_array($value)) {
                    // Apply filter to each item in the list
                    $value = array_map($filter, $value);
                } else {
                    $value = call_user_func($filter, $value);
                }
            }
        }

        // Cast to target type
        if ($typeName) {
            $value = $this->castValue($value, $typeName);
        }

        $property->setAccessible(true);
        $property->setValue($instance, $value);
    }

    protected function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'string' => (string) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            default => $value,
        };
    }
}
