<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Денис Орлов http://denisorlovmusic.ru/
 * Date: 01.10.16
 * Time: 22:40
 * 
 * Generate class implementation from array, for using instead array
 * examples: 
 * 	$arr = $_SERVER; // for example
 * 	echo '<textarea style="width: 99%;height: 30em;" >'.ViceArray::generateClass($arr).'</textarea>';
 */
abstract class ViceArray{
    protected $_arr = array();
    const GET = 'get';
    const SET = 'set';

    function __construct(array $arr = null, $notExistsMethodException = false){
        if(!empty($arr)){
            foreach($arr as $k => $v){
                $funcKey = static::_defineFuncKey($k);
                $getFunc = self::GET.$funcKey;
                $setFunc = self::SET.$funcKey;
                $existsGet = method_exists($this, $getFunc);
                $existsSet = method_exists($this, $setFunc);
                $tmpl = 'Methods "%1$s" and "%2$s" aren\'t exist in '.get_called_class().' for array key "'.$k.'"';
                if($notExistsMethodException && !$existsGet && !$existsSet){
                    throw new Exception( sprintf($tmpl, $getFunc, $setFunc) ) ;
                }
                if($existsGet || $existsSet){
                    $this->_arr[strtolower($k)] = $v;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function toArray(){
        return $this->_arr;
    }

    protected final function _getProxy($getFuncName){
        $k = $this::_defineKey(str_replace(self::GET, '', $getFuncName));
        return isset($this->_arr[$k]) ? $this->_arr[$k] : null;
    }
    protected final function _setProxy($setFuncName, $v){
        $k = $this::_defineKey(str_replace(self::SET, '', $setFuncName));
        return $this->_arr[$k] = $v;
    }

    protected function _defineKey($funcKey){
        return self::camelCaseToUnderscore($funcKey);
    }
    protected static function _defineFuncKey($key){
        return self::underscoreToCamelCase($key, true);
    }

    public static function camelCaseToUnderscore($string){
        $string = preg_replace('/([a-z])([A-Z\d])/',"$1_$2", $string);
        return strtolower($string);
    }
    /**
     * Convert strings with underscores into CamelCase
     *
     * @param    string    $string    The string to convert
     * @param    bool    $firstToUpper    camelCase or CamelCase
     * @return    string    The converted string
     *
     */
    public static function underscoreToCamelCase( $string, $firstToUpper = false){
        $string=strtolower($string.'');
        if($firstToUpper){
            $string[0] = strtoupper($string[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z\d])/', $func, $string);
    }

    public static function generateClass($array, $useValuesAsKeys = false, $genSetters = true,
                                         $tab = "\t", $useValuesAsTypes = false){
        $result = 'class _YOUR_CLASS_NAME_ extends ViceArray{'. PHP_EOL;
        $tmpl = <<<'EOD'
%3$s/**
%3$s* get %5$s
%3$s* @return %4$s
%3$s*/
%3$spublic function %1$s(){
%3$s%3$sreturn $this->_getProxy(__FUNCTION__);
%3$s}
EOD;
        $sTmpl = <<<'EOD'
%3$s/**
%3$s* set %5$s
%3$s* @param %4$s $v
%3$s*/
%3$spublic function %2$s($v){
%3$s%3$sreturn $this->_setProxy(__FUNCTION__, $v);
%3$s}
EOD;
        if($genSetters){
            $tmpl.= PHP_EOL.$sTmpl;
        }
        $cnst = '';
        $consts = '';
        $methods = '';
        $arrKeys = '';
        foreach($array as $k => $v){
            $k = $useValuesAsKeys? $v : $k;
            $type = '';
            if(!$useValuesAsKeys){
                $type = $useValuesAsTypes? $v : (gettype($v)!= "NULL" ? gettype($v) : '');
            };
            if($type == 'object') $type = get_class($v);
            $arrKeys.= PHP_EOL. '%3$s* '.$k.'%3$s'.$type;
            $cnst = 'k_'.strtoupper($k);
            $consts.= '%3$sconst '.$cnst." = '".$k."';". PHP_EOL;

            $funcKey = static::_defineFuncKey($k);
            $getFunc = self::GET.$funcKey;
            $setFunc = self::SET.$funcKey;
            //                       %1$s      %2$s      %3$s  %4$s  %5$s
            $methods.= sprintf($tmpl, $getFunc, $setFunc, $tab, $type, $k). PHP_EOL . PHP_EOL;
        }
        $toArrTmpl = <<<'EOD'
%3$s/**
%3$s* keys:
%3$s* &lt;pre>%1$s
%3$s* &lt;/pre>
%3$s* to get keys, use constants "k_..." of this class: $obj::%2$s
%3$s* @return array
%3$s*/
%3$spublic function toArray(){
%3$s%3$sreturn parent::toArray();
%3$s}
EOD;

        $result.= sprintf($consts, '', '', $tab). PHP_EOL.
            sprintf($toArrTmpl, sprintf($arrKeys, '', '', $tab), $cnst, $tab). PHP_EOL . PHP_EOL.
            $methods;
        return $result.='}';
    }
}