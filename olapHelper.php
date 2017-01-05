<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Codeigniter olapHelper
 *
 * @category   Codeigniter olapHelper
 * @package    olapHelper
 * @author     Robert Slooo <codbro.com>
 * @version    1.0
 * @copyright  2016 Robert S
 */

class olapParser {
    public $result;
    public $sort = array();
    public $config = array();
    public $limit = array();

    function __construct($result = array())
    {
       $this->result = $result;
       sort($this->result);
    }

    // count rows
    public function getRowsCount()
    {
        $axis = $this->hereColumn();
        $rows = count($axis['indexColumn']);
        return $rows;
    }

    // check column
    protected function checkColumn($column)
    {
        $columns = $this->getColumns();
        $columns = array_flip($columns);

        if(!array_key_exists($column, $columns))
        {
            exit('Столбец '.$column.' не найден');
        }
    }

    // get here column
    private function hereColumn()
    {
        $result = $this->result;
        $axis = array();

        if(array_key_exists('Axis0', $result[1]))
        {
            unset($result[0], $result[1], $result[2]['SlicerAxis']);
            $axis['indexColumn'] = $result[2]['Axis1'];
            $axis['nameColumn'] = $result[2]['Axis0'];
            $axis['nameColumnValue'] = $result[3];
        } else {
            unset($result[0], $result[2], $result[3]['SlicerAxis']);
            $axis['indexColumn'] = $result[3]['Axis1'][0];
            $axis['nameColumn'] = $result[3]['Axis0'];
            $axis['nameColumnValue'] = $result[1];
        }

        return $axis;
    }

	// count index column
    public function getColumnsIndexCount()
    {
        return count($this->getColumnsIndex());
    }

    // count name column
    public function getColumnsNameCount()
    {
        return count($this->getColumnsName());
    }

    // index column
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

    // name column
    public function getColumnsName()
    {
        $axis = $this->hereColumn();
        $temp = array();

        foreach($axis['nameColumn'] as $k => $v)
        {
            $x = (array)$v[0];
            $temp[$k] = array_values($x)[1];
        }

        return $temp;
    }

    // all columns
    public function getColumns()
    {
    	$temp = array_merge($this->getColumnsIndex(), $this->getColumnsName());
    	return $temp;
    }

    // count all column
    public function getColumnsCount()
    {
        $count = $this->getColumnsIndexCount() + $this->getColumnsNameCount();
        return $count;
    }

    // data column
    public function getDataColumn($column)
    {
        $this->checkColumn($column);

    	$temp = array();
        $data = array();
        $result = $this->hereColumn();

        // name column
    	if(is_int($column) == false) 
    	{
            /* if name column == 0,
                get all rows count name columns
            */
            $count = $this->getColumnsNameCount();
            if($count == 0)
            {
                $count = $this->getRowsCount();                
            }

            foreach($result['nameColumnValue'] as $k => $v)
            {
                $x = (array)$v;
                $data[$k] = array_values($x)[0];
            }

    		$index = array_chunk($data, $count, false);

	        $columns = $this->getColumnsName();

    		foreach($columns as $k => $row)
    		{
    			if($column == $row)
    			{
		    		foreach($index as $key => $val)
		    		{
                        $x = $val;
                        $x = array_values($x);
                        $temp[$column][] = $x[$k];
		    		}
    			}
    		}
    	}
        
        // index column
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

    // data columns
    public function getData($columns = false)
    {
    	$data = array();
    	$temp = array();

        if(!is_array($columns))
        {
            $columns = $this->getColumns();
        }

    	foreach($columns as $v)
    	{
    		$data[] = $this->getDataColumn($v)->toString();
    	}

        foreach($data as $k => $v)
        {
            foreach($v as $ke => $va)
            {
                $temp[$ke] = $va;
            }
        }

        if(count($this->sort) > 0)
        {
            $temp = $this->toSort($temp);            
        }

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

    // parse to int
    protected function toInt()
    {
        $data = array();
        $temp = $this->dataColumn;
        $name = array_keys($temp)[0];

        return (is_array($temp))
            ? array($name => array_map('intval', $temp[$name])) 
            : false;
    }

    // parse to float
    protected function toFloat($number)
    {
        $data = array();
        $temp = $this->dataColumn;
        $name = array_keys($temp)[0];

        $data[$name] = array_map(function($array) use($number){
            $array = strtr($array, array(',' => '.'));
            return number_format($array, $number);
        }, $temp[$name]);

        return $data;
    }

    // parse to round
    protected function toRound()
    {
        $data = array();
        $temp = $this->dataColumn;
        $name = array_keys($temp)[0];

        $data[$name] = array_map('round', $temp[$name]);
        return $data;
    }

    // parse to ceil
    protected function toCeil()
    {
        $data = array();
        $temp = $this->dataColumn;
        $name = array_keys($temp)[0];

        $data[$name] = array_map('ceil', $temp[$name]);
        return $data;
    }

    // parse to number
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

    // parse to string
    public function toString()
    {
        $temp = $this->dataColumn;
        return $temp;
    }

    // limit
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

    // sorting
    protected function toSort($temp)
    {
        $data = array();

        $config = $this->config;
        $column = $this->sort['column'];
        $direction = $this->sort['direction'];

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

        $toParseKey = array_keys($toParse)[0];

        $beforeTemp = $temp;

        $temp[$toParseKey] = $toParse[$toParseKey];     

        if($direction == 'desc')
        {
            arsort($temp[$column]);
        }

        else
        
        if($direction == 'asc')
        {
            asort($temp[$column]);
        }

        foreach($temp as $key => $val)
        {
            foreach($temp[$column] as $k => $v)
            {
                foreach($temp[$key] as $ke => $ve)
                {
                    if($k == $ke)
                    {
                        $data[$key][$ke] = $ve;
                    }
                }
            }
        }

        if(!array_key_exists($toParseKey, $beforeTemp))
        {
            unset($data[$toParseKey]);
        }

        return $data;
    }

    // settings
    public function setConfig($column, $type)
    {   
        $this->config = array('column' => $column, 'type' => $type);
    }

    // settings to sort
    public function setSort($column, $direction)
    {
        $this->sort = array('column' => $column, 'direction' => $direction);
    }

    // settings limit
    public function setLimit($array = false)
    {
        $this->limit = $array;
    }
}