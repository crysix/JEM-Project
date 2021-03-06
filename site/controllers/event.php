<?php
/**
 * @version 2.1.7
 * @package JEM
 * @copyright (C) 2013-2016 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

require_once (JPATH_COMPONENT_SITE.'/classes/controller.form.class.php');

/**
 * Event Controller
 */
class JemControllerEvent extends JemControllerForm
{
	protected $view_item = 'editevent';
	protected $view_list = 'eventslist';
	protected $_id = 0;

	/**
	 * Method to add a new record.
	 *
	 * @return	boolean	True if the event can be added, false if not.
	 */
	public function add()
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
		}
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param	array	An array of input data.
	 *
	 * @return	boolean
	 */
	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user       = JemFactory::getUser();
		$categoryId = JArrayHelper::getValue($data, 'catid', JFactory::getApplication()->input->getInt('catid', 0), 'int');

		if ($user->can('add', 'event', false, $categoryId ? $categoryId : false)) {
			return true;
		}

		// In the absense of better information, revert to the component permissions.
		return parent::allowAdd();
	}

	/**
	 * Method override to check if you can edit an existing record.
	 * @todo: check if the user is allowed to edit/save
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Initialise variables.
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;
		$user     = JemFactory::getUser();

		if (isset($data['created_by'])) {
			$created_by = $data['created_by'];
		} else {
			$record = $this->getModel()->getItem($recordId);
			$created_by = isset($record->created_by) ? $record->created_by : false;
		}

		if ($user->can('edit', 'event', $recordId, $created_by)) {
			return true;
		}

		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 *
	 * @return	Boolean	True if access level checks pass, false otherwise.
	 */
	public function cancel($key = 'a_id')
	{
		parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 * @param	string	$urlVar	The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return	Boolean	True if access level check and checkout passes, false otherwise.
	 */
	public function edit($key = null, $urlVar = 'a_id')
	{
		$result = parent::edit($key, $urlVar);

		return $result;
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param	string	$name	The model name. Optional.
	 * @param	string	$prefix	The class prefix. Optional.
	 * @param	array	$config	Configuration array for model. Optional.
	 *
	 * @return	object	The model.
	 *
	 */
	public function getModel($name = 'editevent', $prefix = '', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param	int		$recordId	The primary key id for the item.
	 * @param	string	$urlVar		The name of the URL variable for the id.
	 *
	 * @return	string	The arguments to append to the redirect URL.
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'a_id')
	{
		// Need to override the parent method completely.
		$jinput = JFactory::getApplication()->input;
		$tmpl   = $jinput->getCmd('tmpl', '');
		$layout = $jinput->getCmd('layout', 'edit');
		$append = '';

		// Setup redirect info.
		if ($tmpl) {
			$append .= '&tmpl='.$tmpl;
		}

		$append .= '&layout=edit';

		if ($recordId) {
			$append .= '&'.$urlVar.'='.$recordId;
		}

		$itemId = $jinput->getInt('Itemid', 0);
		$catId  = $jinput->getInt('catid', 0);
		$locId  = $jinput->getInt('locid', 0);
		$date   = $jinput->getCmd('date', '');
		$return = $this->getReturnPage();

		if ($itemId) {
			$append .= '&Itemid='.$itemId;
		}

		if($catId) {
			$append .= '&catid='.$catId;
		}

		if($locId) {
			$append .= '&locid='.$locId;
		}

		if($date) {
			$append .= '&date='.$date;
		}

		if ($return) {
			$append .= '&return='.base64_encode($return);
		}

		return $append;
	}

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return	string	The return URL.
	 */
	protected function getReturnPage()
	{
		$return = JFactory::getApplication()->input->get('return', null, 'base64');

		if (empty($return) || !JUri::isInternal(base64_decode($return))) {
			if (!empty($this->_id)) {
				return JRoute::_(JemHelperRoute::getEventRoute($this->_id));
			}
			return JUri::base();
		}
		else {
			return base64_decode($return);
		}
	}


	/**
	 * Function that allows child controller access to model data
	 * after the data has been saved.
	 * Here used to trigger the jem plugins, mainly the mailer.
	 *
	 * @param   JModel(Legacy)  $model      The data model object.
	 * @param   array           $validData  The validated data.
	 *
	 * @return  void
	 */
	protected function _postSaveHook($model, $validData = array())
	{
		$task = $this->getTask();
		if ($task == 'save') {
			$isNew     = $model->getState('editevent.new');
			$this->_id = $model->getState('editevent.id');

			// trigger all jem plugins
			JPluginHelper::importPlugin('jem');
			$dispatcher = JemFactory::getDispatcher();
			$dispatcher->trigger('onEventEdited', array($this->_id, $isNew));

			// but show warning if mailer is disabled
			if (!JPluginHelper::isEnabled('jem', 'mailer')) {
				JError::raiseNotice(100, JText::_('COM_JEM_GLOBAL_MAILERPLUGIN_DISABLED'));
			}
		}
	}

	/**
	 * Method to save a record.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 * @param	string	$urlVar	The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return	Boolean	True if successful, false otherwise.
	 */
	public function save($key = null, $urlVar = 'a_id')
	{
		$result = parent::save($key, $urlVar);

		// If ok, redirect to the return page.
		if ($result) {
			$this->setRedirect($this->getReturnPage());
		}

		return $result;
	}

	/**
	 * Saves the registration to the database
	 */
	function userregister()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');

		$id  = JFactory::getApplication()->input->getInt('rdid', 0);
		$rid = JFactory::getApplication()->input->getInt('regid', 0);

		// Get the model
		$model = $this->getModel('Event', 'JemModel');

		$reg = $model->getUserRegistration($id);
		if ($reg !== false && $reg->id != $rid) {
			$msg = JText::_('COM_JEM_ALLREADY_REGISTERED');
			$this->setRedirect(JRoute::_(JEMHelperRoute::getEventRoute($id), false), $msg, 'error');
			$this->redirect();
			return;
		}

		$model->setId($id);
		$register_id = $model->userregister();

		if (!$register_id)
		{
			$msg = $model->getError();
			$this->setRedirect(JRoute::_(JEMHelperRoute::getEventRoute($id), false), $msg, 'error');
			$this->redirect();
			return;
		}

		JemHelper::updateWaitingList($id);

		JPluginHelper::importPlugin('jem');
		$dispatcher = JemFactory::getDispatcher();
		$dispatcher->trigger('onEventUserRegistered', array($register_id));

		$cache = JFactory::getCache('com_jem');
		$cache->clean();

		$msg = JText::_('COM_JEM_REGISTRATION_THANKS_FOR_RESPONSE');

		$this->setRedirect(JRoute::_(JEMHelperRoute::getEventRoute($id), false), $msg);
	}

	/**
	 * Deletes a registered user
	 */
	function delreguser()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');

		$id = JFactory::getApplication()->input->getInt('rdid', 0);

		// Get/Create the model
		$model = $this->getModel('Event', 'JEMModel');

		$model->setId($id);
		$model->delreguser();

		JEMHelper::updateWaitingList($id);

		JPluginHelper::importPlugin('jem');
		$dispatcher = JemFactory::getDispatcher();
		$dispatcher->trigger('onEventUserUnregistered', array($id));

		$cache = JFactory::getCache('com_jem');
		$cache->clean();

		$msg = JText::_('COM_JEM_UNREGISTERED_SUCCESSFULL');
		$this->setRedirect(JRoute::_(JEMHelperRoute::getEventRoute($id), false), $msg);
	}
}
