<?php

class PHP_Convert_DB
{
	/**
	* Connection to the database server.
	*/
	protected $dbh = null;
	
	/**
	* Copy parameters.
	*/
	protected $auto_redirect = false; // whether to enable automatic redirect
	protected $items_per_page = 5000; // items per page
	
	/**
	* Database configuration.
	*/
	protected $dbh_user = 'root';
	protected $dbh_pass = '';
	
	/**
	* Encoding for establishing a connection to the database.
	*/
	protected $dbh_names = 'utf8';
	
	/**
	* Encoding of data in the old database.
	*/
	protected $old_db_encoding = 'latin1';
	
	/**
	* Database names.
	*/
	protected $old_db_name = 'dbname';
	protected $new_db_name = 'dbname_unicode';
	
	/**
	* Automatically populated table list.
	*/
	protected $table_list = array();
	
	/**
	* Automatically populated table name.
	*/
	protected $table_name = '';
	
	/**
	* Fields that should be treated as binary despite the field type in the database.
	*/
	protected $binary_text_fields = array(
		// 'table1' => array('field1', 'field2'),
	);
	
	/**
	* Constructor.
	*/
	public function __construct()
	{
		// initialize connection to the new database
		$this->dbh = new PDO('mysql:host=localhost;dbname=' . $this->new_db_name, $this->dbh_user, $this->dbh_pass);
		$this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH);
		$this->dbh->query("SET NAMES '" . $this->dbh_names . "'");
		
		// initialize a list of all tables
		$sql = "SHOW TABLES";
		$tables = $this->dbh->query($sql);
		while ($table = $tables->fetch())
		{
			$this->table_list[] = $table[0];
		}
		
		// sanitize table name
		if (isset($_REQUEST['t_name']) && in_array($_REQUEST['t_name'], $this->table_list))
		{
			$this->table_name = $_REQUEST['t_name'];
		}
		else
		{
			$this->table_name = $this->table_list[0];
		}
		
		// determine what the next page is
		$page_index = array_search($this->table_name, $this->table_list);
		if (isset($this->table_list[$page_index + 1]))
		{
			$this->next_page = 'index.php?t_name=' . $this->table_list[$page_index + 1];
		}
		else
		{
			$this->next_page = 'message.php?v=all_done';
		}
	}

	/**
	* Automatically imports a table.
	*/
	public function auto_migrate()
	{
		// set up environment
		$this->dbh->exec("SET FOREIGN_KEY_CHECKS = 0");
		$this->dbh->exec("SET UNIQUE_CHECKS = 0");
		
		// truncate table before importing the first page
		$this->dbh->exec("TRUNCATE TABLE `" . $this->new_db_name . "`.`" . $this->table_name . "`");
		
		// set up data transfer
		$handle = $this->dbh->query("SHOW COLUMNS FROM `" . $this->new_db_name . "`.`" . $this->table_name . "`");
		$keys = array();
		$fields = array();
		
		while ($row = $handle->fetch())
		{
			$keys[] = $row['Field'];
			$is_text = ((strpos($row['Type'], 'char') !== false) || (strpos($row['Type'], 'text') !== false));
			$is_binary_exception = (isset($this->binary_text_fields[$this->table_name]) && in_array($row['Field'], $this->binary_text_fields[$this->table_name]));
			if ($is_text && !$is_binary_exception)
			{
				$fields[] = "IF(NOT ISNULL(src.`" . $row['Field'] . "`), CONVERT(CONVERT(src.`" . $row['Field'] . "` USING binary) USING " . $this->old_db_encoding . "), NULL)";
			}
			else
			{
				$fields[] = 'src.`' . $row['Field'] . '`';
			}
		}
		
		$columns = '(`' . implode('`, `', $keys) . '`)';
		$fields = implode(', ', $fields);

		// set up loop
		$this->dbh->beginTransaction();
		
		// initialize page
		$page = 1;		
		
		// migrate data
		while (true)
		{
			// get start count and end count
			$start_count = ($page - 1) * $this->items_per_page + 1;
			$end_count = $start_count + $this->items_per_page - 1;						
			
			print "Executing insertion on rows $start_count to $end_count...<br />";
			
			// get limit and offset
			list($limit, $offset) = $this->get_limit_offset($page);
			
			// perform paginated direct copy
			$handle = $this->dbh->query("
				INSERT INTO `" . $this->new_db_name . "`.`" . $this->table_name . "` $columns
				(
					SELECT $fields FROM `" . $this->old_db_name . "`.`" . $this->table_name . "` src 
					LIMIT $limit OFFSET $offset
				)
			");

			$inserted_rows = $handle->rowCount();				
			if ($inserted_rows > 0)
			{
				print 'Done!<br />';
											
				if ($page % 5 == 0)
				{
					$this->dbh->commit();
					sleep(5);
					$this->dbh->beginTransaction();
				}
				
				$page++;
			}
			else
			{
				print 'There were no actual records to be copied over!<br />';
				
				break;
			}
		}
		
		// finalize loop
		$this->dbh->commit();
		
		// reset environment
		$this->dbh->exec("SET FOREIGN_KEY_CHECKS = 1");		
		$this->dbh->exec("SET UNIQUE_CHECKS = 1");
		
		// print next page html
		$this->print_next_page_html();
	}
	
	/**
	* Gets limit and offset variables.
	*/
	protected function get_limit_offset($page)
	{
		// set limit and offset
		$limit = $this->items_per_page;
		$offset = ($page - 1) * $this->items_per_page;
		
		// return data
		return array($limit, $offset);
	}
	
	/**
	* Prints HTML to redirect to (or link to) the next page.
	*/
	protected function print_next_page_html()
	{
		if ($this->auto_redirect)
		{
			print 'Redirecting to <a href="' . $this->next_page . '">next page</a>...<br />';
			print '<meta http-equiv="refresh" content="0; url=' . $this->next_page . '">';
		}
		else
		{
			print 'Click <a href="' . $this->next_page . '">here</a> to go to next page.<br />';
		}
	}
}

?>