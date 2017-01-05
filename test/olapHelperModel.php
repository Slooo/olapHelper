<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include 'SCModel.php';

class olapHelperRobert_model extends SCModel { 

    function __construct(){
            parent::__construct(); 
    }

    // query
     function query(){ 
        $mdx = "
          SELECT
          {
           [Measures].[CTE]
          } on 0,
          from [Country]
        ";

        $conn = $this->olap('olap');
        $result = $this->queryMdx($mdx);
    }       

 
}

?>