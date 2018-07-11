<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Laurent Jouanneau
* @contributor
* @copyright   2009-2012 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
* @since 1.2
*/


require_once(__DIR__.'/installer.lib.php');

class testInstallerComponentModule2 extends jInstallerComponentModule {

    function setSourceVersionDate($version, $date) {
        $this->sourceDate = $date;
        $this->sourceVersion = $version;
    }

}



class testInstallerComponentForDependencies extends jInstallerComponentModule {
    
    protected $identityNamespace = 'http://jelix.org/ns/module/1.0';
    protected $rootName = 'module';
    protected $identityFile = 'module.xml';
    
    function getInstaller(jInstallerEntryPoint2 $ep, $installWholeApp) {
        return null;
    }

    function getUpgraders(jInstallerEntryPoint2 $ep) {
        return null;
    }
    
    function readDependenciesFromString($xmlcontent) {
        $xml = simplexml_load_string($xmlcontent);
        //$this->sourceVersion = (string) $xml->info[0]->version[0];   
        $this->readDependencies($xml);
    }
    
}

class jInstaller_ComponentTest extends jUnitTestCase {

    protected $globalSetup;

    function setUp() {
        self::initJelixConfig();
        $this->globalSetup = new testInstallerGlobalSetup();
        jApp::saveContext();
    }

    function tearDown() {
        jApp::restoreContext();
    }

    public function testDependenciesReading() {
        $comp = new testInstallerComponentForDependencies("test","", null);

        $str = '<?xml version="1.0" encoding="UTF-8"?>
<module xmlns="http://jelix.org/ns/module/1.0">
</module>';
        $comp->readDependenciesFromString($str);
        $this->assertEquals(array(), $comp->getDependencies());
        $this->assertEquals(array('*','*'), $comp->getJelixVersion());

        $str = '<?xml version="1.0" encoding="UTF-8"?>
<module xmlns="http://jelix.org/ns/module/1.0">
    <dependencies>
    </dependencies>
</module>';
        $comp->readDependenciesFromString($str);
        $this->assertEquals(array(), $comp->getDependencies());
        $this->assertEquals(array('*','*'), $comp->getJelixVersion());

        $str = '<?xml version="1.0" encoding="UTF-8"?>
<module xmlns="http://jelix.org/ns/module/1.0">
    <dependencies>
        <jelix minversion="1.0" maxversion="1.1" />
    </dependencies>
</module>';

        $comp->readDependenciesFromString($str);
        $this->assertEquals(array(
            array(
                'type'=> 'module',
                'id' => 'jelix@jelix.org',
                'name' => 'jelix',
                'minversion' => '1.0',
                'maxversion' => '1.1',
                'version' => '>=1.0,<=1.1'
            )
            ), $comp->getDependencies());
        $this->assertEquals(array('1.0', '1.1'), $comp->getJelixVersion());


        $str = '<?xml version="1.0" encoding="UTF-8"?>
<module xmlns="http://jelix.org/ns/module/1.0">
    <dependencies>
        <jelix minversion="1.0" maxversion="1.1" />
        <module name="jauthdb" />
        <module name="jacl2db" id="jacl2db@jelix.org"  />
        <module name="jacldb"  id="jacldb@jelix.org"  minversion="1.0"/>
    </dependencies>
</module>';

        $comp->readDependenciesFromString($str);
        $this->assertEquals(array(
            array(
                'type'=> 'module',
                'id' => 'jelix@jelix.org',
                'name' => 'jelix',
                'minversion' => '1.0',
                'maxversion' => '1.1',
                'version' => '>=1.0,<=1.1'
            ),
            array(
                'type'=> 'module',
                'id' => '',
                'name' => 'jauthdb',
                'minversion' => '0',
                'maxversion' => '*',
                'version' => '*'
            ),
            array(
                'type'=> 'module',
                'id' => 'jacl2db@jelix.org',
                'name' => 'jacl2db',
                'minversion' => '0',
                'maxversion' => '*',
                'version' => '*'
            ),
            array(
                'type'=> 'module',
                'id' => 'jacldb@jelix.org',
                'name' => 'jacldb',
                'minversion' => '1.0',
                'maxversion' => '*',
                'version' => '>=1.0'
            ),
            ), $comp->getDependencies());
        $this->assertEquals(array('1.0', '1.1'), $comp->getJelixVersion());
    }


