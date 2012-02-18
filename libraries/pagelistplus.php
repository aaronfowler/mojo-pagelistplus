<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PageListPlus Add-on
 *
 * Original version Apache License v2.0, 2010, by Aaron Fowler http://twitter.com/adfowler
 * Refactored by GDmac, 2012, Released under OSL3 license, http://rosenlaw.com/OSL3.0-explained.htm
 *
 * OSL3 : you are free to use this software in any website and/or application, but...
 * if you alter/modify or change this software, then you have to release and publish those changes too.
 *
 */

class Pagelistplus
{
	var $addon_version = '1.3.3';

	private $addon;
	private $parameters = array();
	private $page_list  = array(); // nested list
	private $page_refs  = array(); // linear list

	// --------------------------------------------------------------------

	function __construct()
	{
		$this->addon =& get_instance();

		if (empty($this->page_refs))
		{
			// default page
			$defaults = $this->addon->page_model->get_page($this->addon->site_model->get_setting('default_page'));
			$this->default_page = ($defaults) ? $defaults->url_title : '';

			// current_page or default
			$this->current_page = trim($this->addon->uri->uri_string, '/');
			$this->current_page = ($this->current_page == '' ? $this->default_page : $this->current_page);

			// For older Mojo versions < 1.2 load extra model-methods
			if (!method_exists($this->addon->page_model, 'get_page_map'))
			{
				$this->addon->load->model(array('MY_page_model'));
				$this->page_map = $this->addon->MY_page_model->get_page_map('include_in_page_list');
			}
			else
			  $this->page_map = $this->addon->page_model->get_page_map('include_in_page_list');

			// build reference lists
			$this->fresh_list($this->page_map);

			// set mojo_active and parent_active CSS classes
			$this->set_active_pages();

			//$this->dump('initialize', $this->page_refs);
		}
	}

	// --------------------------------------------------------------------
	
	function page_list($tag)
	{
		if (! $this->page_map) return FALSE;

		$this->parameters = array();
		$allowable_parameters = array('start','header_link','header','prepend','append','active_children','force_output',    'page', 'depth', 'class', 'id');

		foreach ($allowable_parameters as $param)
		{
			$this->parameters[$param] = isset($tag['parameters'][$param]) ? trim($tag['parameters'][$param]) : FALSE;
		}

		$start = FALSE;
		$tree  = array();

		// a straight page=url_title request
		if ($this->parameters['page'])
		{
			$start = $this->find_page($this->parameters['page']);
			if ($start === FALSE) return 'PAGE not found';

			$tree = isset($this->page_refs[$start]['children']) ? $this->page_refs[$start]['children'] : array();
		}
		else
		{
			$start = $this->find_page($this->current_page);
			if ($start === FALSE) return 'PAGE not found';

			// start parameter
			switch ($this->parameters['start'])
			{
				case 'current':
					$tree = isset($this->page_refs[$start]['children']) ? $this->page_refs[$start]['children'] : array();
				break;

				case 'root':
					$start = $this->page_refs[$start]['root_id'];
		
					if(isset($this->page_refs[$start]['children']))
					{
						$tree = $this->page_refs[$start]['children'];
					}
				break;

				case 'parent': 
					// Only show parent if current is not root level
					$start = $this->page_refs[$start]['parent_id'];

					if($start > 0 && isset($this->page_refs[$start]['children']))
					{
						$tree = $this->page_refs[$start]['children'];
					}
				break;

				default:
					$tree = $this->page_list;
				break;
			}
		}

		//$this->dump($this->parameters, $this->nested_list($tree, $this->parameters));

		$result = $this->nested_list($tree, $this->parameters);

		if(empty($result) && $this->parameters['force_output']==FALSE)
		{
			return FALSE;
		}

		// build header only for valid page
		$header = ($start == 0) ? '' : $this->build_header($this->page_refs[$start]['page_title'], $this->page_refs[$start]['url_title']);

		return $this->parameters['prepend'] . PHP_EOL . $header . PHP_EOL . $result . PHP_EOL . $this->parameters['append'];

	}

