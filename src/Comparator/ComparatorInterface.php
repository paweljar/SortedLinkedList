<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList\Comparator;

/**
 * @template-contravariant T of int|string
 */
interface ComparatorInterface
{
    public function compare(int|string $a, int|string $b): int;
}
