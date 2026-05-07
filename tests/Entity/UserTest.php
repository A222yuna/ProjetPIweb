<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserEntity(): void
    {
        $user = new User();
        
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setRole('Psychologue');
        $user->setStatutValidation('approuve');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('Dupont', $user->getNom());
        $this->assertEquals('Jean', $user->getPrenom());
        $this->assertEquals('Psychologue', $user->getRole());
        $this->assertEquals('approuve', $user->getStatutValidation());
        $this->assertTrue($user->isEstActif()); // Default value
    }
}
