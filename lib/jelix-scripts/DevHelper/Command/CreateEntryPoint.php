<?php

/**
* @package     jelix-scripts
* @author      Laurent Jouanneau
* @contributor
* @copyright   2008-2018 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/
namespace Jelix\DevHelper\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jelix\FileUtilities\Path;

class CreateEntryPoint extends \Jelix\DevHelper\AbstractCommandForApp {

    protected function configure()
    {
        $this
            ->setName('app:createentrypoint')
            ->setDescription('Create a new entry point in the www directory of the application')
            ->setHelp('')
            ->addArgument(
                'entrypoint',
                InputArgument::REQUIRED,
                'Name of the new entrypoint. It can contain a directory path related to the config dir'
            )
            ->addArgument(
                'config',
                InputArgument::OPTIONAL,
                'The name of the configuration file to use. If it does not exists, it will be created with default content or with the content of the configuration file indicated with --copy-config'
            )
            ->addOption(
               'type',
               null,
               InputOption::VALUE_REQUIRED,
               'indicates the type of the entry point: classic, jsonrpc, xmlrpc, soap, cmdline',
               'classic'
            )
            ->addOption(
               'copy-config',
               null,
               InputOption::VALUE_REQUIRED,
               'The name of the configuration file to copy as new configuration file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // retrieve the type of entry point we want to create
        $type = $input->getOption('type');
        if(!in_array($type, array('classic','jsonrpc','xmlrpc','soap','cmdline'))) {
            throw new \Exception("invalid type");
        }

        // retrieve the name of the entry point
        $name = $input->getArgument('entrypoint');
        if (preg_match('/(.*)\.php$/', $name, $m)) {
            $name = $m[1];
        }

        // the full path of the entry point
        if ($type == 'cmdline') {
            $entryPointFullPath = \jApp::scriptsPath($name.'.php');
            $entryPointTemplate = 'scripts/cmdline.php.tpl';
        }
        else {
            $entryPointFullPath = \jApp::wwwPath($name.'.php');
            $entryPointTemplate = 'www/'.($type=='classic'?'index':$type).'.php.tpl';
        }

        if (file_exists($entryPointFullPath)) {
            throw new \Exception("the entry point already exists");
        }

        $entryPointDir = dirname($entryPointFullPath).'/';

        $this->loadProjectXml();

        // retrieve the config file name
        $configFile = $input->getArgument('config');

        if ($configFile == null) {
            if ($type == 'cmdline') {
                $configFile = 'cmdline/'.$name.'.ini.php';
            }
            else {
                $configFile = $name.'/config.ini.php';
            }
        }

        // let's create the config file if needed
        $configFilePath = \jApp::appConfigPath($configFile);
        if (!file_exists($configFilePath)) {
            $this->createDir(dirname($configFilePath));
            // the file doesn't exists
            // if there is a -copy-config parameter, we copy this file
            $originalConfig = $input->getOption('copy-config');
            if ($originalConfig) {
                if (! file_exists(\jApp::appConfigPath($originalConfig))) {
                    throw new Exception ("unknown original configuration file");
                }
                file_put_contents($configFilePath,
                                  file_get_contents(\jApp::appConfigPath($originalConfig)));
                if ($this->verbose()) {
                    $output->writeln("Configuration file $configFile has been created from the config file $originalConfig.");
                }
            }
            else {
                // else we create a new config file
                $param = array();
                $this->createFile($configFilePath,
                                  'app/config/index/config.ini.php.tpl',
                                  $param, "Configuration file");
            }
        }

        $mainIniFile = new \Jelix\IniFile\MultiIniModifier(\jConfig::getDefaultConfigFile(), \jApp::mainConfigFile());
        $inifile = new \Jelix\IniFile\MultiIniModifier($mainIniFile, $configFilePath);
        $urlsFile = \jApp::appConfigPath($inifile->getValue('significantFile', 'urlengine'));
        $xmlMap = new \Jelix\Routing\UrlMapping\XmlMapModifier($urlsFile, true);

        $param = array();
        // creation of the entry point
        $this->createDir($entryPointDir);
        $param['rp_app']   = Path::shortestPath($entryPointDir, \jApp::appPath());
        $param['config_file'] = $configFile;

        $this->createFile($entryPointFullPath, $entryPointTemplate, $param, "Entry point");

        if ($type != 'cmdline') {
            $xmlEp = $xmlMap->addEntryPoint($name, $type);
            /*if ($type == 'classic') {
                $xmlEp->addUrlAction('/', $module, $action);
            }*/
            $xmlMap->save();
        }

        $this->updateProjectXml($name.".php", $configFile , $type);
        if ($this->verbose()) {
            $output->writeln("Project.xml has been updated");
        }
    }

    protected function _execute(InputInterface $input, OutputInterface $output)
    {
    }

    protected function updateProjectXml ($fileName, $configFileName, $type) {

        $elem = $this->projectXml->createElementNS(JELIX_NAMESPACE_BASE.'project/1.0', 'entry');
        $elem->setAttribute("file", $fileName);
        $elem->setAttribute("config", $configFileName);
        $elem->setAttribute("type", $type);

        $ep = $this->projectXml->documentElement->getElementsByTagName("entrypoints");

        if (!$ep->length) {
            $ep = $this->projectXml->createElementNS(JELIX_NAMESPACE_BASE.'project/1.0', 'entrypoints');
            $this->projectXml->documentElement->appendChild($ep);
            $ep->appendChild($elem);
        }
        else {
            $ep->item(0)->appendChild($elem);
        }

        $this->projectXml->save(\jApp::appPath('project.xml'));
    }
}
