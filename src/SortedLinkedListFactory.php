<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList;

use BCS\SortedLinkedList\Comparator\ComparatorInterface;
use BCS\SortedLinkedList\Comparator\DefaultComparator;

final class SortedLinkedListFactory
{
    /**
     * @param ComparatorInterface<int> $comparator
     * @return SortedLinkedList<int>
     */
    public static function ofIntegers(ComparatorInterface $comparator = new DefaultComparator()): SortedLinkedList
    {
        return new SortedLinkedList(ValueType::Int, $comparator);
    }

    /**
     * @param ComparatorInterface<string> $comparator
     * @return SortedLinkedList<string>
     */
    public static function ofStrings(ComparatorInterface $comparator = new DefaultComparator()): SortedLinkedList
    {
        return new SortedLinkedList(ValueType::String, $comparator);
    }

    /**
     * @template T of int|string
     * @param iterable<T> $values
     * @return SortedLinkedList<T>
     */
    public static function fromIterable(ValueType $valueType, iterable $values): SortedLinkedList
    {
        /** @var SortedLinkedList<T> $list */
        $list = new SortedLinkedList($valueType);
        foreach ($values as $value) {
            $list->insert($value);
        }
        return $list;
    }
}
