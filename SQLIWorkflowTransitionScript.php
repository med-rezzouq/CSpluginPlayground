<?php
CS::includeFile(CS_ADMIN . 'core/extensions/activescript/api/plugins/CSActiveScriptItemPlugin.php');

/**
 * @author    SQLI, Christoph Goertler
 * @since     CS20.0
 * @package   CSActiveScript
 * @access    public
 * @version   V4, 03.03.2022        Cgoertler
 */
class SQLIWorkflowTransitionScript extends CSActiveScriptItemPlugin
{
    private $_cachedContextLog = [];
    private $_lastRun = "";
    private $_iBatchSize = 100;
    private $_sLogging = '';
    private $_bAutoCheckin = false;
    private $_sExtendedLog = 'ExtendedLog';
    private $_sLightWeightLog = 'LightWeightLog';
    private $_iDeleteLastLogs = 'iDeleteLastLogs';


    /**
     * {@inheritdoc}
     */
    public function addSteps(CSActiveScriptStepList $oStepList)
    {


        if (empty($bUpdateDelta)) {
            $bUpdateDelta = $this->getValue('bUpdateDelta');
        }

        if (empty($bRunRecursive)) {
            $bRunRecursive = $this->getValue('bRunRecursive');
        }

        if (empty($sStateIDs)) {
            $sStateIDs = $this->getValue('sStateIDs');
        }

        if (empty($sTargetLanguageIDs)) {
            $sTargetLanguageIDs = $this->getValue('sTargetLanguageIDs');
        }

        if (empty($sExecutionMode)) {
            $sExecutionMode = $this->getValue('sExecutionMode');
        }

        if (empty($sWorkflowActionIDs)) {
            $sWorkflowActionIDs = $this->getValue('sWorkflowActionIDs');
        }

        $sWorkflowBuildingBlock = $this->getValue('sWorkflowBuildingBlock');
        $this->_sLogging = $this->getValue('sLogging');
        $this->_bAutoCheckin = $this->getValue('bAutoCheckin');

        if (!($sObjectClass = $this->getValue('ObjectClass'))) {
            $sObjectClass = 'Pdmarticle';
        }

        //Create own ActiveScriptContext Object, since the $this context is always a Pdmarticle context. @TODO Check for proper API usage.
        $sInputContexts = $this->getActiveScriptJob()->getInputValue('ContextIDs');
        if ($sInputContexts) {
            $oContext = CSActiveScript::createContext(
                $sObjectClass,
                explode(',', $sInputContexts)
            );
        } else {
            $oContext = CSActiveScript::createContext(
                $sObjectClass,
                $this->getContext()->getIDs()
            );
        }

        if (empty($aContexts)) {
            if ($bRunRecursive == 1) {
                $aContexts = $oContext->getContextObjects(true);
            } else {
                $aContexts = $oContext->getContextObjects(false);
            }
        }

        if (empty($sStateIDs) || empty($sTargetLanguageIDs) || empty($aContexts)) {
            return;
        }

        //Filter the given context objects by delta time-stamp
        $bUpdateDelta = $this->getValue('bUpdateDelta');
        if ($bUpdateDelta == 1) {
            $aContexts = $this->filterDelta($aContexts, 'LastChange');
        }

        $aBatches = array_chunk($aContexts, $this->_iBatchSize, true);

        foreach ($aBatches as $aContexts) {
            $i = 0;
            //A call from workflow action checkin has only one context and a given language
            foreach ($aContexts as $iContextID => $oContext) {
                $oStepList->addStep(
                    $i++,
                    array(
                        'iContextID' => $iContextID,
                        'sStateIDs' => $sStateIDs,
                        'sTargetLanguageIDs' => $sTargetLanguageIDs,
                        'sExecutionMode' => $sExecutionMode,
                        'sWorkflowBuildingBlock' => $sWorkflowBuildingBlock,
                        'sWorkflowActionIDs' => $sWorkflowActionIDs
                    )
                );
            }
        }

        $this->getActiveScriptJob()->setOutputValue('ContextIDs', $this->getValue('ContextIDs'));
        $this->getActiveScriptJob()->store();
    }

