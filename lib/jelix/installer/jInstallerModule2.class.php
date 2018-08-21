<?php
/**
* @package     jelix
* @subpackage  installer
* @author      Laurent Jouanneau
* @copyright   2008-2017 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * A class that does processing to configure and install a module into
 * an application. A module should have a class that inherits from it
 * in order to configure itself into the application.
 *
 * @package     jelix
 * @subpackage  installer
 * @since 1.7
 */
class jInstallerModule2 implements jIInstallerComponent2 {

    /**
     * @inheritdoc
     */
    function preInstall() {

    }

    /**
     * @inheritdoc
     */
    function install() {

    }

    /**
     * @inheritdoc
     */
    function postInstall() {

    }

    /**
     * @inheritdoc
     */
    function preUninstall() {

    }

    /**
     * @inheritdoc
     */
    function uninstall() {

    }

    /**
     * @inheritdoc
     */
    function postUninstall() {

    }

    /**
     * @var string name of the component
     */
    protected $componentName;

    /**
     * @var string name of the installer
     */
    protected $name;

    /**
     * the versions for which the installer should be called.
     * Useful for an upgrade which target multiple branches of a project.
     * Put the version for multiple branches. The installer will be called
     * only once, for the needed version.
     * If you don't fill it, the name of the class file should contain the
     * target version (deprecated behavior though)
     * @var array $targetVersions list of version by asc order
     * @since 1.2.6
     */
    protected $targetVersions = array();

    /**
     * @var string the date of the release of the update. format: yyyy-mm-dd hh:ii
     * @since 1.2.6
     */
    protected $date = '';

    /**
     * @var string the version for which the installer is called
     */
    protected $version = '0';

    /**
     * global setup
     * @var jInstallerGlobalSetup
     */
    protected $globalSetup;

    /**
     * The path of the module
     * @var string
     */
    protected $path;

    /**
     * @var string the jDb profile for the component
     */
    protected $dbProfile = '';

    /**
     * @var string the default profile name for the component, if it exist. keep it to '' if not
     */
    protected $defaultDbProfile = '';

    /**
     * parameters for the installer, indicated in the configuration file or
     * dynamically, by a launcher in a command line for instance.
     * @var array
     */
    protected $parameters = array();

    /**
     * @var jDbConnection
     */
    private $_dbConn = null;

    /**
     * @param string $componentName name of the component
     * @param string $name name of the installer
     * @param string $path the component path
     * @param string $version version of the component
     * @param boolean $installWholeApp deprecated
     */
    function __construct ($componentName, $name, $path, $version, $installWholeApp = false) {
        $this->path = $path;
        $this->version = $version;
        $this->name = $name;
        $this->componentName = $componentName;
    }

    function getName() {
        return $this->name;
    }


    function getTargetVersions() {
        return $this->targetVersions;
    }

    function setTargetVersions($versions) {
        $this->targetVersions = $versions;
    }

    function getDate() {
        return $this->date;
    }

    function getVersion() {
        return $this->version;
    }

    function setVersion($version) {
        $this->version = $version;
    }

    function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    function getParameter($name) {
        if (isset($this->parameters[$name]))
            return $this->parameters[$name];
        else
            return null;
    }

    function setGlobalSetup(jInstallerGlobalSetup $setup) {
        $this->globalSetup = $setup;
    }

    /**
     * default config and main config combined
     * @return \Jelix\IniFile\IniModifierArray
     * @since 1.7
     */
    public function getConfigIni() {
        return $this->globalSetup->getConfigIni();
    }

    /**
     * the localconfig.ini.php file combined with main and default config
     * @return \Jelix\IniFile\IniModifierArray
     * @since 1.7
     */
    public function getLocalConfigIni() {
        return $this->globalSetup->getLocalConfigIni();
    }

    /**
     * the liveconfig.ini.php file combined with localconfig, main and default config
     * @return \Jelix\IniFile\IniModifierArray
     * @since 1.7
     */
    public function getLiveConfigIni() {
        return $this->globalSetup->getLiveConfigIni();
    }

