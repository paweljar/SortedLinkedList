<?php

declare(strict_types=1);

namespace BCS\SortedLinkedList;

use JsonException;

final class SortedLinkedListSerializer
{
    /**
     * @template T of int|string
     * @param SortedLinkedList<T> $list
     * @throws JsonException
     */
    public static function toJson(SortedLinkedList $list): string
    {
        return json_encode($list->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @template T of int|string
     * @param SortedLinkedList<T> $list
     */
    public static function toString(SortedLinkedList $list): string
    {
        return sprintf(
            'SortedLinkedList(%s)[%s]',
            $list->valueType->value,
            implode(', ', $list->toArray()),
        );
    }
}
