<?php

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//	Author:				Joe Strusz                                                                                           //
	//	E-Mail:				joe.strusz@gmail.com                                                                                         //
	//	Date:				05/01/2010                                                                                                  //
	//                                                                                                                                  //
	//	Testing Completed:	TBD                                                                                                         //
	//	Tested By:			                                                                                                            //
	//                                                                                                                                  //
	//	Description:	This is a wrapper class for interacting with a MySQL Database over a TCP/IP Socket. It allows                   //
	//					the user to use only one connection, and gives access to a history of sql querys, insertion ids,                //
	//					and a history of errors. All arrays returned within this class are associative, so use the appropriate          //
	//					procedures when dealing with them. This class also manages memory and resources appropriately, and it does      //
	//					it transparently, just use the provided public member functions to interact with the objects.                   //
	//                                                                                                                                  //
	// Examples:		Creating a new db Object to a specified MySQL server                                                            //
	//					$DB = new db($serverName, $dbName, $userName, $password);	//Creates a new connection and db object            //
	//                                                                                                                                  //
	//					Preform a SQL query to the connected MySQL Database                                                             //
	//					$DB->queryDatabase($sql); //Preforms the given SQL query, returns true if successful, false otherwise.          //
	//                                                                                                                                  //
	//					Advances the result pointer to the next record returned by a SQL query                                          //
	//					$DB->updateQueryResult(); //returns ture if there is another record or false if there is not.                   //
	//                                                                                                                                  //
	//					Actual data of the current record that was updated with updateQueryResult();                                    //
	//					$DB->getQueryResult();                                                                                          //
	//                                                                                                                                  //
	//					Getting the record id of the last insert preformed.                                                             //
	//					$DB->getLastInsertId();                                                                                         //
	//                                                                                                                                  //
	//					Closes the MySQL Databse connection and cleans up the state                                                     //
	//					$DB->closeConnection();                                                                                         //
	//                                                                                                                                  //
	//					TO access the STACK (array) of SQL commands that were executed.                                                 //
	//					$DB->getQueryHistory();                                                                                         //
	//                                                                                                                                  //
	//					To access the STACK (array) of Error messages.                                                                  //
	//					$DB->getErrorHistory();                                                                                         //
	//                                                                                                                                  //
	//					To access a state summery (array) of the given object.                                                          //
	//					$DB->getStatus();                                                                                               //
	//                                                                                                                                  //
	//					To Clear both the SQL and Error Message History of the current Object.                                          //
	//					$DB->clearHistory();                                                                                            //
	//                                                                                                                                  //
	//					To destroy the current DB Object - frees memory and for security;                                               //
	//					$DB = $DB->destroy();                                                                                           //
	//                                                                                                                                  //
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    class db {
    
    	/* Private members, full OOP encapulation */
    
    	private $hostName;				//name of mysql server
    	private $databaseName;			//name of database to connect to
    	private $userName;				//user name to use to connect to database
    	
    	private $connected;				//boolean value indicated if you have a connection
    	private $databaseLink;			//mysql link to the database
    	
    	private $error;					//two dimentional array with time, type, error message
    	
    	private $queryHistory;			//two dimentional array with time, sql statement
    	
    	private $resourcePresent;		//boolean value indicating if there is a mysql resource pointer
    	private $resource;				//mysql resource pointer
    	
    	private $result;				//the row result from the last fetch on the mysql resource
    	private $lastInsertId;			//keeps track of the last INSERT record id 
    	
    	/**********************************************************************************************************************/
    	
    	
    	
    	/* Constructor & Destructor */

    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function __construct($hostName, $databaseName, $userName, $password) {
    	
      		$tmpLink = mysql_connect($hostName, $userName, $password);

    		$this->connected = false;
    		$this->resource = false;
    		$this->resourcePresent = false;
    		
    		$this->hostName = $hostName;
    		$this->databaseName = $databaseName;
    		$this->userName = $userName;
    		
    		$this->queryHistory = array();
    		
    		$this->result = NULL;
    		$this->lastInsertId = 0;
    		
    		
    		if(!$tmpLink && !mysql_ping($tmpLink)){ //Check to see if a link was established
    			$this->connected = false;
    			array_push($this->error, array("time" => time(), "type" => "MySQL", "error" => mysql_error()));    			
    			return $this;
    		}
    		
    		$this->connected = true;
    		$this->databaseLink = $tmpLink;
    		$this->error = array();
    		$this->resource = '';
    		
    		$this->connectToDatabase($databaseName);
    		
    		return $this;
    		
    	} //End __construct
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function __destruct() {
    	
    		$this = $this->destroy();
    		
    	
    	} //End __desctruct
    	
    	/**********************************************************************************************************************/
    	
    	
    	
    	/* private functions */
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function getDatabaseLink(){
    		return $this->databaseLink;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function getHostName(){
    		return $this->hostName;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function getDatabaseName(){
    		return $this->databaseName;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function getUserName(){
    		return $this->userName;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function clearQueryHistory(){
    		$this->queryHistory = array();
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function clearErrorHistory(){
    		$this->error = array();
    	}
    		
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function connectedToDatabase(){
    	
    		if($this->connected && $this->getDatabaseLink() && mysql_ping($this->getDatabaseLink())){
    			$this->connected = true;
    			return true;
    		}else{
    			$this->connected = false;
    			return false;
    		}
    	}
    		
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function connectToDatabase($databaseName){
    	
    		if(!$this->connectedToDatabase()){
    			return false;
    		}
    		
    		if(!mysql_select_db($databaseName, $this->getDatabaseLink())){
    			array_push($this->error, array("time" => time(), "type" => "MySQL", "error" => mysql_error()));    			
    			return false;
    		}
    		
    		$this->databaseName = $databaseName;
    		
    		return true;
    	} //End connectToDatabase
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function getQueryResource(){
    		if($this->isResourcePresent()){
    			return $this->resource;
    		}
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function isResourcePresent(){
    		return $this->resourcePresent;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	private function freeResource(){
    		if($this->isResourcePresent()){
    			$this->resourcePresent = false;
    			if($this->resource){
    				mysql_free_result($this->resource);
    				$this->resource = false;
    			}
    		}
    		
    		return;
    	}
    	
    	/**********************************************************************************************************************/

    	
    
    	/* Public functions, db Object API */
    	
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	//Executes the given SQL command on the connected Database
    	//Returns: true / false
    	public function queryDatabase($sql, $history=true){
    		$this->freeResource(); //FREE RESOURCE IF THERE IS ALREADY ONE, YOU CAN ONLY USE 1 RESOURCE PER ACTIVE CONNECTION
    	
    		if(!$this->connectedToDatabase()){
    			return false;
    		}
    		
    		$tmpResult = mysql_query($sql, $this->getDatabaseLink());
    		
    		
    		if(!$tmpResult){
				array_push($this->error, array("time" => time(), "type" => "MySQL", "error" => mysql_error()));    			
				return false;
    		}
    		
    		$this->lastInsertId = mysql_insert_id($this->getDatabaseLink());
    		if($history){
    			array_push($this->queryHistory, array("time" => time(), "sql" => $sql));
    		}
    		
    		$this->resourcePresent = true;
    		$this->resource = $tmpResult;
    		
    		return true;
    	
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	//Gets the next Row from the last executed SQL command
    	//Returns: true / false
    	public function updateQueryResult(){
    		if(!$this->isResourcePresent()){
    			$this->freeResource();
    			return false;
    		}
    		
    		$tmpRow = mysql_fetch_assoc($this->getQueryResource());
    		
    		if(!$tmpRow){
    			$this->freeResource();
    			array_push($this->error, array("time" => time(), "type" => "MySQL", "error" => mysql_error()));
    			return false;
    		}
    		
    		$this->result = $tmpRow;
    		
    		return true;
    		
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function getQueryResult(){
    		return $this->result;   	
    	}

    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	//Use this function to close the db connection
    	public function closeConnection(){
    		$this->freeResource();
    	
    		if(!$this->connectedToDatabase()){
    			return;
    		}
    		
    		mysql_close($this->getDatabaseLink());
    		
    		$this->connected = false;
    		
    		return;
    	
    	} //End closeConnection
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function getStatus(){
    	    if($this->connectedToDatabase()){
    			return array("hostname" => $this->getHostName(), 
    		             	"database" => $this->getDatabaseName(), 
    		             	"username" => $this->getUserName(), 
    		             	"resource" => $this->resource,
    		             	"connected" => "true",
    		             	"errorHistory" => $this->getErrorHistory(),
    		             	"queryHistory" => $this->getQueryHistory());
    		             	
    		}
    		
    		return array("hostname" => $this->getHostName(), 
    		             "database" => $this->getDatabaseName(), 
    		             "username" => $this->getUserName(), 
    		             "resource" => $this->resource,
       		             "connected" => "false"),
    		             "errorHistory" => $this->getErrorHistory(),
    		             "queryHistory" => $this->getQueryHistory());
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function getQueryHistory(){	
    		return $this->queryHistory;	
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function getErrorHistory(){
    		return $this->error;
    	}
    	   	
    	// Here is a simple named binding function for queries that makes SQL more readable:
		// $sql = "SELECT * FROM users WHERE user = :user AND password = :password";
		// mysql_bind($sql, array('user' => $user, 'password' => $password));
		/*
		private function mysql_bind(&$sql, $vals) {
    		foreach ($vals as $name => $val) {
        		$sql = str_replace(":$name", "'" . mysql_real_escape_string($val) . "'", $sql);
    		}
		}
    	*/

    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function getLastInsertId(){
    		if(!$this->lastInsertId){
    			return 0;
    		}
    	
    		return $this->lastInsertId;
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function clearHistory(){
    		$this->clearQueryHistory();
    		$this->clearErrorHistory();
    	}
    	
    	//$$$ START FUNCTION COMMENT $$$//
    	//$$$ END FUNCTION COMMENT $$$//
    	public function destroy(){
    		
    			$this->closeConnection();
    			$this->clearHistory();
    			
    			unset($this->hostName);				
    			unset($this->databaseName);			
    			unset($this->userName);				    	
    			unset($this->connected = false);				
    			unset($this->databaseLink);			    	
    			unset($this->error);					    	
    			unset($this->queryHistory);			    	
    			unset($this->resourcePresent = false);		
    			unset($this->resource);				
    	
    			unset($this->result);				
    			unset($this->lastInsertId);	
    			
    			$this = NULL;		

    			return NULL;
    	
    	}	
    	
   		/**********************************************************************************************************************/

    	
    } //End db Class
    
    
    
    
?>
