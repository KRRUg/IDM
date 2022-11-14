<?php

namespace App\Filter;

use App\Entity\User;
use App\Entity\UserClan;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class UserFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        switch ($targetEntity->getReflectionClass()->getName()) {
            case User::class:
                return "{$targetTableAlias}.status >= 0";
            case UserClan::class:
                return "(SELECT min(gamer.status) FROM gamer WHERE gamer.id = {$targetTableAlias}.user_id) >= 0";
            default:
                return '';
        }
    }
}
