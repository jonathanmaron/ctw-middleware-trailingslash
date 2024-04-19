<?php
declare(strict_types=1);

namespace Ctw\Middleware\TrailingSlashMiddleware;

use Ctw\Middleware\AbstractMiddleware;

abstract class AbstractTrailingSlashMiddleware extends AbstractMiddleware
{
    protected array $config = [];

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }
}
