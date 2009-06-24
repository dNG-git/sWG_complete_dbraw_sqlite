<?php
//j// BOF

/*n// NOTE
----------------------------------------------------------------------------
secured WebGine
net-based application engine
----------------------------------------------------------------------------
(C) direct Netware Group - All rights reserved
http://www.direct-netware.de/redirect.php?swg

This work is distributed under the W3C (R) Software License, but without any
warranty; without even the implied warranty of merchantability or fitness
for a particular purpose.
----------------------------------------------------------------------------
http://www.direct-netware.de/redirect.php?licenses;w3c
----------------------------------------------------------------------------
$Id: swg_dbraw_pdo_sqlite.php,v 1.1 2008/12/25 18:27:18 s4u Exp $
#echo(sWGdbrawSqliteVersion)#
sWG/#echo(__FILEPATH__)#
----------------------------------------------------------------------------
NOTE_END //n*/
/**
* We need a unified interface for communication with SQL-compatible database
* servers. This one is designed for the PHP Data Objects (PDO) interface for
* SQLite.
*
* @internal   We are using phpDocumentor to automate the documentation process
*             for creating the Developer's Manual. All sections including
*             these special comments will be removed from the release source
*             code.
*             Use the following line to ensure 76 character sizes:
* ----------------------------------------------------------------------------
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    sWG
* @subpackage db
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;w3c
*             W3C (R) Software License
*/

/* -------------------------------------------------------------------------
All comments will be removed in the "production" packages (they will be in
all development packets)
------------------------------------------------------------------------- */

//j// Functions and classes

/* -------------------------------------------------------------------------
Testing for required classes
------------------------------------------------------------------------- */

$g_continue_check = class_exists ("PDO");
if (defined ("CLASS_direct_dbraw_pdo_sqlite")) { $g_continue_check = false; }
if (!defined ("CLASS_direct_db")) { $g_continue_check = false; }

