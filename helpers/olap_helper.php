<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * Хелпер для работы с olap
 *
 * @author     Robert Slooo
 * @mail       borisworking@gmail.com
 * @copyright LTD "EMSIS"
 * @date 2017
 */

class olapParser {
    public $result;
    public $sort = array();
    public $config = array();
    public $limit = array();

    function __construct($result = array())
    {
       $this->result = (array)$result;
       sort($this->result);
    }

    // количество строк
    public function getRowsCount()
    {
        $axis = $this->hereColumn();
        $rows = count($axis['indexColumn']);
        return $rows;
    }

    // проверка столбца
    protected function checkColumn($column)
    {
        $columns = $this->getColumns();
        $columns = array_flip($columns);

        if(!array_key_exists($column, $columns))
        {
            exit('Столбец '.$column.' не найден');
        }
    }

    // определяем откуда брать индексные столбцы
    private function hereColumn()
    {
        $result = $this->result;
        $axis = array();

        if(array_key_exists('Axis0', $result[1]))
        {
            unset($result[0], $result[1], $result[2]['SlicerAxis']);
            $axis['Temp_indexColumn'] = $result[2]['Axis1'];
            $axis['Temp_nameColumn'] = $result[2]['Axis0'];
            $axis['Temp_nameColumnValue'] = $result[3];
        } else {
            unset($result[0], $result[2], $result[3]['SlicerAxis']);
            $axis['Temp_indexColumn'] = $result[3]['Axis1'];
            $axis['Temp_nameColumn'] = $result[3]['Axis0'];
            $axis['Temp_nameColumnValue'] = $result[1];
        }

        if(!is_array($axis['Temp_nameColumn'][0]))
        {
            $x = $axis['Temp_nameColumn'][0];
            $axis['nameColumn'] = array(0 => array(0 => array_pad(array(), 5, $x)));
            $axis['nameColumnValue'] = array(0 => array(0 => array_pad(array(), 3, 0)));
        } else {
            $axis['nameColumn'] = $axis['Temp_nameColumn'];
            $axis['nameColumnValue'] = $axis['Temp_nameColumnValue'];
        }

        if(!is_array($axis['Temp_indexColumn'][0]))
        {
            foreach($axis['Temp_indexColumn'] as $k => $v)
            {
                $axis['indexColumn'][$k] = array(0 => array_pad(array(), 5, 0));                
            }
        } else {
            $axis['indexColumn'] = $axis['Temp_indexColumn'];
        }

        unset($axis['Temp_nameColumn']);
        unset($axis['Temp_nameColumnValue']);
        unset($axis['Temp_indexColumn']);

        return $axis;
    }

	// количество неименованных столбцов
    public function getColumnsIndexCount()
    {
        return count($this->getColumnsIndex());
    }

    // количество именованных столбцов
    public function getColumnsNameCount()
    {
        return count($this->getColumnsName());
    }

    // неименованные столбцы
    public function getColumnsIndex()
    {
        $axis = $this->hereColumn();
    	$temp = array();

        if(array_key_exists(0, $axis['indexColumn'][0]))
        {
            foreach($axis['indexColumn'][0] as $k => $v)
            {
                $temp[] = $k;
            }
        } else {
            foreach($axis['indexColumn'] as $k => $v)
            {
                $temp[] = $k;
            }
        }
       
		return $temp;
    }

    // наименование столбцов
    public function getColumnsName()
    {
        $axis = $this->hereColumn();
        $temp = array();

        foreach($axis['nameColumn'] as $k => $v)
        {
            $x = (array)$v[0];
            $y = array_values($x);
            $temp[$k] = $y[1];
        }

        return $temp;
    }

    // все столбцы
    public function getColumns()
    {
    	$temp = array_merge($this->getColumnsIndex(), $this->getColumnsName());
    	return $temp;
    }

    // количество всех столбцов
    public function getColumnsCount()
    {
        $count = $this->getColumnsIndexCount() + $this->getColumnsNameCount();
        return $count;
    }

