<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


// missing model-methods for 1.1.x compatibility

class MY_page_model extends CI_Model {

// ------------------------------------------------------------------------

	public function get_page_map($additional_fields = array(), $additional_where = array())
	{
		// get the page hierarchy
		$query = $this->db->select('site_structure')
							->limit(1)
							->get('site_settings');

		$site_structure = unserialize($query->row('site_structure'));

		// get basic page info
		$this->db->select('id, page_title, url_title');

		// additional fields
		$this->db->select($additional_fields);

		// additional where
		$this->db->where($additional_where);

		$query = $this->db->get('pages');

		if ($query->num_rows() > 0)
		{
			$page_info = array();

			foreach($query->result_array() as $page)
			{
				$page_info[$page['id']] = $page;
			}

			return $this->_build_page_map($site_structure, $page_info);
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	private function _build_page_map($site_structure, $page_info, &$map = array())
	{
		foreach($site_structure as $id => $val)
		{
			if (isset($page_info[$id])) // graceful handling of $site_structure/$page_info mismatch
			{
				$map[$id] = $page_info[$id];	
		
				if (is_array($val))
				{
					$this->_build_page_map($val, $page_info, $map[$id]['children']);
				}			
			}
		}
	
		return $map;
	}
}

/* End of file MY_page_model.php */
/* Location: models/MY_page_model.php */
