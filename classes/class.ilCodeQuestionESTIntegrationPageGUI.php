<?php
require_once ('Modules/Test/classes/class.ilObjTest.php');
require_once('./Services/FileUpload/classes/class.ilFileUploadGUI.php');
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

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
		$this->plugin->loadLanguageModule();

		$this->testObj = new ilObjTest($_GET['ref_id']);
		$this->estObj = new ilCodeQuestionESTIntegration($this->testObj, $this->plugin);
	}

	private function redirectToIndex(){
		ilUtil::redirect('ilias.php?baseClass=iluipluginroutergui&cmdNode=we:xh&cmdClass=ilcodequestionestintegrationpagegui&cmd=showMainESTPage&ref_id='.$this->testObj->getRefId());
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
			ilUtil::sendFailure($lng->txt("permission_denied"), true);
            $this->redirectToIndex();
		}
		$cmd = $this->ctrl->getCmd('showTestOverview');
		
		switch ($cmd)
		{
			case 'uploadFiles':			
				$this->prepareOutput();
				$this->tpl->setContent($this->uploadFiles());
				$this->tpl->show();
				break;
			case 'showMainESTPage':
				$this->prepareOutput();
				$this->tpl->setContent($this->overviewContent());
				$this->tpl->show();
			break;
			case 'zip':
				$this->sendZIP(FALSE);
			break;
			case 'latexZip':
				$this->sendZIP(TRUE);
			break;
			default:
                ilUtil::sendFailure($lng->txt("permission_denied"), true);
                $this->redirectToIndex();
				break;
		}
	}

	protected function getMaxFileSizeString()
	{
		// get the value for the maximal uploadable filesize from the php.ini (if available)
		$umf = ini_get("upload_max_filesize");
		// get the value for the maximal post data from the php.ini (if available)
		$pms = ini_get("post_max_size");
		
		//convert from short-string representation to "real" bytes
		$multiplier_a=array("K"=>1024, "M"=>1024*1024, "G"=>1024*1024*1024);
		
		$umf_parts=preg_split("/(\d+)([K|G|M])/", $umf, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $pms_parts=preg_split("/(\d+)([K|G|M])/", $pms, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        
        if (count($umf_parts) == 2) { $umf = $umf_parts[0]*$multiplier_a[$umf_parts[1]]; }
        if (count($pms_parts) == 2) { $pms = $pms_parts[0]*$multiplier_a[$pms_parts[1]]; }
        
        // use the smaller one as limit
		$max_filesize = min($umf, $pms);

		if (!$max_filesize) $max_filesize=max($umf, $pms);
	
    	//format for display in mega-bytes
		$max_filesize = sprintf("%.1f MB",$max_filesize/1024/1024);
		
		return $max_filesize;
	}

	/**
	 * Prepares Fileupload form and returns it.
	 * @return ilPropertyFormGUI
	 */
	public function getFileUploadForm()
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng, $ilCtrl;

		$form = new ilPropertyFormGUI();
		$form->setId("upload");
        $form->setMultipart(true);
		$form->setHideLabels();
		//$form->setTarget("cld_blank_target");
		$form->setFormAction($ilCtrl->getFormAction($this, "uploadFiles"));
		$form->setTableWidth("100%");

		$item = new ilCustomInputGUI($this->plugin->txt('archive_file'));		
		$item->setHTML('<input type="file" id="upload_files" name="upload_files">');
		$form->addItem($item);	
		$form->addCommandButton('uploadFiles', $lng->txt('submit'));
		return $form;
	}

	private function uploadCodeToMessage($code) 
    { 
        switch ($code) { 
            case UPLOAD_ERR_INI_SIZE: 
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break; 
            case UPLOAD_ERR_PARTIAL: 
                $message = "The uploaded file was only partially uploaded"; 
                break; 
            case UPLOAD_ERR_NO_FILE: 
                $message = "No file was uploaded"; 
                break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $message = "Missing a temporary folder"; 
                break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $message = "Failed to write file to disk"; 
                break; 
            case UPLOAD_ERR_EXTENSION: 
                $message = "File upload stopped by extension"; 
                break; 

            default: 
                $message = "Unknown upload error"; 
                break; 
        } 
        return $message; 
    } 

	/**
	 * Prepares Fileupload form and returns it.
	 * @return ilPropertyFormGUI
	 */
	public function getDragAndDropFileUploadForm()
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng, $ilCtrl;
		include_once("./Services/Form/classes/class.ilDragDropFileInputGUI.php");
        include_once("./Services/jQuery/classes/class.iljQueryUtil.php");
		$form = new ilPropertyFormGUI();
		$form->setId("upload");
        $form->setMultipart(true);
		$form->setHideLabels();
		$form->setTarget("cld_blank_target");
		$form->setFormAction($ilCtrl->getFormAction($this, "uploadFiles"));
		$form->setTableWidth("100%");
		
		$file_input = new ilDragDropFileInputGUI($lng->txt("cld_upload_files"), "upload_files");
		$file_input->setPostVar('file_to_upload');		
		$file_input->setTitle($lng->txt('upload'));
		$file_input->setSuffixes(array( ".zip" ));		
		
		$form->addItem($file_input);
		$form->addCommandButton("uploadFiles", $lng->txt("upload"));
        $form->addCommandButton("cancelAll", $lng->txt("cancel"));
		
		
		return $form;
	}

	protected function uploadFiles(){
		/*$form = $this->getDragAndDropFileUploadForm();			
		if ($form->checkInput()){}*/
		$tpl = $this->overviewTemplate();
		$file = $_FILES['upload_files'];

		if ($file['error']!=0){
			ilUtil::sendFailure($this->uploadCodeToMessage($file['error']), true);
			$this->redirectToIndex();
			return;
		}

		if (!file_exists($file['tmp_name'])){
			ilUtil::sendFailure($lng->txt('file_not_found'), true);
			$this->redirectToIndex();
			return;
		}
		$results = $this->estObj->processZipFile($file['tmp_name']);

		$info_tpl = $this->plugin->getTemplate('tpl.il_ui_uihk_uicodequestionest_succes_info.html');
		$err_tpl = $this->plugin->getTemplate('tpl.il_ui_uihk_uicodequestionest_fail_info.html');

		//SUCCESS		
		$info_tpl->setCurrentBlock("success_line");
		$count = 0;
		$countu = 0;
		foreach($results["files"] as $file){
			if ($file['stored']){
				$count++;
				
				$info_tpl->setVariable("LOGIN", $file['login']);
				$info_tpl->setVariable("POINTS", $file['points']);
				$info_tpl->setVariable("COMMENT", $file['comment']);
				$info_tpl->parseCurrentBlock();
			} else {
				$countu++;
			}
		}
		$info_tpl->setVariable("NUMBER_STORED", $count);

		//ERROR
		if ($countu>0){
			$err_tpl->setCurrentBlock("unprocessed_line");
			foreach($results["files"] as $file){
				if (!$file['stored']){
					$err_tpl->setVariable("LOGIN", $file['login']);
					$err_tpl->setVariable("POINTS", $file['points']);
					$err_tpl->setVariable("COMMENT", $file['comment']);
					$err_tpl->setVariable("ERROR", $file['error']);
					$err_tpl->parseCurrentBlock();
				}
			}
			$err_tpl->setVariable("NUMBER_UNPROCESSED", $countu);

			$err_tpl->setCurrentBlock("unprocessed");
			$err_tpl->parseCurrentBlock();
		}
				
		
		if (count($results['wrongTest'])>0){
			$err_tpl->setCurrentBlock("wrongtest_line");
			foreach($results["wrongTest"] as $file){
				$err_tpl->setVariable("LOGIN", $file['login']);
				$err_tpl->setVariable("TEST_ID", $file['testID']);
				$err_tpl->parseCurrentBlock();				
			}
			$err_tpl->setVariable("NUMBER_WRONG", count($results['wrongTest']));

			$err_tpl->setCurrentBlock("wrongtest");
			$err_tpl->parseCurrentBlock();
		}

		if (count($results['invalidComment'])>0){
			$err_tpl->setCurrentBlock("invalid_line");
			foreach($results["invalidComment"] as $file){
				$err_tpl->setVariable("LOGIN", $file['login']);
				$err_tpl->setVariable("CONTENT", $file['rawContent']);
				$err_tpl->parseCurrentBlock();				
			}
			$err_tpl->setVariable("NUMBER_INVALID", count($results['invalidComment']));

			$err_tpl->setCurrentBlock("invalid");
			$err_tpl->parseCurrentBlock();
		}
		
		ilUtil::sendSuccess($info_tpl->get(), true);
		ilUtil::sendFailure($err_tpl->get(), true);
		return $tpl->get();
	}

	protected function overviewTemplate(){
		global $ilCtrl, $ilDB;

		$data      = $this->testObj->getCompleteEvaluationData(TRUE);
		$ilCtrl->saveParameterByClass('ilCodeQuestionESTIntegrationPageGUI','ref_id');

		$tpl = $this->plugin->getTemplate('tpl.il_ui_uihk_uicodequestionest_main_page.html');
		$tpl->setVariable("PARTICIPANT_COUNT", count($data->getParticipants()));
		$tpl->setVariable("LINK_ZIP", $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionESTIntegrationPageGUI')).'&cmd=zip');
		$tpl->setVariable("LINK_LATEXZIP", $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionESTIntegrationPageGUI')).'&cmd=latexZip');
		//echo $this->getFileUploadFormHTML()."<hr>";die;
		//$upload = $this->getFileUploadForm();
		$tpl->setVariable("FILE_UPLOAD", $this->getFileUploadForm()->getHTML());
		return $tpl;
	}

	protected function overviewContent(){		
		
		return $this->overviewTemplate()->get();					
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

	function sendZIP($latex){
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		if (!$ilAccess->checkAccess('write','',$this->testObj->getRefId()))
		{
			ilUtil::sendFailure($lng->txt("permission_denied"), true);
            $this->redirectToIndex();
		}

		if ($latex) {
			$zipFile  = tempnam(sys_get_temp_dir(), 'Latex_');
			$err = $this->estObj->buildLatexZIP($zipFile);
		} else {
			$zipFile  = tempnam(sys_get_temp_dir(), 'EST_');
			$err = $this->estObj->buildZIP($zipFile);
		}

		if (!is_null($err))
		{
			ilUtil::sendFailure($err, true);
            $this->redirectToIndex();
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
		ilUtil::sendSuccess($this->plugin->txt("download_created"), true);		
		$this->redirectToIndex();
		//die;		
	}
}
?>