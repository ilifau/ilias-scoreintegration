<?php

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");
 
/**
 * Example user interface plugin
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 */
class ilCodeQuestionScoreIntegrationPlugin extends ilUserInterfaceHookPlugin
{
	function getPluginName()
	{
		return "CodeQuestionScoreIntegration";
	}
}

?>
