<?php

/**
 * Basic class for EST import/Export
 *
 * @author Frank Bauer <frank.bauer@fau.de>
 * @version $Id$
 *
 */
class ilCodeQuestionESTIntegration
{
	/** @var ilObjTest $testObj */
	protected $testObj;

	/** @var ilCodeQuestionESTIntegrationPlugin $plugin */
	protected $plugin;

	/**
	 * ilCodeQuestionESTIntegration constructor.
	 *
	 * @param ilObjTest $a_test_obj
	 * @param ilCodeQuestionESTIntegration $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		$this->testObj = $a_test_obj;
		$this->plugin = $a_plugin;
	}
	
	function buildZIP($zipFile){
		$data      = $this->testObj->getCompleteEvaluationData(TRUE);
		
		$zip = new ZipArchive();
		if ($zip->open($zipFile, ZipArchive::CREATE)!==TRUE) {			
			return "cannot open <$tempBase>\n";            
		}

		$tempBase = './EST';
		foreach($data->getParticipants() as $active_id => $userdata)
		{
				// Do something with the participants				
				$pass = $userdata->getScoredPass();
				foreach($userdata->getQuestions($pass) as $question)
				{
					$questionBase = $tempBase.'/'.sprintf("%06d", $question["id"]);					
					$objQuestion = $questions[$question["id"]];
					if (!$objQuestion){
						$objQuestion = $this->testObj->_instanciateQuestion($question["id"]);
						$questions[$question["id"]] = $objQuestion;
					}
					if (method_exists($objQuestion, 'getCompleteSource') && 
						method_exists($objQuestion, 'getExportFilename') &&
						method_exists($objQuestion, 'getExportSolution')){
						$filename = $objQuestion->getExportFilename();
						$solution = $objQuestion->getExportSolution($active_id, $pass);
						$code = $objQuestion->getCompleteSource($solution);

						$subFolder = sprintf("%06d-%06d-%06d-%s", $solution['solution_id'], $solution['active_fi'], $userdata->user_id, $userdata->login);
						$subFolder = $questionBase.'/'.preg_replace('/[^A-Za-z0-9_\-]/', '', $subFolder);
						$zip->addFromString($subFolder.'/'.$filename, $code);
					}
				}
				
				// Access some user related properties
				//$last_visited = $data->getParticipant($active_id)->getLastVisit();
		}
		$zip->close();

		return NULL;
	}
}

?>