if ($g_continue_check)
{
//c// direct_dbraw_pdo_sqlite
/**
* This class has been designed to be used with a SQLite database.
*
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    sWG
* @subpackage db
* @uses       CLASS_direct_virtual_class
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;w3c
*             W3C (R) Software License
*/
class direct_dbraw_pdo_sqlite extends direct_virtual_class
{
/**
	* @var string $query_cache This variable saves a built SQL query
*/
	protected $query_cache;
/**
	* @var array $pdo PHP Data Objects (PDO) constants array
*/
	protected $pdo;
/**
	* @var resource $resource Resource handle
*/
	protected $resource;
/**
	* @var integer $transactions Number of active transactions
*/
	protected $transactions;

/* -------------------------------------------------------------------------
Extend the class
------------------------------------------------------------------------- */

	//f// direct_dbraw_pdo_sqlite->__construct ()
/**
	* Constructor (PHP5) __construct (direct_dbraw_pdo_sqlite)
	*
	* @uses  direct_basic_functions::include_file()
	* @uses  direct_debug()
	* @uses  USE_debug_reporting
	* @since v0.1.00
*/
	public function __construct ()
	{
		global $direct_classes,$direct_settings;
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->__construct (direct_dbraw_pdo_sqlite)- (#echo(__LINE__)#)"); }

		if (isset ($direct_settings['db_synchronisation']))
		{
			if (!isset ($direct_settings['db_synchronisation_mantrig'])) { $direct_settings['db_synchronisation_mantrig'] = true; }

			if (($direct_settings['db_synchronisation'])&&($direct_settings['db_synchronisation_mantrig']))
			{
				if (!$direct_classes['basic_functions']->include_file ($direct_settings['path_system']."/functions/swg_dbsync.php",1)) { trigger_error ("sWG/#echo(__FILEPATH__)# _dbraw_pdo_sqlite_ (#echo(__LINE__)#) reporting: Manual synchronisation trigger unavailable",E_USER_WARNING); }
			}
		}

/* -------------------------------------------------------------------------
My parent should be on my side to get the work done
------------------------------------------------------------------------- */

		parent::__construct ();

/* -------------------------------------------------------------------------
Informing the system about available functions 
------------------------------------------------------------------------- */

		$this->functions['connect'] = true;
		$this->functions['disconnect'] = true;
		$this->functions['query_build'] = true;
		$this->functions['query_build_attributes'] = true;
		$this->functions['query_build_ordering'] = true;
		$this->functions['query_build_row_conditions_walker'] = true;
		$this->functions['query_build_search_conditions'] = true;
		$this->functions['query_build_set_attributes'] = true;
		$this->functions['query_build_values'] = true;
		$this->functions['query_build_values_keys'] = true;
		$this->functions['query_exec'] = true;
		$this->functions['optimize'] = true;
		$this->functions['secure'] = true;
		$this->functions['transaction_begin'] = true;
		$this->functions['transaction_commit'] = true;
		$this->functions['transaction_rollback'] = true;

/* -------------------------------------------------------------------------
Set up some variables
------------------------------------------------------------------------- */

		$this->query_cache = "";
		$this->resource = NULL;

/* -------------------------------------------------------------------------
Set up PDO constants
------------------------------------------------------------------------- */

		if (defined ("PDO_ATTR_PERSISTENT"))
		{
$this->pdo = array (
"errormode" => PDO_ATTR_ERRMODE,
"errormode_silent" => PDO_ERRMODE_SILENT,
"fetch_assoc" => PDO_FETCH_ASSOC,
"fetch_numbered" => PDO_FETCH_NUM,
"persistent" => PDO_ATTR_PERSISTENT,
"timeout" => PDO_ATTR_TIMEOUT
);
		}
		else
		{
$this->pdo = array (
"errormode" => PDO::ATTR_ERRMODE,
"errormode_silent" => PDO::ERRMODE_SILENT,
"fetch_assoc" => PDO::FETCH_NAMED,
"fetch_numbered" => PDO::FETCH_NUM,
"persistent" => PDO::ATTR_PERSISTENT,
"timeout" => PDO::ATTR_TIMEOUT
);
		}
	}
	//f// direct_dbraw_pdo_sqlite->__destruct ()
/**
	* Destructor (PHP5) __destruct (direct_dbraw_pdo_sqlite)
	*
	* @since v0.1.00
*/
	public function __destruct () { $this->disconnect (); }

	//f// direct_dbraw_pdo_sqlite->connect ()
/**
	* Opens the connection to a database server and selects a database.
	*
	* @uses   direct_dbraw_pdo_sqlite::secure()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function connect ()
	{
		global $direct_settings;
		if (USE_debug_reporting) { direct_debug (3,"sWG/#echo(__FILEPATH__)# -db_class->connect ()- (#echo(__LINE__)#)"); }

		$f_return = false;

		if (is_object (($this->resource))) { $f_return = true; }
		elseif ((class_exists ("PDO"))&&(strlen ($direct_settings['db_dsa'])))
		{
			$f_error = "An unknown error occured";

			try
			{
				if ($direct_settings['db_peristent']) { $this->resource = new PDO ($direct_settings['db_dsa'],"","",(array ($this->pdo['persistent'] => true,$this->pdo['errormode'] => $this->pdo['errormode_silent'],$this->pdo['timeout'] => $direct_settings['timeout_core']))); }
				else { $this->resource = new PDO ($direct_settings['db_dsa'],"","",(array ($this->pdo['persistent'] => false,$this->pdo['errormode'] => $this->pdo['errormode_silent'],$this->pdo['timeout'] => $direct_settings['timeout_core']))); }
			}
			catch (Exception $f_handled_exception) { $f_error = $f_handled_exception->getMessage (); }

			if (is_object ($this->resource)) { $f_return = true; }
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->connect ()- (#echo(__LINE__)#) reporting: ".$f_error,E_USER_ERROR); }
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->connect ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->disconnect ()
/**
	* Closes an active database connection to the server.
	*
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function disconnect ()
	{
		global $direct_settings;
		if (USE_debug_reporting) { direct_debug (3,"sWG/#echo(__FILEPATH__)# -db_class->disconnect ()- (#echo(__LINE__)#)"); }

		$f_return = false;

		if (is_object ($this->resource))
		{
			if (!$direct_settings['db_peristent']) { $this->resource = NULL; }
			$f_return = true;
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->disconnect ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build ($f_data)
/**
	* Builds a valid SQL query for SQLite and executes it.
	*
	* @param  array $f_data Array containing query specific information.
	* @uses   direct_dbraw_pdo_sqlite::query_build_attributes()
	* @uses   direct_dbraw_pdo_sqlite::query_build_row_conditions_walker()
	* @uses   direct_dbraw_pdo_sqlite::query_build_search_conditions()
	* @uses   direct_dbraw_pdo_sqlite::query_build_set_attributes()
	* @uses   direct_dbraw_pdo_sqlite::query_build_ordering()
	* @uses   direct_dbraw_pdo_sqlite::query_build_values()
	* @uses   direct_dbraw_pdo_sqlite::query_exec()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return mixed Result returned by the server in the specified format
	* @since  v0.1.00
*/
	public function query_build ($f_data)
	{
		global $direct_classes;
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build (+f_data)- (#echo(__LINE__)#)"); }

		$f_return = false;

		if (is_object ($this->resource))
		{
			$f_continue_check = true;

			switch ($f_data['type'])
			{
			case "delete":
			{
				if (empty ($f_data['table'])) { $f_continue_check = false; }

				$this->query_cache = "DELETE FROM ";
				break 1;
			}
			case "insert":
			{
				if ((empty ($f_data['set_attributes']))&&(empty ($f_data['values']))) { $f_continue_check = false; }
				if (empty ($f_data['table'])) { $f_continue_check = false; }

				$this->query_cache = "INSERT INTO ";
				break 1;
			}
			case "replace":
			{
				if (empty ($f_data['table'])) { $f_continue_check = false; }
				if (empty ($f_data['values'])) { $f_continue_check = false; }

				$this->query_cache = "REPLACE INTO ";
				break 1;
			}
			case "select":
			{
				if (empty ($f_data['attributes'])) { $f_continue_check = false; }
				if (empty ($f_data['table'])) { $f_continue_check = false; }

				$this->query_cache = "SELECT ";
				break 1;
			}
			case "update":
			{
				if ((empty ($f_data['set_attributes']))&&(empty ($f_data['values']))) { $f_continue_check = false; }
				if (empty ($f_data['table'])) { $f_continue_check = false; }

				$this->query_cache = "UPDATE ";
				break 1;
			}
			default: { $f_continue_check = false; }
			}

			if ($f_continue_check)
			{
				if ($f_data['type'] == "select")
				{
					if (!empty ($f_data['attributes'])) { $this->query_cache .= ($this->query_build_attributes ($f_data['attributes']))." FROM "; }
				}

				$this->query_cache .= $f_data['table'];

				if ($f_data['type'] == "select")
				{
					if (!empty ($f_data['joins']))
					{
						foreach ($f_data['joins'] as $f_join_array)
						{
							switch ($f_join_array['type'])
							{
							case "cross-join":
							{
								$this->query_cache .= " CROSS JOIN ".$f_join_array['table'];
								break 1;
							}
							case "inner-join":
							{
								$this->query_cache .= " INNER JOIN {$f_join_array['table']} ON ";
								break 1;
							}
							case "left-outer-join":
							{
								$this->query_cache .= " LEFT OUTER JOIN {$f_join_array['table']} ON ";
								break 1;
							}
							case "natural-join":
							{
								$this->query_cache .= " NATURAL JOIN ".$f_join_array['table'];
								break 1;
							}
							case "right-outer-join":
							{
								$this->query_cache .= " RIGHT OUTER JOIN {$f_join_array['table']} ON ";
								break 1;
							}
							}

							if (!empty ($f_join_array['requirements']))
							{
								$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_join_array['requirements'],true,false);
								if (isset ($f_xml_node_array['sqlconditions'])) { $this->query_cache .= $this->query_build_row_conditions_walker ($f_xml_node_array['sqlconditions']); }
							}
						}
					}
				}

				if (($f_data['type'] == "insert")||($f_data['type'] == "replace")||($f_data['type'] == "update"))
				{
					if ($f_data['set_attributes'])
					{
						$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_data['set_attributes'],true,false);

						if (isset ($f_xml_node_array['sqlvalues']))
						{
							if ($f_data['type'] == "update") { $this->query_cache .= " SET ".($this->query_build_set_attributes ($f_xml_node_array['sqlvalues'])); }
							else { $this->query_cache .= " ".($this->query_build_values_keys ($f_xml_node_array['sqlvalues'])); }
						}
					}
				}

				if (($f_data['type'] == "delete")||($f_data['type'] == "select")||($f_data['type'] == "update"))
				{
					$f_where_defined = false;

					if ($f_data['row_conditions'])
					{
						$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_data['row_conditions'],true,false);

						if (isset ($f_xml_node_array['sqlconditions']))
						{
							$f_where_defined = true;
							$this->query_cache .= " WHERE ".($this->query_build_row_conditions_walker ($f_xml_node_array['sqlconditions']));
						}
					}

					if ($f_data['type'] == "select")
					{
						if ($f_data['search_conditions'])
						{
							$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_data['search_conditions'],true,false);

							if (isset ($f_xml_node_array['sqlconditions']))
							{
								if ($f_where_defined) { $this->query_cache .= " AND (".($this->query_build_search_conditions ($f_xml_node_array['sqlconditions'])).")"; }
								else { $this->query_cache .= " WHERE ".($this->query_build_search_conditions ($f_xml_node_array['sqlconditions'])); }
							}
						}

						if (!empty ($f_data['grouping'])) { $this->query_cache .= " GROUP BY ".(implode (",",$f_data['grouping'])); }
					}
				}

				if ($f_data['type'] == "select")
				{
					if ($f_data['ordering'])
					{
						$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_data['ordering'],true,false);
						if (isset ($f_xml_node_array['sqlordering'])) { $this->query_cache .= " ORDER BY ".($this->query_build_ordering ($f_xml_node_array['sqlordering'])); }
					}
				}

				if (($f_data['type'] == "insert")||($f_data['type'] == "replace"))
				{
					if ($f_data['values_keys'])
					{
						if (is_array ($f_data['values_keys']))
						{
							$f_values_keys = "";

							foreach ($f_data['values_keys'] as $f_values_key)
							{
								if ($f_values_keys) { $f_values_keys .= ","; }
								$f_values_keys .= preg_replace ("#^(.*?)\.(\w+)$#","\\2",$f_values_key);
							}

							$this->query_cache .= " ($f_values_keys)";
						}
					}

					if ($f_data['values'])
					{
						$f_xml_node_array = $direct_classes['xml_bridge']->xml2array ($f_data['values'],true,false);
						if (isset ($f_xml_node_array['sqlvalues'])) { $this->query_cache .= " VALUES ".($this->query_build_values ($f_xml_node_array['sqlvalues'])); }
					}
				}

				if ($f_data['type'] == "select")
				{
					if ($f_data['limit']) { $this->query_cache .= " LIMIT ".$f_data['limit']; }
					if ($f_data['offset']) { $this->query_cache .= " OFFSET ".$f_data['offset']; }
				}

				if ($f_data['answer'] == "sql") { $f_return = $this->query_cache; }
				else { $f_return = $this->query_exec ($f_data['answer'],$this->query_cache); }
			}
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->query_build ()- (#echo(__LINE__)#) reporting: Required definition elements are missing",E_USER_WARNING); }
		}
		else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->query_build ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_attributes ($f_attributes_array)
/**
	* Builds the SQL attributes list of a query.
	*
	* @param  array $f_attributes_array Array of attributes
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Attributes list with translated function names
	* @since  v0.1.00
*/
	protected function query_build_attributes ($f_attributes_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_attributes (+f_attributes_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if ((is_array ($f_attributes_array))&&(!empty ($f_attributes_array)))
		{
			foreach ($f_attributes_array as $f_attribute)
			{
				if ($f_return) { $f_return .= ", "; }

				if (preg_match ("#^(.*?)\((.*?)\)(.*?)$#",$f_attribute,$f_result_array))
				{
					switch ($f_result_array[1])
					{
					case ($f_result_array[1] == "count-rows"):
					{
						$f_return .= "COUNT({$f_result_array[2]}){$f_result_array[3]}";
						break 1;
					}
					case ($f_result_array[1] == "sum-rows"):
					{
						$f_return .= "SUM({$f_result_array[2]}){$f_result_array[3]}";
						break 1;
					}
					default: { $f_return .= $f_result_array[1]."({$f_result_array[2]}){$f_result_array[3]}"; }
					}
				}
				else { $f_return .= $f_attribute; }
			}
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_attributes ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_ordering ($f_ordering_list)
/**
	* Builds the SQL ORDER BY part of a query.
	*
	* @param  array $f_ordering_list ORDER BY list given as a XML array tree
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Valid SQL ORDER BY definition
	* @since  v0.1.00
*/
	protected function query_build_ordering ($f_ordering_list)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_ordering (+f_ordering_list)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_ordering_list))
		{
			if (isset ($f_ordering_list['xml.item'])) { unset ($f_ordering_list['xml.item']); }

			foreach ($f_ordering_list as $f_ordering_array)
			{
				if ($f_return) { $f_return .= ", "; }

				if ($f_ordering_array['attributes']['type'] == "desc") { $f_return .= $f_ordering_array['attributes']['attribute']." DESC"; }
				else { $f_return .= $f_ordering_array['attributes']['attribute']." ASC"; }
			}
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_ordering ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_row_conditions_walker ($f_requirements_array)
/**
	* Creates a WHERE string including sublevel conditions.
	*
	* @param  array $f_requirements_array WHERE definitions given as a XML array tree
	* @uses   direct_dbraw_pdo_sqlite::query_build_row_conditions_walker()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Valid SQL WHERE definition
	* @since  v0.1.00
*/
	protected function query_build_row_conditions_walker ($f_requirements_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_row_conditions_walker (+f_requirements_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_requirements_array))
		{
			if (isset ($f_requirements_array['xml.item'])) { unset ($f_requirements_array['xml.item']); }

			foreach ($f_requirements_array as $f_requirement_array)
			{
				if (is_array ($f_requirement_array))
				{
					if (isset ($f_requirement_array['xml.item']))
					{
						if ($f_return)
						{
							if ((isset ($f_requirement_array['xml.item']['attributes']['condition']))&&($f_requirement_array['xml.item']['attributes']['condition'] == "or")) { $f_return .= " OR "; }
							else { $f_return .= " AND "; }
						}

						if (count ($f_requirement_array) > 2) { $f_return .= "(".($this->query_build_row_conditions_walker ($f_requirement_array)).")"; }
						else { $f_return .= $this->query_build_row_conditions_walker ($f_requirement_array); }
					}
					elseif ($f_requirement_array['value'] != "*")
					{
						if (!isset ($f_requirement_array['attributes']['type'])) { $f_requirement_array['attributes']['type'] = "string"; }

						if ($f_return)
						{
							if ((isset ($f_requirement_array['attributes']['condition']))&&($f_requirement_array['attributes']['condition'] == "or")) { $f_return .= " OR "; }
							else { $f_return .= " AND "; }
						}

						if (!isset ($f_requirement_array['attributes']['operator'])) { $f_requirement_array['attributes']['operator'] = ""; }

						switch ($f_requirement_array['attributes']['operator'])
						{
						case "!=": { break 1; }
						case "<": { break 1; }
						case "<=": { break 1; }
						case ">": { break 1; }
						case ">=": { break 1; }
						default: { $f_requirement_array['attributes']['operator'] = "="; }
						}

						$f_return .= $f_requirement_array['attributes']['attribute'];

						if (isset ($f_requirement_array['attributes']['null'])) { $f_return .= " {$f_requirement_array['attributes']['operator']} NULL"; }
						elseif (($f_requirement_array['attributes']['type'] == "string")&&($f_requirement_array['value'][0] != "'")) { $f_return .= " {$f_requirement_array['attributes']['operator']} '{$f_requirement_array['value']}'"; }
						elseif (strlen ($f_requirement_array['value'])) { $f_return .= " {$f_requirement_array['attributes']['operator']} ".$f_requirement_array['value']; }
						else { $f_return .= " {$f_requirement_array['attributes']['operator']} NULL"; }
					}
				}
			}
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_row_conditions_walker ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_search_conditions ($f_conditions_array)
/**
	* Creates search requests
	*
	* @param  array $f_conditions_array WHERE definitions given as a XML array tree
	* @uses   direct_dbraw_pdo_sqlite::secure()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Valid SQL WHERE definition
	* @since  v0.1.00
*/
	protected function query_build_search_conditions ($f_conditions_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_search_conditions (+f_conditions_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_conditions_array))
		{
			$f_attributes_array = array ();
			$f_search_advanced = false;
			$f_search_term = "";

			if (isset ($f_conditions_array['xml.item'])) { unset ($f_conditions_array['xml.item']); }

			foreach ($f_conditions_array as $f_condition_name => $f_condition_array)
			{
				if (($f_condition_name == "attribute")&&(isset ($f_condition_array['xml.mtree'])))
				{
					foreach ($f_condition_array as $f_condition_attribute_array)
					{
						if (isset ($f_condition_attribute_array['value'])) { $f_attributes_array[] = $f_condition_attribute_array['value']; }
					}
				}
				elseif (($f_condition_name == "searchterm")&&(isset ($f_condition_array['xml.mtree'])))
				{
					foreach ($f_condition_array as $f_condition_searchterm_array)
					{
						if ((isset ($f_condition_searchterm_array['value']))&&(strlen ($f_condition_searchterm_array['value']))) { $f_search_term .= $f_condition_searchterm_array['value']; }
					}
				}
				elseif (isset ($f_condition_array['tag']))
				{
					switch ($f_condition_array['tag'])
					{
					case "attribute":
					{
						$f_attributes_array[] = $f_condition_array['value'];
						break 1;
					}
					case "searchterm":
					{
						if (strlen ($f_condition_array['value'])) { $f_search_term .= $f_condition_array['value']; }
						break 1;
					}
					}
				}
			}

			if ((!empty ($f_attributes_array))&&(strlen ($f_search_term)))
			{
/* -------------------------------------------------------------------------
We will split on spaces to get valid LIKE expressions for each word.
"Test Test1 AND Test2 AND Test3 Test4 Test5 Test6 AND Test7" should be
Result is: [0] => %Test% [1] => %Test1 Test2 Test3% [2] => %Test4%
           [3] => %Test5% [4] => %Test6 Test7%
------------------------------------------------------------------------- */

				$f_search_term = preg_replace ("#^\*#m","%",$f_search_term);
				$f_search_term = preg_replace ("#(\w)\*#","\\1%",$f_search_term);
				$f_search_term = str_replace (" OR "," ",$f_search_term);
				$f_search_term = str_replace (" NOT "," ",$f_search_term);
				$f_search_term = str_replace ("HIGH ","",$f_search_term);
				$f_search_term = str_replace ("LOW ","",$f_search_term);

				$f_words = explode (" ",$f_search_term);
				$f_and_check = false;
				$f_search_term_array = array ();
				$f_single_check = false;
				$f_word_buffer = "";

				foreach ($f_words as $f_word)
				{
					if ($f_word == "AND")
					{
						$f_single_check = true;
						$f_and_check = true;
						$f_word_buffer .= " ";
					}
					elseif ($f_single_check)
					{
						$f_single_check = false;
						$f_word_buffer .= $f_word;
					}
					else
					{
						if ($f_and_check)
						{
							$f_and_check = false;
							$f_word_buffer = str_replace ("%%","%","%".(trim ($f_word_buffer))."%");
							$f_search_term_array[] = $f_word_buffer;
							$f_word_buffer = $f_word;
						}
						else
						{
							if ($f_word_buffer)
							{
								$f_word_buffer = str_replace ("%%","%","%".(trim ($f_word_buffer))."%");
								$f_search_term_array[] = $f_word_buffer;
							}

							$f_word_buffer = $f_word;
						}
					}
				}

/* -------------------------------------------------------------------------
Don't forget to check the buffer $f_word_buffer
------------------------------------------------------------------------- */

				if ($f_word_buffer)
				{
					$f_word_buffer = str_replace ("%%","%","%".(trim ($f_word_buffer))."%");
					$f_search_term_array[] = $f_word_buffer;
				}

				foreach ($f_attributes_array as $f_attribute)
				{
					foreach ($f_search_term_array as $f_search_term)
					{
						if ($f_return) { $f_return .= " OR "; }

						$this->secure ($f_search_term);
						$f_return .= $f_attribute." LIKE $f_search_term";
					}
				}
			}
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_search_conditions ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_set_attributes ($f_attributes_array)
/**
	* Builds the SQL attributes and values list for UPDATE.
	*
	* @param  array $f_attributes_array Attributes given as a XML array tree
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Attributes list with translated function names
	* @since  v0.1.00
*/
	protected function query_build_set_attributes ($f_attributes_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_set_attributes (+f_attributes_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_attributes_array))
		{
			if (isset ($f_attributes_array['xml.item'])) { unset ($f_attributes_array['xml.item']); }

			foreach ($f_attributes_array as $f_attribute_array)
			{
				if ($f_return) { $f_return .= ", "; }
				$f_return .= (preg_replace ("#^(.*?)\.(\w+)$#","\\2",$f_attribute_array['attributes']['attribute']))."=";

				if (isset ($f_attribute_array['attributes']['null'])) { $f_return .= "NULL"; }
				elseif (($f_attribute_array['attributes']['type'] == "string")&&($f_attribute_array['value'][0] != "'")) { $f_return .= "'{$f_attribute_array['value']}'"; }
				else
				{
					if (strlen ($f_attribute_array['value'])) { $f_return .= $f_attribute_array['value']; }
					else { $f_return .= "NULL"; }
				}
			}
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_set_attributes ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_values ($f_values_array)
/**
	* Builds the SQL VALUES part of a query.
	*
	* @param  array $f_values_array WHERE definitions given as a XML array tree
	* @uses   direct_dbraw_pdo_sqlite::query_build_values()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Valid SQL VALUES definition
	* @since  v0.1.00
*/
	protected function query_build_values ($f_values_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_value (+f_values_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_values_array))
		{
			if (isset ($f_values_array['xml.item'])) { unset ($f_values_array['xml.item']); }
			$f_bracket_check = false;

			foreach ($f_values_array as $f_value_array)
			{
				if (isset ($f_value_array['xml.item']))
				{
					if ($f_return) { $f_return .= ","; }
					$f_return .= $this->query_build_values ($f_value_array);
				}
				else
				{
					$f_bracket_check = true;

					if ($f_return) { $f_return .= ","; }
					else { $f_return .= "("; }

					if (isset ($f_value_array['attributes']['null'])) { $f_return .= "NULL"; }
					elseif (($f_value_array['attributes']['type'] == "string")&&($f_value_array['value'][0] != "'")) { $f_return .= "'{$f_value_array['value']}'"; }
					else
					{
						if (strlen ($f_value_array['value'])) { $f_return .= $f_value_array['value']; }
						else { $f_return .= "NULL"; }
					}
				}
			}

			if ($f_bracket_check) { $f_return .= ")"; }
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_value ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_build_values_keys ($f_attributes_array)
/**
	* Builds the SQL attributes and values list for INSERT.
	*
	* @param  array $f_attributes_array Attributes given as a XML array tree
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return string Attributes list with translated function names
	* @since  v0.1.00
*/
	protected function query_build_values_keys ($f_attributes_array)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->query_build_values_keys (+f_attributes_array)- (#echo(__LINE__)#)"); }
		$f_return = "";

		if (is_array ($f_attributes_array))
		{
			if (isset ($f_attributes_array['xml.item'])) { unset ($f_attributes_array['xml.item']); }
			$f_keys = array ();
			$f_values = array ();

			foreach ($f_attributes_array as $f_attribute_array)
			{
				$f_keys[] = preg_replace ("#^(.*?)\.(\w+)$#","\\2",$f_attribute_array['attributes']['attribute']);

				if (isset ($f_attribute_array['attributes']['null'])) { $f_values[] = "NULL"; }
				elseif (($f_attribute_array['attributes']['type'] == "string")&&($f_attribute_array['value'][0] != "'")) { $f_values[] = "'{$f_attribute_array['value']}'"; }
				else
				{
					if (strlen ($f_attribute_array['value'])) { $f_values[] = $f_attribute_array['value']; }
					else { $f_values[] = "NULL"; }
				}
			}

			$f_return = "(".(implode (",",$f_keys)).") VALUES (".(implode (",",$f_values)).")";
		}

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_build_values_keys ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->query_exec ($f_answer,$f_query)
/**
	* Transmits an SQL query and returns the result in a developer specified
	* format via $f_answer.
	*
	* @param  string $f_answer Defines the requested type that should be returned
    *         The following types are supported: "ar", "co", "ma", "ms", "nr",
    *         "sa" or "ss".
	* @param  string $f_query Valid SQL query
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return mixed Result returned by the server in the specified format
	* @since  v0.1.00
*/
	public function query_exec ($f_answer,$f_query)
	{
		if (USE_debug_reporting) { direct_debug (3,"sWG/#echo(__FILEPATH__)# -db_class->query_exec ($f_answer,$f_query)- (#echo(__LINE__)#)"); }
		$f_return = false;

		if (is_object ($this->resource))
		{
			if ($f_answer == "ar") { $f_return = $this->resource->exec ($f_query); }
			else
			{
				$f_result_object = $this->resource->query ($f_query);

				if (($f_answer == "co")||(!$f_result_object)||(!is_object ($f_result_object)))
				{
					if ($this->resource->errorCode () == "00000") { $f_return = true; }
					else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->query_exec ()- (#echo(__LINE__)#) reporting: ANSI ".($this->resource->errorCode ())." - ".serialize ($this->resource->errorInfo ()),E_USER_ERROR); }
				}
				elseif ($f_answer == "ma")
				{
					$f_return = array ();

					while ($f_row_array = $f_result_object->fetch ($this->pdo['fetch_assoc']))
					{
						$f_filtered_row_array = array ();

						foreach ($f_row_array as $f_column => $f_column_data)
						{
							if (is_numeric ($f_column_data)) { $f_filtered_row_array[$f_column] = (0 + $f_column_data); }
							else { $f_filtered_row_array[$f_column] = $f_column_data; }
						}

						$f_return[] = $f_filtered_row_array;
					}
				}
				elseif ($f_answer == "ms")
				{
					$f_return = array ();

					while ($f_row_array = $f_result_object->fetch ($this->pdo['fetch_numbered']))
					{
						if (!empty ($f_row_array)) { $f_return[] = implode ("\n",$f_row_array); }
					}
				}
				elseif ($f_answer == "nr") { $f_return = $f_result_object->rowCount (); }
				elseif ($f_answer == "sa")
				{
					$f_return = array ();
					$f_row_array = $f_result_object->fetch ($this->pdo['fetch_assoc']);

					if ($f_row_array)
					{
						foreach ($f_row_array as $f_column => $f_column_data)
						{
							if (is_numeric ($f_column_data)) { $f_return[$f_column] = (0 + $f_column_data); }
							else { $f_return[$f_column] = $f_column_data; }
						}
					}
				}
				elseif ($f_answer == "ss")
				{
					$f_row_array = $f_result_object->fetch ($this->pdo['fetch_numbered']);

					if (empty ($f_row_array)) { $f_return = ""; }
					else { $f_return = implode ("\n",$f_row_array); }
				}
				else { $f_return = false; }

				if (is_object ($f_result_object)) { $f_result_object->closeCursor (); }
			}
		}
		else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->query_exec ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->query_exec ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->optimize ($f_table)
/**
	* Optimizes a given table.
	*
	* @param  string $f_table Name of the table
	* @uses   direct_dbraw_pdo_sqlite::query_exec()
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function optimize ($f_table)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->optimize ($f_table)- (#echo(__LINE__)#)"); }
		$f_return = false;

		if (is_object ($this->resource)) { $f_return = true; }
		else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->optimize ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->optimize ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->secure (&$f_data)
/**
	* Secures a given string to protect against SQL injections.
	*
	* @param  mixed &$f_data Input array or string; $f_data is NULL if there is no
	*         valid SQL resource. 
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @since  v0.1.00
*/
	public function secure (&$f_data)
	{
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->secure (+f_data)- (#echo(__LINE__)#)"); }

		if (is_object ($this->resource))
		{
			if (is_array ($f_data))
			{
				foreach ($f_data as $f_key => $f_value) { $f_data[$f_key] = $this->resource->quote ($f_value); }
			}
			else { $f_data = $this->resource->quote ($f_data); }
		}
		else { $f_data = NULL; }
	}

	//f// direct_dbraw_pdo_sqlite->transaction_begin ()
/**
	* Starts a transaction.
	*
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function transaction_begin ()
	{
		global $direct_settings;
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->transaction_begin ()- (#echo(__LINE__)#)"); }

		if ($direct_settings['db_transaction_supported'])
		{
			$f_return = false;

			if (is_object ($this->resource))
			{
				if ($this->transactions > 0) { $f_return = true; }
				else { $f_return = $this->query_exec ("co","BEGIN"); }

				if ($f_return) { $this->transactions++; }
			}
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->transaction_begin ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }
		}
		else { $f_return = true; }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->transaction_begin ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->transaction_commit ()
/**
	* Commits all transaction statements.
	*
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function transaction_commit ()
	{
		global $direct_settings;
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->transaction_commit ()- (#echo(__LINE__)#)"); }

		if ($direct_settings['db_transaction_supported'])
		{
			$f_return = false;

			if (($this->transactions > 0)&&(is_object ($this->resource)))
			{
				if ($this->transactions > 1) { $f_return = true; }
				else { $f_return = $this->query_exec ("co","COMMIT"); }

				if ($f_return) { $this->transactions--; }
			}
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->transaction_commit ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }
		}
		else { $f_return = true; }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->transaction_commit ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}

	//f// direct_dbraw_pdo_sqlite->transaction_rollback ()
/**
	* Calls the ROLLBACK statement.
	*
	* @uses   direct_debug()
	* @uses   USE_debug_reporting
	* @return boolean True on success
	* @since  v0.1.00
*/
	public function transaction_rollback ()
	{
		global $direct_settings;
		if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -db_class->transaction_rollback ()- (#echo(__LINE__)#)"); }

		if ($direct_settings['db_transaction_supported'])
		{
			$f_return = false;

			if (($this->transactions > 0)&&(is_object ($this->resource)))
			{
				if ($this->transactions > 1) { $f_return = true; }
				else { $f_return = $this->query_exec ("co","ROLLBACK"); }

				if ($f_return) { $this->transactions--; }
			}
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -db_class->transaction_rollback ()- (#echo(__LINE__)#) reporting: Database resource invalid",E_USER_WARNING); }
		}
		else { $f_return = true; }

		return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -db_class->transaction_rollback ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
	}
}

/* -------------------------------------------------------------------------
Mark this class as the most up-to-date one
------------------------------------------------------------------------- */

define ("CLASS_direct_dbraw_pdo_sqlite",true);

//j// Script specific commands

if (!isset ($direct_settings['db_advanced_search_supported'])) { $direct_settings['db_advanced_search_supported'] = false; }
if (!isset ($direct_settings['db_transaction_supported'])) { $direct_settings['db_transaction_supported'] = true; }
if (!isset ($direct_settings['db_nested_transactions_supported'])) { $direct_settings['db_nested_transactions_supported'] = false; }
}

//j// EOF
?>