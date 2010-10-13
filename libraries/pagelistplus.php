<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PageListPlus Addon
 *
 * @package		MojoMotor
 * @subpackage	Addons
 * @author		Aaron Fowler
 * @link		http://twitter.com/adfowler
 * @license		Apache License v2.0
 * @copyright	2010 Aaron Fowler
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *	http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Pagelistplus
{
	var $addon;
	var $addon_version = '1.1';
	var $site_structure;
	var $page_id = 0;
	var $root_parent_page_id = FALSE;
	var $parent_page_id = FALSE;
	var $has_siblings = FALSE;
	var $has_children = FALSE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		$this->addon =& get_instance();
		$this->site_structure = $this->addon->site_model->get_setting('site_structure');
	}

	// --------------------------------------------------------------------


	/**
	 * Page list
	 *
	 * Creates an unordered list of all pages in the site that haven't been
	 * opted out of appearing in the page_list.
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	function page_list($tag)
	{
		$this->addon->load->helper(array('page', 'array'));
		$this->addon->load->model(array('page_model'));
		$attributes = array();

		// Gather up parameters
		$allowable_parameters = array('class', 'id');

		foreach ($allowable_parameters as $param)
		{
			if (isset($tag['parameters'][$param]))
			{
				$attributes[$param] = trim($tag['parameters'][$param]);
			}
		}
		
		$start = isset($tag['parameters']['start']) ? $tag['parameters']['start'] : FALSE;
		$header = '';
		
		if(isset($tag['parameters']['page']))
		{
			if($page = $this->addon->page_model->get_page_by_url_title($tag['parameters']['page']))
			{
				$result = parser_page_list(array_find_element_by_key($page->id, $this->site_structure), $attributes);
				$header = $this->build_header($page->page_title, $page->url_title, $tag);
			}
		}
		else if($start=='current' || $start=='parent' || $start=='root')
		{
			if($this->page_id===0) // just run this once, no matter how many times this addon is called
			{
				if ($page = $this->addon->mojomotor_parser->page->page_info)
				{
					$this->page_id = $page->id;
					$this->parent_page_id = $this->array_find_parent_by_key($this->page_id, $this->site_structure);
					if (!$this->root_parent_page_id)
					{
						$this->root_parent_page_id = $this->page_id;
					}
				}
				else
				{
					return '';
				}
			}
			
			if($start=='current')
			{
				$result = parser_page_list(array_find_element_by_key($this->page_id, $this->site_structure), $attributes);
				if(strtolower($tag['parameters']['header_link']) == 'yes' || isset($tag['parameters']['header']))
				{
					$header = $this->build_header($this->addon->mojomotor_parser->page->page_info->page_title, $this->addon->mojomotor_parser->page->page_info->url_title, $tag);
				}
			}
			
			if($start=='parent' && $this->parent_page_id)
			{
				$result = parser_page_list(array_find_element_by_key($this->parent_page_id, $this->site_structure), $attributes);
				if((strtolower($tag['parameters']['header_link']) == 'yes' || isset($tag['parameters']['header'])) && $page = $this->addon->page_model->get_page($this->parent_page_id))
				{
					$header = $this->build_header($page->page_title, $page->url_title, $tag);
				}
			}
			
			if($start=='root' && $this->root_parent_page_id)
			{
				$result = parser_page_list(array_find_element_by_key($this->root_parent_page_id, $this->site_structure), $attributes);
				if((strtolower($tag['parameters']['header_link']) == 'yes' || isset($tag['parameters']['header'])) && $page = $this->addon->page_model->get_page($this->root_parent_page_id))
				{
					$header = $this->build_header($page->page_title, $page->url_title, $tag);
				}
			}
		}
		else // output the default page_list
		{
			$result = parser_page_list($this->site_structure);
		}
		
		if(!$result || is_numeric($result))
		{
			return '';
		}
		else
		{
			return $tag['parameters']['prepend'] . "\n" . $header . "\n" . $result . "\n" . $tag['parameters']['append'];
		}
	}
	
	
	/**
	 * Array Find Parent By Key
	 *
	 * Returns parent page id and sets $this->has_children and $this->has_siblings variables
	 *
	 * @access	private
	 * @param	string
	 * @param	array
	 * @return	int
	 */
	function array_find_parent_by_key($needle, $haystack = array(), $parent = FALSE, $root_parent = FALSE)
	{
		if (array_key_exists($needle, $haystack))
		{
			if (is_array($haystack[$needle]))
			{
				$this->has_children = TRUE;
			}
			if (count($haystack) > 1)
			{
				$this->has_siblings = TRUE;
			}
			
			return $parent;
		}
		
		foreach ($haystack as $key => $value)
		{
			if (is_array($value)) 
			{
				if (!$root_parent)
				{
					$root_parent = $key;
				}
				$found = $this->array_find_parent_by_key($needle, $haystack[$key], $key, $root_parent);
				if ($found)
				{
					$this->root_parent_page_id = $root_parent;
					return $found;
				}
			}
			$root_parent = FALSE;
		}
		
		return FALSE;
	}
	
	
	/**
	 * Build Header
	 *
	 * Returns header string
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	function build_header($header, $url_title, $tag)
	{
		if(strtolower($tag['parameters']['header_link']) == 'yes' || isset($tag['parameters']['header']))
		{
			if(strtolower($tag['parameters']['header_link']) == 'yes')
			{
				$header = '<a href="' . site_url('page/' . $url_title) . '">' . $header . '</a>';
			}
			if(isset($tag['parameters']['header']))
			{
				$header = '<' . $tag['parameters']['header'] . '>' . $header . '</' . $tag['parameters']['header'] . '>';
			}
			return $header;
		}
		
		return '';
	}
	

}

/* End of file pagelistplus.php */
/* Location: system/mojomotor/third_party/pagelistplus/libraries/pagelistplus.php */