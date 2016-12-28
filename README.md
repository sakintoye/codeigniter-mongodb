#CodeIgniter-MongoDb

A high level library for querying MongoDb in Code Igniter. This library helps you perform active record queries on MongoDb, just like you would in Code Igniter.

#Installation and Configuration
Download the repo and unzip it. You will see 2 folders `config` and `libraries`. Paste those 2 folders in your CodeIgniter application folder.
After this, you will see mongo_db.php file in your application/config and application/libraries folder of CodeIgniter setup.
Now its time to configure the library and connect to MongoDb.
 1) Open config/mongo_db.php file and set MongoDb login details.
 2) Open config/autoload.php file and add 'mongo_db' in $autoload['libraries'] array.
That's it - installation and configuration completed. By default library connect to database provided in "default" group.
Now you can access the methods using $this->mongo_db-> in your controller as well as in model.


#Methods

##Insert Method
* `insert` Insert a new document into a collection
* `batch_insert` Insert multiple new documents into a collection

##Select Method
* `select` Get select fields from returned documents. Uses MongoDb's juicy projection method
* `where` OR `get_where` Where section of the query
* `where_in` Where something is in an array of something
* `where_in_all` Where something is in all of an array of * something
* `where_not_in` Where something is not in array of something
* `where_or` Where something is based on or
* `where_gt` Where something is greater than something
* `where_gte` Where something is greater than or equal to something
* `where_lt` Where something is less than something
* `where_lte` Where something is less than or equal to something
* `where_between` Where something is in between to something
* `where_between_ne` Where something is in between and but not equal to something
* `where_ne` Where something is not equal to something
* `like` Where something is search by like query
* `order_by` Order the results
* `limit` OR `offset` Limit the number of returned results
* `count` Document Count based on where query
* `distinct` Retrieve a list of distinct values for the given key across a single collection
* `find_one` Retrieve single document from collection
* `find` Retrieve all documents with matching conditions

##Update Method
* `set` Sets a field to a value
* `unset_field` Unsets a field
* `addtoset` Adds a value to an array if doesn't exist
* `push` Pushes a value into an array field
* `pop` Pops a value from an array field
* `pull` Removes an array by the value of a field
* `rename_field` Rename a field
* `inc` Increments the value of a field
* `mul` Multiple the value of a field
* `max` Updates the value of the field to a specified value if the specified value is greater than the current value of the field
* `min` Updates the value of the field to a specified value if the specified value is less than the current value of the field.
* `update` Update a single document in a collection
* `update_all` Update all documents in a collection

##Delete Method
* `delete` Delete a single document in a collection
* `delete_all` Delete all documents in a collection

##Aggregation Method
* `aggregate` Perform aggregation query on document

##Profiling Methods
* `output_benchmark` return complete explain data for all the find based query performed


##Index Method
* `add_index` Create a new index on collection
* `remove_index` Remove index from collection
* `list_indexes` Show all index created on collections

##Extra Helper
* `date` Create or convert date to MongoDb based Date

##License
Creative Commons Attribution 3.0 License.
Codes are provided AS IS basis, i am not responsible for anything.
