<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PageListPlus Add-on
 *
 * Original version Apache License v2.0, 2010, by Aaron Fowler http://twitter.com/adfowler
 * Refactored by GDmac, 2012, Released under OSL3 license, http://rosenlaw.com/OSL3.0-explained.htm
 *
 * OSL3 license means: you are free to use this software in any website and/or application, but...
 * if you alter/modify or change this software, then you have to release and publish those changes too.
 *
 */

class Pagelistplus
{
	var $addon_version = '1.3.0';

	private $addon;
	private $site_structure;
	private $parameters = array();
	private $all_pages  = array();
	private $page_list  = array();
	private $page_refs  = array();

	// --------------------------------------------------------------------

	function __construct()
	{
		$this->addon =& get_instance();

		// Fetch fresh copy of the site structure. 
		// Just run this once, no matter how many times this addon is called
		if (empty($this->all_pages) || empty($this->page_refs))
		{
			$this->initialize();
		}
	}

	// --------------------------------------------------------------------
	
	function page_list($tag)
	{
		$start = FALSE;
		$tree = FALSE;

		$allowable_parameters = array('start', 'header_link', 'header', 'prepend', 'append',    'page', 'depth', 'class', 'id', 'depth');

		$this->parameters = array();

		foreach ($allowable_parameters as $param)
		{
			$this->parameters[$param] = isset($tag['parameters'][$param]) ? trim($tag['parameters'][$param]) : FALSE;
		}

		// a straight request for page
		if ($this->parameters['page'])
		{
			$start = $this->find_current_page($this->parameters['page']);
			$tree = array($start => $this->page_refs[$start]);

			$page_info = $this->page_refs[$start];
			$header = $this->build_header($page_info['page_title'], $page_info['url_title'], $this->parameters);
		}


		// main switch for start parameter
		switch ($this->parameters['start'])
		{
			case 'current':
				$start = $this->find_current_page($this->current_page);
				$tree = array($start => $this->page_refs[$start]);
			break;

			case 'root':
				$start = $this->find_root_page($this->current_page);
				if(!isset($this->page_refs[$start]['children'])) return FALSE;
				$tree = $this->page_refs[$start]['children'];
			break;

			case 'parent': 
				// Legacy: Only show parent, don't show when parent is at root level
				$start = $this->find_parent_page($this->current_page);
				if($start)
				{
					$tree = array($start => $this->page_refs[$start]);
				}
				else
				{
					$this->dump($start,$this->parameters,$tree);
					return FALSE;
				}
			break;

			default:
				if($start===false)
				{
					$start = $this->find_current_page('');
					$tree = $this->page_list;
				}
		}

		// debug info
		//$this->dump($start, $this->parameters, $tree, $this->nested_list($tree, $this->parameters) );

		// see the lists
		//$this->dump($this->page_list);
		//$this->dump($this->page_refs);


		// set mojo_active and parent_active on whole tree, nice to have some css to play with :-)
		$this->set_active_pages($this->current_page);

		$header = $this->build_header($this->page_refs[$start]['page_title'], $this->page_refs[$start]['url_title']);
		
		return $header . $this->nested_list($tree, $this->parameters);

	}

	// --------------------------------------------------------------------

	function nested_list($tree, $attributes, $level=1)
	{
		$ret  = $level > 1 ? PHP_EOL : '';
		$ret .= '<ul';
		$ret .= !empty($attributes['id']) ?' id="'.$attributes['id'].'"':'';
		$ret .= !empty($attributes['class']) ?' class="'.$attributes['class'].'"':'';
		$ret .= '>'.PHP_EOL;
		
		foreach($tree as $items)
		{
			// set CSS-classes
			$ret .= '<li class="mojo_page_list_'.$items['url_title'];
			$ret .= isset($items['active']) ? ' '.$items['active'] : '';
			$ret .= '">';
	
			$ret .= anchor($items['url_title'], $items['page_title']);
	
			if (isset($items['children']) && ($this->parameters['depth']===FALSE || $this->parameters['depth'] > $level))
			{
				$ret .= $this->nested_list($items['children'], array(), $level+1);
			}
			
			$ret .= '</li>'.PHP_EOL;
		}

		$ret .= '</ul>'.PHP_EOL;
		return $ret;

	}


	// ----------------------------------------------------

	function build_header($header, $url_title)
	{
		if ($this->parameters['header_link']=='yes' || $this->parameters['header'] !== FALSE)
		{
			if($this->parameters['header_link'] == 'yes')
			{
				$header = '<a href="'.site_url($url_title).'">'.$header.'</a>';
			}
			if($this->parameters['header'] !== FALSE)
			{
				$header = '<'.$this->parameters['header'].'>'.$header.'</'.$this->parameters['header'].'>';
			}
			return $header;
		}
		
		return '';
	}

