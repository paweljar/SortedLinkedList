# SortedLinkedList

A PHP 8.4 library implementing a singly-linked list that automatically maintains its values in sorted order. The list holds either `int` or `string` values — never both — with the type fixed at construction time.

## Requirements

- PHP 8.4+

## Installation

```bash
composer require bcs/sorted-linked-list
```

## Quick Start

```php
use BCS\SortedLinkedList\SortedLinkedListFactory;
use BCS\SortedLinkedList\SortedLinkedListSerializer;

$list = SortedLinkedListFactory::ofIntegers();
$list->insert(5)->insert(1)->insert(3)->insert(2)->insert(4);

echo SortedLinkedListSerializer::toString($list); // SortedLinkedList(int)[1, 2, 3, 4, 5]
```

## Construction

Three ways to create a list:

```php
use BCS\SortedLinkedList\SortedLinkedList;
use BCS\SortedLinkedList\SortedLinkedListFactory;
use BCS\SortedLinkedList\ValueType;

// Named factories (recommended)
$ints    = SortedLinkedListFactory::ofIntegers();
$strings = SortedLinkedListFactory::ofStrings();

// Constructor with explicit ValueType
$list = new SortedLinkedList(ValueType::Int);

// Build from an existing iterable
$list = SortedLinkedListFactory::fromIterable(ValueType::Int, [5, 3, 1, 4, 2]);
// → [1, 2, 3, 4, 5]
```

## Inserting Values

`insert()` places the value in sorted position and returns `$this` for fluent chaining:

```php
$list = SortedLinkedListFactory::ofIntegers();
$list->insert(30)->insert(10)->insert(20);
// → [10, 20, 30]

// Duplicates are allowed
$list->insert(20);
// → [10, 20, 20, 30]
```

Inserting a value of the wrong type throws `TypeMismatchException`:

```php
$list = SortedLinkedListFactory::ofIntegers();
$list->insert('hello'); // throws TypeMismatchException
```

## Reading Values

```php
$list = SortedLinkedListFactory::fromIterable(ValueType::Int, [3, 1, 2]);

// Property hooks — return null on empty list
$list->first; // 1
$list->last;  // 3

// Methods — throw EmptyListException on empty list
$list->first(); // 1
$list->last();  // 3

$list->count;   // 3  (asymmetric visibility: readable, not settable)
count($list);   // 3  (Countable)
$list->isEmpty(); // false
$list->toArray(); // [1, 2, 3]
```

## Searching

```php
$list = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 2, 2, 3]);

$list->contains(2);   // true
$list->contains(99);  // false
$list->countOf(2);    // 2
```

Both methods use **early-exit** — they stop traversing as soon as the current node's value exceeds the target, thanks to the sorted invariant.

## Removing Values

```php
$list = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 2, 2, 3]);

// Remove first occurrence — returns bool
$list->remove(2);     // true  → [1, 2, 3]
$list->remove(99);    // false (not found)

// Remove all occurrences — returns count removed
$list->removeAll(2);  // 1    → [1, 3]

// Pop from ends — return the removed value, throw EmptyListException if empty
$list->removeFirst(); // 1    → [3]
$list->removeLast();  // 3    → []

// Wipe everything
$list->clear();
```

## Merging Two Lists

`merge()` combines two same-type lists into a new sorted list in **O(n+m)** time. The original lists are not modified.

```php
$odds  = SortedLinkedListFactory::fromIterable(ValueType::Int, [1, 3, 5]);
$evens = SortedLinkedListFactory::fromIterable(ValueType::Int, [2, 4, 6]);

$merged = $odds->merge($evens);
// → [1, 2, 3, 4, 5, 6]

// Originals are unchanged
$odds->toArray();  // [1, 3, 5]
$evens->toArray(); // [2, 4, 6]
```

Merging lists of different types throws `TypeMismatchException`.

## Custom Comparator

Pass a `ComparatorInterface` implementation to control sort order. The `compare()` method follows the same convention as `usort()`: return a negative int, zero, or positive int.

```php
use BCS\SortedLinkedList\Comparator\ComparatorInterface;
use BCS\SortedLinkedList\SortedLinkedList;
use BCS\SortedLinkedList\ValueType;

// Descending order
$desc = new SortedLinkedList(ValueType::Int, new class implements ComparatorInterface {
    public function compare(int|string $a, int|string $b): int { return $b <=> $a; }
});
$desc->insert(3)->insert(1)->insert(2);
// → [3, 2, 1]

// Case-insensitive string sort
$ci = new SortedLinkedList(ValueType::String, new class implements ComparatorInterface {
    public function compare(int|string $a, int|string $b): int { return strcasecmp((string) $a, (string) $b); }
});
$ci->insert('Banana')->insert('apple')->insert('Cherry');
// → ['apple', 'Banana', 'Cherry']
```

## Standard Interface Support

The list implements two standard PHP interfaces out of the box:

```php
$list = SortedLinkedListFactory::fromIterable(ValueType::Int, [3, 1, 2]);

// Countable
count($list); // 3

// IteratorAggregate — foreach in sorted order
foreach ($list as $value) {
    echo $value . "\n"; // 1, 2, 3
}
```

## Serialization

Use `SortedLinkedListSerializer` for JSON and string representations:

```php
use BCS\SortedLinkedList\SortedLinkedListSerializer;

SortedLinkedListSerializer::toJson($list);   // "[1,2,3]"
SortedLinkedListSerializer::toString($list); // "SortedLinkedList(int)[1, 2, 3]"
```

## Exceptions

| Exception | Extends | Thrown when |
|---|---|---|
| `TypeMismatchException` | `\InvalidArgumentException` | Value type doesn't match the list's type |
| `EmptyListException` | `\UnderflowException` | `first()`, `last()`, `removeFirst()`, or `removeLast()` called on an empty list |

```php
use BCS\SortedLinkedList\Exception\EmptyListException;
use BCS\SortedLinkedList\Exception\TypeMismatchException;

$list = SortedLinkedListFactory::ofIntegers();

try {
    $list->first();
} catch (EmptyListException $e) {
    // handle empty list
}

try {
    $list->insert('oops');
} catch (TypeMismatchException $e) {
    // handle wrong type
}
```

## Development

```bash
# Install dependencies
composer install

# Run all checks (cs-check + analyse + test)
composer run check

# Run tests
composer run test

# Static analysis
composer run analyse

# Code style check (dry-run)
composer run cs-check

# Code style fix
composer run cs-fix
```

## License

MIT
