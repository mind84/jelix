<?php
/**
* @package     jelix
* @subpackage  forms
* @author      Laurent Jouanneau
* @contributor
* @copyright   2006-2007 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 * Interface for objects which provides a source of data to fill some controls in a form,
 * like menulist, listbox etc...
 * @package     jelix
 * @subpackage  forms
 */
interface jIFormsDatasource {
    /**
     * load and returns data to fill a control. The returned array should be 
     * an associative array  key => label
     * @param jFormsBase $form  the form
     * @return array the data
     */
    public function getData($form);

    /**
     * Return the label corresponding to the given key
     * @param string $key the key 
     * @return string the label
     */
    public function getLabel($key);
}


/**
 * old interface which have been renamed to jIFormsDatasource.
 * use jIFormsDatasource instead
 * @package     jelix
 * @subpackage  forms
 * @deprecated since 1.1
 */
interface jIFormDatasource extends jIFormsDatasource {
}

/**
 * A datasource which is based on static values.
 * @package     jelix
 * @subpackage  forms
 */
class jFormsStaticDatasource implements jIFormsDatasource {
    /**
     * associative array which contains keys and labels
     * @var array
     */
    public $data = array();

    public function getData($form){
        return $this->data;
    }

    public function getLabel($key){
        if(isset($this->data[$key]))
            return $this->data[$key];
        else
            return null;
    }
}


/**
 * A datasource which is based on a dao
 * @package     jelix
 * @subpackage  forms
 */
class jFormsDaoDatasource implements jIFormsDatasource {

    protected $selector;
    protected $method;
    protected $labelProperty;
    protected $keyProperty;

    protected $criteria;
    protected $criteriaForm;

    protected $dao = null;

    function __construct ($selector ,$method , $label, $key, $criteria=null, $criteriaFrom=null){
        $this->selector  = $selector;
        $this->method = $method ;
        $this->labelProperty = $label;
        $this->criteria = $criteria;
        $this->criteriaFrom = $criteriaFrom;
        if($key == ''){
            $rec = jDao::createRecord($this->selector);
            $pfields = $rec->getPrimaryKeyNames();
            $key = $pfields[0];
        }
        $this->keyProperty = $key;
    }

    public function getData($form){
        if($this->dao === null)
            $this->dao = jDao::get($this->selector);
        if($this->criteria !== null) {
            $found = $this->dao->{$this->method}($this->criteria);
        } else if ($this->criteriaFrom !== null) {
            $found = $this->dao->{$this->method}($form->getData($this->criteriaFrom));
        } else {
            $found = $this->dao->{$this->method}();
        }
        $result=array();
        foreach($found as $obj){
            $result[$obj->{$this->keyProperty}] = $obj->{$this->labelProperty};
        }
        return $result;
    }

    public function getLabel($key){
        if($this->dao === null) $this->dao = jDao::get($this->selector);
        $rec = $this->dao->get($key);
        if($rec)
            return $rec->{$this->labelProperty};
        else
            return null;
    }

}

