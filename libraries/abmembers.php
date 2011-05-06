<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once PATH_THIRD.'libraries/ab/ab_libbase'.EXT;


/**
 * Various functionality related to EE member handling
 */
class Abmembers extends Ab_LibBase
{
    /**
     * Will return an array of [member_field_name] => [member_data.col_name]
     *
     * This will also cache the result so that they are only looked up once for each page load
     */
    public function get_member_fields()
    {
        $mfields = array();
        if(!isset($this->EE->session->cache['abmembers']['member_fields']))
        {
            $query = $this->EE->db->get('member_fields');

            foreach($query->result() as $member_field)
            {
                $mfields[ $member_field->m_field_name ] = "m_field_id_".$member_field->m_field_id;
            }
            $this->EE->session->cache['abmembers']['member_fields'] = $mfields;
        }
        else
        {
            $mfields = $this->EE->session->cache['abmembers']['member_fields'];
        }
        return $mfields;
    }

    /**
     * Get a member field's database column name
     *
     * @param $field_name the field name
     * @return string (column name) or FALSE if the member field did not exist
     */
    public function get_member_field_colname($field_name)
    {
        $fields = $this->get_member_fields();
        if(isset($fields[$field_name]))
        {
            return $fields[$field_name];
        }
        else
        {
            return FALSE;
        }
    }

}
