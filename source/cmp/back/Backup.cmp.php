<?php
/**
* SBND F&CMS - Framework & CMS for PHP developers
*
* Copyright (C) 1999 - 2013, SBND Technologies Ltd, Sofia, info@sbnd.net, http://sbnd.net
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author SBND Techologies Ltd <info@sbnd.net>
* @package backup
* @version 1.1
*/

BASIC::init()->imported("filesystem.mod");
/**
 * 
 * Package for admin panel
 * @author Evgeni Baldzhiyski
 * @version 0.3
 * @package cmp.back
 */
class Backup extends CmsComponent {
	/**
	 * Template for the component
	 * @access public
	 * @var string
	 */
	var $template_list = 'backup-list.tpl';
	/**
	 * @access privat
	 * @var array
	 */
	protected $list = array();
	/**
	 * 
	 * Main function - the constructor of the component
	 * @access public
	 * @see CmsComponent::main()
	 */
	function main(){
		parent::main();
		
		$this->base = BASIC_SQL::init()->backupEngine->option('storage');
		
		$this->setField("time", array(
			'text' => BASIC_LANGUAGE::init()->get('backup_time')
		));
		
		$this->sorting = new BasicSorting('time', true, $this->prefix);
		
		$this->delAction('add');
		$this->delAction('edit');
		$this->delAction('save');
		
		$this->addAction('revert', 'ActionRevert', BASIC_LANGUAGE::init()->get('revert'), 2, BASIC_LANGUAGE::init()->get('are_you_sure_to_revert'));
		$this->addAction('backup', 'ActionBackUp', BASIC_LANGUAGE::init()->get('backup'));	
	}
    /**
     * Create system variables
     * 
     * @access public
     * @return void
     */
	function startManager(){
		parent::startManager();
		
		if(!$this->cmd && BASIC_URL::init()->test('days')){
			CMS_SETTINGS::init()->setAndSave('backup', (int)BASIC_URL::init()->request('days'), false, true);
		}
		$this->system[] = 'days';
	}
	/**
	 * Return HTML for list view
	 * 
	 * @access public 
	 * @return string
	 */
	function ActionList(){
		$this->map('', ' ', null, 'width=50');
		$this->map('time', BASIC_LANGUAGE::init()->get('backup_time'), 'formatter');
		
		BASIC_TEMPLATE2::init()->set('auto_backup_control', BASIC_GENERATOR::init()->controle('select', $this->prefix.'days', CMS_SETTINGS::init()->get('backup'), array(
			'data' => array(
				0 => BASIC_LANGUAGE::init()->get('never'),
				1 => BASIC_LANGUAGE::init()->get('day'),
				7 => BASIC_LANGUAGE::init()->get('week'),
				30 => BASIC_LANGUAGE::init()->get('month'),
				365 => BASIC_LANGUAGE::init()->get('year')
			),
			'onchange' => "this.form.submit()"
		)), $this->template_list);
		
		return parent::ActionList();
	}
	/**
	 * Format cells in list view
	 * 
	 * @access public
	 * @param string $val
	 * @param string $name
	 * @return string
	 */
	function formatter($val, $name){
		if($name == 'time'){
			return @date("d.m.Y H:i:s", $val);
		}
		return $val;
	}
	/**
	 * Callback of AddAction method executed in main function with parameter revert 
	 * Perform revert action 
	 * 
	 * @access public
	 * @param string $id
	 * @return void
	 */
	function ActionRevert($id){
		BASIC_SQL::init()->backupEngine->revert('', $this->base."/".$id);
	}
	/**
	 * Callback of AddAction method executed in main function with parameter backup 
	 * Perform BackUp action 
	 * 
	 * @access public
	 * @param string $id
	 * @return void
	 */
	function ActionBackUp(){
		BASIC_SQL::init()->backupEngine->backup();
	}
	/**
     * Full record's data loader
     *
     * @access public
     * @param array [$ids]
     * @param string $criteria
     * @param boolean $include_all
     * @return ComponentReader
     */
	function getRecords($ids = array(), $criteria = '', $include_all = false){		
		if(!$this->list){
			
			if(!$dir = @opendir(BASIC::init()->root().$this->base)){
				if(!@mkdir(BASIC::init()->root().$this->base)){
					BASIC_ERROR::init()->setError(BASIC_LANGUAGE::init()->get('access_denied_for_backup_storage'));
				}else{
					$dir = @opendir(BASIC::init()->root().$this->base);
				}
			}
			while ($file_name = readdir($dir)){
				if($file_name[0] == '.') continue;
				
				$this->list[] = array(
					'id' => $file_name,
					'time' => $file_name
				);
			}
		}
		$reader = ComponentReader::getEmptyReader();
		
		$this->paging = new BasicComponentPaging($this->prefix);
		$this->paging->init(count($this->list), $this->maxrow);
		
		$this->list = $this->paging->filterArray($this->list, true);
		$this->list = $this->sorting->sortCollection($this->list);
		
		$reader->addRows($this->list);
		
		return $reader;
	}
	/**
     * Overwrite Cms Component method to delete physical directory in file structure
     *
     * @access public
     * @param array [$ids]
     * @return void
     */
	function ActionRemove($ids){
		if(!is_array($ids)) $ids = array($ids);
		
		foreach ($ids as $id){
			$dir = new BasicFolder(BASIC::init()->root().$this->base."/".$id);
			$dir->remove();
		}
	}
}