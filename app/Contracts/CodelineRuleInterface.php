<?php

namespace App\Contracts;

interface CodelineRuleInterface
{
    public function apply(array $codelineData, array $context): array;
}