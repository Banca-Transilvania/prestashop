<?php
namespace BTiPay\Factory;

class PSTMapFactory
{
    public function create($type, array $items = [])
    {
        return new PSTMap($type, $items);
    }
}
