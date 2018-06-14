<?php

require_once './Modules/TestQuestionPool/classes/class.assQuestion.php';
require_once './Modules/Test/classes/inc.AssessmentConstants.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjFileHandlingQuestionType.php';
// fred: added for logging
include_once "./Modules/Test/classes/class.ilObjAssessmentFolder.php";
// fred.

/**
 * Basic class for EST import/Export
 *
 * @author Frank Bauer <frank.bauer@fau.de>
 * @version $Id$
 *
 */
class ilCodeQuestionScoreIntegration
{
	/** @var ilObjTest $testObj */
	protected $testObj;

	/** @var ilCodeQuestionScoreIntegrationPlugin $plugin */
	protected $plugin;

	/**
	 * ilCodeQuestionScoreIntegration constructor.
	 *
	 * @param ilObjTest $a_test_obj
	 * @param ilCodeQuestionScoreIntegration $a_plugin
	 */
	public function __construct($a_test_obj, $a_plugin)
	{
		global $lng;
		$lng->loadLanguageModule('assessment');
		$this->testObj = $a_test_obj;
		$this->plugin = $a_plugin;
	}

// fred: new function logAction
	function logAction($logtext = "", $question_id = "")
	{
		global $ilUser;
		if (ilObjAssessmentFolder::_enabledAssessmentLogging())
		{
			ilObjAssessmentFolder::_addLog($ilUser->getId(), $this->testObj->getId(), $logtext, $question_id, NULL, TRUE, $this->testObj->getRefId());
		}
	}
// fred.

