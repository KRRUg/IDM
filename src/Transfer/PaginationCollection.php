<?php


namespace App\Transfer;

use Symfony\Component\Serializer\Annotation\Groups;


final class PaginationCollection
{
    /**
     * @Groups({"dto"})
     */
    public $total;
    /**
     * @Groups({"dto"})
     */
    public $count;
    /**
     * @Groups({"dto"})
     */
    public $items;

    public function __construct($items, $total)
    {
        $this->items = $items;
        $this->total = $total;
        $this->count = count($items);
    }
}