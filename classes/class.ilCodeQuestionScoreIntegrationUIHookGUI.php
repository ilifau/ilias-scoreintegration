<?php

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
require_once ('Modules/Test/classes/class.ilObjTest.php');

/**
 * User interface hook class
 *
 * @author Frank Bauer <frank.bauer@fau.de>
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilCodeQuestionScoreIntegrationUIHookGUI extends ilUIHookPluginGUI
{

	/**
	 * @var bool true if the test includes source code questions
	 */
	var $hasCodeQuestions = false;
	

	public function __construct(){
		
	}

	/**
	 * Modify HTML output of GUI elements. Modifications modes are:
	 * - ilUIHookPluginGUI::KEEP (No modification)
	 * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
	 * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
	 * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 *
	 * @return array array with entries "mode" => modification mode, "html" => your html
	 */
	function getHTML($a_comp, $a_part, $a_par = array())
	{
		return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
	}
	
	/**
	 * Modify GUI objects, before they generate ouput
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 */
	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
		global $ilCtrl, $ilTabs;
		
		switch ($a_part)
		{
			// case 'tabs':
			case 'sub_tabs':
			if (in_array($ilCtrl->getCmdClass(), array('iltestscoringbyquestionsgui', 'iltestscoringgui')) ) {
				if (!$this->hasCodeQuestions) {
					$test = new ilObjTest($_GET['ref_id']);

					//see if we have source Code questions available
					$questions = $test->getQuestions();
					foreach ($questions as $q) {
						$question =& ilObjTest::_instanciateQuestion($q);
						if (method_exists($question, 'getCompleteSource') && 
							method_exists($question, 'getExportFilename') &&
							method_exists($question, 'getExportSolution')){
								$this->hasCodeQuestions = true;
								break;
							}
					}
				}
				if (!$this->hasCodeQuestions) return;
				
				$ilCtrl->saveParameterByClass('ilCodeQuestionScoreIntegrationPageGUI','ref_id');
				
				$ilTabs->addSubTab("scrintegration",
					$this->plugin_object->txt("score_integration"),
					$ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionScoreIntegrationPageGUI')));				

				// save the tabs for reuse on the plugin pages
				// (these do not have the test gui as parent)
				// not nice, but effective
				$_SESSION['CodeQuestionScoreIntegration']['TabTarget'] = $ilTabs->target;
				$_SESSION['CodeQuestionScoreIntegration']['TabSubTarget'] = $ilTabs->sub_target;				
			}

			if ($ilCtrl->getCmdClass()  == 'ilcodequestionscoreintegrationpagegui')
				{
					// reuse the tabs that were saved from the test gui
					if (isset($_SESSION['CodeQuestionScoreIntegration']['TabTarget']))
					{ 
						$ilTabs->target = $_SESSION['CodeQuestionScoreIntegration']['TabTarget'];
					}
					if (isset($_SESSION['CodeQuestionScoreIntegration']['TabSubTarget']))
					{						
						$ilTabs->sub_target = $_SESSION['CodeQuestionScoreIntegration']['TabSubTarget'];
					}

					// this works because the tabs are rendered after the sub tabs
					$ilTabs->activateTab('manscoring');																
				}
				
			break;

			default:
			break;
		}
	}

}
?>
