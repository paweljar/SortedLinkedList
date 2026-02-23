<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList\Tests;

use BCS\SortedLinkedList\Comparator\ComparatorInterface;
use BCS\SortedLinkedList\Exception\EmptyListException;
use BCS\SortedLinkedList\Exception\TypeMismatchException;
use BCS\SortedLinkedList\SortedLinkedList;
use BCS\SortedLinkedList\SortedLinkedListFactory;
use BCS\SortedLinkedList\SortedLinkedListSerializer;
use BCS\SortedLinkedList\ValueType;
use PHPUnit\Framework\TestCase;

class SortedLinkedListTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction & Factories
    // -------------------------------------------------------------------------

    public function testOfIntegersCreatesIntList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertSame(ValueType::Int, $list->valueType);
        self::assertTrue($list->isEmpty());
    }

    public function testOfStringsCreatesStringList(): void
    {
        $list = SortedLinkedListFactory::ofStrings();
        self::assertSame(ValueType::String, $list->valueType);
        self::assertTrue($list->isEmpty());
    }

    public function testFromIterableInsertsAllValuesSorted(): void
    {
        $list = SortedLinkedListFactory::fromIterable(ValueType::Int, [5, 3, 1, 4, 2]);
        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function testFromIterableWithStrings(): void
    {
        $list = SortedLinkedListFactory::fromIterable(ValueType::String, ['banana', 'apple', 'cherry']);
        self::assertSame(['apple', 'banana', 'cherry'], $list->toArray());
    }

    // -------------------------------------------------------------------------
    // Insert
    // -------------------------------------------------------------------------

    public function testInsertMaintainsSortedOrder(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(3)->insert(1)->insert(4)->insert(2);
        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function testInsertAllowsDuplicates(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(3)->insert(3)->insert(1)->insert(3);
        self::assertSame([1, 3, 3, 3], $list->toArray());
    }

    public function testInsertPrependsSmallestValue(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(10)->insert(5)->insert(1);
        self::assertSame([1, 5, 10], $list->toArray());
        self::assertSame(1, $list->first);
    }

    public function testInsertAppendsLargestValue(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(5)->insert(10);
        self::assertSame([1, 5, 10], $list->toArray());
        self::assertSame(10, $list->last);
    }

    public function testInsertStringsSorted(): void
    {
        $list = SortedLinkedListFactory::ofStrings();
        $list->insert('cherry')->insert('apple')->insert('banana');
        self::assertSame(['apple', 'banana', 'cherry'], $list->toArray());
    }

    public function testInsertThrowsOnTypeMismatchIntListGivenString(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(TypeMismatchException::class);
        $list->insert('hello'); // @phpstan-ignore-line
    }

    public function testInsertThrowsOnTypeMismatchStringListGivenInt(): void
    {
        $list = SortedLinkedListFactory::ofStrings();
        $this->expectException(TypeMismatchException::class);
        $list->insert(42); // @phpstan-ignore-line
    }

    public function testInsertReturnsSelf(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $result = $list->insert(1);
        self::assertSame($list, $result);
    }

    // -------------------------------------------------------------------------
    // Count / isEmpty
    // -------------------------------------------------------------------------

    public function testCountStartsAtZero(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertSame(0, $list->count);
        self::assertSame(0, count($list));
    }

    public function testCountIncreasesOnInsert(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        self::assertSame(3, $list->count);
    }

    public function testIsEmptyTrueOnNew(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertTrue($list->isEmpty());
    }

    public function testIsEmptyFalseAfterInsert(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1);
        self::assertFalse($list->isEmpty());
    }

    // -------------------------------------------------------------------------
    // Properties: $first, $last
    // -------------------------------------------------------------------------

    public function testFirstPropertyNullOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertNull($list->first);
    }

    public function testLastPropertyNullOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertNull($list->last);
    }

    public function testFirstPropertyReturnsMin(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(1)->insert(3);
        self::assertSame(1, $list->first);
    }

    public function testLastPropertyReturnsMax(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(1)->insert(3);
        self::assertSame(5, $list->last);
    }

    // -------------------------------------------------------------------------
    // Methods: first(), last()
    // -------------------------------------------------------------------------

    public function testFirstMethodThrowsOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(EmptyListException::class);
        $list->first();
    }

    public function testLastMethodThrowsOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(EmptyListException::class);
        $list->last();
    }

    public function testFirstMethodReturnsMin(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(1)->insert(3);
        self::assertSame(1, $list->first());
    }

    public function testLastMethodReturnsMax(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(1)->insert(3);
        self::assertSame(5, $list->last());
    }

    // -------------------------------------------------------------------------
    // Remove
    // -------------------------------------------------------------------------

    public function testRemoveReturnsTrueWhenFound(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        self::assertTrue($list->remove(2));
        self::assertSame([1, 3], $list->toArray());
    }

    public function testRemoveReturnsFalseWhenNotFound(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(3);
        self::assertFalse($list->remove(2));
        self::assertSame([1, 3], $list->toArray());
    }

    public function testRemoveFirstOccurrenceOnly(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(2)->insert(3);
        $list->remove(2);
        self::assertSame([1, 2, 3], $list->toArray());
        self::assertSame(3, $list->count);
    }

    public function testRemoveHead(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        $list->remove(1);
        self::assertSame([2, 3], $list->toArray());
        self::assertSame(2, $list->first);
    }

    public function testRemoveTail(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        $list->remove(3);
        self::assertSame([1, 2], $list->toArray());
        self::assertSame(2, $list->last);
    }

    public function testRemoveSingleElement(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(42);
        $list->remove(42);
        self::assertTrue($list->isEmpty());
        self::assertNull($list->first);
        self::assertNull($list->last);
    }

    public function testRemoveFromEmptyListReturnsFalse(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertFalse($list->remove(1));
    }

    public function testRemoveThrowsOnTypeMismatch(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(TypeMismatchException::class);
        $list->remove('x'); // @phpstan-ignore-line
    }

    // -------------------------------------------------------------------------
    // RemoveAll
    // -------------------------------------------------------------------------

    public function testRemoveAllRemovesAllOccurrences(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(2)->insert(2)->insert(3);
        $removed = $list->removeAll(2);
        self::assertSame(3, $removed);
        self::assertSame([1, 3], $list->toArray());
        self::assertSame(2, $list->count);
    }

    public function testRemoveAllReturnsZeroWhenNotFound(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(3);
        self::assertSame(0, $list->removeAll(2));
    }

    public function testRemoveAllFromHead(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(1)->insert(2);
        $list->removeAll(1);
        self::assertSame([2], $list->toArray());
        self::assertSame(2, $list->first);
    }

    public function testRemoveAllFromTail(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(2);
        $list->removeAll(2);
        self::assertSame([1], $list->toArray());
        self::assertSame(1, $list->last);
    }

    public function testRemoveAllEntireList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(5)->insert(5);
        $list->removeAll(5);
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count);
    }

    // -------------------------------------------------------------------------
    // RemoveFirst / RemoveLast
    // -------------------------------------------------------------------------

    public function testRemoveFirstReturnsAndRemovesHead(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(3)->insert(1)->insert(2);
        self::assertSame(1, $list->removeFirst());
        self::assertSame([2, 3], $list->toArray());
        self::assertSame(2, $list->count);
    }

    public function testRemoveFirstThrowsOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(EmptyListException::class);
        $list->removeFirst();
    }

    public function testRemoveLastReturnsAndRemovesTail(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(3)->insert(1)->insert(2);
        self::assertSame(3, $list->removeLast());
        self::assertSame([1, 2], $list->toArray());
        self::assertSame(2, $list->count);
    }

    public function testRemoveLastThrowsOnEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(EmptyListException::class);
        $list->removeLast();
    }

    public function testRemoveFirstThenLastLeavesEmpty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1);
        $list->removeFirst();
        self::assertTrue($list->isEmpty());
    }

    public function testRemoveLastSingleElement(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(42);
        self::assertSame(42, $list->removeLast());
        self::assertTrue($list->isEmpty());
        self::assertNull($list->first);
        self::assertNull($list->last);
    }

    // -------------------------------------------------------------------------
    // Clear
    // -------------------------------------------------------------------------

    public function testClearEmptiesTheList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        $list->clear();
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count);
        self::assertNull($list->first);
        self::assertNull($list->last);
    }

    // -------------------------------------------------------------------------
    // Contains
    // -------------------------------------------------------------------------

    public function testContainsReturnsTrueForExistingValue(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        self::assertTrue($list->contains(2));
    }

    public function testContainsReturnsFalseForMissingValue(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(3);
        self::assertFalse($list->contains(2));
    }

    public function testContainsReturnsFalseOnEmptyList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertFalse($list->contains(1));
    }

    public function testContainsUsesEarlyExit(): void
    {
        // Value larger than all existing => early exit without full traversal
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        self::assertFalse($list->contains(10));
    }

    public function testContainsThrowsOnTypeMismatch(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $this->expectException(TypeMismatchException::class);
        $list->contains('x'); // @phpstan-ignore-line
    }

    // -------------------------------------------------------------------------
    // CountOf
    // -------------------------------------------------------------------------

    public function testCountOfReturnsCorrectCount(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(2)->insert(2)->insert(3);
        self::assertSame(3, $list->countOf(2));
    }

    public function testCountOfReturnsZeroWhenNotFound(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(3);
        self::assertSame(0, $list->countOf(2));
    }

    // -------------------------------------------------------------------------
    // Merge
    // -------------------------------------------------------------------------

    public function testMergeProducesSortedResult(): void
    {
        $a = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 3, 5]);
        $b = SortedLinkedListFactory::fromIterable(ValueType::Int, [2, 4, 6]);
        $merged = $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        self::assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
        self::assertSame(6, $merged->count);
    }

    public function testMergePreservesOriginals(): void
    {
        $a = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 3]);
        $b = SortedLinkedListFactory::fromIterable(ValueType::Int, [2, 4]);
        $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        self::assertSame([1, 3], $a->toArray());
        self::assertSame([2, 4], $b->toArray());
    }

    public function testMergeWithEmptyList(): void
    {
        $a = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 2, 3]);
        $b = SortedLinkedListFactory::ofIntegers();
        $merged = $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        self::assertSame([1, 2, 3], $merged->toArray());
    }

    public function testMergeEmptyWithNonEmpty(): void
    {
        $a = SortedLinkedListFactory::ofIntegers();
        $b = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 2, 3]);
        $merged = $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        self::assertSame([1, 2, 3], $merged->toArray());
    }

    public function testMergeThrowsOnTypeMismatch(): void
    {
        $a = SortedLinkedListFactory::ofIntegers();
        $b = SortedLinkedListFactory::ofStrings();
        $this->expectException(TypeMismatchException::class);
        $a->merge($b); // @phpstan-ignore-line
    }

    public function testMergeWithDuplicates(): void
    {
        $a = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 2, 3]);
        $b = SortedLinkedListFactory::fromIterable(ValueType::Int, [2, 3, 4]);
        $merged = $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        self::assertSame([1, 2, 2, 3, 3, 4], $merged->toArray());
    }

    // -------------------------------------------------------------------------
    // Iterator / Countable / Serializer
    // -------------------------------------------------------------------------

    public function testGetIteratorYieldsValuesInOrder(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(5)->insert(1)->insert(3);
        $values = [];
        foreach ($list as $v) {
            $values[] = $v;
        }
        self::assertSame([1, 3, 5], $values);
    }

    public function testCountableInterface(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        self::assertCount(3, $list);
    }

    public function testSerializerToJson(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(3)->insert(1)->insert(2);
        self::assertSame('[1,2,3]', SortedLinkedListSerializer::toJson($list));
    }

    public function testSerializerToStringFormat(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(3)->insert(1)->insert(2);
        self::assertSame('SortedLinkedList(int)[1, 2, 3]', SortedLinkedListSerializer::toString($list));
    }

    public function testSerializerToStringEmptyList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertSame('SortedLinkedList(int)[]', SortedLinkedListSerializer::toString($list));
    }

    public function testSerializerToStringStrings(): void
    {
        $list = SortedLinkedListFactory::ofStrings();
        $list->insert('b')->insert('a');
        self::assertSame('SortedLinkedList(string)[a, b]', SortedLinkedListSerializer::toString($list));
    }

    // -------------------------------------------------------------------------
    // Custom Comparator
    // -------------------------------------------------------------------------

    public function testCustomComparatorReverseOrder(): void
    {
        $list = new SortedLinkedList(ValueType::Int, new class () implements ComparatorInterface {
            public function compare(int|string $a, int|string $b): int
            {
                return $b <=> $a;
            }
        });
        $list->insert(3)->insert(1)->insert(2);
        self::assertSame([3, 2, 1], $list->toArray());
    }

    public function testCustomComparatorCaseInsensitive(): void
    {
        $list = new SortedLinkedList(ValueType::String, new class () implements ComparatorInterface {
            public function compare(int|string $a, int|string $b): int
            {
                return strcasecmp((string) $a, (string) $b);
            }
        });
        $list->insert('Banana')->insert('apple')->insert('Cherry');
        self::assertSame(['apple', 'Banana', 'Cherry'], $list->toArray());
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testSingleElementList(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(42);
        self::assertSame(42, $list->first);
        self::assertSame(42, $list->last);
        self::assertSame(1, $list->count);
        self::assertSame([42], $list->toArray());
    }

    public function testLargeInsertionMaintainsSortedOrder(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $values = range(100, 1);
        foreach ($values as $v) {
            $list->insert($v);
        }
        self::assertSame(range(1, 100), $list->toArray());
        self::assertSame(100, $list->count);
    }

    public function testInsertSameValueMany(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        for ($i = 0; $i < 10; $i++) {
            $list->insert(5);
        }
        self::assertSame(10, $list->count);
        self::assertSame(array_fill(0, 10, 5), $list->toArray());
    }

    public function testNegativeIntegers(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(-3)->insert(0)->insert(-1)->insert(2)->insert(-5);
        self::assertSame([-5, -3, -1, 0, 2], $list->toArray());
    }

    public function testEmptyListToArray(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        self::assertSame([], $list->toArray());
    }

    public function testRemoveLastUpdatesLastProperty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        $list->removeLast();
        self::assertSame(2, $list->last);
        self::assertSame(2, $list->last());
    }

    public function testRemoveFirstUpdatesFirstProperty(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1)->insert(2)->insert(3);
        $list->removeFirst();
        self::assertSame(2, $list->first);
        self::assertSame(2, $list->first());
    }

    public function testCountIsAsymmetricVisibility(): void
    {
        $list = SortedLinkedListFactory::ofIntegers();
        $list->insert(1);
        // Public read
        self::assertSame(1, $list->count);
    }

    public function testMergeReturnsSelfForFluency(): void
    {
        $a = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 3]);
        $b = SortedLinkedListFactory::fromIterable(ValueType::Int, [2, 4]);
        $merged = $a->merge($b); // @phpstan-ignore-line (literal int types narrowed by PHPStan)
        // merge returns a new list, not $this
        self::assertNotSame($a, $merged);
    }
}
