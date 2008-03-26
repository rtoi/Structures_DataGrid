<?php
/**
 * Base abstract class for data source drivers
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Andrew Nagy <asnagy@webitecture.org>,
 *                          Olivier Guilyardi <olivier@samalyse.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * CVS file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * Base abstract class for DataSource drivers
 * 
 * SUPPORTED OPTIONS:
 *
 * - fields:            (array) Which data fields to fetch from the datasource.
 *                              An empty array means: all fields.
 *                              Form: array(field1, field2, ...)
 * - primaryKey:        (array) Name(s), or numerical index(es) of the 
 *                              field(s) which contain a unique record 
 *                              identifier (only use several fields in case
 *                              of a multiple-fields primary key)
 * - generate_columns:  (bool)  Generate Structures_DataGrid_Column objects 
 *                              with labels. See the 'labels' option.
 *                              DEPRECATED: 
 *                              use Structures_DataGrid::generateColumns() instead
 * - labels:            (array) Data field to column label mapping. Only used 
 *                              when 'generate_columns' is true. 
 *                              Form: array(field => label, ...)
 *                              DEPRECATED: 
 *                              use Structures_DataGrid::generateColumns() instead
 *
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @package  Structures_DataGrid
 * @category Structures
 * @version  $Revision$
 */
class Structures_DataGrid_DataSource
{
    /**
     * Common and driver-specific options
     *
     * @var array
     * @access protected
     * @see Structures_DataGrid_DataSource::_setOption()
     * @see Structures_DataGrid_DataSource::addDefaultOptions()
     */
    var $_options = array();

    /**
     * Special driver features
     *
     * @var array
     * @access protected
     */
    var $_features = array();

    /**
     * Constructor
     *
     */
    function Structures_DataGrid_DataSource()
    {
        $this->_options = array('generate_columns' => false,
                                'labels'           => array(),
                                'fields'           => array(),
                                'primaryKey'       => null,
                               );

        $this->_features = array(
                'multiSort' => false, // Multiple field sorting
                'writeMode' => false, // insert, update and delete records
        );
    }

    /**
     * Adds some default options.
     *
     * This method is meant to be called by drivers. It allows adding some
     * default options. 
     *
     * @access protected
     * @param array $options An associative array of the form:
     *                       array(optionName => optionValue, ...)
     * @return void
     * @see Structures_DataGrid_DataSource::_setOption
     */
    function _addDefaultOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Add special driver features
     *
     * This method is meant to be called by drivers. It allows specifying 
     * the special features that are supported by the current driver.
     *
     * @access protected
     * @param array $features An associative array of the form:
     *                        array(feature => true|false, ...)
     * @return void
     */
    function _setFeatures($features)
    {
        $this->_features = array_merge($this->_features, $features);
    }
    
    /**
     * Set options
     *
     * @param   mixed   $options    An associative array of the form:
     *                              array("option_name" => "option_value",...)
     * @access  protected
     */
    function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Set a single option
     *
     * @param   string  $name       Option name
     * @param   mixed   $value      Option value
     * @access  public
     */
    function setOption($name, $value)
    {
        $this->_options[$name] = $value;
    }

    /**
     * Generate columns if options are properly set
     *
     * Note: must be called after fetch()
     * 
     * @access public
     * @return array Array of Column objects. Empty array if irrelevant.
     * @deprecated This method relates to the deprecated "generate_columns" option.
     */
    function getColumns()
    {
        $columns = array();
        if ($this->_options['generate_columns'] 
            and $fieldList = $this->_options['fields']) {
            include_once 'Structures/DataGrid/Column.php';
            
            foreach ($fieldList as $field) {
                $label = strtr($field, $this->_options['labels']);
                $col = new Structures_DataGrid_Column($label, $field, $field);
                $columns[] = $col;
            }
        }
        
        return $columns;
    }
    
    
    // Begin driver method prototypes DocBook template
     