    function testGetInstallerWithNoInstaller() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $ini = new testInstallerIniFileModifier("test.ini.php");

            // testinstall1 has no install.php file
            $component = new jInstallerComponentModule('testinstall1', jApp::appPath().'modules/testinstall1/', null);
            $component->init();
            $conf =(object) array( 'modules'=>array(
               'testinstall1.access'=>2, 
               'testinstall1.dbprofile'=>'default', 
               'testinstall1.installed'=>false, 
               'testinstall1.version'=>JELIX_VERSION,
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup,
                                                   $ini, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall1', $conf->modules));

            $installer = $component->getInstaller($EPindex, true);
            $this->assertNull($installer);
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
        
    }

    function testGetInstallerWithInstaller() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier('index/config.ini.php');
            $iniFoo = new testInstallerIniFileModifier('foo/config.ini.php');

            // testinstall2 has an install.php file
            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2,
               'testinstall2.dbprofile'=>'default',
               'testinstall2.installed'=>false,
               'testinstall2.version'=>JELIX_VERSION,
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules));

            $EPfoo = new testInstallerEntryPoint($this->globalSetup, $iniFoo, 'foo.php', 'classic', $conf);
            $component->addModuleInfos($EPfoo->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules));

            $installer = $component->getInstaller($EPindex, true);
            $this->assertTrue (is_object($installer));

            $installer = $component->getInstaller($EPfoo, true);
            $this->assertTrue (is_object($installer));

        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }


    function testGetUpgradersWithNoUpgraders() {
        try {

            // dummy ini file modifier. not used by installer of tested modules
            $ini = new testInstallerIniFileModifier("index/config.ini.php");

            // testinstall1 has no upgrade scripts
            $component = new jInstallerComponentModule('testinstall1', jApp::appPath().'modules/testinstall1/', $this->globalSetup);
            $component->init();
            $conf =(object) array( 'modules'=>array(
               'testinstall1.access'=>2, 
               'testinstall1.dbprofile'=>'default', 
               'testinstall1.installed'=>false, 
               'testinstall1.version'=>JELIX_VERSION,
            ));
            $EPindex = new testInstallerEntryPoint($this->globalSetup, $ini, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall1', $conf->modules) );

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue(is_array($upgraders));
            $this->assertEquals(0, count($upgraders));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }

    function testGetUpgradersWithNoValidUpgrader() {

        try {
            // dummy ini file modifier. not used by installer of tested modules
            $ini = new testInstallerIniFileModifier("index/config.ini.php");

            //------------ testinstall2 has some upgraders file
            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            // the current version is the latest one : no updaters
            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2, 
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>JELIX_VERSION, 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $ini, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(0, count($upgraders));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }

    function testGetUpgradersWithOneValidUpgrader() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier("index/config.ini.php");
            $iniFoo = new testInstallerIniFileModifier("foo/config.ini.php");

            // the current version is the previous one : one updater
            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2, 
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>"1.2.3", 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );


            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(1, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));

            $EPfoo = new testInstallerEntryPoint($this->globalSetup, $iniFoo, 'foo.php', 'classic', $conf);
            $component->addModuleInfos($EPfoo->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPfoo);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(1, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }


    function testGetUpgradersWithTwoValidUpgrader() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier("index/config.ini.php");
            $iniFoo = new testInstallerIniFileModifier("foo/config.ini.php");

            // the current version is the previous one : one updater
            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2, 
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>"1.1.2", 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            // since newupgraderfilename targets '1.1.2' and '1.2.4', we should have second then newupgraderfilename
            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(3, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[2]));

            $EPfoo = new testInstallerEntryPoint($this->globalSetup, $iniFoo, 'foo.php', 'classic', $conf);
            $component->addModuleInfos($EPfoo->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPfoo);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(3, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[2]));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }

    function testGetUpgradersWithTwoValidUpgrader2() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier("index/config.ini.php");
            $iniFoo = new testInstallerIniFileModifier("foo/config.ini.php");

            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2, 
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>"1.1.1", 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            // since newupgraderfilename targets '1.1.2' and '1.2.4', we should have newupgraderfilename then second

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(3, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[2]));

            $EPfoo = new testInstallerEntryPoint($this->globalSetup, $iniFoo, 'foo.php', 'classic', $conf);
            $component->addModuleInfos($EPfoo->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPfoo);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(3, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[2]));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }

    function testGetUpgradersWithTwoValidUpgraderWithDate() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier("index/config.ini.php");
            $iniFoo = new testInstallerIniFileModifier("foo/config.ini.php");

            file_put_contents(jApp::tempPath('dummyInstaller.ini'), '');
            $installerIni = new \Jelix\IniFile\IniModifier(jApp::tempPath('dummyInstaller.ini'));
            $this->globalSetup->setInstallerIni($installerIni);

            $component = new testInstallerComponentModule2('testinstall2', jApp::appPath('modules/testinstall2/'), $this->globalSetup);
            $component->init();

            // 1.1  1.1.2* 1.1.3** 1.1.5 1.2.2** 1.2.4*

            $installerIni->setValue('testinstall2.firstversion', '1.1' , 'index');
            $installerIni->setValue('testinstall2.firstversion.date', '2011-01-10' , 'index');
            $installerIni->setValue('testinstall2.version', '1.1.2' , 'index');
            $installerIni->setValue('testinstall2.version.date', '2011-01-12' , 'index');
            $component->setSourceVersionDate('1.1.5','2011-01-15');
            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2, 
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>"1.1", 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(3, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[2]));

            $installerIni->setValue('testinstall2.firstversion', '1.1.3' , 'index');
            $installerIni->setValue('testinstall2.firstversion.date', '2011-01-13' , 'index');
            $installerIni->setValue('testinstall2.version', '1.1.5' , 'index');
            $installerIni->setValue('testinstall2.version.date', '2011-01-15' , 'index');
            $component->setSourceVersionDate('1.2.5','2011-01-25');
            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2,
               'testinstall2.dbprofile'=>'default', 
               'testinstall2.installed'=>false, 
               'testinstall2.version'=>"1.1.5", 
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(1, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[0]));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }

    function testGetUpgradersWithAllUpgraders() {
        try {
            // dummy ini file modifier. not used by installer of tested modules
            $iniIndex = new testInstallerIniFileModifier("index/config.ini.php");
            $iniFoo = new testInstallerIniFileModifier("foo/config.ini.php");

            // the current version is a very old one : all updaters
            $component = new jInstallerComponentModule('testinstall2', jApp::appPath().'modules/testinstall2/', $this->globalSetup);
            $component->init();

            $conf =(object) array( 'modules'=>array(
               'testinstall2.access'=>2,
               'testinstall2.dbprofile'=>'default',
               'testinstall2.installed'=>false,
               'testinstall2.version'=>"0.9",
            ));

            $EPindex = new testInstallerEntryPoint($this->globalSetup, $iniIndex, 'index.php', 'classic', $conf);
            $component->addModuleInfos($EPindex->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPindex);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(4, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_first', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[2]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[3]));

            $EPfoo = new testInstallerEntryPoint($this->globalSetup, $iniFoo, 'foo.php', 'classic', $conf);
            $component->addModuleInfos($EPfoo->getEpId(), new jInstallerModuleInfos('testinstall2', $conf->modules) );

            $upgraders = $component->getUpgraders($EPfoo);
            $this->assertTrue (is_array($upgraders));
            $this->assertEquals(4, count($upgraders));
            $this->assertEquals('testinstall2ModuleUpgrader_first', get_class($upgraders[0]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilename', get_class($upgraders[1]));
            $this->assertEquals('testinstall2ModuleUpgrader_newupgraderfilenamedate', get_class($upgraders[2]));
            $this->assertEquals('testinstall2ModuleUpgrader_second', get_class($upgraders[3]));
        }
        catch(jInstallerException $e) {
            $this->fail("Unexpected exception : ".$e->getMessage()." (".var_export($e->getLocaleParameters(),true).")");
        }
    }
}

