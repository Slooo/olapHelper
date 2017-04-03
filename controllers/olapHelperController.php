<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include 'MainController.php';

class olapHelperController extends MainController {  
        
    var $dbmodel = "olapHelperModel";

    function __construct() {
            parent::__construct();
    }

    function pre($message, $var)
    {
        return "<strong>".$message."</strong>"."<pre>".print_r($var, 1)."</pre><hr>";
    }

   	function olapHelper(){
        $this->load->model($this->dbmodel);

        $result = $this->olapHelperRobert_model->query();

        // include helper
        $this->load->helper('olap_helper');

        if($result['success'])
        {
            $result = $result['result'];

            $olapHelper = new olapHelper($result);

            $olapHelper->setConfig('CTE', 'string');
           	$olapHelper->setSort('CTE', 'asc');
            $olapHelper->setLimit(array(0, 10));

            // index column
			echo $this->pre("Index column", $olapHelper->getColumnsIndex());

			// count index column
			echo $this->pre("Count index column", $olapHelper->getColumnsIndexCount());

			// name column
			echo $this->pre("Name column", $olapHelper->getColumnsName());

			 // count name column
			echo $this->pre("Count name column", $olapHelper->getColumnsNameCount());

			// all columns
			echo $this->pre("All columns", $olapHelper->getColumns());

			// all count columns
			echo $this->pre("All count columns", $olapHelper->getColumnsCount());

			// all rows
			echo $this->pre("All rows", $olapHelper->getRowsCount());

            // get data column
			echo $this->pre("Get data column", $olapHelper->getDataColumn('CTE')
															->toNumber('float', 5));
            // get data all columns
            echo $this->pre("Get data all columns", $olapHelper->getData());

            // get data list columns
            echo $this->pre("Get data list columns", $olapHelper->getData(array(0, 'CTE', 5));
        }
    
    }

}