    /**
     * Lets a plugin developer tell which output his script will provide
     *
     * @return string[] array with the allowed output keys
     */
    public function getOutputKeys()
    {
        return array('ContextIDs');
    }

    /**
     * {@inheritdoc}
     */
    public function runStep($oStep)
    {
        if (!($sObjectClass = $this->getValue('ObjectClass'))) {
            $sObjectClass = 'Pdmarticle';
        }
        $iContextID = $oStep->getValue('iContextID');
        $sStateIDs = $oStep->getValue('sStateIDs');
        $aStateIDs = explode(',', $sStateIDs);
        $sTargetLanguageIDs = $oStep->getValue('sTargetLanguageIDs');
        $aTargetLanguageIDs = explode("\n", str_replace(',', "\n", $sTargetLanguageIDs));        // when $sTargetLanguageIDs are from attribute 3028 they are separeted with \n
        $sExecutionMode = $oStep->getValue('sExecutionMode');
        $sWorkflowBuildingBlock = $oStep->getValue('sWorkflowBuildingBlock');
        $sWorkflowActionIDs = $oStep->getValue('sWorkflowActionIDs');
        $aWorkflowActionIDs = explode(',', $sWorkflowActionIDs);
        $bIgnoreRightCheck = $oStep->getValue('bIgnoreRightCheck');


        if ($bIgnoreRightCheck == 1) {
            CS::disableRightCheck(true);
        }

        $aLogs = [];
        foreach ($aTargetLanguageIDs as $currentLanguage) {
            //Used to change data language to fetch available actions regarding the given context.
            /**
             * @var CSPmsProduct $oContext
             */
            $oContext = $this->getObjectByClass($sObjectClass, $iContextID);
            $oContext->setDefaultLanguageID($currentLanguage, false);

            if (!empty($oContext->item->data['CheckoutUser']) && $oContext->item->data['CheckoutUser'] != CSUser::getUserID()) {
                $sLanguageFullName = CSLanguage::getLanguage($currentLanguage)->getFullName();
                if ($this->_bAutoCheckin) {
                    $this->logWarning('Object is automatically checked in. ID: ' . $iContextID);
                    $oContext->checkin();
                } else {
                    $this->logWarning('Object is checked out - see extended logging for ID: ' . $iContextID);
                    $aLogs[] = $this->logContextResult($sLanguageFullName, false, 'All actions - Object is checked out by other user.');
                    break; //Skip the current object.
                }
            }

            //Filter objects by state for the given target language ids
            if (in_array($oContext->getValue('StateID', $currentLanguage), $aStateIDs)) {
                //Switch case for chosen building block
                if ($sExecutionMode == 'WorkflowAction') {
                    foreach ($aWorkflowActionIDs as $iWorkflowActionID) {
                        $aLogs[] = $this->executeWorkflowActionByLanguage($oContext, $currentLanguage, $iWorkflowActionID);
                    }
                }
            } else {
                $sLanguageFullName = CSLanguage::getLanguage($currentLanguage)->getFullName();
                $aLogs[] = $this->logContextResult($sLanguageFullName, false, "All actions - Object not in required state.");
            }
        }

        //Set script run back to default language to avoid logging in last selected language
        if (isset($oContext)) {
            $oContext->setDefaultLanguageID(CSLanguage::getDefaultLanguage()->getID(), false);
            $this->logDebug('Default language restored: ' . CSLanguage::getDefaultLanguage()->getID());
        }


        //Logging
        if (!empty($aLogs)) {
            // $this->_cachedContextLog[] = $aLogs;
        } else {
            $aLogs = [];
        }
        $this->writeLogs($oContext, $aLogs, $sExecutionMode);
        $memoryUsed =  (memory_get_usage() / 1024);
        $newLog = [];
        $newLog[] = '<tr><td  style="color:#0099ff;">' . (number_format($memoryUsed, 2) > 500000 ? 'High ' : 'Normal ') . ' Memory used until now in Kilobytes  ' . $currentLanguage . '</td><td  style="' . (number_format($memoryUsed, 2) > 500000 ? 'color:red;' : 'color:#0099ff;') . '">' . number_format($memoryUsed, 2) . '</td></tr>';

        $this->writeDevLogs($oContext, $newLog, $sExecutionMode);
        // unset($aLogs);
        // unset($newLog);
        if ($bIgnoreRightCheck == 1) {
            CS::disableRightCheck(false);
        }
    }