    // данные по столбцу
    public function getDataColumn($column)
    {
        $this->checkColumn($column);

    	$temp = array();
        $data = array();
        $result = $this->hereColumn();

        // если столбец именованный
    	if(is_int($column) == false) 
    	{
            /* если количество именованных столбцов 0,
                то выбираем количество всех строк 
            */
            $count = $this->getColumnsNameCount();
            if($count == 0)
            {
                $count = $this->getRowsCount();                
            }

            // имена столбцов
            $columns = $this->getColumnsName();

            // преобразуем внутри массива объекты в массив
            foreach($result['nameColumnValue'] as $k => $v)
            {
                $x = (array)$v;
                $y = array_values($x);
                $data[$k] = $y[0];
            }

            // дробим массив по строкам
    		$index = array_chunk($data, $count, false);

	        // сравниваем наименование столбцов
    		foreach($columns as $k => $row)
    		{
    			if($column == $row)
    			{
		    		foreach($index as $key => $val)
		    		{
                        $x = $val;
                        $x = array_values($x);
                        if(array_key_exists($k, $x)){
                            $temp[$column][] = $x[$k];
                        }
		    		}
    			}
    		}
    	}
        
        // если столбец по index (не именованный)
        else {

            foreach($result['indexColumn'] as $k => $v)
            {
                if(is_array($v))
                {
                    foreach($v as $ke => $va)
                    {
                        if($ke == $column)
                        {
                            $x = (array)$va;
                            $x = array_values($x);
                            $x = (array)$x;
                            $temp[$column][] = $x[1];
                        }
                    }
                } else {
                   $x = (array)$v;
                   $x = array_values($x);
                   $temp[$column][] = $x[1];
                }
    	    }
        }

        $this->dataColumn = $temp;
    	return $this;
    }

    // все данные по столбцам
    public function getData($columns = false)
    {
    	$data = array();
    	$temp = array();

        if(!is_array($columns))
        {
            $columns = $this->getColumns();
        }

    	foreach($columns as $k => $v)
    	{
    		$data[] = $this->getDataColumn($v)->toString();
    	}

        // из многомерного в одномерный
        foreach($data as $k => $v)
        {
            foreach($v as $ke => $va)
            {
                $temp[$ke] = $va;
            }
        }

        // сортировка
        if(count($this->sort) > 0)
        {
            $temp = $this->toSort($temp);            
        }

        // лимит
        if(count($this->limit) > 0)
        {
            $temp = $this->toLimit($temp);            
        }

    	return $temp;
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
    protected function toFloat($number)
    {
        $data = array();
        $temp = $this->dataColumn;
        $y = array_keys($temp);
        $name = $y[0];

        $data[$name] = array_map(function($array) use($number){
            $array = strtr($array, array(',' => '.'));
            return number_format($array, $number);
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
        $temp = $this->dataColumn;
        return $temp;
    }

    // лимит
    protected function toLimit($temp)
    {
        $data = array();

        if(is_array($this->limit))
        {
            $offset = $this->limit[0];
            $limit = $this->limit[1];
        } else {
            $offset = 0;
            $limit = $this->limit;
        }

        if($limit > 0)
        {
            foreach($temp as $k => $v)
            {
                $data[$k] = array_slice($v, $offset, $limit);        
            }            
        } else {
            $data = $temp;
        }

        return $data;
    }

    // сортировка
    protected function toSort($temp)
    {
        /* если столбец не указан, но по нему происходит сортировка
            ...
        */

        $data = array();

        $config = $this->config;
        $column = $this->sort['column'];
        $direction = $this->sort['direction'];

        // если массив то float
        if(is_array($config['type']))
        {
            $type = $config['type'][0];
            $number = $config['type'][1];
        } else {
            $type = $config['type'];
        }

        if($type == 'string')
        {
            $toParse = $this->getDataColumn($config['column'])
                            ->toString();
        } else {

            if(isset($number))
            {
                $toParse = $this->getDataColumn($config['column'])
                                ->toNumber($type, $number);
            } else {
                $toParse = $this->getDataColumn($config['column'])
                                ->toNumber($type);
            }
        }

        $y = array_keys($toParse);
        $toParseKey = $y[0];

        // сохраняем текущий массив
        $beforeTemp = $temp;

        $temp[$toParseKey] = $toParse[$toParseKey];     

        // по убыванию
        if($direction == 'desc')
        {
            arsort($temp[$column]);
        }

        else
        
        // по возрастанию
        if($direction == 'asc')
        {
            asort($temp[$column]);
        }

        // сортируем остальные столбцы
        foreach($temp as $key => $val)
        {
            # столбец задающий сортировку
            foreach($temp[$column] as $k => $v)
            {
                # сортируемые массивы
                foreach($temp[$key] as $ke => $ve)
                {
                    # сортировка по ключам
                    if($k == $ke)
                    {
                        $data[$key][$ke] = $ve;
                    }
                }
            }
        }

        // если сортируемый столбец не был указан при выводе
        if(!array_key_exists($toParseKey, $beforeTemp))
        {
            unset($data[$toParseKey]);
        }

        return $data;
    }

    // конфигурация
    public function setConfig($column, $type)
    {   
        $this->config = array('column' => $column, 'type' => $type);
    }

    // параметры сортировки
    public function setSort($column, $direction)
    {
        $this->sort = array('column' => $column, 'direction' => $direction);
    }

    // параметры лимита (offset, limit);
    public function setLimit($array = false)
    {
        $this->limit = $array;
    }
}