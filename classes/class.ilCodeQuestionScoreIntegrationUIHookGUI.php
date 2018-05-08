<?php

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

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
	 * @var assCodeQuestion	The question object
	 */
	var $object = null;

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
				$ilCtrl->saveParameterByClass('ilCodeQuestionScoreIntegrationPageGUI','ref_id');
				$a_par["tabs"]->addTab("score_integration", $this->plugin_object->txt("score_integration"), $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionScoreIntegrationPageGUI'), 'showMainAutoScorePage'));

				$ilTabs->addSubTabTarget(
					$this->plugin_object->txt('score_integration'), // text is also the aubtab id
					$ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilCodeQuestionScoreIntegrationPageGUI'), 'showMainAutoScorePage'),
					array('showMainAutoScorePage'), // commands to be recognized for activation
					'ilCodeQuestionScoreIntegrationPageGUI', 	// cmdClass to be recognized activation
					'', 								// frame
					false, 								// manual activation
					true								// text is direct, not a language var
				);
			}
				
			break;

			default:
			break;
		}
	}

}
?>
