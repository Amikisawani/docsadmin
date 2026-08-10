<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests ne dépendent pas du bundle Vite : les vues sont rendues
        // sans manifeste, sinon la suite exige un `npm run build` préalable.
        $this->withoutVite();
    }
}
