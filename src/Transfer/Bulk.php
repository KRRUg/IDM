<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class Bulk
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $uuid = [];
}
