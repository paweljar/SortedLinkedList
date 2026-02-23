<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList\Comparator;

/**
 * @implements ComparatorInterface<int|string>
 */
final class DefaultComparator implements ComparatorInterface
{
    public function compare(int|string $a, int|string $b): int
    {
        return $a <=> $b;
    }
}