    /**
     * Point d'entrée principal de l'application (en général index.php)
     * @return jInstallerEntryPoint2
     */
    public function getMainEntryPoint() {
        return $this->globalSetup->getMainEntryPoint();
    }

    /**
     * List of entry points of the application
     *
     * @return jInstallerEntryPoint2[]
     */
    public function getEntryPointsList() {
        return $this->globalSetup->getEntryPointsList();
    }

    /**
     * @param $epId
     * @return jInstallerEntryPoint2
     */
    protected function getEntryPointsById($epId) {
        return $this->globalSetup->getEntryPointById($epId);
    }

    /**
     * internal use
     * @param string $dbProfile the name of the current jdb profile. It will be replaced by $defaultDbProfile if it exists
     */
    public function initDbProfile($dbProfile) {
        if ($this->defaultDbProfile != '') {
            $this->useDbProfile($this->defaultDbProfile);
        }
        else
            $this->useDbProfile($dbProfile);
    }

    /**
     * use the given database profile. check if this is an alias and use the
     * real db profile if this is the case.
     * @param string $dbProfile the profile name
     */
    protected function useDbProfile($dbProfile) {

        if ($dbProfile == '')
            $dbProfile = 'default';

        $this->dbProfile = $dbProfile;

        // we check if it is an alias
        $profilesIni = $this->globalSetup->getProfilesIni();
        $alias = $profilesIni->getValue($dbProfile, 'jdb');
        if ($alias) {
            $this->dbProfile = $alias;
        }

        $this->_dbConn = null; // we force to retrieve a db connection
    }

    /**
     * @return jDbTools  the tool class of jDb
     */
    protected function dbTool () {
        return $this->dbConnection()->tools();
    }

    /**
     * @return jDbConnection  the connection to the database used for the module
     */
    protected function dbConnection () {
        if (!$this->_dbConn)
            $this->_dbConn = jDb::getConnection($this->dbProfile);
        return $this->_dbConn;
    }

    /**
     * @param string $profile the db profile
     * @return string the name of the type of database
     */
    protected function getDbType($profile = null) {
        if (!$profile)
            $profile = $this->dbProfile;
        $conn = jDb::getConnection($profile);
        return $conn->dbms;
    }

    /**
     * import a sql script into the current profile.
     *
     * The name of the script should be store in install/$name.databasetype.sql
     * in the directory of the component. (replace databasetype by mysql, pgsql etc.)
     * You can however provide a script compatible with all databases, but then
     * you should indicate the full name of the script, with a .sql extension.
     *
     * @param string $name the name of the script
     * @param string $module the module from which we should take the sql file. null for the current module
     * @param boolean $inTransaction indicate if queries should be executed inside a transaction
     * @throws Exception
     */
    final protected function execSQLScript ($name, $module = null, $inTransaction = true)
    {
        $conn = $this->dbConnection();
        $tools = $this->dbTool();

        if ($module) {
            $conf = $this->globalSetup->getMainEntryPoint()->getConfigObj()->_modulesPathList;
            if (!isset($conf[$module])) {
                throw new Exception('execSQLScript : invalid module name');
            }
            $path = $conf[$module];
        }
        else {
            $path = $this->path;
        }

        $file = $path.'install/'.$name;
        if (substr($name, -4) != '.sql')
            $file .= '.'.$conn->dbms.'.sql';

        if ($inTransaction)
            $conn->beginTransaction();
        try {
            $tools->execSQLScript($file);
            if ($inTransaction) {
                $conn->commit();
            }
        }
        catch(Exception $e) {
            if ($inTransaction)
                $conn->rollback();
            throw $e;
        }
    }