	function updatePoints($active_fi, $question_fi, $pass, $reachedPoints, $maxPoints, $comment=NULL){
		global $ilDB;
		/*$ilDB->update("tst_solutions", array(
			"points" => array("float", $points)
		), array(
			"solution_id" => array("integer", $solution_id),
			"active_fi" => array("integer", $active_fi),
			"question_fi" => array("integer", $question_fi)
		));*/

		/*$ilDB->update("tst_test_result", array(
			"points" => array("float", $reachedPoints),
			"manual" => array("integer", 1)
		), array(
			"pass" => array("integer", $pass),
			"active_fi" => array("integer", $active_fi),
			"question_fi" => array("integer", $question_fi)
		));

		if (!is_null($comment)){
			$ilDB->update("tst_manual_fb", array(
				"feedback" => array("float", $reachedPoints)
			), array(
				"pass" => array("integer", $pass),
				"active_fi" => array("integer", $active_fi),
				"question_fi" => array("integer", $question_fi)
			));
		}*/


// fred: Don't update everything always, log the action
//		assQuestion::_setReachedPoints(
//			$active_fi,
//			$question_fi,
//			$reachedPoints,
//			$maxPoints,
//			$pass,
//			1, $this->testObj->areObligationsEnabled()
//		);

		self::_setReachedPointsOnly(
			$active_fi,
			$question_fi,
			$reachedPoints,
			$maxPoints,
			$pass,
			1, $this->testObj->areObligationsEnabled()
		);

		global $ilUser;
		$logtext = sprintf($this->plugin->txt('log_import_score'),
			$ilUser->getFullname() . " (" . $ilUser->getLogin() . ")",
			$reachedPoints,
			ilObjTestAccess::_getParticipantData($active_fi),
			assQuestion::_getQuestionTitle($question_fi));

		$this->logAction($logtext, $question_fi);
// fred.

		if (!is_null($comment)){
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";

			$feedback = ilUtil::stripSlashes(
				$comment,
				false, 
				ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")
			);
			$this->testObj->saveManualFeedback(
				$active_fi, 
				$question_fi, 
				$pass, 
				$feedback
			);
		}


// fred: In StudOn this is done in recalculateSolutions (patched)
//
//		include_once "./Modules/Test/classes/class.ilObjTestAccess.php";
//		include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
//		ilLPStatusWrapper::_updateStatus(
//				$this->testObj->getId(),
//				ilObjTestAccess::_getParticipantId($active_fi)
//		);
// fred.
	}

// fred: copied from assQuestion and modified
	/**
	 * Only set the points, a learner has reached answering the question
	 * Don't update the pass result or the the result cache
	 *
	 * @param integer $user_id The database ID of the learner
	 * @param integer $test_id The database Id of the test containing the question
	 * @param integer $points The points the user has reached answering the question
	 * @return boolean true on success, otherwise false
	 * @access public
	 */
	protected static function _setReachedPointsOnly($active_id, $question_id, $points, $maxpoints, $pass, $manualscoring, $obligationsEnabled)
	{
		global $ilDB;

		if ($points <= $maxpoints)
		{
			if (is_null($pass))
			{
				$pass = assQuestion::_getSolutionMaxPass($question_id, $active_id);
			}

			// retrieve the already given points
			$old_points = 0;
			$result = $ilDB->queryF("SELECT points FROM tst_test_result WHERE active_fi = %s AND question_fi = %s AND pass = %s",
				array('integer','integer','integer'),
				array($active_id, $question_id, $pass)
			);
			$manual = ($manualscoring) ? 1 : 0;
			$rowsnum = $result->numRows();
			if($rowsnum)
			{
				$row = $ilDB->fetchAssoc($result);
				$old_points = $row["points"];
				if($old_points != $points)
				{
					$affectedRows = $ilDB->manipulateF("UPDATE tst_test_result SET points = %s, manual = %s, tstamp = %s WHERE active_fi = %s AND question_fi = %s AND pass = %s",
						array('float', 'integer', 'integer', 'integer', 'integer', 'integer'),
						array($points, $manual, time(), $active_id, $question_id, $pass)
					);
				}
			}
			else
			{
				$next_id = $ilDB->nextId('tst_test_result');
				$affectedRows = $ilDB->manipulateF("INSERT INTO tst_test_result (test_result_id, active_fi, question_fi, points, pass, manual, tstamp) VALUES (%s, %s, %s, %s, %s, %s, %s)",
					array('integer', 'integer','integer', 'float', 'integer', 'integer','integer'),
					array($next_id, $active_id, $question_id, $points, $pass, $manual, time())
				);
			}

			if($old_points != $points || !$rowsnum)
			{
				return TRUE;
			}

			return FALSE;
		}
		else
		{
			return FALSE;
		}
	}
// fred.

	function processZipFile($zipFile){
		$zip = new ZipArchive();
		$zip->open($zipFile);
		$result = array(
			"numberOfFiles" => $zip->numFiles,
			"files" => array(),
			"metaFiles" => array(),
			"unparsableEntries" => array(),
			"ignoredFiles" => array(),
			"invalidComment" => array(),
			"wrongTest" => array()
		);
		
		for ($i=0; $i<$zip->numFiles;$i++) {
			//echo "index: $i\n";
			$item = $zip->statIndex($i);

			$filePath = trim($item['name']).'';
			
			if (substr( $filePath, 0, 9 ) == '__MACOSX/') {
				$result['metaFiles'][] = $item['name'];
				continue;
			} else if (substr( $filePath, 0, 1 ) == '.' || !(strpos($filePath, '/.')===false)) {
				$result['metaFiles'][] = $item['name'];
				continue;
			}

			$matches = array();
			preg_match_all(':test-([0-9]+)/question-([0-9]+)/solution-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-(.*)/(.*):', $filePath, $matches);
			if (count($matches)!=9 || trim($matches[8][0])=='' || count($matches[0])==0) {
				$result['unparsableEntries'][] = $item['name'];
				continue;
			}			
			$fileName = trim($matches[8][0]);

			$obj = array(
				"path" => $filePath,
				"file" => $fileName,
				"testID" => (int)$matches[1][0],
				"questionID" => (int)$matches[2][0],
				"solutionID" => (int)$matches[3][0],
				"activeID" => (int)$matches[4][0],
				"pass" => (int)$matches[5][0],
				"userID" => (int)$matches[6][0],
				"login" => trim($matches[7][0])
			);
			if (strtolower($obj["file"]) == 'comment'){		
				$obj['rawContent'] = $zip->getFromIndex($i);
				preg_match_all('/.*\:\s*(-?[0-9]+(\.[0-9]+)?)\s*\n([\s\S]*)/', $obj['rawContent'], $matches);
				//print_r($matches);
				if (count($matches)!=4 || count($matches[0])==0){
					$result['invalidComment'][] = $obj;					
				} else if ($this->testObj->getID() != $obj['testID']){
					$result['wrongTest'][] = $obj;
				} else {
					$obj['points'] = (float)$matches[1][0];
					$obj['comment'] = '<pre style="font-family:monospace">'.trim($matches[3][0]).'</pre>';
					$obj['stored'] = false;
					$result['files'][] = $obj;			
				}
			} else {
				$result['ignoredFiles'][] = $obj;			
			}
		}
		$this->storeInfo($result);


// fred: put to the end
		//we may have to do this only once!
		require_once './Modules/Test/classes/class.ilTestScoring.php';
		$scorer = new ilTestScoring($this->testObj);
		$scorer->setPreserveManualScores(true);
		$scorer->recalculateSolutions();

		$logtext = $this->plugin->txt('log_recalculated_solutions');
		$this->logAction($logtext);
// fred.


		return $result;
	}

