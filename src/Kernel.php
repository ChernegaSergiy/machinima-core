<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private function getProfile(): string
    {
        return $_SERVER['APP_PROFILE'] ?? $_ENV['APP_PROFILE'] ?? 'core-only';
    }

    protected function getContainerClass(): string
    {
        $class = parent::getContainerClass();
        $profile = $this->getProfile();

        return 'core-only' === $profile ? $class : $class.str_replace('-', '_', $profile);
    }

    public function registerBundles(): iterable
    {
        if (!is_file($this->getBundlesPath())) {
            yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();

            return;
        }

        $profile = $this->getProfile();
        $profileBundlesPath = $this->getConfigDir().'/profiles/'.$profile.'/bundles.php';

        if (is_file($profileBundlesPath)) {
            foreach (require $profileBundlesPath as $class => $envs) {
                if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                    yield new $class();
                }
            }
        }

        foreach ($this->getBundlesDefinition() as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());

        $container->import($configDir.'/{packages}/*.{php,yaml}');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.{php,yaml}');

        $profile = $this->getProfile();
        $profileDir = $configDir.'/{profiles}/'.$profile;
        if (is_dir($this->getConfigDir().'/profiles/'.$profile)) {
            $container->import($profileDir.'/{packages}/*.{php,yaml}');
            $container->import($profileDir.'/{services}.yaml');
        }

        if (is_file($this->getConfigDir().'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import($configDir.'/{services}.php');
            $container->import($configDir.'/{services}_'.$this->environment.'.php');
        }
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());

        $routes->import($configDir.'/{routes}/'.$this->environment.'/*.{php,yaml}');
        $routes->import($configDir.'/{routes}/*.{php,yaml}');

        $profile = $this->getProfile();
        $profileDir = $configDir.'/{profiles}/'.$profile;
        if (is_dir($this->getConfigDir().'/profiles/'.$profile)) {
            $routes->import($profileDir.'/{routes}/*.{php,yaml}');
            $routes->import($profileDir.'/{routes}.yaml');
        }

        if (is_file($this->getConfigDir().'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        } else {
            $routes->import($configDir.'/{routes}.php');
        }

        if ($fileName = (new \ReflectionObject($this))->getFileName()) {
            $routes->import($fileName, 'attribute');
        }
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