    /**#@+
     * 
     * This method is public, but please note that it is not intended to be 
     * called by user-space code. It is meant to be called by the main 
     * Structures_DataGrid class.
     *
     * It is an abstract method, part of the DataGrid Datasource driver 
     * interface, and must/may be overloaded by drivers.
     */
   
    /**
     * Fetching method prototype
     *
     * When overloaded this method must return an array of records. 
     * Each record can be either an associative array of field name/value 
     * pairs, or an object carrying fields as properties.
     *
     * This method must return a PEAR_Error object on failure.
     *
     * @abstract
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function &fetch($offset = 0, $len = null)
    {
        return PEAR::raiseError("No data source driver loaded");
    }

    /**
     * Counting method prototype
     *
     * Note: must be called before fetch() 
     *
     * When overloaded, this method must return the total number or records 
     * or a PEAR_Error object on failure
     * 
     * @abstract
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function count()
    {
        return PEAR::raiseError("No data source driver loaded");
    }
    
    /**
     * Sorting method prototype
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     * 
     * Note: must be called before fetch() 
     * 
     * @abstract
     * @param   string  $sortSpec   If the driver supports the "multiSort" 
     *                              feature this can be either a single field 
     *                              (string), or a sort specification array of 
     *                              the form: array(field => direction, ...)
     *                              If "multiSort" is not supported, then this
     *                              can only be a string.
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function sort($sortSpec, $sortDir = null)
    {
        return PEAR::raiseError("No data source driver loaded");
    }    
  
    /**
     * Datasource binding method prototype
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     *
     * @abstract
     * @param   mixed $container The datasource container
     * @param   array $options   Binding options
     * @return  object           PEAR_Error with message 
     *                           "No data source driver loaded" 
     * @access  public                          
     */
    function bind($container, $options = array())
    {
        return PEAR::raiseError("No data source driver loaded");
    }
 
    /**
     * Record insertion method prototype
     *
     * Drivers that support the "writeMode" feature must implement this method.
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure. 
     *
     * @abstract
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ..)
     * @return  object          PEAR_Error with message 
     *                          "No data source driver loaded or write mode not 
     *                          supported by the current driver"
     * @access  public                          
     */
    function insert($data)
    {
        return PEAR::raiseError("No data source driver loaded or write mode not". 
                                "supported by the current driver");
    }

    /**
     * Return the primary key specification
     *
     * This method always returns an array containing:
     * - either one field name or index in case of a single-field key
     * - or several field names or indexes in case of a multiple-fields key
     *
     * Drivers that support the "writeMode" feature should overload this method
     * if the key can be detected. However, the detection must not override the
     * "primaryKey" option.
     *
     * @return  array       Field(s) name(s) or numerical index(es)
     * @access  protected
     */
    function getPrimaryKey()
    {
        return $this->_options['primaryKey'];
    }

    /**
     * Record updating method prototype
     *
     * Drivers that support the "writeMode" feature must implement this method.
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     *
     * @abstract
     * @param   array   $key    Unique record identifier
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ..)
     * @return  object          PEAR_Error with message 
     *                          "No data source driver loaded or write mode 
     *                          not supported by the current driver"
     * @access  public                          
     */
    function update($key, $data)
    {
        return PEAR::raiseError("No data source driver loaded or write mode not". 
                                "supported by the current driver");
    }

    /**
     * Record deletion method prototype
     *
     * Drivers that support the "writeMode" feature must implement this method.
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     *
     * @abstract
     * @param   array   $key    Unique record identifier
     * @return  object          PEAR_Error with message 
     *                          "No data source driver loaded or write mode 
     *                          not supported by the current driver"
     * @access  public                          
     */
    function delete($key)
    {
        return PEAR::raiseError("No data source driver loaded or write mode not". 
                                "supported by the current driver");
    }

    /**
     * Resources cleanup method prototype
     *
     * This is where drivers should close sql connections, files, etc...
     * if needed.
     *
     * @abstract
     * @return  void 
     * @access  public                          
     */
    function free()
    {
    }

    /**#@-*/

    // End DocBook template
  
    /**
     * List special driver features
     *
     * @return array Of the form: array(feature => true|false, etc...)
     * @access public
     */
    function getFeatures()
    {
        return $this->_features;
    }
   
