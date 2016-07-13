 <?php

$connect = new ConnectToDatabase("localhost","root","root","wordpress");
$conn = $connect->connectMySQL();


$pass = "1234567890"/*.mt_rand()*/;
$mysalt = strrev($pass);
$hash = hash_pbkdf2('sha1', $pass, $mysalt, 10, 32);


//print"<h3>Welcome to the site API</h3><hr />";


if(isset($_GET['access_token'])){
if($_GET['access_token']==$pass){

	if (isset($_GET['table'])) {

			try{
				$data = new TableData ($conn, $_GET['table']);
				$columns = new TableColumns($conn, $_GET['table']);

				echo "{\"records\":[";

				for($i=0;$i<count($data->getData());$i++){
				
					echo "{";
					for($a=0;$a<count($columns->getColumns());$a++){
						echo "\"".$columns->getColumns()[$a]."\":";					
						echo htmlentities (json_encode ($data->getData()[$i][$a]), true);	//Get the data into JSON format
							if ($a==count($columns->getColumns())-1){
								echo "";	
							}else{
								echo ",";				
							}				
					}
					if ($i==count($data->getData())-1){
						echo "}";	
					}else{
						echo "},";				
					}
				}
				echo "]}";

			}catch(PDOException $e){
				return "Connection failed: " . $e->getMessage();
			}
			
	}else{
		print "<h4>Your endpoints:</h4>";
	
		$apiSlug = new ApiSlug();	
		$tables = new SchemaTables($conn); //get tables


		for($i=0;$i<count($tables->getTables());$i++){
	
			print "<p><a href=\"".$apiSlug->getSlug()."&table=".$tables->getTables()[$i]."\" >".$tables->getTables()[$i]."</a></p>";

		}	

	}
}else{
	print "Incorrect Auth token!";
}
}else{
	print "Auth token needed!";
}
$conn = $connect->disconnectMySQL(); //close database connection


/*------------------------------------------------------------------Connect to DB--------------------------------------------------------------------------*/
class ConnectToDatabase{
	private $servername = null;
	private $username = null;
	private $password = null;
	private $dbname = null;


	public function __construct($servername, $username, $password, $dbname ){
		$this->servername = $servername;
		$this->username = $username;
		$this->password = $password;
		$this->dbname = $dbname;
	
	}


	public function connectMySQL(){
		try {
			$conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname;", $this->username, $this->password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $conn;
		}		
		catch(PDOException $e){
			return "Connection failed: " . $e->getMessage();
		}
	}
	public function disconnectMySQL(){
		return null; 	
	}


};
/*------------------------------------------------------------------Get Tables--------------------------------------------------------------------------*/
class SchemaTables{
	private $tables = array();
	
	public function __construct($conn){
		try {   
			$tableList = array();
			$result = $conn->query("SHOW TABLES");

			while ($row = $result->fetch(PDO::FETCH_NUM)) {
				$this->tables[] = $row[0];
			}
		}
		catch (PDOException $e) {
			echo $e->getMessage();
		}	
	
	}
	public function getTables(){
			return $this->tables;	
	}	
};
/*------------------------------------------------------------------Get Columns--------------------------------------------------------------------------*/
class TableColumns{
	private $columns = array();
	
	public function __construct($conn, $tableName){
		try {   
			$stmt = $conn->prepare("select column_name from information_schema.columns where table_name = '$tableName'");  
			if($stmt->execute()){ 
				$raw_column_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

				foreach($raw_column_data as $outer_key => $array){ 
				foreach($array as $inner_key => $value){ 
						if (!(int)$inner_key){ 
							$this->columns[] = $value; 
						} 
				} 
				} 
			} 
		} 
		catch (Exception $e){ 
			echo $e->getMessage(); //return exception 
		} 	
	
	}
	public function getColumns(){
			return $this->columns;	
	}	
};
/*------------------------------------------------------------------Get Table Data--------------------------------------------------------------------------*/
class TableData{
	private $data = array();
	
	public function __construct($conn, $tableName){
		try {   
			$stmt = $conn->prepare("SELECT * FROM $tableName");  
			
			if($stmt->execute()){ 
				$raw_column_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 
				$i = 0;
				foreach($raw_column_data as $outer_key => $array){ 
				foreach($array as $inner_key => $value){ 
						if (!(int)$inner_key){ 
							$this->data[$i][] = $value; 
						} 
				} 
					$i++;
				} 
			} 
		} 
		catch (Exception $e){ 
			echo $e->getMessage(); //return exception 
		} 
	
	}
	public function getData(){
		return $this->data;	
	}	
};
/*------------------------------------------------------------------Get Domain--------------------------------------------------------------------------*/
class ApiSlug{
	private $slug = "http://localhost/";
	
	public function __construct(){
		$this->slug = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	
	}
	public function getSlug(){
			return $this->slug;	
	}	
};
?>