    /**
     * @param CSGuiEditor $oEditor
     */

    public function prepareEditor(CSGuiEditor $oEditor)
    {
        $oEditor->addField(
            'Info',
            CS::translate('Info'),
            'html',
            CS::translate('This script handles the scheduling of workflow actions and provides an overview via logging.
            <ul>
            <li>If triggered from a workflow, NEVER add an executing user within "automation". The core precondition will fail if the checked-out user is not the same as the executing user.</li>
            <li>Use "ignore rights check" for cases when the action should not be visible to users on the object itself (e.g., product/asset).</li>
            <li>Enable the auto-checkin feature to automatically check in objects in order to process the workflow action with a different executing user. For example: If ID 1234 is checked out by user1, but the admin wants to execute a workflow action as a different user in the script.</li>
            <li>For large update jobs with 10,000+ checks, use lightweight logging to be notified of actual changes. Make sure to enable log cleanup.</li>
            </ul>')
        );

        //Object type - define handles object type in the script
        $oEditor->addField(
            'ObjectClass',
            CS::translate('Object type'),
            array(
                'Pdmarticle' => CS::translate('Products'),
                'Mamfile' => CS::translate('Files'),
                'Pdmarticlestructure' => CS::translate('Channels'),
            ),
            'Pdmarticle',
            false,
            array(
                'noEmptyOption' => true,
                'onChange' => 'saveWithoutClose();',
            )
        );

        if (!($sObjectClass = $this->getValue('ObjectClass'))) {
            $sObjectClass = 'Pdmarticle';
        }

        parent::prepareEditor($oEditor);
        $oEditor->addField(
            'ContextIDs',
            CS::translate('FLEX_GUI_DATA_SELECTION_TYPE_CONTEXT', 'flex'),
            'Record(' . $sObjectClass . ')',
            CSSecurityUtils::getStringVariable('ContextIDs'),
            false,
            array(
                'multiple' => true,
            )
        );

        $oEditor->addField(
            'ContextClass',
            CS::translate('FLEX_GUI_DATA_SELECTION_TYPE_CONTEXT', 'flex'),
            'hidden',
            $sObjectClass,
            false,
            array(
                'multiple' => true,
            )
        );

        $oEditor->addField(
            'bRunRecursive',
            array(
                CS::translate('Run recursive', 'activescript'),
                CS::translate('Runs the script only with the selected objects, if set to no. Otherwise, all children will be checked for the execution on the selected workflow-actions.', 'activescript')
            ),
            'boolean'
        );

        $oEditor->addField(
            'bIgnoreRightCheck',
            array(
                CS::translate('Ignore right-check', 'activescript'),
                CS::translate('The script will ignore the role rights of the executing user. For tree execution, as well as the defined "Run as User" configuration.', 'activescript')
            ),
            'boolean'
        );

        $oEditor->addField(
            'bAutoCheckin',
            array(
                CS::translate('Auto Checkin', 'activescript'),
                CS::translate('For processed products that are checked out, the current data will be checked in.', 'activescript')
            ),
            'boolean'
        );

        //Option button - filter objects by last run (delta)
        $oEditor->addField(
            'bUpdateDelta',
            array(
                CS::translate('Delta update', 'activescript'),
                CS::translate('A delta is identified based on the last job run of the ActiveScript.', 'activescript') //Ein Delta wird anhand der letzen Ausführung des Jobs bestimmt.', 'activescript
            ),
            'boolean'
        );

        //Workflow state selector - all children are checked recursively
        $oEditor->addField(
            'sStateIDs',
            CS::translate('Workflow-State filter'),
            'states',
            '',
            false,
            array(
                'multiple' => true,
                'workflowtypes' => $sObjectClass,
                'onChange' => "saveWithoutClose();",
                'required' => true
            )
        );

        //Language filter - all data languages are checked for the given configured languages
        $oEditor->addField(
            'sTargetLanguageIDs',
            CS::translate('Language filter'),
            CS::getRecord('Language'),
            null,
            null,
            array(
                'multiple' => true,
                'required' => true,
            )
        );

        $aExecutionMode = array(
            'WorkflowAction' => 'Action'
        );

        $oEditor->addField(
            'sExecutionMode',
            CS::translate('Execution mode'),
            $aExecutionMode,
            '',
            '',
            array(
                'multiple' => false,
                'onChange' => "saveWithoutClose();",
                'required' => true
            )
        );

        //$record     = CS::getRecord('PdmArticle');
        $aActions = Workflow::getActionsArray($sObjectClass, true);
        $oEditor->addField(
            'sWorkflowActionIDs',
            CS::translate('Workflow-actions'),
            $aActions,
            '',
            '',
            array(
                'multiple' => true,
                'onChange' => "saveWithoutClose();",
                'required' => true
            )
        );

        $oEditor->addField(
            'sLogging',
            array(
                CS::translate('Logging', 'Translation'),
                CS::translate('Additional logging for the execution mode "action". This checks the precondition 
                before executing the workflow action, and thus makes the check slower. The light weight log only logs 
                positive results. Leave empty to disable logging entirely.', 'Translation')
            ),
            array(
                $this->_sExtendedLog => 'Extended logging',
                $this->_sLightWeightLog => 'Light weight logging'
            )
        );

        $oEditor->addField(
            $this->_iDeleteLastLogs,
            array(
                CS::translate('Clean logs', 'Translation'),
                CS::translate('Insert an integer of logs that are kept. Log entries with the status "ok" beyond the amount are deleted! Attention! ', 'Translation')
            ),
            'integer'
        );
    }

    /**
     * @return mixed
     */
    public function getPluginName()
    {
        return CS::translate('SQLI - Workflow transitions for multiples-languages');
    }

    /**
     * @return mixed
     */
    private function filterDelta($aContexts, $iAttributeID)
    {
        //LastChange has to be cached from onBeforeRunScript()
        foreach ($aContexts as $iContextID => $oContext) {
            $sLastChange = $oContext->getValue($iAttributeID);
            if (strtotime($this->_lastRun) >= strtotime($sLastChange)) {
                unset($aContexts[$iContextID]);
            }
        }
        return $aContexts;
    }

    /**
     * @return mixed
     */
    private function logContextResult($currentLanguage, $bResult, $sAction)
    {
        if ($this->_sLogging == $this->_sExtendedLog) {
            if ($bResult) {
                return '<tr><td  style="color:green;">' . $currentLanguage . '</td><td  style="color:green;">' . 'positive' . '</td><td  style="color:green;">' . $sAction . '</td></tr>';
            } else {
                return '<tr><td  style="color:red;">' . $currentLanguage . '</td><td  style="color:red;">' . 'negative' . '</td><td  style="color:red;">' . $sAction . '</td></tr>';
            }
        } else {
            if ($bResult) {
                return '<tr><td  style="color:green;">' . $currentLanguage . '</td><td  style="color:green;">' . 'positive' . '</td><td  style="color:green;">' . $sAction . '</td></tr>';
            }
        }
    }
    /**
     * @return mixed
     */
    private function writeLogs($oContext, array $aLogs, string $sExecutionMode)
    {
        $sLogs = implode('', $aLogs);
        if (!empty($sLogs)) {
            $strHtmlTable = '<table style="width:100%">'
                . '<th style="color:grey;">' . CS::translate('Language') . ' </th>'
                . '<th style="color:grey;">' . CS::translate('Result') . ' </th>'
                . '<th style="color:grey;">' . CS::translate($sExecutionMode) . ' </th>'
                . implode('', $aLogs)
                . '</table>';
            $this->log('<font style="color:grey">'
                . 'Processing of : '
                . $oContext->getLabel()
                . ' ('
                . $oContext->getID()
                . ')'
                . '</font><br><br>' . $strHtmlTable);
        }
    }

    /**
     * @return mixed
     */
    private function writeDevLogs($oContext, array $aLogs, string $sExecutionMode)
    {
        $sLogs = implode('', $aLogs);
        if (!empty($sLogs)) {
            $strHtmlTable = '<table style="width:100%">'
                . '<th style="color:grey;">' . CS::translate('System Message') . ' </th>'
                . '<th style="color:grey;">' . CS::translate('Description') . ' </th>'
                . implode('', $aLogs)
                . '</table>';
            $this->log('<font style="color:blue">'
                . 'Processing of : '
                . $oContext->getLabel()
                . ' ('
                . $oContext->getID()
                . ')'
                . '</font><br><br>' . $strHtmlTable);
        }
    }

    /**
     * Will be called by the framework after the script was executed. Compared to the
     * onBeforeRunSteps-method, when it is called, all other tasks are already finished.
     *
     * @return void
     */
    public function onAfterRunScript()
    {
        // if (isset($this->_cachedContextLog)) {
        //     $this->log('Result for: ' . CSActiveScript::getActiveScript($this->getActiveScriptJob()->getActiveScriptID())->getLabel() . ' - ' . $this->getActiveScriptJob()->getID());
        //     $counter = 0;
        //     $resultCounterNegative = 0;
        //     $resultCounterPositive = 0;
        //     $resultEntries = 0;
        //     foreach ($this->_cachedContextLog as $contextLog) {
        //         $countedLogs = count($contextLog);
        //         $counter = $counter + count($contextLog);
        //         $resultEntries = $resultEntries + $countedLogs;
        //         foreach ($contextLog as $entry) {
        //             if (strpos($entry, 'negative')) {
        //                 $resultCounterNegative++;
        //             }
        //             if (strpos($entry, 'positive')) {
        //                 $resultCounterPositive++;
        //             }
        //         }
        //     }

        //     if ($this->_sLogging) {
        //         if ($this->_sLogging == $this->_sExtendedLog) {
        //             $this->log('Negative checks: ' . $resultCounterNegative);
        //         }
        //         $this->log('Positive checks: ' . $resultCounterPositive);
        //     }
        //     $this->log('Processed context languages (language + context object) overall: ' . $counter); //Verarbeitete Kontextsprachen (Sprache +  Kontextobjekt)  insgesamt:
        //     $this->log('Processed objects overall: ' . count($this->_cachedContextLog)); //Verarbeitete Objekte insgesamt:

        //     $this->cleanActiveScriptLog();
        // }
    }

    //this is just to cleanup the logging status messages of execution of steps
    public
    function cleanActiveScriptLog()
    {
        $iKeptLogAmount = $this->getActiveScript()->getValue($this->_iDeleteLastLogs); // this $this->_iDeleteLastLogs is a field which we need to enter the number of logs  which need to be kept and which are not status = ok
        if ($iKeptLogAmount) { //0 is not processed which is right.
            $aScripts = CSActiveScript::getActiveScriptJobsForFilter(
                array($this->getActiveScript()->getID()),
                array(CSActiveScript::ACTIVE_SCRIPT_STATUS_OK)
            );

            if (count($aScripts) > $iKeptLogAmount) {
                // Remove the last 10 elements
                $aScripts = array_slice($aScripts, 0, count($aScripts) - $iKeptLogAmount);
                $this->log('Cleansed ' . count($aScripts) . ' old logs.');
                //Delete script logs
                foreach ($aScripts as $oScript) {
                    $oScript->delete();
                }
            }
        }
    }

    /**
     * // this actually is not called
     * @return mixed
     */
    public  // this actually is not called
    function executeWorkflowPluginByLanguage($oContext, $iLanguageID, $sPlugin)
    {
        $sLanguageFullName = CSLanguage::getLanguage($iLanguageID)->getFullName();
        //Used to change data language to fetch available actions regarding to the given context.
        $oContext->setDefaultLanguageID($iLanguageID);

        //Very important, the instance has to be generation of the product AFTER the language has been changed, otherwise the pre-conditions may be wrong.
        $oContext = CSPMS::getProduct($oContext->getID());
        //getAvailableWorkflowActions already runs the MayExecute
        $aWorkflowActions = $oContext->getAvailableWorkflowActions(false);
        $bResult = false;
        $sAction = 'N/A';
        foreach ($aWorkflowActions as $oWorkflowAction) {
            $definedPlugins = $oWorkflowAction->getDefinedPlugins();
            foreach ($definedPlugins as $definedPlugin) {
                if ($definedPlugin->getPluginName() == $sPlugin) {
                    $bResult = $oContext->executeWorkflowAction($oWorkflowAction, false, 'Executed by ActiveScriptID: .' . $this->getPluginName());
                    $sAction = $oWorkflowAction->getValue('ActionName');
                    return $this->logContextResult($sLanguageFullName, $bResult, $sAction);
                }
            }
        }
        return $this->logContextResult($sLanguageFullName, $bResult, $sAction);
    }

    /**
     * @return mixed
     */
    public
    function executeWorkflowActionByLanguage($oContext, $iLanguageID, $iActionID)
    {
        if ($this->_sLogging) { //When logging is activated
            $sLanguageFullName = CSLanguage::getLanguage($iLanguageID)->getFullName();
            $oAction = CSWorkflow::getAction($iActionID);
            $sAction = $oAction->getValue('ActionName');

            if ($oAction->mayExecute($oContext->item)) {
                $bResult = $oContext->executeWorkflowAction($iActionID, false, 'Executed by ActiveScriptID: .' . $this->getPluginName());
                return $this->logContextResult($sLanguageFullName, $bResult, $sAction . ' - Tried to execute.');
            } else {

                return $this->logContextResult($sLanguageFullName, false, $sAction . ' -  Not executed due to precondition being false. StateID: ' . $oContext->getValue('StateID', $iLanguageID) . ' LanguageID: ' . $oContext->item->getCurrentLanguageID());
            }
        } else {
            //Return value of executeWorkflowAction not usable - always true.
            $oContext->executeWorkflowAction($iActionID, false, 'Executed by ActiveScriptID: .' . $this->getPluginName());
            return null;
        }
    }

    /**
     * Will be called by the framework before the script is executed.
     *
     * @return void
     */
    public
    function onBeforeRunScript()
    {
        $this->_lastRun = $this->getActiveScript()->getLastRun();
    }

    /**
     * Will deliver the instanced object by the given class name.
     * @param string $sClassName the given class
     * @param string $iContextID
     * @return mixed
     */
    public
    function getObjectByClass($sClassName, $iContextID)
    {
        switch ($sClassName) {
            case 'Pdmarticle':
                return CSPms::getProduct($iContextID);
            case 'Mamfile':
                return CSMam::getFile($iContextID);
            case 'Pdmarticlestructure':
                return CSPms::getView($iContextID);
        }
        return null;
    }


    /**
     * Allows your plugin to select the context it can run for
     *
     * @param CSActiveScriptContext $oContext the context
     *
     * @return bool
     */
    public
    function isAvailableForContext(CSActiveScriptContext $oContext)
    {

        if ((in_array(
            $oContext->getType(),
            array(
                CSActiveScriptContext::CONTEXT_TYPE_PIM_PRODUCT,
                CSActiveScriptContext::CONTEXT_TYPE_PIM_VIEW,
                CSActiveScriptContext::CONTEXT_TYPE_MAM_FILE
            )
        ))) {
            return true;
        }
        return false;
    }
}
