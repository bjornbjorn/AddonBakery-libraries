<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'libraries/ab/ab_libbase'.EXT;

/**
 * AddonBakery preferences library
 *
 * @package		Libraries
 * @subpackage	ThirdParty
 * @category	Library
 * @author      Bjorn Borresen / AddonBakery
 * @since       06.nov.2010 07:51:47
 * @link		http://www.addonbakery.com
 */ 
class Abprefs extends Ab_LibBase
{
    /**
     * @var Devkit_code_completion
     */
    public $module_name = '';
    public $table_name = '';
    private $preferences;    

    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';

    /**
     * Load this library like this:
     *
     * $this->EE->load->library('abprefs', array('module_name' => $this->module_name));
     *
     * @param  $config - array('module_name' => 'your_module_name')
     */
	public function __construct($config)
	{
		parent::__construct();  // run constructor which will handle get_instance() etc.
        $this->module_name = strtolower($config['module_name']);
        $this->table_name = $this->module_name.'_prefs';

        if($this->EE->db->table_exists($this->table_name))
        {
            $this->fetch_preferences();
        }
	}

    /**
     * Fetch preferences into memory
     */
    public function fetch_preferences()
    {
        $q = $this->EE->db->get_where($this->table_name, array('site_id' => $this->EE->config->item('site_id')));
        if($q->num_rows() > 0)
        {
            $arr = $q->result_array();
            $this->preferences = $arr[0];
        }
    }

    /**
     * This function should be run on install
     *
     * @var $default_preferences
     */
    public function install($default_preferences=array(), $site_id=FALSE)
    {
        $this->EE->load->dbforge();
        
        $fields = array(
                $this->table_name.'_id'	=>	array('type' => 'int',
                            'constraint'	=>	'10',
                            'unsigned'	=>	TRUE,
                            'auto_increment'=>	TRUE),

                'site_id' => array('type' => 'int',
                            'constraint'	=> '10',
                             'null' => FALSE),
        );

        foreach($default_preferences as $pref_name => $pref)
        {
            $abpref_dbtype = Abprefs_dbtype::getDbType($pref['type']);
            $field_creation_info = array(
                'type' => $abpref_dbtype->type,
                'null' => $abpref_dbtype->null,
            );
            if($abpref_dbtype->constraint) {
                $field_creation_info['constraint'] = $abpref_dbtype->constraint;
            }

            $fields[$pref_name] = $field_creation_info;
        }

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key($this->table_name.'_id', TRUE);
        $this->EE->dbforge->create_table($this->table_name);
        $this->init_site($default_preferences, $site_id);
    }

    /**
     * Init the current site's preferences
     *
     * @param  $default_preferences
     * @return void
     */
    public function init_site($default_preferences, $site_id=FALSE)
    {
        if(!$site_id)
        {
            $site_id = $this->EE->config->item('site_id');
        }

        $insert = array('site_id' => $site_id);
        foreach($default_preferences as $pref_name => $pref)
        {
            $insert[$pref_name] = $pref['value'];
        }
        $this->EE->db->insert($this->table_name, $insert );
    }

    public function uninstall()
    {
        $this->EE->load->dbforge();
        $this->EE->dbforge->drop_table($this->table_name);
    }

    public function get_preferences()
    {
        return $this->preferences;
    }

     /**
     * Return a preference value
     *
     * @param $key the preference key / name
     * @param $default_value the default value to return if preference is not set
     * @return 
     */
    public function get($key, $default_value = FALSE)
    {
        if(isset($this->preferences[$key]))
        {
            return $this->preferences[$key];
        }
        else
        {
            return $default_value;
        }
    }

     /**
     * Set preference value
     *
     * @param $key the preference key
     * @param $value the preference value
     * @return 
     */
    public function set($key, $value)
    {
        $this->preferences[$key] = $value;
    }

    /**
     * Check whether or not we have preferences saved for a specific site
     *
     * @param site_id the site id to check (optional, will default to current site)
     */
    public function has_preferences($site_id=FALSE)
    {
        if(!$site_id)
        {
            $site_id = $this->EE->config->item('site_id');
        }
        $q = $this->EE->db->get_where($this->table_name, array('site_id' => $site_id));
        return $q->num_rows() > 0;
    }

    /**
     * Save the current preferences to the database
     *
     * @return bool
     */
    public function save_preferences()
    {
        $save_arr = $this->preferences;
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $this->EE->db->update($this->table_name, $save_arr);
    }

    
   /**
	 * Serialize an array
	 *
	 * This function first converts any slashes found in the array to a temporary
	 * marker, so when it gets unserialized the slashes will be preserved
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	private function _serialize($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = str_replace('\\', '{{slash}}', $val);
			}
		}
		else
		{
			$data = str_replace('\\', '{{slash}}', $data);
		}

		return serialize($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Unserialize
	 *
	 * This function unserializes a data string, then converts any
	 * temporary slash markers back to actual slashes
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	private function _unserialize($data)
	{
		$data = @unserialize(strip_slashes($data));

		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = str_replace('{{slash}}', '\\', $val);
			}

			return $data;
		}

		return str_replace('{{slash}}', '\\', $data);
	}
}

class Abprefs_dbtype
{
    public $type;
    public $constraint;
    public $null = FALSE;

    public function __construct($type, $constraint=FALSE)
    {
        $this->type = $type;
        $this->constraint = $constraint;
    }

    /**
     * Get database type for a name
     *
     * @static
     * @param  $name
     * @return Abprefs_dbtype
     */
    public static function getDbType($name)
    {
        switch($name)
        {
            case Abprefs::TYPE_BOOLEAN:
                return new Abprefs_dbtype('char', 1);
                break;
            case Abprefs::TYPE_STRING:
                return new Abprefs_dbtype('varchar', 255);
                break;
            case Abprefs::TYPE_TEXT:
                return new Abprefs_dbtype('text');
                break;
        }
    }
}