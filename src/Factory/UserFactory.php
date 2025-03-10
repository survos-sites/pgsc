<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
// src/Factory/UserFactory.php

final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();

    }

    public static function class(): string
    {
        return '';
//        return UserForPersistentFactory::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'password' => '1234',
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(UserForPersistentFactory $user) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            })
            ;
    }
}