    /**
     * Tell if the driver as a specific feature
     *
     * @param  string $name Feature name
     * @return bool 
     * @access public
     */
    function hasFeature($name)
    {
        return $this->_features[$name];
    }
    
    /**
     * Dump the data as returned by fetch().
     *
     * This method is meant for debugging purposes. It returns what fetch()
     * would return to its DataGrid host as a nicely formatted console-style
     * table.
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     * @return  string              The table string, ready to be printed
     * @uses    Structures_DataGrid_DataSource::fetch()
     * @access  public
     */
    function dump($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        $records =& $this->fetch($offset, $len, $sortField, $sortDir);
        $columns = $this->getColumns();

        if (!$columns and !$records) {
            return "<Empty set>\n";
        }
        
        include_once 'Console/Table.php';
        $table = new Console_Table();
        
        $headers = array();
        if ($columns) {
            foreach ($columns as $col) {
                $headers[] = is_null($col->fieldName)
                            ? $col->columnName
                            : "{$col->columnName} ({$col->fieldName})";
            }
        } else {
            $headers = array_keys($records[0]);
        }

        $table->setHeaders($headers);
        
        foreach ($records as $rec) {
            $table->addRow($rec);
        }
       
        return $table->getTable();
    }

}

/**
 * Base abstract class for SQL query based DataSource drivers
 * 
 * SUPPORTED OPTIONS:
 *
 * - db_options:  (array)  Options for the created database object. This option
 *                         is only used when the 'dsn' option is given.
 * - count_query: (string) Query that calculates the number of rows. See below
 *                         for more information about when such a count query
 *                         is needed.
 * - quote_identifiers: (bool) Whether identifiers should be quoted or not. If
 *                             you have field names in the form "table.field",
 *                             set this option to boolean false; otherwise the
 *                             automatic quoting might lead to syntax errors.
 *
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @package  Structures_DataGrid
 * @category Structures
 * @version  $Revision$
 */
