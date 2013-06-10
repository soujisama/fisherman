<?php 

  error_reporting(E_ALL);
  ini_set("display_errors",1);

    // Config  
    $dbhost = 'localhost';  
    $dbname = 'test';  
    
    echo extension_loaded("mongo") ? "loaded\n" : "not loaded\n";
    
    if (class_exists('Mongo')) {
        echo 'MongoDB is installed';
    }
    else {
        echo 'MongoDB is not installed';
    }
    
    print_r(get_loaded_extensions());
    
    
    phpinfo();  
    // Connect to test database  
    //$m = new Mongo("mongodb://$dbhost");
    //$m = new MongoClient(); //connect
    //$m = new MongoDB(); // connect
    //$db = $m->selectDB("example");
    //$db = $m->$dbname;  
      
    // select the collection  
    //$collection = $db->shows;  
      
    // pull a cursor query  
    //$cursor = $collection->find();  
?>