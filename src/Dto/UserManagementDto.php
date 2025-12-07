<?php

namespace Src\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UserManagementDto
{
    #[Assert\All([
        new Assert\Type('numeric')
    ])]
    public array $userIds;

    public static function fromRequest(Request $request): UserManagementDto // or self
    {
        $dto = new self();
        $dto->userIds = $request->request->all('userIds');
        return $dto;
    }
}