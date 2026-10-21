<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->useThrowawayAppKey();

        parent::setUp();
    }

    /**
     * The suite must run on a fresh clone with no `.env`, so the encrypter gets a
     * fixed, obviously-fake key instead of relying on a developer's local file.
     */
    private function useThrowawayAppKey(): void
    {
        $key = 'base64:'.base64_encode(str_repeat('0', 32));

        putenv("APP_KEY={$key}");
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;
    }
}
