<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2005 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Structures/DataGrid/Renderer/Common.php';
//require_once 'XML/XUL.php';
require_once 'XML/Util.php';

/**
 * Structures_DataGrid_Renderer_XUL Class
 *
 * This renderer class will render an XUL listbox.
 * For additional information on the XUL Listbox, refer to this url:
 * http://www.xulplanet.com/references/elemref/ref_listbox.html
 *
 * Recognized options:
 *
 * - title:     (string) The title of the datagrid
 *                       (default: 'DataGrid')
 * - css:       (array)  An array of css URL's
 *                       (default: 'chrome://global/skin/')
 * - selfPath:  (string) The complete path for sorting and paging links
 *                       (default: $_SERVER['PHP_SELF'])
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <asnagy@webitecture.org>
 * @author      Olivier Guilyardi <olivier@samalyse.com>
 * @author      Mark Wiesemann <wiesemann@php.net>
 * @access      public
 * @package     Structures_DataGrid
 * @category    Structures
 * @todo        Implement PEAR::XML_XUL upon maturity
 */
class Structures_DataGrid_Renderer_XUL extends Structures_DataGrid_Renderer_Common
{
    /**
     * Whether the container is user-provided or not
     * @var bool
     * @access protected
     */
    var $_isCustomContainer;
    
    /**
     * Constructor
     *
     * Initialize default options
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XUL()
    {
        parent::Structures_DataGrid_Renderer_Common();
        $this->_addDefaultOptions(
            array(
                'title'    => 'DataGrid',
                'css'      => array('chrome://global/skin/'),
                'selfPath' => $_SERVER['PHP_SELF']
            )
        );
    }

    /**
     * Generate the XML declaration and initialize the XUL datagrid, if needed
     * 
     * A user may provide a custom container by means of the setContainer() 
     * method (or equivalent). If so, this custom container should already 
     * contains XML, XUL window and listbox declarations similar/equivalent 
     * to the ones generated by this method.
     *
     * @access protected
     */
    function init()
    {
        if (is_null($this->_container)) {

            $this->_isCustomContainer = false;

            // Define XML
            $this->_container = XML_Util::getXMLDeclaration() . "\n";
            
            // Define Stylesheets
            foreach ($this->_options['css'] as $css) {
                $this->_container .= "<?xml-stylesheet href=\"$css\" " .
                                                      "type=\"text/css\"?>\n";
            }
            
            // Define Window Element
            $this->_container .= 
                "<window title=\"{$this->_options['title']}\" " . 
                "xmlns=\"http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul\">\n";
            
            // Define Listbox Element
            $this->_container .= "<listbox rows=\"" . $this->_pageLimit . "\">\n";

        } else {
            $this->_isCustomContainer = true;
        }
    }

    /**
     * Sets the datagrid title
     *
     * @access  public
     * @param   string      $title      The title of the datagrid
     */
    function setTitle($title)
    {
        $this->_options['title'] = $title;
    }
    
    /**
     * Adds a stylesheet to the list of stylesheets
     *
     * @access  public
     * @param   string      $url        The url of the stylesheet
     */
    function addStyleSheet($url)
    {
        array_push($this->_options['css'], $url);
    }

    /**
     * Build the <listhead> grid header 
     *
     * @access  protected
     * @return  void
     */
    function buildHeader()
    {
        $this->_container .= "  <listhead>\n";
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $field = $this->_columns[$col]['field'];
            $label = $this->_columns[$col]['label'];

            if (in_array($field, $this->_sortableFields)) {
                if ($this->_currentSort and $this->_currentSort[0]['field'] == $field) {
                    if ($this->_currentSort[0]['direction'] == 'ASC') {
                        // The data is currently sorted by $column, ascending.
                        // That means we want $dirArg set to 'DESC', for the next
                        // click to trigger a reverse order sort, and we need 
                        // $dirCur set to 'ascending' so that a neat xul arrow 
                        // shows the current "ASC" direction.
                        $dirArg = 'DESC'; 
                        $dirCur = 'ascending'; 
                    } else {
                        // Next click will do ascending sort, and we show a reverse
                        // arrow because we're currently descending.
                        $dirArg = 'ASC';
                        $dirCur = 'descending';
                    }
                } else {
                    // No current sort on this column. Next click will ascend. We
                    // show no arrow.
                    $dirArg = 'ASC';
                    $dirCur = 'natural';
                }

                $onCommand = 
                    "oncommand=\"location.href='{$this->_options['selfPath']}". 
                        "?{$this->_requestPrefix}orderBy=$field".
                        "&amp;{$this->_requestPrefix}direction=$dirArg';\"";
                $sortDirection = "sortDirection=\"$dirCur\"";
            } else {
                $onCommand = '';
                $sortDirection = '';
            }

            $label = XML_Util::replaceEntities($label);
            $this->_container .= '    <listheader label="' . $label . '" ' . 
                    "$sortDirection $onCommand />\n";
        }
        $this->_container .= "  </listhead>\n";
    }
    
    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $this->_container .= "  <listitem>\n";
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];

                // FIXME: '�' is displayed as '?' ==> encoding is required!
                $this->_container .= '    ' .
                        XML_Util::createTag('listcell',
                                            array('label' => $value)) . "\n";
            }

            $this->_container .= "  </listitem>\n";
        }
    }

    /**
     * Close the XUL listbox and window, if needed
     *
     * @access protected
     */
    function finalize()
    {
        if (!$this->_isCustomContainer) {
            $this->_container .= "</listbox>\n";
            $this->_container .= "</window>\n";
        }
    }
    
    /**
     * Returns the XUL for the DataGrid
     *
     * @access  public
     * @return  string      The XUL of the DataGrid
     */
    function toXUL()
    {
        return $this->getOutput();
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_container;
    }

    /**
     * Render to the standard output
     *
     * @access  public
     */
    function render()
    {
        header('Content-type: application/vnd.mozilla.xul+xml');
        parent::render();
    }
}

?>