	// --------------------------------------------------------------------

	function initialize()
	{
		// fetch some default settings
		$this->current_page = trim($this->addon->uri->uri_string, '/');

		$defaults = $this->addon->page_model->get_page($this->addon->site_model->get_setting('default_page'));

		$this->default_page = ($defaults) ? $defaults->url_title : '';

		// fetch structure and pages
		$this->site_structure = $this->addon->site_model->get_setting('site_structure');
		$this->all_pages = $this->addon->page_model->get_all_pages_info();

		// build a reference list, the whole shebang
		$this->fresh_list($this->site_structure);
	}

	// --------------------------------------------------------------------

	function find_root_page($url_title)
	{
		// no url_title, then the default page
		if ($url_title=='') $url_title = $this->default_page;

		// walk the pages refs untill we find our url_title
		foreach($this->page_refs as $item)
		{
			if ($item['url_title']==$url_title)
			{
				$parent_id = $item['parent_id'];

				if ($parent_id == 0) return $item['id']; // that was easy, found a root item

				// hmmm, walk up the refs array
				while ($parent_id > 0)
				{
					$found_id = $this->page_refs[$parent_id]['id'];
					$parent_id = $this->page_refs[$parent_id]['parent_id'];
				}

				return $found_id;
			} 
		}
	}

	// --------------------------------------------------------------------
	// Legacy, compatibility function
	// Finds parent id, or returns false if parent is a root item

	function find_parent_page($url_title)
	{
		if ($url_title=='') return FALSE;

		foreach($this->page_refs as $item)
		{
			if ($item['url_title'] == $url_title)
			{
				// not a root-item, and parent not a root item
				if($item['parent_id'] > 0 && $this->page_refs[$item['parent_id']]['parent_id'] > 0)
				{
					return $item['parent_id'];	
				}
				else
				  return false;
			}

		}
	}

	// --------------------------------------------------------------------
	function find_current_page($url_title)
	{
		if ($url_title=='') $url_title = $this->default_page;

		foreach($this->page_refs as $item)
		{
			if ($item['url_title'] == $url_title) return $item['id'];
		}
	}

	// --------------------------------------------------------------------
	function set_active_pages($url_title)
	{
		if ($url_title=='') $url_title = $this->default_page;

		// by reference sets the main array item
		foreach($this->page_refs as &$item)
		{
			if ($item['url_title'] == $url_title)
			{
				$item['active'] = 'mojo_active';
				$parent_id = $item['parent_id'];

				while($parent_id > 0)
				{
					$this->page_refs[$parent_id]['active'] = 'parent_active';
					$parent_id = $this->page_refs[$parent_id]['parent_id'];	
				}
			}
		}
	}

	// --------------------------------------------------------------------
	// OMG nesting by reference

	function fresh_list($nested_arr, $parent=0)
	{
		foreach($nested_arr as $key => $value)
		{
			// build a reference list
			$thisref = &$this->page_refs[$key];

			// root items are added to page_list, children to page_refs
			if ($parent == 0)
			{
				$this->page_list[$key] = &$thisref;
			}
			else
			{
				$this->page_refs[$parent]['children'][$key] = &$thisref;
			}

			$thisref['id']         = $key;
			$thisref['parent_id']  = $parent;
			$thisref['page_title'] = $this->all_pages[$key]['page_title'];
			$thisref['url_title']  = $this->all_pages[$key]['url_title'];

			if(is_array($value))
			{
				$this->fresh_list($value, $key);
			}
		}
	}

	// ----------------------------------------------------
	
	/**
	  * Debug Helper
	  *
	  * Outputs the given variable(s) with formatting and location
	  *
	  * @access        public
	  * @param        mixed    variables to be output
	  */
	function dump()
	{
	    list($callee) = debug_backtrace();
	    $arguments = func_get_args();
	    $total_arguments = count($arguments);

	    echo '<fieldset style="background: #fefefe !important; border:2px red solid; padding:5px; text-align:left;">';
	    echo '<legend style="background:lightgrey; padding:5px;">'.$callee['file'].' @ line: '.$callee['line'].'</legend><pre>';
	    $i = 0;
	    foreach ($arguments as $argument)
	    {
	        echo '<br/><strong>Debug #'.(++$i).' of '.$total_arguments.'</strong>: ';
	        var_dump($argument);
	    }

	    echo "</pre>";
	    echo "</fieldset>";
	}
}

/* End of file pagelistplus.php */
/* Location: system/mojomotor/third_party/pagelistplus/libraries/pagelistplus.php */
