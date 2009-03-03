<?php
/**
* see jISelector.iface.php for documentation about selectors. 
* @package     jelix
* @subpackage  core_selector
* @author      Laurent Jouanneau
* @contributor Thibault PIRONT < nuKs >
* @copyright   2005-2009 Laurent Jouanneau
* @copyright   2007 Thibault PIRONT
* @link        http://www.jelix.org
* @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * Generic Action selector
 *
 * main syntax: "module~action@requestType". module should be a valid module name or # (#=says to get
 * the module of the current request). action should be an action name (controller:method or controller_method).
 * all part are optional, but it should have one part at least.
 * @package    jelix
 * @subpackage core_selector
 */
class jSelectorAct extends jSelectorActFast {

    /**
     * @param string $sel  the selector
     * @param boolean $enableRequestPart true if the selector can contain the request part
     */
    function __construct($sel, $enableRequestPart = false){
        global $gJCoord;

#if ENABLE_PHP_JELIX
        if(jelix_scan_action_sel($sel, $this, $gJCoord->actionName)){
            if($this->module == '#'){
                $this->module = $gJCoord->moduleName;
            }elseif($this->module ==''){
                $this->module = jContext::get ();
            }

            if($this->request == '')
                $this->request = $gJCoord->request->type;

#else
        if(preg_match("/^(?:([a-zA-Z0-9_\.]+|\#)~)?([a-zA-Z0-9_:]+|\#)?(?:@([a-zA-Z0-9_]+))?$/", $sel, $m)){
            $m=array_pad($m,4,'');
            if($m[1]!=''){
                if($m[1] == '#')
                    $this->module = $gJCoord->moduleName;
                else
                    $this->module = $m[1];
            }else{
                $this->module = jContext::get ();
            }
            if($m[2] == '#')
                $this->resource = $gJCoord->actionName;
            else
                $this->resource = $m[2];
            $r = explode(':',$this->resource);
            if(count($r) == 1){
                $this->controller = 'default';
                $this->method = $r[0]==''?'index':$r[0];
            }else{
                $this->controller = $r[0]=='' ? 'default':$r[0];
                $this->method = $r[1]==''?'index':$r[1];
            }
            $this->resource = $this->controller.':'.$this->method;

            if($m[3] != '' && $enableRequestPart)
                $this->request = $m[3];
            else
                $this->request = $gJCoord->request->type;
#endif
            $this->_createPath();
        }else{
            throw new jExceptionSelector('jelix~errors.selector.invalid.syntax', array($sel,$this->type));
        }
    }
}
