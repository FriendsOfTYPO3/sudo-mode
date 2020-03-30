<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Tests;

trait TestingTrait
{
    protected function generateTestString(int $length = 10): string
    {
        return bin2hex(random_bytes($length));
    }

    protected function generateTestInteger(int $max = 4294967296): int
    {
        return random_int(0, $max);
    }
}
