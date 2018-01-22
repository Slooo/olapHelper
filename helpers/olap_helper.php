<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * Хелпер для работы с olap
 *
 * @author     Robert Slooo
 * @mail       borisworking@gmail.com
 * @copyright LTD "EMSIS"
 * @version   2.4
 * @date 2018
 */

class olapParser {
    public $result;

    function __construct($result = array())
    {  
        $this->result = (array)$result;
        $this->dataColumn = array();
        sort($this->result);
    }

    /**
     * Парсер
     * @return array
     */
    private function axis()
    {
        $result = $this->result;
        $axis = array(); $data = array(); $properties = array();

        // определяем тип запроса
        if(array_key_exists('Axis0', $result[1]))
        {
            $axis['Temp_indexColumn'] = $result[2]['Axis1'];
            $axis['Temp_nameColumn'] = $result[2]['Axis0'];
            $axis['Temp_nameColumnValue'] = $result[3];
        } else {
            // для запросов с пустыми значениями и одностроковых
            if(array_key_exists('Axis0', $result[2])) {
                if(array_key_exists('Axis1', $result[3])) {
                    $axis['Temp_indexColumn'] = $result[3]['Axis1'];
                }

                if(array_key_exists('Axis0', $result[3])) {
                    $axis['Temp_nameColumn'] = $result[3]['Axis0'];
                } else {
                    $axis['Temp_nameColumn'] = $result[2]['Axis0'];
                }

                if(count($result[1]) > 0) {
                    $axis['Temp_nameColumnValue'] = $result[1];
                }
            } else {
                // числовые значения (ИМЕНОВАННЫЙ СТОЛБЕЦ)
                if(array_key_exists('Axis1', $result[3])) {
                    $axis['Temp_indexColumn'] = $result[3]['Axis1'];
                }
                // только измерения
                if(array_key_exists('Axis0', $result[3])) {
                    $axis['Temp_nameColumn'] = $result[3]['Axis0'];                
                }
                // значения измерений
                if(count($result[1]) > 0) {
                   $axis['Temp_nameColumnValue'] = $result[1];                
                }
            }
        }

        // Если измерения и числовое значение
        if(isset($axis['Temp_nameColumn']) && isset($axis['Temp_nameColumnValue']) && isset($axis['Temp_indexColumn'])) {
            $axis['Temp_nameColumnValue'] = array_chunk($axis['Temp_nameColumnValue'], count($axis['Temp_nameColumn']));

            // 0. Присваеваем id TODO
            foreach($axis['Temp_indexColumn'] as $k => $v) {
                foreach($v as $ke => $va) {
                    $temp = (array) $va;

                    // поиск id у элемента
                    preg_match_all("/\[([^\]]*)\]/", array_shift($temp), $elements);
                    // вытаскиваем id
                    $id = array_pop($elements[1]);

                    // TODO пока только для нормальных запросов
                    if(array_key_exists(1, $elements[1])) {
                        // имя столбца - первые два уровня
                        $column = $elements[1][0].'|'.$elements[1][1].'|'.$id;//implode('|', $elements[1]);

                        $x = $this->hasNumber(array_shift($temp));
                        $properties[$k][$column] = $x;
                    }
                }
            }

            // 1. Выбираем неименованные столбцы
            array_walk($axis['Temp_indexColumn'], function(&$v){
                $v = array_map(function(&$va){
                    $temp = (array) $va;
                    $x = $this->hasNumber(next($temp));
                    return $x;
                },$v);
            });

            // 2. Выбираем именованные столбцы
            array_walk($axis['Temp_nameColumn'], function(&$v){
                $x = array_map(function(&$va){
                    $temp = (array) $va;
                    return next($temp);
                },$v);
                $v = implode("|", $x);
            });

            // 3. Выбираем значения именованных столбцов
            array_walk($axis['Temp_nameColumnValue'], function(&$v){
                $v = array_map(function(&$va){
                    $temp = (array) $va;
                    return next($temp);
                },$v);
            });


            // 3. Готовим финальный массив, 1-ый уровень строки, 2-ой имена столбцов
            foreach($axis['Temp_nameColumnValue'] as $k => $v) {
                foreach($v as $ke => $va) {
                    $x = $this->hasNumber(array_shift($v));
                    $axis['Temp_indexColumn'][$k][$axis['Temp_nameColumn'][$ke]] = $x;
                    $properties[$k][$axis['Temp_nameColumn'][$ke]] = $x;
                }
            }

            $data['result'] = $axis['Temp_indexColumn'];
            $data['properties'] = $properties;
        } else if(isset($axis['Temp_nameColumn']) && isset($axis['Temp_nameColumnValue']) && !isset($axis['Temp_indexColumn'])) {
            // Если только именованные столбцы

            // 1. Выбираем именованные столбцы
            array_walk($axis['Temp_nameColumn'], function(&$v){
                $x = array_map(function(&$va){
                    $temp = (array) $va;
                    return next($va);
                },$v);
                $v = implode("|", $x);
            });

            // 2. Выбираем значения именованных столбцов
            array_walk($axis['Temp_nameColumnValue'], function(&$v){
                if(!is_array($v)) {
                    $v = array((array) $v);
                }

                $v = array_map(function(&$va){
                    $temp = (array) $va;
                    return next($temp);
                },$v);
            });

            // 3. Готовим финальный массив, 1-ый уровень строки, 2-ой имена столбцов
            foreach($axis['Temp_nameColumnValue'] as $k => $v) {
                foreach($v as $ke => $va) {
                    $x = $this->hasNumber(array_shift($v));
                    $data['result'][$k][$axis['Temp_nameColumn'][$ke]] = $x;
                }
            }

        } else if(!isset($axis['Temp_nameColumn']) && !isset($axis['Temp_nameColumnValue']) && isset($axis['Temp_indexColumn'])) {
            // Если только неименованные столбцы

            // 1. Выбираем неименованные столбцы
            array_walk($axis['Temp_indexColumn'], function(&$v){
                $v = array_map(function(&$va){
                    $temp = (array) $va;
                    $x = $this->hasNumber(next($temp));
                    return $x;
                },$v);
            });

            $data['result'] = $axis['Temp_indexColumn'];
        } else if(isset($axis['Temp_nameColumn']) && !isset($axis['Temp_nameColumnValue']) && !isset($axis['Temp_indexColumn'])) {
            $data['result'] = false;
        } else {
            $data['result'] = false;
        }

        return $data;
    }