	private function storeInfo(&$zipResults){
		global $lng;

		foreach($zipResults['files'] as &$obj){
			$objQuestion = $questions[$obj["questionID"]];
			if (!$objQuestion){
				$objQuestion = $this->testObj->_instanciateQuestion($obj["questionID"]);
				$questions[$obj["questionID"]] = $objQuestion;
			}
			if (method_exists($objQuestion, 'getExportSolution') ){
				$solution = $objQuestion->getExportSolution($obj["activeID"], $obj["pass"]);
				if ($solution['solution_id'] == $obj["solutionID"] && 
					$solution['active_fi'] == $obj["activeID"] &&
					$solution['question_fi'] == $obj["questionID"] &&
					$solution['pass'] == $obj["pass"] ){
						$this->updatePoints($obj["activeID"], $obj["questionID"], $obj["pass"], $obj["points"], $objQuestion->getPoints(), $obj["comment"]);
						$obj['stored'] = true;
				} else {
					$obj['error'] = $this->plugin->txt('error_inconsistent_id');
				}
			}  else {
				$obj['error'] = $this->plugin->txt('error_incompatible_question');
			}
		}
	}
	
	function buildZIP($zipFile){
		$data      = $this->testObj->getCompleteEvaluationData(TRUE);
		
		$zip = new ZipArchive();
		if ($zip->open($zipFile, ZipArchive::CREATE)!==TRUE) {			
			return "cannot open <$tempBase>\n";            
		}

		$tempBase = sprintf('./EST/test-%06d', $this->testObj->getId());
		
		foreach($data->getParticipants() as $active_id => $userdata)
		{			
				// Do something with the participants				
				$pass = $userdata->getScoredPass();
				foreach($userdata->getQuestions($pass) as $question)
				{
					$questionBase = $tempBase.'/'.sprintf("question-%06d", $question["id"]);					
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
						
						//ignore invalid solution
						if ($solution == null) continue;

						$code = $objQuestion->getCompleteSource($solution);

						$subFolder = sprintf("solution-%06d-%06d-%06d-%06d-%s", $solution['solution_id'], $solution['active_fi'], $solution['pass'], $userdata->user_id, $userdata->login);
						$subFolder = $questionBase.'/'.preg_replace('/[^A-Za-z0-9_\-]/', '', $subFolder);
						$zip->addFromString($subFolder.'/'.$filename, $code);

						
						$feedback = $this->testObj->getManualFeedback(
							$solution['active_fi'], 
							$solution['question_fi'],
							$solution["pass"]
						);
						$points = $this->getReachedPoints(
							$solution['active_fi'], 
							$solution['question_fi'],
							$solution["pass"]
						);

						$comment = "POINTS: %3.1f\n%s\n";
						$comment = sprintf($comment, $points, $feedback);						
						$zip->addFromString($subFolder.'/comment_', $comment);
					}
				}
				// Access some user related properties
				//$last_visited = $data->getParticipant($active_id)->getLastVisit();
		}
		$zip->close();

