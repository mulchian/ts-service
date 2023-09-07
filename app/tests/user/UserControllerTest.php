<?php

namespace touchdownstars\tests\user;

use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    public function testClass()
    {
        $this->assertTrue(class_exists('touchdownstars\user\UserController'));
    }
}