class Structures_DataGrid_DataSource_SQLQuery
    extends Structures_DataGrid_DataSource
{
    /**
     * SQL query
     * @var string
     * @access protected
     */
    var $_query;

    /**
     * Fields/directions to sort the data by
     * @var array
     * @access protected
     */
    var $_sortSpec;

    /**
     * Instantiated database object
     * @var object
     * @access protected
     */
    var $_handle;

    /**
     * Total number of rows
     * 
     * This property caches the result of count() to avoid running the same
     * database query multiple times.
     *
     * @var int
     * @access private
     */
     var $_rowNum = null;

    /**
     * Constructor
     *
     */
    function Structures_DataGrid_DataSource_SQLQuery()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('dbc' => null,
                                        'dsn' => null,
                                        'db_options'  => array(),
                                        'count_query' => '',
                                        'quote_identifiers' => true));
        $this->_setFeatures(array('multiSort' => true));
    }

    /**
     * Bind
     *
     * @param   string    $query     The query string
     * @param   mixed     $options   array('dbc' => [connection object])
     *                               or
     *                               array('dsn' => [dsn string])
     * @access  public
     * @return  mixed                True on success, PEAR_Error on failure
     */
    function bind($query, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }

        if (isset($this->_options['dbc']) &&
            $this->_isConnection($this->_options['dbc'])) {
            $this->_handle = &$this->_options['dbc'];
        } elseif (isset($this->_options['dsn'])) {
            $dbOptions = array();
            if (array_key_exists('db_options', $options)) {
                $dbOptions = $options['db_options'];
            }
            $this->_handle =& $this->_connect();
            if (PEAR::isError($this->_handle)) {
                return PEAR::raiseError('Could not create connection: ' .
                                        $this->_handle->getMessage() . ', ' .
                                        $this->_handle->getUserInfo());
            }
        } else {
            return PEAR::raiseError('No Database object or dsn string specified');
        }

        if (is_string($query)) {
            $this->_query = $query;
            return true;
        } else {
            return PEAR::raiseError('Query parameter must be a string');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @access  public
     * @return  mixed               The 2D Array of the records on success,
     *                              PEAR_Error on failure
     */
    function &fetch($offset = 0, $limit = null)
    {
        if (!empty($this->_sortSpec)) {
            foreach ($this->_sortSpec as $field => $direction) {
                if ($this->_options['quote_identifiers'] === true) {
                    $field = $this->_quoteIdentifier($field);
                }
                $sortArray[] = $field . ' ' . $direction;
            }
            $sortString = join(', ', $sortArray);
        } else {
            $sortString = '';
        }

        $query = $this->_query;

        // drop LIMIT statement
        $query = preg_replace('#\sLIMIT\s.*$#isD', ' ', $query);

        // if we have a sort string, we need to add it to the query string
        if ($sortString != '') {
            // if there is an existing ORDER BY statement, we can just add the
            // sort string
            $result = preg_match('#ORDER\s+BY#is', $query);
            if ($result === 1) {
                $query .= ', ' . $sortString;
            } else {  // otherwise we need to specify 'ORDER BY'
                $query .= ' ORDER BY ' . $sortString;
            }
        }

        //FIXME: What about SQL injection ?
        $recordSet = $this->_getRecords($query, $limit, $offset);

        if (PEAR::isError($recordSet)) {
            return $result;
        }

        // Determine fields to render
        if (!$this->_options['fields'] && count($recordSet)) {
            $this->setOptions(array('fields' => array_keys($recordSet[0])));
        }                

        return $recordSet;
    }

    /**
     * Count
     *
     * @access  public
     * @return  mixed       The number or records (int),
     *                      PEAR_Error on failure
     */
    function count()
    {
        // do we already have the cached number of records? (if yes, return it)
        if (!is_null($this->_rowNum)) {
            return $this->_rowNum;
        }
        // try to fetch the number of records
        if ($this->_options['count_query'] != '') {
            // complex queries might require special queries to get the
            // right row count
            $count = $this->_getOne($this->_options['count_query']);
            // $count has an integer value with number of rows or is a
            // PEAR_Error instance on failure
        }
        elseif (preg_match('#GROUP\s+BY#is', $this->_query) === 1 ||
                preg_match('#SELECT.+SELECT#is', $this->_query) === 1 ||
                preg_match('#\sUNION\s#is', $this->_query) === 1 ||
                preg_match('#SELECT.+DISTINCT.+FROM#is', $this->_query) === 1
            ) {
            // GROUP BY, DISTINCT, UNION and subqueries are special cases
            // ==> use the normal query and then numRows()
            $count = $this->_getRecordsNum($this->_query);
            if (PEAR::isError($count)) {
                return $count;
            }
        } else {
            // don't query the whole table, just get the number of rows
            $query = preg_replace('#SELECT\s.+\sFROM#is',
                                  'SELECT COUNT(*) FROM',
                                  $this->_query);
            $count = $this->_getOne($query);
            // $count has an integer value with number of rows or is a
            // PEAR_Error instance on failure
        }
        // if we've got a number of records, save it to avoid running the same
        // query multiple times
        if (!PEAR::isError($count)) {
            $this->_rowNum = $count;
        }
        return $count;
    }

    /**
     * Disconnect from the database, if needed 
     *
     * @abstract
     * @return void
     * @access public
     */
    function free()
    {
        if ($this->_handle && is_null($this->_options['dbc'])) {
            $this->_disconnect();
            unset($this->_handle);
        }
    }

    /**
     * This can only be called prior to the fetch method.
     *
     * @access  public
     * @param   mixed   $sortSpec   A single field (string) to sort by, or a 
     *                              sort specification array of the form:
     *                              array(field => direction, ...)
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     *                              This is ignored if $sortDesc is an array
     */
    function sort($sortSpec, $sortDir = 'ASC')
    {
        if (is_array($sortSpec)) {
            $this->_sortSpec = $sortSpec;
        } else {
            $this->_sortSpec[$sortSpec] = $sortDir;
        }
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
