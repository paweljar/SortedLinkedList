<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList;

use BCS\SortedLinkedList\Comparator\ComparatorInterface;
use BCS\SortedLinkedList\Comparator\DefaultComparator;
use BCS\SortedLinkedList\Exception\EmptyListException;
use BCS\SortedLinkedList\Exception\TypeMismatchException;
use BCS\SortedLinkedList\Internal\Node;
use Countable;
use Generator;
use IteratorAggregate;

/**
 * @template T of int|string
 * @implements IteratorAggregate<int, T>
 */
final class SortedLinkedList implements Countable, IteratorAggregate
{
    /** @var Node<T>|null */
    private ?Node $head = null;

    /** @var Node<T>|null */
    private ?Node $tail = null;

    /** @var ComparatorInterface<T> */
    private ComparatorInterface $comparator;

    /** @var int<0, max> */
    public private(set) int $count = 0;

    /**
     * @return T|null
     */
    public int|string|null $first {
        get => $this->head?->value;
    }

    /**
     * @return T|null
     */
    public int|string|null $last {
        get => $this->tail?->value;
    }

    /**
     * @param ComparatorInterface<T> $comparator
     */
    public function __construct(
        public readonly ValueType $valueType,
        ComparatorInterface $comparator = new DefaultComparator(),
    ) {
        $this->comparator = $comparator;
    }

    /**
     * @param T $value
     * @throws TypeMismatchException
     * @return self<T>
     */
    public function insert(int|string $value): self
    {
        $this->assertType($value);

        $node = new Node($value);

        // Empty list
        if ($this->head === null) {
            $this->head = $node;
            $this->tail = $node;
            $this->count++;
            return $this;
        }

        // Prepend: value <= head
        if ($this->compare($value, $this->head->value) <= 0) {
            $node->next = $this->head;
            $this->head = $node;
            $this->count++;
            return $this;
        }

        // Append: value >= tail (fast path for ascending bulk insert)
        if ($this->compare($value, $this->tail->value) >= 0) { // @phpstan-ignore-line (tail non-null when head non-null)
            $this->tail->next = $node; // @phpstan-ignore-line (tail non-null when head non-null)
            $this->tail = $node;
            $this->count++;
            return $this;
        }

        // General case: find insertion point
        $current = $this->head;
        while ($current->next !== null && $this->compare($value, $current->next->value) > 0) {
            $current = $current->next;
        }
        $node->next = $current->next;
        $current->next = $node;
        $this->count++;

        return $this;
    }

    /**
     * @param T $value
     * @throws TypeMismatchException
     */
    public function remove(int|string $value): bool
    {
        $this->assertType($value);

        if ($this->head === null) {
            return false;
        }

        if ($this->compare($this->head->value, $value) === 0) {
            $this->head = $this->head->next;
            if ($this->head === null) {
                $this->tail = null;
            }
            $this->decrementCount();
            return true;
        }

        $current = $this->head;
        while ($current->next !== null) {
            $cmp = $this->compare($current->next->value, $value);

            if ($cmp === 0) {
                if ($current->next === $this->tail) {
                    $this->tail = $current;
                }
                $current->next = $current->next->next;
                $this->decrementCount();
                return true;
            }

            if ($cmp > 0) {
                return false;
            }

            $current = $current->next;
        }

        return false;
    }

    /**
     * @param T $value
     * @throws TypeMismatchException
     */
    public function removeAll(int|string $value): int
    {
        $this->assertType($value);

        $removed = 0;

        // Remove from head while matching
        while ($this->head !== null && $this->compare($this->head->value, $value) === 0) {
            $this->head = $this->head->next;
            if ($this->head === null) {
                $this->tail = null;
            }
            $this->decrementCount();
            $removed++;
        }

        if ($this->head === null) {
            return $removed;
        }

        $current = $this->head;
        while ($current->next !== null) {
            $cmp = $this->compare($current->next->value, $value);

            if ($cmp > 0) {
                break;
            }

            if ($cmp === 0) {
                if ($current->next === $this->tail) {
                    $this->tail = $current;
                }
                $current->next = $current->next->next;
                $this->decrementCount();
                $removed++;
            } else {
                $current = $current->next;
            }
        }

        return $removed;
    }

    /**
     * Remove and return the first (smallest) value.
     *
     * @throws EmptyListException
     * @return T
     */
    public function removeFirst(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('Cannot removeFirst() on an empty list.');
        }

        $value = $this->head->value;
        $this->head = $this->head->next;
        if ($this->head === null) {
            $this->tail = null;
        }
        $this->decrementCount();