	// --------------------------------------------------------------------

	function nested_list($tree, $attributes = array(), $level = 1)
	{
		if(empty($tree)) return FALSE;

		$ret  = $level > 1 ? PHP_EOL : '';
		$ret .= '<ul';
		$ret .= !empty($attributes['id']) ?' id="'.$attributes['id'].'"':'';
		$ret .= !empty($attributes['class']) ?' class="'.$attributes['class'].'"':'';
		$ret .= '>'.PHP_EOL;
		
		foreach($tree as $items)
		{
			if ($items['include_in_page_list']=='n' && $this->parameters['page']!=$items['url_title']) continue;
			// set CSS-classes
			$ret .= '<li class="mojo_page_list_'.$items['url_title'];
			$ret .= !empty($items['active']) ? ' '.$items['active'] : '';
			$ret .= !empty($items['children']) ? ' has_kids': '';
			$ret .= '">';
	
			// homepage empty anchor, link to site-root.
			if ($items['url_title'] == $this->default_page) $items['url_title'] = '';

			$ret .= anchor($items['url_title'], $items['page_title']);

			if (isset($items['children']) && ($this->parameters['depth']===FALSE || $this->parameters['depth'] > $level))
			{
				if ($this->parameters['active_children']=== FALSE || ($this->parameters['active_children']=='yes' && $items['active'] !=''))
				{
					$ret .= $this->nested_list($items['children'], array(), $level+1);
				}
			}
			$ret .= '</li>'.PHP_EOL;
		}
		$ret .= '</ul>'.PHP_EOL;
		return $ret;
	}

	// --------------------------------------------------------------------

	function fresh_list($page_map, $parent = 0)
	{
		foreach($page_map as $key => $page_info)
		{
			// build a reference list
			$thisref = &$this->page_refs[$key];

			$thisref['id']                    = $page_info['id']; // same as $key
			$thisref['parent_id']             = $parent;
			$thisref['page_title']            = $page_info['page_title'];
			$thisref['url_title']             = $page_info['url_title'];
			$thisref['include_in_page_list']  = $page_info['include_in_page_list'];

			// root items are added to page_list, children to page_refs
			if ($parent == 0)
			{
				// store root ID for fast access
				$thisref['root_id']       = $key; 
				$this->page_list[$key] = &$thisref;
			}
			else
			{
				$thisref['root_id']       = $this->page_refs[$parent]['root_id'];
				$this->page_refs[$parent]['children'][$key] = &$thisref;
			}

			if(isset($page_info['children']))
			{
				$this->fresh_list($page_info['children'], $key);
			}
		}
	}

	// --------------------------------------------------------------------

	function set_active_pages()
	{
		foreach($this->page_refs as &$item)
		{
			if ($item['url_title'] == $this->current_page)
			{
				// current page is mojo_active
				$item['active'] = 'mojo_active';
				
				$parent_id = $item['parent_id'];

				// any parents get parent_active
				while($parent_id > 0)
				{
					$this->page_refs[$parent_id]['active'] = 'parent_active';
					$parent_id = $this->page_refs[$parent_id]['parent_id'];	
				}
			}
			else $item['active'] = '';

		}
	}

	// --------------------------------------------------------------------

	function find_page($url_title)
	{
		if ($url_title=='') $url_title = $this->default_page;

		foreach($this->page_refs as $item)
		{
			if ($item['url_title'] == $url_title) return $item['id'];
		}
		return FALSE;
	}

	// ----------------------------------------------------

	function build_header($header, $url_title)
	{
		if ($this->parameters['header_link']=='yes' || $this->parameters['header'] !== FALSE)
		{
			if($this->parameters['header_link'] == 'yes')
			{
				$header = anchor($url_title, $header);
			}
			if($this->parameters['header'] !== FALSE)
			{
				$header = '<'.$this->parameters['header'].'>'.$header.'</'.$this->parameters['header'].'>';
			}
			return $header;
		}
		
		return '';
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