    /**
     * Выбор данных по столбцу
     * @param  string $column Столбец
     * @return array
     *
     * TODO убрать
     */
    public function getDataColumn($column)
    {
        $temp = $this->getDataColumns();
        $data = array();

        if(array_key_exists($column, $temp)) {
            $data[$column] = $temp[$column];
        } else {
            $data['success'] = false;
            $data['result'] = 'Столбец '.$column.' не найден';
            echo "<pre>".print_r($data, 1)."</pre>";
            exit;
        }

        $this->dataColumn = $data;
        return $this;
    }

    /**
     * Вывод данных по столбцам
     * @param  boolean $column столбцы
     * @return array
     *
     * TODO добавить выборку столбцов
     */
    public function getDataColumns($column = false)
    {
    	$temp = $this->axis();
        if($temp['result']) {
            foreach($temp['result'] as $k => $v) {
                foreach($v as $ke => $va) {
                    $data[$ke][] = $va;
                }
            }
        } else {
            $data = $temp['result'];
        }

        return $data;
    }

    // Выбираем данные построчно
    public function getDataRows($properties = false)
    {
        // для уникальных столбцов
        if($properties === 'unique') {
            $temp = array();

            foreach($this->axis()['result'] as $k => $v) {
                foreach($v as $ke => $va) {
                    if(strpos($ke, '|') !== false) {
                        $exp = explode('|', $ke);

                        // TODO только для числовых
                        $exp = array_filter($exp, function(&$v) {
                            if(is_numeric($v)) return $v;
                        });

                        // TODO условие
                        if(count($exp) <= 2) {
                            if($exp[0] === $exp[1]) {
                                $x = array_shift($exp);
                                $temp[$k][$x] = $va;
                            }

                        } else {
                            if($exp[0] === $exp[1] && $exp[1] === $exp[2]) {
                                $x = array_shift($exp);
                                $temp[$k][$x] = $va;  
                            }
                        }
                    } else if(strpos($ke, '|') === false){
                        $temp[$k][$ke] = $va;
                    }
                }
            }

            return $temp;
        } else {
            return $properties ? $this->axis()['properties'] : $this->axis()['result'];            
        }
    }

}

class olapHelper extends olapParser {

    function __construct($result = array()){
         parent::__construct($result);
    }

    // преобразование в число
    protected function toInt()
    {
        $data = array();
        $temp = $this->dataColumn;
        $y = array_keys($temp);
        $name = $y[0];

        return (is_array($temp))
            ? array($name => array_map('intval', $temp[$name])) 
            : false;
    }

    // преобразует в число с плавающей точкой
    protected function toFloat($number = false)
    {
        $data = array();
        $temp = $this->dataColumn;
        $y = array_keys($temp);
        $name = $y[0];

        $data[$name] = array_map(function($array) use($number){
            $array = strtr($array, array(',' => '.'));
            return !$number ? (float) $array : number_format($array, $number);
        }, $temp[$name]);

        return $data;
    }

    // округляет число
    protected function toRound()
    {
        $data = array();
        $temp = $this->dataColumn;
        $y = array_keys($temp);
        $name = $y[0];

        $data[$name] = array_map('round', $temp[$name]);
        return $data;
    }

    // округляет дровь в большую сторону
    protected function toCeil()
    {
        $data = array();
        $temp = $this->dataColumn;
        $y = array_keys($temp);
        $name = $y[0];

        $data[$name] = array_map('ceil', $temp[$name]);
        return $data;
    }

    // преобразование в число
    public function toNumber($type = false, $number = false)
    {
        $data = array();

        switch ($type) {
            case 'float':
                $data = $this->toFloat($number);
                break;

            case 'round':
                $data = $this->toRound();
                break;

            case 'ceil':
                $data = $this->toCeil();
                break;

            case 'int':
                $data = $this->toInt();
                break;
            
            default:
                $data = $this->toInt();
                break;
        }

        return $data;
    }

    // преобразование в строку
    public function toString()
    {
        return $this->dataColumn;
    }

    protected function hasNumber($value) {
        $search = strpos($value, ',');
        if($search !== false) {
            $temp = str_replace(',', '.', $value);
            return is_numeric($temp) ? (float) $temp : $value;
        } else if(is_integer($value)) {
            return (int) $value;
        } else {
            if(is_numeric($value)) {
                // число начинается с нуля
                if($value[0] === '0' && strlen($value) > 1) {
                    return $value;
                } else {
                    return (int) $value;
                }
            } else {
                return $value;
            }
        }
    }

}