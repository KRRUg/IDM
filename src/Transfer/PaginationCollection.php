<?php

namespace App\Transfer;

final class PaginationCollection
{
    public int $count;

    public function __construct(public $items, public $total)
    {
        $this->count = is_countable($items) ? count($items) : 0;
    }
}
