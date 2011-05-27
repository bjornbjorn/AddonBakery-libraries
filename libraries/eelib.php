<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once PATH_THIRD.'libraries/ab/ab_libbase.php';



/**
 * some functions to help with EE specific tasks
 * 
 * 
 * @package		eelib
 * @subpackage	ThirdParty
 * @category	Library
 * @author      bjorn	
 * @since       04.02.11 14:54
 * @link		http://ee.bybjorn.com/ 
 */ 
class eelib extends Ab_LibBase
{
    /**
     * @var Devkit_code_completion
     */
    public $EE;    

	public function __construct()
	{
		parent::__construct();  // run constructor which will handle get_instance() etc.
	}

    public function get_global_variables()
    {
        $arr = array();
        $q = $this->EE->db->get('global_variables');
        foreach($q->result() as $global_variable)
        {
            $arr[$global_variable->variable_name] = $global_variable->variable_data;
        }

        return $arr;
    }

    /**
     * Get array of [field_name] -> field_id
     *
     */
    public function get_channel_fields($field_group_id=FALSE)
    {
        $fields = array();

        if($field_group_id)
        {
            $this->EE->db->where('group_id', $field_group_id);
        }

        $q = $this->EE->db->get('channel_fields');
        foreach($q->result() as $field)
        {
            $fields[$field->field_name] = $field->field_id;
        }

        return $fields;
    }

    public function get_channel_field_colname($field_name)
    {
        $q = $this->EE->db->get_where('channel_fields', array('field_name' => $field_name));
        if($q->num_rows() > 0)
        {
            return 'field_id_'.$q->row('field_id');
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Get array of [field_id] => field_name
     *
     * @param  $channel_id
     * @return void
     */
    public function get_channel_field_map($channel_id=FALSE, $channel_name=FALSE)
    {
        $where_array = array();
        if($channel_id)
        {
            $where_array['channel_id'] = $channel_id;
        }
        if($channel_name)
        {
            $where_array['channel_name'] = $channel_name;
        }

        $c = $this->EE->db->get_where('channels', $where_array);
        if($c->num_rows() > 0)
        {
            $field_group_id = $c->row('field_group');
            // fieldgroup
            $custom_fields = array();

            $cf = $this->EE->db->get_where('channel_fields', array('group_id' => $field_group_id));

            foreach($cf->result() as $field)
            {
                $custom_fields[$field->field_id] = $field->field_name;
            }

            return $custom_fields;

        }
        else
        {
            return FALSE;
        }                
    }

    /**
     * Will return an array of [member_field_name] => [member_data.col_name]
     *
     * This will also cache the result so that they are only looked up once for each page load at least
     */
    public function get_member_fields()
    {
        $mfields = array();
        if(!isset($this->EE->session->cache['eelib']['member_fields']))
        {
            $query = $this->EE->db->get('member_fields');

            foreach($query->result() as $member_field)
            {
                $mfields[ $member_field->m_field_name ] = "m_field_id_".$member_field->m_field_id;
                $mfields[ 'fielddesc_' . $member_field->m_field_name ] = $member_field->m_field_description;
            }
            $this->EE->session->cache['eelib']['member_fields'] = $mfields;
        }
        else
        {
            $mfields = $this->EE->session->cache['eelib']['member_fields'];
        }
        return $mfields;
    }

    public function get_member_field_colname($field_name)
    {
        $fields = $this->get_member_fields();
        return $fields[$field_name];
    }


    /**
     * Get category field map [field_name] => field_id
     */
    public function get_category_fieldmap()
    {
        $cf = $this->EE->db->get('category_fields');
        $cfieldmap = array();
        foreach($cf->result() as $category_field)
        {
            $cfieldmap[$category_field->field_name] = $category_field->field_id;
        }

        return $cfieldmap;
    }

    /**
     * Get value for a specific member field (by label)
     *
     * @param  $field_name
     * @return
     */
    public function get_member_field($field_name, $member_id=FALSE)
    {
        if(!$member_id)
        {
            $member_id = $this->EE->session->userdata('member_id');
        }
                
        $fields = $this->get_member_fields();
        if(!isset($this->EE->session->cache['eelib']['member_field_values_'.$member_id]))
        {
            $md = $this->EE->db->get_where('member_data', array('member_id' => $member_id));
            $this->EE->session->cache['eelib']['member_field_values_'.$member_id] = $md;
        }
        else
        {
            $md = $this->EE->session->cache['eelib']['member_field_values_'.$member_id];
        }

        return $md->row($fields[$field_name]);
    }

    /**
     * Get a member_id from a session_id
     * 
     * @param  $session_id
     * @return bool
     */
    public function get_member_id($session_id)
    {
        $q = $this->EE->db->get_where('sessions', array('session_id' => $session_id));
        if($q->num_rows() == 0)
        {
            return FALSE;
        }
        else
        {
            return $q->row('member_id');
        }
    }

    /**
     * Will initiallize a membermap in memory for quick lookups. Will only do it once per request.
     *
     * @return void
     */
    public function init_membermap()
    {

        if(!isset($this->EE->session->cache['eelib']['membermap']))
        {
            $membermap = array();
            $q = $this->EE->db->get('members');
            foreach($q->result() as $member)
            {
                $membermap[$member->member_id] = $member;
            }
            $this->EE->session->cache['eelib']['membermap'] = $membermap;
        }
    }

    /**
     * Get a member from the member map
     *
     * @param  $member_id
     * @return
     */
    public function get_member_from_map($member_id)
    {
        $membermap = $this->EE->session->cache['eelib']['membermap'];
        if($membermap && isset($membermap[$member_id]))
        {
            return $membermap[$member_id];
        }
    }
	
}	