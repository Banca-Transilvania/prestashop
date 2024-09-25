<?php

namespace BTiPay\Command;

interface CommandInterface
{
    public function execute(array $commandSubject);
}