    /**
     * Insert data into a database, from a json file, using a DAO mapping
     * @param string $relativeSourcePath name of the json file into the install directory
     * @param integer $option one of jDbTools::IBD_* const
     * @return integer number of records inserted/updated
     * @throws Exception
     * @since 1.6.16
     */
    final protected function insertDaoData($relativeSourcePath, $option, $module = null) {

        if ($module) {
            $conf = $this->globalSetup->getMainEntryPoint()->getModulesList();
            if (!isset($conf[$module])) {
                throw new Exception('insertDaoData : invalid module name');
            }
            $path = $conf[$module];
        }
        else {
            $path = $this->path;
        }

        $file = $path.'install/'.$relativeSourcePath;
        $dataToInsert = json_decode(file_get_contents($file), true);
        if (!$dataToInsert) {
            throw new Exception("Bad format for dao data file.");
        }
        if (is_object($dataToInsert)) {
            $dataToInsert = array($dataToInsert);
        }
        $daoMapper = new jDaoDbMapper($this->dbProfile);
        $count = 0;
        foreach($dataToInsert as $daoData) {
            if (!isset($daoData['dao']) ||
                !isset($daoData['properties']) ||
                !isset($daoData['data'])
            ) {
               throw new Exception("Bad format for dao data file.");
            }
            $count += $daoMapper->insertDaoData($daoData['dao'],
                $daoData['properties'], $daoData['data'], $option);
        }
        return $count;
    }


    /**
     * copy the whole content of a directory existing in the install/ directory
     * of the component, to the given directory
     * @param string $relativeSourcePath relative path to the install/ directory of the component
     * @param string $targetPath the full path where to copy the content
     */
    final protected function copyDirectoryContent($relativeSourcePath, $targetPath, $overwrite = false) {
        $targetPath = $this->expandPath($targetPath);
        $this->_copyDirectoryContent ($this->path.'install/'.$relativeSourcePath, $targetPath, $overwrite);
    }

    /**
     * private function which copy the content of a directory to an other
     *
     * @param string $sourcePath
     * @param string $targetPath
     */
    private function _copyDirectoryContent($sourcePath, $targetPath, $overwrite) {
        jFile::createDir($targetPath);
        $dir = new DirectoryIterator($sourcePath);
        foreach ($dir as $dirContent) {
            if ($dirContent->isFile()) {
                $p = $targetPath.substr($dirContent->getPathName(), strlen($dirContent->getPath()));
                if ($overwrite || !file_exists($p))
                    copy($dirContent->getPathName(), $p);
            } else {
                if (!$dirContent->isDot() && $dirContent->isDir()) {
                    $newTarget = $targetPath.substr($dirContent->getPathName(), strlen($dirContent->getPath()));
                    $this->_copyDirectoryContent($dirContent->getPathName(),$newTarget, $overwrite);
                }
            }
        }
    }


    /**
     * copy a file from the install/ directory to an other
     * @param string $relativeSourcePath relative path to the install/ directory of the file to copy
     * @param string $targetPath the full path where to copy the file
     */
    final protected function copyFile($relativeSourcePath, $targetPath, $overwrite = false) {
        $targetPath = $this->expandPath($targetPath);
        if (!$overwrite && file_exists($targetPath))
            return;
        $dir = dirname($targetPath);
        jFile::createDir($dir);
        copy ($this->path.'install/'.$relativeSourcePath, $targetPath);
    }

    protected function expandPath($path) {
        if (strpos($path, 'www:') === 0)
            $path = str_replace('www:', jApp::wwwPath(), $path);
        elseif (strpos($path, 'jelixwww:') === 0) {
            $p = $this->globalSetup->getConfigIni()->getValue('jelixWWWPath','urlengine');
            if (substr($p, -1) != '/') {
                $p .= '/';
            }
            $path = str_replace('jelixwww:', jApp::wwwPath($p), $path);
        }
        elseif (strpos($path, 'varconfig:') === 0) {
            $path = str_replace('varconfig:', jApp::varConfigPath(), $path);
        }
        elseif (strpos($path, 'appconfig:') === 0) {
            $path = str_replace('appconfig:', jApp::appConfigPath(), $path);
        }
        elseif (strpos($path, 'epconfig:') === 0) {
            throw new \Exception("'epconfig:' alias is no more supported in path");
        }
        elseif (strpos($path, 'config:') === 0) {
            throw new \Exception("'config:' alias is no more supported in path");
        }
        return $path;
    }

