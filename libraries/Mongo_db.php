<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* CodeIgniter Active Record Library for MongoDb
*
* A library to interface with the NoSQL database MongoDB. For more information see http://www.mongodb.org
*
* @package CodeIgniter
* @author Ola Akintoye | http://akintoye.me | GitHub: @sakintoye
* @copyright Copyright (c) 2016, Sunkanmi Akintoye.
* @license http://www.opensource.org/licenses/mit-license.php
* @link https://github.com/sakintoye/codeigniter-mongodb
* @version Version 1.0
* Thanks to Intekhab Rizvi whose library inspired this project.
*/

Class Mongo_db{

	private $CI;
	private $config = array();
	private $param = array();
	private $activate;
	private $connect;
	private $db;
	private $hostname;
	private $port;
	private $database;
	private $username;
	private $password;
	private $debug;
	private $write_concerns;
	private $journal;
	private $selects = array();
	private $updates = array();
	private $wheres	= array();
	private $limit	= 999999;
	private $offset	= 0;
	private $sorts	= array();
	private $return_as = 'array';
	private $upserts = false;
	public $benchmark = array();

	/**
	* --------------------------------------------------------------------------------
	* Class Constructor
	* --------------------------------------------------------------------------------
	*
	* Automatically check if the Mongo PECL extension has been installed/enabled.
	* Get Access to all CodeIgniter available resources.
	* Load mongodb config file from application/config folder.
	* Prepare the connection variables and establish a connection to the MongoDB.
	* Try to connect on MongoDB server.
	*/

	function __construct($param)
	{

		// if ( ! class_exists('Mongo') && ! class_exists('MongoC'))
		// {
		// 	show_error("The MongoDB PECL extension has not been installed or enabled", 500);
		// }
		$this->CI =& get_instance();
		$this->CI->load->config('mongo_db');
		$this->config = $this->CI->config->item('mongo_db');
		$this->param = $param;
		$this->connect();
	}

	/**
	* --------------------------------------------------------------------------------
	* Class Destructor
	* --------------------------------------------------------------------------------
	*
	* Close all open connections.
	*/
	function __destruct()
	{
    /*
		if(is_object($this->connect))
		{
			$this->connect->close();
		}
    */
	}

	/**
	* --------------------------------------------------------------------------------
	* Prepare configuration for mongoDB connection
	* --------------------------------------------------------------------------------
	*
	* Validate group name or autoload default group name from config file.
	* Validate all the properties present in config file of the group.
	*/

	private function prepare()
	{
		if(is_array($this->param) && count($this->param) > 0 && isset($this->param['activate']) == TRUE)
		{
			$this->activate = $this->param['activate'];
		}
		else if(isset($this->config['active']) && !empty($this->config['active']))
		{
			$this->activate = $this->config['active'];
		}else
		{
			show_error("MongoDB configuration is missing.", 500);
		}

		if(isset($this->config[$this->activate]) == TRUE)
		{
			if(empty($this->config[$this->activate]['hostname']))
			{
				show_error("Hostname missing from mongodb config group : {$this->activate}", 500);
			}
			else
			{
				$this->hostname = trim($this->config[$this->activate]['hostname']);
			}

			if(empty($this->config[$this->activate]['port']))
			{
				show_error("Port number missing from mongodb config group : {$this->activate}", 500);
			}
			else
			{
				$this->port = trim($this->config[$this->activate]['port']);
			}

			if(empty($this->config[$this->activate]['username']))
			{
				show_error("Username missing from mongodb config group : {$this->activate}", 500);
			}
			else
			{
				$this->username = trim($this->config[$this->activate]['username']);
			}

			if(empty($this->config[$this->activate]['password']))
			{
				show_error("Password missing from mongodb config group : {$this->activate}", 500);
			}
			else
			{
				$this->password = trim($this->config[$this->activate]['password']);
			}

			if(empty($this->config[$this->activate]['database']))
			{
				show_error("Database name missing from mongodb config group : {$this->activate}", 500);
			}
			else
			{
				$this->database = trim($this->config[$this->activate]['database']);
			}

			if(empty($this->config[$this->activate]['db_debug']))
			{
				$this->debug = FALSE;
			}
			else
			{
				$this->debug = $this->config[$this->activate]['db_debug'];
			}

			if(empty($this->config[$this->activate]['write_concerns']))
			{
				$this->write_concerns = 1;
			}
			else
			{
				$this->write_concerns = $this->config[$this->activate]['write_concerns'];
			}

			if(empty($this->config[$this->activate]['journal']))
			{
				$this->journal = TRUE;
			}
			else
			{
				$this->journal = $this->config[$this->activate]['journal'];
			}

			if(empty($this->config[$this->activate]['return_as']))
			{
				$this->return_as = 'array';
			}
			else
			{
				$this->return_as = $this->config[$this->activate]['return_as'];
			}
		}
		else
		{
			show_error("mongodb config group :  <strong>{$this->activate}</strong> does not exist.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Connect to MongoDB Database
	* --------------------------------------------------------------------------------
	*
	* Connect to mongoDB database or throw exception with the error message.
	*/

	private function connect()
	{
		$this->prepare();
		try
		{
			$dns = "mongodb://{$this->hostname}:{$this->port}";
			if(isset($this->config[$this->activate]['no_auth']) == TRUE && $this->config[$this->activate]['no_auth'] == TRUE)
			{
				$options = array();
			}
			else
			{
				$options = array('username'=>$this->username, 'password'=>$this->password);
			}
			$this->connect = (new MongoDB\Client($dns, $options));
			// $this->db = $this->connect->selectDB($this->database);
			$this->db = $this->connect->{$this->database};
		}
		catch (MongoConnectionException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Unable to connect to MongoDB: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Unable to connect to MongoDB", 500);
			}
		}
	}

  /**
  * --------------------------------------------------------------------------------
	* //! Set
	* --------------------------------------------------------------------------------
  *
  * Accepts single array or multiple arrays
  * @usage : $this->mongo_db->set(['title'=>'Batman v Superman']) Single array element
  * or
  * $this->mongo_db->set([['title'=>'Batman v Superman'], ['rating'=>9]]) Multi array elements
  */
  public function set($data, $value='')
  {
    if(empty($data)){
      show_error("Expected an array but got empty value");
    }
    if(! is_array($data)){
			if(is_string($data)){
				$this->updates['$set'][$data] = $value;
				// if($value != ''){
				// 	$this->updates['$set'][$data] = $value;
				// }
				// else{
				// 	show_error("Expected second parameter to be non empty string. Got empty string");
				// }
			}
			else{
				show_error("Expected an array but got non array value");
			}
    }
		else{
			if(is_array($data[0])){ // Traverse array and build "$this->updates" instance variable
	      foreach ($data as $key => $val) {
	        // $key = key($prop);
	        $this->updates['$set'][$key] = $val;
	      }
	    }
	    else{
	      $key = key($data);
	      $this->updates['$set'][$key] = $data[$key];
	    }
		}

    return $this;
  }

	/**
	* --------------------------------------------------------------------------------
	* //! Insert
	* --------------------------------------------------------------------------------
	*
	* Insert a new document into the passed collection
	*
	* @usage : $this->mongo_db->insert('foo', $data = array());
	*/
	public function insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to insert into", 500);
		}
		$insert = array_merge($insert, $this->updates['$set']);
		if (!is_array($insert) || count($insert) == 0)
		{
			show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
		}

		try
		{
      $insertOneResult = $this->db->{$collection}->insertOne($insert);
			if (! empty($insertOneResult))
			{
        if($insertOneResult->getInsertedCount() > 0){
          return ($insertOneResult->getInsertedId());
        }
        else{
          return (FALSE);
        }
			}
			else
			{
				return (FALSE);
			}
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Insert of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Batch Insert
	* --------------------------------------------------------------------------------
	*
	* Insert a multiple document into the collection
	*
	* @usage : $this->mongo_db->batch_insert('foo', $data = array());
	*/
	public function insertMany($collection = "", $inserts = array())
	{
    if (empty($collection))
		{
			show_error("No Mongo collection selected to insert into", 500);
		}

		if (!is_array($inserts) || count($inserts) == 0)
		{
			show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
		}

		try
		{
      $insertManyResult = $this->db->{$collection}->insertMany($inserts);
			if (! empty($insertManyResult))
			{
        if($insertManyResult->getInsertedCount() > 0){
          return ($insertManyResult->getInsertedIds());
        }
        else{
          return (FALSE);
        }
			}
			else
			{
				return (FALSE);
			}
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Insert of data into MongoDB failed", 500);
			}
		}
	}

  /**
  * --------------------------------------------------------------------------------
	* //! findOne
	* --------------------------------------------------------------------------------
  * Returns a single document matching the query. Accepts a (key=>value) pair array as WHERE clause
  * @ussage: $this->mongo_db->find('users', ['first_name'=>'foo'])
  */
  public function find_one($collection){
    if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}
    if(! is_array($this->wheres) || empty($this->wheres)){
      show_error("Condition should be an array.", 500);
    }
    $document = $this->db->{$collection}->findOne($this->wheres, ['projection'=>$this->selects]);
		$this->_clear();
    return $document;
  }

  public function find($collection){
    if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}
    if(! is_array($this->wheres) || empty($this->wheres)){
      show_error("Condition should be an array.", 500);
    }
    $result = $this->db->{$collection}->find($this->wheres, ['projection'=>$this->selects, 'limit'=>$this->limit, 'sort'=>$this->sorts]);
    $documents = array();
    if(! empty($result)){
      foreach ($result as $row) {
        $documents[] = $row;
      }
    }
		$this->_clear();
    return $documents;
  }

	/**
	* --------------------------------------------------------------------------------
	* //! Select
	* --------------------------------------------------------------------------------
	*
	* Determine which fields to include OR which to exclude during the query process.
	* If you want to only choose fields to exclude, leave $includes an empty array().
	*
	* @usage: $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
	*/
	public function select($includes = array(), $excludes = array())
	{
		if ( ! is_array($includes))
		{
			$includes = array();
		}
		if ( ! is_array($excludes))
		{
			$excludes = array();
		}
		if ( ! empty($includes))
		{
			foreach ($includes as $key=> $col)
			{
				if(is_array($col)){
					//support $elemMatch in select
					$this->selects[$key] = $col;
				}else{
					$this->selects[$col] = 1;
				}
			}
		}
		if ( ! empty($excludes))
		{
			foreach ($excludes as $col)
			{
				$this->selects[$col] = 0;
			}
		}
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Where
	* --------------------------------------------------------------------------------
	*
	* Get the documents based on these search parameters. The $wheres array should
	* be an associative array with the field as the key and the value as the search
	* criteria.
	*
	* @usage : $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
	*/
	public function where($wheres, $value = null)
	{
		if (is_array($wheres))
		{
			foreach ($wheres as $wh => $val)
			{
				$this->wheres[$wh] = $val;
			}
		}
		else
		{
			$this->wheres[$wheres] = $value;
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Where greater than or equal to
	* --------------------------------------------------------------------------------
	*
	* Get the documents where the value of a $field is greater than or equal to $x
	*
	* @usage : $this->mongo_db->where_gte('foo', 20);
	*/
	public function where_gte($field = "", $x)
	{
		if (!isset($field))
		{
			show_error("Mongo field is require to perform greater then or equal query.", 500);
		}

		if (!isset($x))
		{
			show_error("Mongo field's value is require to perform greater then or equal query.", 500);
		}

		$this->_w($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Get One Document [Alias of find_one()]
	* --------------------------------------------------------------------------------
	*
	* Get a single document based upon the passed parameters
	*
	* @usage : $this->mongo_db->get_one('foo');
	*/
	public function get_one($collection = "")
	{
		if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}
		return $this->find_one($collection);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Get [Alias of find()]
	* --------------------------------------------------------------------------------
	*
	* Get the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->get('foo');
	*/
	public function get($collection = "")
	{
		if (empty($collection))
		{
			show_error("In order to retrieve documents from MongoDB, a collection name must be passed", 500);
		}
		return $this->find($collection);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Get where
	* --------------------------------------------------------------------------------
	*
	* Get the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->get_where('foo', array('bar' => 'something'));
	*/
	public function get_where($collection = "", $where = array())
	{
		if (is_array($where) && count($where) > 0)
		{
			return $this->where($where)
			->get($collection);
		}
		else
		{
			show_error("Nothing passed to perform search or value is empty.", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Count
	* --------------------------------------------------------------------------------
	*
	* Count the documents based upon the passed parameters
	*
	* @usage : $this->mongo_db->count('foo');
	*/
	public function count($collection = "")
	{
		if (empty($collection))
		{
			show_error("In order to retrieve a count of documents from MongoDB, a collection name must be passed", 500);
		}
		$count = $this->db->{$collection}->find($this->wheres)->limit((int) $this->limit)->skip((int) $this->offset)->count();
		$this->_clear();
		return ($count);
	}


	/**
	* --------------------------------------------------------------------------------
	* Inc
	* --------------------------------------------------------------------------------
	*
	* Increments the value of a field
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->inc(array('num_comments' => 1))->update('blog_posts');
	*/
	public function inc($fields = array(), $value = 0)
	{
		$this->_u('$inc');
		if (is_string($fields))
		{
			$this->updates['$inc'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$inc'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Multiple
	* --------------------------------------------------------------------------------
	*
	* Multiple the value of a field
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->mul(array('num_comments' => 3))->update('blog_posts');
	*/
	public function mul($fields = array(), $value = 0)
	{
		$this->_u('$mul');
		if (is_string($fields))
		{
			$this->updates['$mul'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$mul'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Maximum
	* --------------------------------------------------------------------------------
	*
	* The $max operator updates the value of the field to a specified value if the specified value is greater than the current value of the field.
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->max(array('num_comments' => 3))->update('blog_posts');
	*/
	public function max($fields = array(), $value = 0)
	{
		$this->_u('$max');
		if (is_string($fields))
		{
			$this->updates['$max'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$max'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* Minimum
	* --------------------------------------------------------------------------------
	*
	* The $min updates the value of the field to a specified value if the specified value is less than the current value of the field.
	*
	* @usage: $this->mongo_db->where(array('blog_id'=>123))->min(array('num_comments' => 3))->update('blog_posts');
	*/
	public function min($fields = array(), $value = 0)
	{
		$this->_u('$min');
		if (is_string($fields))
		{
			$this->updates['$min'][$fields] = $value;
		}
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$min'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	* --------------------------------------------------------------------------------
	* //! distinct
	* --------------------------------------------------------------------------------
	*
	* Finds the distinct values for a specified field across a single collection
	*
	* @usage: $this->mongo_db->distinct('collection', 'field');
	*/
	public function distinct($collection = "", $field="")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for update", 500);
		}

		if (empty($field))
		{
			show_error("Need Collection field information for performing distinct query", 500);
		}

		try
		{
			$documents = $this->db->{$collection}->distinct($field, $this->wheres);
			$this->_clear();
			if ($this->return_as == 'object')
			{
				return (object)$documents;
			}
			else
			{
				return $documents;
			}
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("MongoDB Distinct Query Failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Update
	* --------------------------------------------------------------------------------
	*
	* Updates a single document in Mongo
	*
	* @usage: $this->mongo_db->update('foo', $data = array());
	*/
	public function update($collection = "", $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected for update", 500);
		}

		try
		{
			$options = array_merge($options, array('w' => $this->write_concerns, 'j'=>$this->journal, 'multiple' => FALSE));
			$this->db->{$collection}->updateOne($this->wheres, $this->updates, $options);
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Update of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Update all
	* --------------------------------------------------------------------------------
	*
	* Updates a collection of documents
	*
	* @usage: $this->mongo_db->update_all('foo', $data = array());
	*/
	public function update_all($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to update", 500);
		}
		if (is_array($data) && count($data) > 0)
		{
			$this->updates = array_merge($data, $this->updates);
		}
		if (count($this->updates) == 0)
		{
			show_error("Nothing to update in Mongo collection or update is not an array", 500);
		}
		try
		{
			$options = array_merge($options, array('w' => $this->write_concerns, 'j'=>$this->journal, 'multiple' => TRUE));
			$this->db->{$collection}->update($this->wheres, $this->updates, $options);
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Update of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Delete
	* --------------------------------------------------------------------------------
	*
	* delete document from the passed collection based upon certain criteria
	*
	* @usage : $this->mongo_db->delete('foo');
	*/
	public function delete($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to delete from", 500);
		}
		try
		{
			$this->db->{$collection}->remove($this->wheres, array('w' => $this->write_concerns, 'j'=>$this->journal, 'justOne' => TRUE));
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Delete of data into MongoDB failed", 500);
			}

		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Delete all
	* --------------------------------------------------------------------------------
	*
	* Delete all documents from the passed collection based upon certain criteria
	*
	* @usage : $this->mongo_db->delete_all('foo', $data = array());
	*/
	public function delete_all($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection selected to delete from", 500);
		}
		/*if (isset($this->wheres['_id']) and ! is_object($this->wheres['_id']))
		{
			$this->wheres['_id'] = new MongoId($this->wheres['_id']);
		}*/
		try
		{
			$this->db->{$collection}->remove($this->wheres, array('w' => $this->write_concerns, 'j'=>$this->journal, 'justOne' => FALSE));
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Delete of data into MongoDB failed", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Aggregation Operation
	* --------------------------------------------------------------------------------
	*
	* Perform aggregation on mongodb collection
	*
	* @usage : $this->mongo_db->aggregate('foo', $ops = array());
	*/
	public function aggregate($collection, $operation)
	{
        if (empty($collection))
	 	{
	 		show_error("In order to retreive documents from MongoDB, a collection name must be passed", 500);
	 	}

 		if (empty($operation) && !is_array($operation))
	 	{
	 		show_error("Operation must be an array to perform aggregate.", 500);
	 	}

	 	try
	 	{
	 		$documents = $this->db->{$collection}->aggregate($operation);
	 		$this->_clear();
	 		if ($this->return_as == 'object')
			{
				return (object)$documents;
			}
			else
			{
				return $documents;
			}
	 	}
	 	catch (MongoResultException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Aggregation operation failed: {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Aggregation operation failed.", 500);
			}
		}
    }

	/**
	* --------------------------------------------------------------------------------
	* // Order by
	* --------------------------------------------------------------------------------
	*
	* Sort the documents based on the parameters passed. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->order_by(array('foo' => 'ASC'))->get('foobar');
	*/
	public function order_by($fields = array())
	{
		foreach ($fields as $col => $val)
		{
		if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$this->sorts[$col] = -1;
			}
			else
			{
				$this->sorts[$col] = 1;
			}
		}
		return ($this);
	}

	 /**
	* --------------------------------------------------------------------------------
	* Mongo Date
	* --------------------------------------------------------------------------------
	*
	* Create new MongoDate object from current time or pass timestamp to create
	* mongodate.
	*
	* @usage : $this->mongo_db->date($timestamp);
	*/
	public function date($stamp = FALSE)
	{
		if ( $stamp == FALSE )
		{
			return new MongoDate();
		}
		else
		{
			return new MongoDate($stamp);
		}

	}

	 /**
	* --------------------------------------------------------------------------------
	* Mongo Benchmark
	* --------------------------------------------------------------------------------
	*
	* Output all benchmark data for all performed queries.
	*
	* @usage : $this->mongo_db->output_benchmark();
	*/
	public function output_benchmark()
	{
		return $this->benchmark;
	}
	/**
	* --------------------------------------------------------------------------------
	* // Limit results
	* --------------------------------------------------------------------------------
	*
	* Limit the result set to $x number of documents
	*
	* @usage : $this->mongo_db->limit($x);
	*/
	public function limit($x = 99999)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->limit = (int) $x;
		}
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* // Offset
	* --------------------------------------------------------------------------------
	*
	* Offset the result set to skip $x number of documents
	*
	* @usage : $this->mongo_db->offset($x);
	*/
	public function offset($x = 0)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->offset = (int) $x;
		}
		return ($this);
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Add indexes
	* --------------------------------------------------------------------------------
	*
	* Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/
	public function add_index($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to add index to", 500);
		}

		if (empty($keys) || ! is_array($keys))
		{
			show_error("Index could not be created to MongoDB Collection because no keys were specified", 500);
		}

		foreach ($keys as $col => $val)
		{
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$keys[$col] = -1;
			}
			else
			{
				$keys[$col] = 1;
			}
		}
		try{
			$this->db->{$collection}->createIndex($keys, $options);
			$this->_clear();
			return ($this);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Creating Index failed : {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Creating Index failed.", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Remove index
	* --------------------------------------------------------------------------------
	*
	* Remove an index of the keys in a collection. To set values to descending order,
	* you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	* set to 1 (ASC).
	*
	* @usage : $this->mongo_db->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	*/
	public function remove_index($collection = "", $keys = array())
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to remove index from", 500);
		}

		if (empty($keys) || ! is_array($keys))
		{
			show_error("Index could not be removed from MongoDB Collection because no keys were specified", 500);
		}

		try
		{
			$this->db->{$collection}->deleteIndex($keys);
			$this->_clear();
			return ($this);
		}
		catch (MongoCursorException $e)
		{
			if(isset($this->debug) == TRUE && $this->debug == TRUE)
			{
				show_error("Creating Index failed : {$e->getMessage()}", 500);
			}
			else
			{
				show_error("Creating Index failed.", 500);
			}
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* List indexes
	* --------------------------------------------------------------------------------
	*
	* Lists all indexes in a collection.
	*
	* @usage : $this->mongo_db->list_indexes($collection);
	*/
	public function list_indexes($collection = "")
	{
		if (empty($collection))
		{
			show_error("No Mongo collection specified to remove all indexes from", 500);
		}
		return ($this->db->{$collection}->getIndexInfo());
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Switch database
	* --------------------------------------------------------------------------------
	*
	* Switch from default database to a different db
	*
	* $this->mongo_db->switch_db('foobar');
	*/
	public function switch_db($database = '')
	{
		if (empty($database))
		{
			show_error("To switch MongoDB databases, a new database name must be specified", 500);
		}

		$this->database = $database;

		try
		{
			$this->db = $this->connect->{$this->database};
			return (TRUE);
		}
		catch (Exception $e)
		{
			show_error("Unable to switch Mongo Databases: {$e->getMessage()}", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Drop database
	* --------------------------------------------------------------------------------
	*
	* Drop a Mongo database
	* @usage: $this->mongo_db->drop_db("foobar");
	*/
	public function drop_db($database = '')
	{
		if (empty($database))
		{
			show_error('Failed to drop MongoDB database because name is empty', 500);
		}

		try
		{
			$this->connect->{$database}->drop();
			return (TRUE);
		}
		catch (Exception $e)
		{
			show_error("Unable to drop Mongo database `{$database}`: {$e->getMessage()}", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* //! Drop collection
	* --------------------------------------------------------------------------------
	*
	* Drop a Mongo collection
	* @usage: $this->mongo_db->drop_collection('bar');
	*/
	public function drop_collection($col = '')
	{
		if (empty($col))
		{
			show_error('Failed to drop MongoDB collection because collection name is empty', 500);
		}

		try
		{
			$this->db->{$col}->drop();
			return TRUE;
		}
		catch (Exception $e)
		{
			show_error("Unable to drop Mongo collection `{$col}`: {$e->getMessage()}", 500);
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* _clear
	* --------------------------------------------------------------------------------
	*
	* Resets the class variables to default settings
	*/
	private function _clear()
	{
		$this->selects	= array();
		$this->updates	= array();
		$this->wheres	= array();
		$this->limit	= 999999;
		$this->offset	= 0;
		$this->sorts	= array();
	}

	/**
	* --------------------------------------------------------------------------------
	* Where initializer
	* --------------------------------------------------------------------------------
	*
	* Prepares parameters for insertion in $wheres array().
	*/
	private function _w($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}

	/**
	* --------------------------------------------------------------------------------
	* Update initializer
	* --------------------------------------------------------------------------------
	*
	* Prepares parameters for insertion in $updates array().
	*/
	private function _u($method)
	{
		if ( ! isset($this->updates[$method]))
		{
			$this->updates[ $method ] = array();
		}
	}

	private function explain($cursor, $collection, $aggregate=null)
	{
		array_push($this->benchmark,
			array(
					'benchmark'=>$cursor->explain(),
					'query'=> array(
							'collection'=>$collection,
							'select'=>$this->selects,
							'update'=>$this->updates,
							'where'=>$this->wheres,
							'sort'=>$this->sorts)
				)
		);
	}
}