        /** @var T */
        return $value;
    }

    /**
     * Remove and return the last (largest) value.
     *
     * @throws EmptyListException
     * @return T
     */
    public function removeLast(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('Cannot removeLast() on an empty list.');
        }

        $value = $this->tail->value; // @phpstan-ignore-line (tail is non-null when head is non-null)

        if ($this->head === $this->tail) {
            // Single element
            $this->head = null;
            $this->tail = null;
            $this->decrementCount();
            /** @var T */
            return $value;
        }

        // Walk to second-to-last
        $current = $this->head;
        while ($current->next !== $this->tail) { // @phpstan-ignore-line
            $current = $current->next; // @phpstan-ignore-line
        }
        $current->next = null; // @phpstan-ignore-line
        $this->tail = $current;
        $this->decrementCount();

        /** @var T */
        return $value;
    }

    /**
     * Remove all elements.
     */
    public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->count = 0;
    }

    /**
     * Sorted merge of two lists in O(n+m).
     * Both lists must have the same ValueType.
     *
     * @param self<T> $other
     * @throws TypeMismatchException
     * @return self<T>
     */
    public function merge(self $other): self
    {
        if ($other->valueType !== $this->valueType) {
            throw new TypeMismatchException(
                sprintf(
                    'Cannot merge a list of type "%s" with a list of type "%s".',
                    $this->valueType->value,
                    $other->valueType->value,
                ),
            );
        }

        /** @var self<T> $result */
        $result = new self($this->valueType, $this->comparator);

        $a = $this->head;
        $b = $other->head;

        while ($a !== null && $b !== null) {
            if ($this->compare($a->value, $b->value) <= 0) {
                $result->appendNode(new Node($a->value));
                $a = $a->next;
            } else {
                $result->appendNode(new Node($b->value));
                $b = $b->next;
            }
        }

        while ($a !== null) {
            $result->appendNode(new Node($a->value));
            $a = $a->next;
        }

        while ($b !== null) {
            $result->appendNode(new Node($b->value));
            $b = $b->next;
        }

        return $result;
    }

    /**
     * Check if the list contains the given value.
     * Uses early-exit optimization on sorted data.
     *
     * @param T $value
     * @throws TypeMismatchException
     */
    public function contains(int|string $value): bool
    {
        $this->assertType($value);

        $current = $this->head;
        while ($current !== null) {
            $cmp = $this->compare($current->value, $value);
            if ($cmp === 0) {
                return true;
            }
            if ($cmp > 0) {
                return false;
            }
            $current = $current->next;
        }

        return false;
    }

    /**
     * Return the first (smallest) value, throwing if empty.
     *
     * @throws EmptyListException
     * @return T
     */
    public function first(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('Cannot get first() of an empty list.');
        }
        /** @var T */
        return $this->head->value;
    }

    /**
     * Return the last (largest) value, throwing if empty.
     *
     * @throws EmptyListException
     * @return T
     */
    public function last(): int|string
    {
        if ($this->tail === null) {
            throw new EmptyListException('Cannot get last() of an empty list.');
        }
        /** @var T */
        return $this->tail->value;
    }

    public function isEmpty(): bool
    {
        return $this->head === null;
    }

    /**
     * @return list<T>
     */
    public function toArray(): array
    {
        $result = [];
        $current = $this->head;
        while ($current !== null) {
            $result[] = $current->value;
            $current = $current->next;
        }
        /** @var list<T> */
        return $result;
    }

    /**
     * Count occurrences of a value.
     *
     * @param T $value
     * @throws TypeMismatchException
     */
    public function countOf(int|string $value): int
    {
        $this->assertType($value);

        $count = 0;
        $current = $this->head;
        while ($current !== null) {
            $cmp = $this->compare($current->value, $value);
            if ($cmp === 0) {
                $count++;
            } elseif ($cmp > 0) {
                break;
            }
            $current = $current->next;
        }

        return $count;
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return Generator<int, T>
     */
    public function getIterator(): Generator
    {
        $current = $this->head;
        while ($current !== null) {
            yield $current->value;
            $current = $current->next;
        }
    }

    /**
     * @throws TypeMismatchException
     */
    private function assertType(int|string $value): void
    {
        $actualType = is_int($value) ? ValueType::Int : ValueType::String;
        if ($actualType !== $this->valueType) {
            throw new TypeMismatchException(
                sprintf(
                    'List accepts values of type "%s", but "%s" was given.',
                    $this->valueType->value,
                    $actualType->value,
                ),
            );
        }
    }

    /**
     * @param T $a
     * @param T $b
     */
    private function compare(int|string $a, int|string $b): int
    {
        return $this->comparator->compare($a, $b);
    }

    private function decrementCount(): void
    {
        assert($this->count > 0);
        $this->count--;
    }

    /**
     * @param Node<T> $node
     */
    private function appendNode(Node $node): void
    {
        if ($this->head === null) {
            $this->head = $node;
            $this->tail = $node;
        } else {
            $this->tail->next = $node; // @phpstan-ignore-line (tail non-null when head non-null)
            $this->tail = $node;
        }
        $this->count++;
    }
}
