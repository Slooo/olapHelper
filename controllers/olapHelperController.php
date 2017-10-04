<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include 'SCController.php';

class olapHelperRobert extends SCController {  
        
    var $needAuth = true;
    var $dbmodel = "olapHelperRobert_model";
    /**
     * Конструктор
     */
    function __construct() {
            parent::__construct();
    }

    function pre($message, $var)
    {
        return "<strong>".$message."</strong>"."<pre>".print_r($var, 1)."</pre><hr>";
    }

    // olap_helper
    function helper(){
        $this->load->model($this->dbmodel);

        $result = $this->olapHelperRobert_model->robert();

        # helper
        if($result['success'])
        {
            $olapHelper = new olapHelper($result['result']);
            echo $this->pre("Данные по строке", $olapHelper->getDataRows());
        }
    
    }

}