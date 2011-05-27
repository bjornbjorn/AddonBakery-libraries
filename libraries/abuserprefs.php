<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once PATH_THIRD.'libraries/ab/ab_libbase'.EXT;

/**
 * EE Library - User preferences
 *
 * Developer: Bjorn Borresen / AddonBakery
 * Date: 09.05.11
 * Time: 08:52
 *  
 */
 
class Abuserprefs extends Ab_LibBase
{

    /**
     * Set a preference key/valye combo
     *
     * @param  $key
     * @param  $value
     * @param $member_id optional, will default to logged in member
     * @return
     */
    public function set_pref($key, $value, $member_id = FALSE)
    {
        if(!$member_id)
        {
            $member_id = $this->EE->session->userdata('member_id');
        }

        if(!$member_id)
        {
            return FALSE;   // user preferences are for specific members only
        }

        $pref_array = array(
            'site_id' => $this->EE->config->item('site_id'),
            'member_id' => $member_id,
            'pref_key' => $key,
        );

        $this->EE->db->from('ab_user_prefs');
        $this->EE->db->where($pref_array);
        $status = FALSE;
        if($this->EE->db->count_all_results())
        {
            $status = $this->EE->db->update('ab_user_prefs', array('pref_value' => $value), $pref_array);
        }
        else
        {
            $pref_array['pref_value'] = $value;
            $status = $this->EE->db->insert('ab_user_prefs', $pref_array);
        }

        return $status;
    }


    /**
     * Get a user preference
     *
     * @param  $key
     * @param $member_id (optional, defaults to currently logged in member)
     * @return the preference value of FALSE if not found
     */
    public function get_pref($key, $member_id=FALSE)
    {
        if(!$member_id)
        {
            $member_id = $this->EE->session->userdata('member_id');
        }

        if(!$member_id)
        {
            return FALSE;   // user preferences are for specific members only
        }

        $q = $this->EE->db->get_where('ab_user_prefs', array(
            'site_id' => $this->EE->config->item('site_id'),
            'member_id' => $member_id,
            'pref_key' => $key));
        if($q->num_rows() > 0)
        {
            return $q->row('pref_value');
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Install this preference library
     */
    public function install()
    {
        $this->EE->load->dbforge();

        $ab_user_prefs_fields = array(
            'ab_user_pref_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,),
            'site_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'null' => FALSE,),
            'member_id' => array(
                'type' => 'int',
                'constraint' => '10',
                'null' => FALSE,),
            'pref_key' => array(
                'type' => 'varchar',
                'constraint' => '255',
                'null' => FALSE,),
            'pref_value' => array(
                'type' => 'text',),
        );

        $this->EE->dbforge->add_field($ab_user_prefs_fields);
        $this->EE->dbforge->add_key('ab_user_pref_id', TRUE);
        $this->EE->dbforge->create_table('ab_user_prefs');
    }

    /**
     * Uninstall this preference library
     */
    public function uninstall()
    {
        $this->EE->load->dbforge();
        $this->EE->dbforge->drop_table('ab_user_prefs');
    }

}