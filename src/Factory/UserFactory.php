<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
// src/Factory/UserFactory.php

final class UserFactory extends PersistentProxyObjectFactory
{
    // the injected service should be nullable in order to be used in unit test, without container
    public function __construct(
        private ?UserPasswordHasherInterface $passwordHasher = null
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'password' => '1234',
            'roles' => ['ROLE_USER'],
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(User $user) {
                if ($this->passwordHasher !== null) {
//                    dd($user->getPassword(), $user->getPlainPassword());
                    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
                }
            })
            ;
    }
}
