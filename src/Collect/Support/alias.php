<?php

$aliases = [
    QL\Collect\Contracts\Support\Arrayable::class => Illuminate\Contracts\Support\Arrayable::class,
    QL\Collect\Contracts\Support\Jsonable::class => Illuminate\Contracts\Support\Jsonable::class,
    QL\Collect\Contracts\Support\Htmlable::class => Illuminate\Contracts\Support\Htmlable::class,
    QL\Collect\Contracts\Support\CanBeEscapedWhenCastToString::class => Illuminate\Contracts\Support\CanBeEscapedWhenCastToString::class,
    QL\Collect\Support\Arr::class => Illuminate\Support\Arr::class,
    QL\Collect\Support\Collection::class => Illuminate\Support\Collection::class,
    QL\Collect\Support\Enumerable::class => Illuminate\Support\Enumerable::class,
    QL\Collect\Support\HigherOrderCollectionProxy::class => Illuminate\Support\HigherOrderCollectionProxy::class,
    QL\Collect\Support\LazyCollection::class => Illuminate\Support\LazyCollection::class,
    QL\Collect\Support\Traits\EnumeratesValues::class => Illuminate\Support\Traits\EnumeratesValues::class,
];

# echo "\n\n-- Aliasing....\n---------------------------------------------\n\n";

foreach ($aliases as $tighten => $illuminate) {
    if (! class_exists($illuminate) && ! interface_exists($illuminate) && ! trait_exists($illuminate)) {
        # echo "Aliasing {$tighten} to {$illuminate}.\n";
        class_alias($tighten, $illuminate);
    }
}
