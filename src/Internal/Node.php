<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList\Internal;

/**
 * @internal
 * @template T of int|string
 */
class Node
{
    /** @var Node<T>|null */
    public ?Node $next = null;

    /**
     * @param T $value
     */
    public function __construct(
        public readonly int|string $value,
    ) {
    }
}