    /**
     * declare a new db profile. if the content of the section is not given,
     * it will declare an alias to the default profile
     * @param string $name  the name of the new section/alias
     * @param null|string|array  $sectionContent the content of the new section, or null
     *     to create an alias.
     * @param boolean $force true:erase the existing profile
     * @return boolean true if the ini file has been changed
     */
    protected function declareDbProfile($name, $sectionContent = null, $force = true ) {

        $profiles = $this->globalSetup->getProfilesIni();
        if ($sectionContent == null) {
            if (!$profiles->isSection('jdb:'.$name)) {
                // no section
                if ($profiles->getValue($name, 'jdb') && !$force) {
                    // already a name
                    return false;
                }
            }
            else if ($force) {
                // existing section, and no content provided : we erase the section
                // and add an alias
                $profiles->removeValue('', 'jdb:'.$name);
            }
            else {
                return false;
            }
            $default = $profiles->getValue('default', 'jdb');
            if($default) {
                $profiles->setValue($name, $default, 'jdb');
            }
            else // default is a section
                $profiles->setValue($name, 'default', 'jdb');
        }
        else {
            if ($profiles->getValue($name, 'jdb') !== null) {
                if (!$force)
                    return false;
                $profiles->removeValue($name, 'jdb');
            }
            if (is_array($sectionContent)) {
                foreach($sectionContent as $k=>$v) {
                    if ($force || !$profiles->getValue($k, 'jdb:'.$name)) {
                        $profiles->setValue($k,$v, 'jdb:'.$name);
                    }
                }
            }
            else {
                $profile = $profiles->getValue($sectionContent, 'jdb');
                if ($profile !== null) {
                    $profiles->setValue($name, $profile, 'jdb');
                }
                else
                    $profiles->setValue($name, $sectionContent, 'jdb');
            }
        }
        $profiles->save();
        jProfiles::clear();
        return true;
    }


    /**
     * return the section name of configuration of a plugin for the coordinator
     * or the IniModifier for the configuration file of the plugin if it exists.
     * @param \Jelix\IniFile\IniModifier $config  the global configuration content
     * @param string $pluginName
     * @return array|null null if plugin is unknown, else array($iniModifier, $section)
     * @throws Exception when the configuration filename is not found
     */
    public function getCoordPluginConf(\Jelix\IniFile\IniModifierInterface $config, $pluginName) {
        $conf = $config->getValue($pluginName, 'coordplugins');
        if (!$conf) {
            return null;
        }
        if ($conf == '1') {
            $pluginConf = $config->getValues($pluginName);
            if ($pluginConf) {
                return array($config, $pluginName);
            }
            else {
                // old section naming. deprecated
                $pluginConf = $config->getValues('coordplugin_' . $pluginName);
                if ($pluginConf) {
                    return array($config, 'coordplugin_' . $pluginName);
                }
            }
            return null;
        }
        // the configuration value is a filename
        $confpath = jApp::appConfigPath($conf);
        if (!file_exists($confpath)) {
            $confpath = jApp::varConfigPath($conf);
            if (!file_exists($confpath)) {
                return null;
            }
        }
        return array(new \Jelix\IniFile\IniModifier($confpath), 0);
    }

    /**
     * declare web assets into the main configuration
     * @param string $name the name of webassets
     * @param array $values should be an array with one or more of these keys 'css' (array), 'js'  (array), 'require' (string)
     * @param string $collection the name of the webassets collection
     * @param bool $force
     */
    public function declareGlobalWebAssets($name, array $values, $collection, $force)
    {
        $config = $this->globalSetup->getConfigIni();
        $this->globalSetup->declareWebAssetsInConfig($config['main'], $name, $values, $collection, $force);
    }
}