		return NULL;
	}

	protected function getReachedPoints($active_fi, $question_fi, $pass){
		global $ilDB;

		$query = "SELECT * FROM tst_test_result WHERE " . 
			'active_fi='. $ilDB->quote($active_fi,'integer') . " AND " . 
			'question_fi='. $ilDB->quote($question_fi,'integer') . " AND " .
			'pass='. $ilDB->quote($pass,'integer') . 
			" ORDER BY active_fi";
		
		$result = $ilDB->query($query);
		
		
		while ($row = $ilDB->fetchAssoc($result))
		{
			return $row['points'];
		}

		return 0;
	}

	function buildLatexZIP($zipFile) {
		$data      = $this->testObj->getCompleteEvaluationData(TRUE);
		$testString = sprintf("test-%06d", $this->testObj->getId());
		$tempBase = sprintf('./Latex-Export/%s', $testString);
		
		$zip = new ZipArchive();
		if ($zip->open($zipFile, ZipArchive::CREATE)!==TRUE) {			
			return "cannot open <$tempBase>\n";            
		}

		foreach($data->getParticipants() as $active_id => $userdata) {			
				// Do something with the participants				
			$pass = $userdata->getScoredPass();
			$usrInfo = $this->getParticipantInfo($active_id);
			$stringP = $this->initParticipantString($usrInfo, $testString);

			foreach($userdata->getQuestions($pass) as $question) {
				$objQuestion = $questions[$question["id"]];
				if (!$objQuestion){
					$objQuestion = $this->testObj->_instanciateQuestion($question["id"]);
					$questions[$question["id"]] = $objQuestion;
				}
				if (method_exists($objQuestion, 'getCompleteSource') && 
					method_exists($objQuestion, 'getExportSolution')) {
						
					$solution = $objQuestion->getExportSolution($active_id, $pass);					
					$code = $this->buildCode($objQuestion, $solution);
					$questionString = sprintf("question-%06d", $question["id"]);
					$stringP = $this->addQuestionToString($stringP, $questionString, $code);
					$stringP = $this->addTestResultToString($stringP, $solution);
				}
			}
			$stringP = $this->finishParticipantString($stringP);
			$file = $tempBase . sprintf("/%s.tex", $usrInfo.'');
			$zip->addFromString($file, $stringP);
		}
		$zip->close();

		return NULL;
	}
	protected function buildCode($objQuestion, $solution) {
		$studentCode = $objQuestion->prepareSolutionData($solution);
	
		$res = '';
		$justCode = '';
		$line = 1;
		for ($i=0; $i<$objQuestion->getNumberOfBlocks(); $i++){
			$t = $objQuestion->getTypeForBlock($i);
			if ($t == assCodeQuestionBlockTypes::SolutionCode) {
				$res .= '\\begin{lstlisting}[firstnumber='.$line.', frame=single]'."\n";						
				if (!empty($studentCode)){
					$res .= $studentCode->$i."\n";
					$justCode .= $studentCode->$i."\n";
				}	
				$res .= '\\end{lstlisting}'."\n";			
			} else if ($t == assCodeQuestionBlockTypes::StaticCode || $t== assCodeQuestionBlockTypes::HiddenCode) {
				$res .= '\\begin{lstlisting}[firstnumber='.$line.', basicstyle=\\linespread{0.8}\\sffamily]'."\n";				
				$res .= $objQuestion->getContentForBlock($i)."\n";
				$justCode .= $objQuestion->getContentForBlock($i)."\n";
				$res .= '\\end{lstlisting}'."\n";
			}

			$line = count(explode("\n", $justCode));
		}
		return $res;
	}

	protected function initParticipantString($participant, $test) {
		$string = sprintf("\\documentclass[landscape]{article}\n\n" .
					"\\usepackage[margin=2cm,right=3cm]{geometry}\n" .
					"\\usepackage[ngerman]{babel}\n" .
					"\\usepackage[doublespacing]{setspace}\n" .
					"\usepackage{lastpage}" .
					"\\usepackage{listings}\n" .
					"\\usepackage{color}\n\n" .
					"\\definecolor{light-gray}{gray}{0.85}\n\n" .
					"\\lstset{ \n" .
						"\t language=Java,\n" .
						"\t breakatwhitespace=false,\n" . 
						"\t breaklines=true,\n" . 
						"\t captionpos=b,\n" . 
						"\t keepspaces=true,\n" . 
						"\t numbers=left, \n" . 
						"\t showspaces=false,\n" .
						"\t showstringspaces=true,\n" .
						"\t showtabs=false,\n" .
						"\t tabsize=4,\n" .
						"\t showlines=true,\n" .
						"\t basicstyle=\\linespread{1.5}\\ttfamily,\n" .
						"\t keywordstyle=\\color{blue}\\ttfamily,\n" .
						"\t stringstyle=\\color{red}\\ttfamily,\n" .
						"\t commentstyle=\\color{light-gray}\\ttfamily,\n" .
						"\t morecomment=[l][\\color{light-gray}]\n" .
					"}\n\n" .
					"\\usepackage{fancyhdr}\n" .
					"\\pagestyle{fancy}\n" .
					"\\lhead{%s}\n" .
					"\\lfoot{Klausur Grundlagen der Informatik (%s)}\n" .
					"\\rfoot{(Seite \\thepage /\\pageref{LastPage})}\n" .
					"\\cfoot{}" . 
					"\\renewcommand{\\headrulewidth}{0.4pt}\n" .
					"\\renewcommand{\\footrulewidth}{0.4pt}\n\n" .
					"\\begin{document}\n", $participant, $test);
		return $string;
	}

	protected function addQuestionToString($string, $question, $code) {	
		$string = $string .sprintf( 
					"\\rhead{%s}\n" .
					"%s \n".
					"\\newpage \n\n" ,
					$question, $code);
		return $string;
	}

	protected function addTestResultToString($string, $solution) {	
		$feedback = $this->testObj->getManualFeedback(
			$solution['active_fi'], 
			$solution['question_fi'],
			$solution["pass"]
		);
		$feedback = str_replace('<pre style="font-family:monospace">', '', $feedback);
		$feedback = str_replace('</pre>', '', $feedback);
		$points = $this->getReachedPoints(
			$solution['active_fi'], 
			$solution['question_fi'],
			$solution["pass"]
		);

		$comment = "POINTS: %3.1f\n%s\n";
		$comment = sprintf($comment, $points, $feedback);						
		$string = $string .'\\begin{verbatim}'.$comment.'\\end{verbatim}';
		return $string;
	}

	protected function finishParticipantString($string) {
		$string = $string . sprintf( 
					"\\end{document}"
				);
		return $string;
	}

	protected function getParticipantInfo($active_fi) {
		global $ilDB;

		$query = "SELECT lastname, firstname, login, matriculation " .
				 "FROM tst_active, usr_data " .
				 "WHERE user_fi = usr_id " . 
				 "AND active_id = " . $ilDB->quote($active_fi, 'integer');
		
		$result = $ilDB->query($query);

		while($row = $ilDB->fetchAssoc($result)) {
			return $row['lastname'] . '-' .$row['firstname'] . '-' . $row['login'] . '-' . $row['matriculation'];
		}

		return 0;
	}
}

?>