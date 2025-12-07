<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UserRegistrationDto
{
    #[Assert\Length(min: 3, minMessage: 'Name must be at least {{ limit }} characters long')]
    #[Assert\NotBlank(message: 'Name cannot be blank')]
    public string $name;

    #[Assert\Email]
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    public string $email;

    #[Assert\Length(min: 1)]
    #[Assert\NotBlank(message: 'Password cannot be blank')]
    public string $plainPassword;

    public static function fromRequest(Request $request): UserRegistrationDto
    {
        $dto = new self();
        $dto->name = $request->request->getString('name');
        $dto->email = $request->request->getString('email');
        $dto->plainPassword = $request->request->getString('password');
        return $dto;
    }
}