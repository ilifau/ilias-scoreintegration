<?php
require_once ('Modules/Test/classes/class.ilObjTest.php');

/**
 * Extended Test Statistic Page GUI
 *
 * @author Frank Bauer <frank.bauer@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilCodeQuestionESTIntegrationPageGUI: ilUIPluginRouterGUI
 */
class ilCodeQuestionESTIntegrationPageGUI
{
    /** @var ilCtrl $ctrl */
	protected $ctrl;

	/** @var ilTemplate $tpl */
	protected $tpl;

	/** @var ilCodeQuestionESTIntegrationPlugin $plugin */
	protected $plugin;

	/** @var ilObjTest $testObj */
	protected $testObj;

	/** @var ilCodeQuestionESTIntegration $estObj */
	protected $estObj;

	/**
	 * ilCodeQuestionESTIntegrationPageGUI constructor.
	 */
	public function __construct()
	{
		global $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;

		$lng->loadLanguageModule('assessment');

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'CodeQuestionESTIntegration');
		$this->plugin->includeClass('class.ilCodeQuestionESTIntegration.php');

		$this->testObj = new ilObjTest($_GET['ref_id']);
		$this->estObj = new ilCodeQuestionESTIntegration($this->testObj, $this->plugin);
	}

	/**
	* Handles all commands, default is "show"
	*/
	public function executeCommand()
	{
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		if (!$ilAccess->checkAccess('write','',$this->testObj->getRefId()))
		{
			echo "no permission";
            ilUtil::sendFailure($lng->txt("permission_denied"), true);
            ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
		}
		
		$cmd = $this->ctrl->getCmd('showTestOverview');
		
		switch ($cmd)
		{
			case 'showMainESTPage':
				$this->prepareOutput();
				$this->tpl->setContent($this->overviewContent());
				$this->tpl->show();
			break;
			case 'zip':
				$this->sendZIP();
			break;
			default:
                ilUtil::sendFailure($lng->txt("permission_denied"), true);
                ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
				break;
		}
	}

	protected function overviewContent(){
		global $ilCtrl;
		$data      = $this->testObj->getCompleteEvaluationData(TRUE);
		$ilCtrl->saveParameterByClass('ilCodeQuestionESTIntegrationPageGUI','ref_id');

		$tpl = $this->plugin->getTemplate('tpl.il_ui_uihk_uicodequestionest_main_page.html');
		$tpl->setVariable("PARTICIPANT_COUNT", count($data->getParticipants()));
		$tpl->setVariable("LINK_ZIP", $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionESTIntegrationPageGUI')).'&cmd=zip');
		return $tpl->get();					
	}

	/**
	 * Prepare the test header, tabs etc.
	 */
	protected function prepareOutput()
	{
		/** @var ilLocatorGUI $ilLocator */
		/** @var ilLanguage $lng */
		global $ilLocator, $lng;

		$this->ctrl->setParameterByClass('ilObjTestGUI', 'ref_id',  $this->testObj->getRefId());
		$ilLocator->addRepositoryItems($this->testObj->getRefId());
		$ilLocator->addItem($this->testObj->getTitle(),$this->ctrl->getLinkTargetByClass('ilObjTestGUI'));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->testObj->getPresentationTitle());
		$this->tpl->setDescription($this->testObj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', 'tst'), $lng->txt('obj_tst'));
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('uicodequestionest.css'));		

		return true;
	}

	function sendZIP(){
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		if (!$ilAccess->checkAccess('write','',$this->testObj->getRefId()))
		{
			ilUtil::sendFailure($lng->txt("permission_denied"), true);
            ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
		}

		$zipFile  = tempnam(sys_get_temp_dir(), 'EST_');
		$err = $this->estObj->buildZIP($zipFile);

		if (!is_null($err))
		{
			ilUtil::sendFailure($err, true);
            ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		header('Content-Type: ' . finfo_file($finfo, $zipFile));
		finfo_close($finfo);

		//Use Content-Disposition: attachment to specify the filename
		header('Content-Disposition: attachment; filename='.basename($zipFile));

		//No cache
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		//Define file size
		header('Content-Length: ' . filesize($zipFile));

		ob_clean();
		flush();
		readfile($zipFile);

		//cleanup
		if (file_exists($zipFile)){
			unlink($zipFile);
		}
		die;		
	}
}
?>