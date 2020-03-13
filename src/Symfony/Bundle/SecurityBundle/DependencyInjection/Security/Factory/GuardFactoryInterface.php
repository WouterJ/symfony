<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
interface GuardFactoryInterface
{
    /**
     * Creates the Guard service for the provided configuration.
     *
     * @return string The Guard service ID to be used by the firewall
     */
    public function createGuard(ContainerBuilder $container, string $id, array $config, string $userProviderId): string;
}
