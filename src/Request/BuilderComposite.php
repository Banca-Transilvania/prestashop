<?php

namespace BTiPay\Request;

use BTiPay\Factory\PSTMapFactory;

class BuilderComposite implements BuilderInterface
{
    /**
     * @var BuilderInterface[]
     */
    private $builders;

    /**
     * @param PSTMapFactory $factory
     * @param array $builders
     */
    public function __construct(
        PSTMapFactory $factory,
        array $builders = []
    ) {
        $this->builders = $factory->create(BuilderInterface::class, $builders);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $result = [];
        foreach ($this->builders as $builder) {
            $result = $this->merge($result, $builder->build($buildSubject));
        }

        return $result;
    }

    /**
     * Merge function for builders
     *
     * @param array $result
     * @param array $builder
     * @return array
     */
    protected function merge(array $result, array $builder)
    {
        return array_replace_recursive($result, $builder);
    }
}