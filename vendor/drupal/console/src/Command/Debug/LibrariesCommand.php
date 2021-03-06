<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\LibrariesCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Core\Style\DrupalStyle;

class LibrariesCommand extends Command
{
    /**
 * @var  ModuleHandlerInterface
*/
    protected $moduleHandler;

    /**
 * @var  ThemeHandlerInterface;
*/
    protected $themeHandler;

    /**
 * @var  LibraryDiscoveryInterface
*/
    protected $libraryDiscovery;

    /**
 * @var string
*/
    protected $appRoot;

    /**
     * DebugCommand constructor.
     *
     * @param ModuleHandlerInterface    $moduleHandler
     * @param ThemeHandlerInterface     $themeHandler
     * @param LibraryDiscoveryInterface $libraryDiscovery
     * @param string                    $appRoot
     */
    public function __construct(
        ModuleHandlerInterface $moduleHandler,
        ThemeHandlerInterface $themeHandler,
        LibraryDiscoveryInterface $libraryDiscovery,
        $appRoot
    ) {
        $this->moduleHandler = $moduleHandler;
        $this->themeHandler = $themeHandler;
        $this->libraryDiscovery = $libraryDiscovery;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:libraries')
            ->setDescription($this->trans('commands.debug.libraries.description'))
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.libraries.options.name')
            )->setAliases(['dl']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $group = $input->getArgument('group');

        if (!$group) {
            $groups = $this->getAllLibraries();

            $tableHeader = [
                $this->trans('commands.debug.libraries.messages.name'),
            ];

            $io->table($tableHeader, $groups, 'compact');
        } else {
            $librariesData = $this->libraryDiscovery
                ->getLibrariesByExtension($group);

            foreach ($librariesData as $key => $libraries) {
                $io->comment($key);
                $io->writeln(Yaml::encode($libraries));
            }
        }
    }

    private function getAllLibraries()
    {
        $modules = $this->moduleHandler->getModuleList();
        $themes = $this->themeHandler->rebuildThemeData();
        $extensions = array_merge($modules, $themes);
        $libraries = [];

        foreach ($extensions as $extensionName => $extension) {
            $libraryFile = $extension->getPath() . '/' . $extensionName . '.libraries.yml';
            if (is_file($this->appRoot . '/' . $libraryFile)) {
                $libraries[$extensionName] = $this->libraryDiscovery->getLibrariesByExtension($extensionName);
            }
        }

        return array_keys($libraries);
    }
}
