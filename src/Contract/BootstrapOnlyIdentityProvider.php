<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

/**
 * Marker interface for an IdentityProviderPort that is only ever invoked via
 * AuthBootstrapController (zero-click, driven by a platform adapter's own
 * bootstrap module) and must never be offered as a manual login button on
 * the /login screen.
 */
interface BootstrapOnlyIdentityProvider extends IdentityProviderPort
{
}
