<?php
# Database class
class database {
	
	private $db_host = ''; 
	private $db_user = ''; 
	private $db_pass = ''; 
	private $db_name = '';

	private $connection = '';
	private $queryset   = '';
	private $resultset  = '';

	public function __construct($cfg)
	{
		$this->db_host = $cfg['hostname'];
		$this->db_user = $cfg['username'];
		$this->db_pass = $cfg['password'];
		$this->db_name = $cfg['database'];

		if ($this->connect() == false) {
			die('Connection to '. $cfg['hostname'] .' for database '. $cfg['database'].' failed.');
		}
	}

	private function connect()
	{
		if(!$this->connection) {
		    $myconn = @mysql_connect($this->db_host,$this->db_user,$this->db_pass);
		    if($myconn) {
		        $seldb = @mysql_select_db($this->db_name,$myconn);
		        if($seldb) {
		            $this->connection = true; 
		            return true; 
		        } else {
		            return false; 
		        }
		    } else {
		        return false; 
		    }
		} else{
		    return true; 
		}
	} #end Connect
 
	public function disconnect()  
	{  
	    if($this->connection)  
	    {  
	        if(@mysql_close())  
	        {  
	            $this->connection = false;   
	            return true;   
	        }  
	        else  
	        {  
	            return false;   
	        }  
	    }  
	} # End Disconnect      
    
    public function execute($sql)
    {
    	$query = mysql_query($sql);
    	if ($query) {
    		$this->queryset = $query;
    		return true;
    	} else {
    		return false;
    	}
    } #end of sql

    public function getrows($sql = '')
    {
    	if ($sql != '') { $this->execute($sql); }
    	if ($this->queryset != '') {
			$this->resultset = mysql_fetch_array($this->queryset);
			return true;
    	} else {
    		return false;
    	}    	
    } # end getrows

    public function insertdata($table='', $obj = '')
    {
    	$fields = '';
    	$values = '';
    	if ( !is_object($obj) || $table == '') {
    		return false;
    	}else{
    		$objtoarray = get_object_vars($obj);
    		$columns    = implode(", ",array_keys($objtoarray));
			$esc_values = array_map('mysql_real_escape_string', array_values($objtoarray));
			$values     = implode("','", $escaped_values);
			$sqlins     = "INSERT INTO $table ($columns) VALUES($values)";
			if ($this->execute($sqlins) == true) { return true;} else { return false;}
    	}
    } # end insertdata

	public function select($table, $rows = '*', $where = null, $order = null)  
	{  
		$q = 'SELECT '.$rows.' FROM '.$table;  
		if($where != null)  
		    $q .= ' WHERE '.$where;  
		if($order != null)  
		    $q .= ' ORDER BY '.$order;  
		if($this->tableExists($table))  
		{  
			$query = @mysql_query($q);  
			if($query)  
			{  
			    $this->numResults = mysql_num_rows($query);  
			    for($i = 0; $i < $this->numResults; $i++)  
			    {  
			        $r = mysql_fetch_array($query);  
			        $key = array_keys($r);   
			        for($x = 0; $x < count($key); $x++)  
			        {  
			            // Sanitizes keys so only alphavalues are allowed  
			            if(!is_int($key[$x]))  
			            {  
			                if(mysql_num_rows($query) > 1)  
			                    $this->result[$i][$key[$x]] = $r[$key[$x]];  
			                else if(mysql_num_rows($query) < 1)  
			                    $this->result = null;   
			                else  
			                    $this->result[$key[$x]] = $r[$key[$x]];   
			            }  
			        }  
			    }              
			    return true;   
			}  
			else  
			{  
			    return false;   
			}  
		}  
		else  
		return false;   
	}  # end select

    public function insert($table,$values,$rows = null)
    {
        if($this->tableExists($table))
        {
            $insert = 'INSERT INTO '.$table;
            if($rows != null)
            {
                $insert .= ' ('.$rows.')'; 
            }

            for($i = 0; $i < count($values); $i++)
            {
                if(is_string($values[$i]))
                    $values[$i] = '"'.$values[$i].'"';
            }
            $values = implode(',',$values);
            $insert .= ' VALUES ('.$values.')';
            $ins = @mysql_query($insert);            
            if($ins)
            {
                return true; 
            }
            else
            {
                return false; 
            }
        }
    } # end insert

 
    public function delete($table,$where = null)
    {
        if($this->tableExists($table))
        {
            if($where == null) {
                $delete = 'DELETE '.$table; 
            } else {
                $delete = 'DELETE FROM '.$table.' WHERE '.$where; 
            }
            $del = @mysql_query($delete);

            if($del) {
                return true; 
            } else {
               return false; 
            }
        } else {
            return false; 
        }
    } # End DELETE
  
    public function update($table,$rows,$where)
    {
        if($this->tableExists($table))
        {
            // Parse the where values
            // even values (including 0) contain the where rows
            // odd values contain the clauses for the row
            for($i = 0; $i < count($where); $i++)
            {
                if($i%2 != 0)
                {
                    if(is_string($where[$i]))
                    {
                        if(($i+1) != null)
                            $where[$i] = '"'.$where[$i].'" AND ';
                        else
                            $where[$i] = '"'.$where[$i].'"';
                    }
                }
            }
            $where = implode('=',$where);
            
            
            $update = 'UPDATE '.$table.' SET ';
            $keys = array_keys($rows); 
            for($i = 0; $i < count($rows); $i++)
           {
                if(is_string($rows[$keys[$i]]))
                {
                    $update .= $keys[$i].'="'.$rows[$keys[$i]].'"';
                }
                else
                {
                    $update .= $keys[$i].'='.$rows[$keys[$i]];
                }
                
                // Parse to add commas
                if($i != count($rows)-1)
                {
                    $update .= ','; 
                }
            }
            $update .= ' WHERE '.$where;
            $query = @mysql_query($update);
            if($query)
            {
                return true; 
            }
            else
            {
                return false; 
            }
        }
        else
        {
            return false; 
        }
    }
 
} # EOC
?>