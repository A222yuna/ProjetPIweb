<?php

namespace App\Tests\Entity;

use App\Entity\Cabinet;
use PHPUnit\Framework\TestCase;

class CabinetTest extends TestCase
{
    public function testCabinetEntity(): void
    {
        $cabinet = new Cabinet();

        $cabinet->setAdresse('123 Rue de la Paix');
        $cabinet->setVille('Paris');
        $cabinet->setHoraires('9h-18h');
        $cabinet->setValide(true);

        $this->assertEquals('123 Rue de la Paix', $cabinet->getAdresse());
        $this->assertEquals('Paris', $cabinet->getVille());
        $this->assertEquals('9h-18h', $cabinet->getHoraires());
        $this->assertTrue($cabinet->isValide());
        $this->assertFalse($cabinet->isArchive());
    }
}
