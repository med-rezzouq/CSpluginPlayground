<?php

///// CLASS DEFINITION /////////////////////////////////////////////////////////
CS::includeFile('../admin/core/extensions/system/api/CSValueRangeValue.php');

/**
 * This plugin is used to check attributes and states of different contexts as precondition for a workflow execution,
 * as well as the option to set one to many attributes to pre-defined attribute values. The values are usually given as unformatted databased value.
 * The plugin contains the options to define complex preconditions via operators listed in the constants below.
 * @author    SQLI, Christoph Goertler
 * @since     CS20.0
 * @package   CSActiveScript
 * @access    public
 * @version   V6, 21,08.2024        Cgoertler
 */
class SQLIWorkflowConfiguratorWorkflowAction extends CSWorkflowActionPlugin
{

    ///// CONSTANTS //////////////////////////////////////////////////////////////////////////////////////////////////////
    const LABEL = 'SQLI Workflow Configurator';
    const OPERATOR_SMAlLER = '<';
    const OPERATOR_GREATER = '>';
    const OPERATOR_SMAlLER_EQUAL = '<=';
    const OPERATOR_GREATER_EQUAL = '>=';
    const OPERATOR_NOT_EQUAL = '!';
    const OPERATOR_CONTAINS = 'contains';

    private ?Record $WFRecord = null;

    ///// PUBLIC METHODS /////////////////////////////////////////////////////////

    /**
     * @return string the Name
     */
    function getPluginName()
    {
        return CS::translate(self::LABEL);
    }

    /**
     * @return string the Plugin Name
     */
    function getDescription()
    {
        return CS::translate(self::LABEL);
    }

    /**
     * Returns the name of the group the plugin should be added to.
     *
     * @return string the name of the plugin group (it must not be translated and will be translated automatically)
     */
    public function getPluginGroup()
    {
        return CS::translate(self::LABEL);
    }

    /**
     * Returns the label of the automation action translated
     *
     * @return string Returns the translated label as string
     * @access public
     */
    public function getLabel()
    {
        return CS::translate(self::LABEL);
    }

    /**
     * This methods is called in the action editor to add some additional fields to the editor
     * Additional fields can be added using the addEditorField method
     * Added fields can be removed using the removeEditorField method
     *
     * @param Action $oAction The current workflow Action record.
     * @param Workflow $oWorkflow The current Workflow record.
     * @param Record[]|Item[] $aoRecords The Records which may be visible with this workflow.
     *
     * @access public
     */
    public function addEditorFields($oAction, $oWorkflow, $aoRecords = array())
    {
        $sObjectClass = $oWorkflow->getType();
        if (!($sObjectClass)) {
            $sObjectClass = 'Pdmarticle';
        }

        /** Field for Objecttypes **/
        $oAction->addEditorField(CS::translate('Preconditions'), 'CheckedObjects', 'Objects to check:', array(
            'Parent' => 'Parent',
            'Context' => 'Context',
            'Children' => 'Children'
        ), 'Context', array(
            'multiple' => false,
            'required' => false,
            'tooltip' => 'Mandatory field: Counts as precondition for the processed action.'
        ));

        /** Checktypes to Status **/
        $currentType = $oAction->getValue('CheckedObjects');
        if ($currentType != 'Context' && $currentType) {
            $oAction->addEditorField(CS::translate('Preconditions'), 'CheckState', 'Status to check:', 'states', null, array(
                'multiple' => true,
                'workflowtypes' => $sObjectClass,
                'required' => false,
                'tooltip' => 'Mandatory field: Identified objects are checked to the selected workflow status.'
            ));
        }
        if ($currentType == 'Children' && $currentType) {
            //How many children to be checked
            $oAction->addEditorField(CS::translate('Preconditions'), 'NumberOfChildren', 'Number of Children:', array(
                'minOne' => 'At least one Child',
                'dynamicNumber' => 'Dynamic Number of Children',
                'allChildren' => 'All Children'
            ), 'allChildren', array(
                'multiple' => false,
                'required' => false,
            ));

            $minimumChildren = $levelOfChildren = $oAction->getValue('NumberOfChildren');
            if ($minimumChildren == 'dynamicNumber') {
                $oAction->addEditorField(
                    CS::translate('Preconditions'),
                    'minimumChildrenNumber',
                    'Minimum Children:',
                    'number',
                    1,
                    array(
                        'multiple' => false,
                        'required' => false,
                    )
                );
            }


            //What levels to be checked
            $oAction->addEditorField(CS::translate('Preconditions'), 'levelOfChildren', 'Level of Children:', array(
                'allLevels' => 'All Levels',
                'directChildren' => 'Only direct Children',
                'endNotes' => 'Only end notes',
                'dynamicNumberOfLayers' => 'Dynamic Number of Layers'
            ), 'allLevels', array(
                'multiple' => false,
                'required' => false,
            ));

            $levelOfChildren = $oAction->getValue('levelOfChildren');
            if ($levelOfChildren == 'dynamicNumberOfLayers') {
                $oAction->addEditorField(
                    CS::translate('Preconditions'),
                    'numberOfLevels',
                    'NumberOfLevels:',
                    'number',
                    null,
                    array(
                        'multiple' => false,
                        'required' => false,
                    )
                );
            }

            $oAction->addEditorField(CS::translate('Preconditions'), 'IgnoredStates', 'Ignored States:', 'states', null, array(
                'multiple' => true,
                'workflowtypes' => $sObjectClass,
                'required' => false,
            ));
        }



        if ($currentType == 'Parent' && $currentType) {
            //How many children to be checked
            $oAction->addEditorField(CS::translate('Preconditions'), 'NumberOfChildren', 'Number of Children:', array(
                'minOne' => 'At least one Child',
                'dynamicNumber' => 'Dynamic Number of Children',
                'allChildren' => 'All Children'
            ), 'allChildren', array(
                'multiple' => false,
                'required' => false,
            ));

            $minimumChildren = $levelOfChildren = $oAction->getValue('NumberOfChildren');
            if ($minimumChildren == 'dynamicNumber') {
                $oAction->addEditorField(
                    CS::translate('Preconditions'),
                    'minimumChildrenNumber',
                    'Minimum Children:',
                    'number',
                    1,
                    array(
                        'multiple' => false,
                        'required' => false,
                    )
                );
            }


            //What levels to be checked
            $oAction->addEditorField(CS::translate('Preconditions'), 'levelOfChildren', 'Level of Children:', array(
                'allLevels' => 'All Levels',
                'directChildren' => 'Only direct Children',
                'endNotes' => 'Only end notes',
                'dynamicNumberOfLayers' => 'Dynamic Number of Layers'
            ), 'allLevels', array(
                'multiple' => false,
                'required' => false,
            ));

            $levelOfChildren = $oAction->getValue('levelOfChildren');
            if ($levelOfChildren == 'dynamicNumberOfLayers') {
                $oAction->addEditorField(
                    CS::translate('Preconditions'),
                    'numberOfLevels',
                    'NumberOfLevels:',
                    'number',
                    null,
                    array(
                        'multiple' => false,
                        'required' => false,
                    )
                );
            }

            $oAction->addEditorField(CS::translate('Preconditions'), 'IgnoredStates', 'Ignored States:', 'states', null, array(
                'multiple' => true,
                'workflowtypes' => $sObjectClass,
                'required' => false,
            ));
        }

        /** Dynamic Field Creation for to-be checked attributeIDs **/
        $oAction->addEditorField(CS::translate('Preconditions'), 'CheckAttributeIDs', 'Attributes to check:', CS::getRecord($this->getFieldNameByClass($sObjectClass)), '', array(
            'multiple' => true,
            'tooltip' => 'Select one or many attributes that are checked on the target objects during execution of the workflow-action.',
            'attributes' => 'bottomFrame="core|extensions|item|dialogs|itemConfigurationRecordInputBottomFrame.php&Class=' . $sObjectClass . '"'
        ));

        $oAction->addEditorField(
            CS::translate('Preconditions'),
            'CheckAttributesHint',
            'Hint:',
            'html',
            'You can use "||" as OR and "!" as NOT operator on preconditions. E.g., "!4||3" or "!null" to check attributes for non-empty values.</br>' .
                'Other supported operators and placeholders are:<ul>' .
                '<li>"<" for less than</li>' .
                '<li>">" for greater than</li>' .
                '<li>"<=" for less than or equal to</li>' .
                '<li>">=" for greater than or equal to</li>' .
                '<li>"contains" for substring matching, e.g., "%value%"</li>' .
                '<li>"{Today}" for today\'s date; use "{Today#DateFormat}" for a custom <a href="https://www.w3schools.com/php/func_date_date_format.asp" style="color: #00f" target="_blank">date format</a></li>' .
                '<li>"null" or "{null}" for empty fields</li>' .
                '</ul></br>' .
                'Additionally, you can use attribute IDs to compare attribute values, e.g., "{123}" or "!{123}".</br>' .
                'Use "#123#" to fetch translated value list values for checking or setting values.'
        );

        $checkAttributes = $oAction->getValue('CheckAttributeIDs');
        $checkAttributes = explode(',', $checkAttributes);
        $checkAttribute = "";

        if ($checkAttributes[0] != '') {
            foreach ($checkAttributes as $checkAttribute) {
                $oField = $this->getObjectByClass($this->getFieldNameByClass($sObjectClass), $checkAttribute);
                $Label = ($oField->getValue('Label') != null) ? $oField->getValue('Label') : $checkAttribute;
                $oAction->addEditorField(
                    CS::translate('Preconditions'),
                    (string)$checkAttribute . '_CheckAttribute',
                    $Label,
                    'text',
                    '',
                    array('tooltip' => 'Dynamic field based on "Attributes to check" Types: all')
                );
            }
        }

        $oAction->addEditorField(
            CS::translate('Preconditions'),
            'ApproveByDialog',
            'Approve with dialog window',
            'checkbox',
            'false',
            null,
            false
        );

        $DialogButton = $oAction->getValue('ApproveByDialog');
        if ($DialogButton == true) {
            $oAction->addEditorField(
                CS::translate('Preconditions'),
                'HiddenField',
                'HiddenField',
                'hidden',
                null,
                null,
                true
            );
        }

        /** Dynamic Field Creation **/
        /** Attributes to set **/
        $oAction->addEditorField(CS::translate('Action'), 'PreconditionSetAttributes', 'Preconditions', 'boolean', '', array(
            'multiple' => false,
            'tooltip' => 'Turns on the preconditions for attributes to set'
        ));
        $oAction->addEditorField(CS::translate('Action'), 'AttributeIDs', 'Attributes to set:', CS::getRecord($this->getFieldNameByClass($sObjectClass)), '', array(
            'multiple' => true,
            'tooltip' => 'Selection of one or many attributes that are set on worklflow execution. !CONTEXT OBJECT!',
            'attributes' => 'bottomFrame="core|extensions|item|dialogs|itemConfigurationRecordInputBottomFrame.php&Class=' . $sObjectClass . '"'
        ));

        $bPreconditions = $oAction->getValue('PreconditionSetAttributes');
        $attributes = $oAction->getValue('AttributeIDs');
        $attributes = explode(',', $attributes);
        if ($attributes[0] != '') {
            foreach ($attributes as $attribute) {
                $oField = $this->getObjectByClass($this->getFieldNameByClass($sObjectClass), $attribute);
                $Label = ($oField->getValue('Label') != null) ? $oField->getValue('Label') : $attribute;
                if ($bPreconditions) {
                    $oAction->addEditorField(
                        CS::translate('Action'),
                        (string)$attribute . '_Attribute_Preconditions',
                        'Precondition for ' . $Label,
                        'text',
                        '',
                        array('tooltip' => 'Precondition for ' . $Label . ' configured just as in the Action preconditions')
                    );
                }
                $oAction->addEditorField(
                    CS::translate('Action'),
                    (string)$attribute . '_Attribute',
                    $Label,
                    'text',
                    '',
                    array('tooltip' => 'Dynamic field based on "Attributes to set". Types: all')
                );
            }
        }

        /** Attributes to reset **/
        $oAction->addEditorField(CS::translate('Action'), 'ResetAttributeIDs', 'Attributes to reset:', CS::getRecord($this->getFieldNameByClass($sObjectClass)), '', array(
            'multiple' => true,
            'tooltip' => 'Selection of one to many attributes that will be reset during workflow execution. !CONTEXT OBJECT!'
        ));

        /** Attributes to copy by tag **/
        if (class_exists('CSTags')) {
            $sTagRecordClass = "Tag";
        } else {
            $sTagRecordClass = "Valuerangevalue";
        }
        $oAction->addEditorField(CS::translate('Action'), 'CopyAttributeIDs', 'Attributes to copy:', CS::getRecord($sTagRecordClass), '', array(
            'multiple' => true,
            'tooltip' => 'Selection of one to many attributes that will be copied during workflow execution. A copy is done from one language dimension to another. !CONTEXT OBJECT!'
        ));
        $this->addFieldForTagNote($oAction, 'CopyAttributeIDs', CS::translate('Action'));

        /** Dynamic Field Creation **/ /** Attributes to set on X **/
        //If targetObject is Context, don't build onObject InputFields
        $checkedObjects = $oAction->getValue('CheckedObjects');
        if ($checkedObjects != 'Context') {
            $oAction->addEditorField(
                CS::translate('Action'),
                'AttributeIDsOnObject',
                'Attributes to set on: ' . $oAction->getValue('CheckedObjects'),
                CS::getRecord($this->getFieldNameByClass($sObjectClass)),
                '',
                array(
                    'multiple' => true,
                    'tooltip' => 'Selection of one to many attributes that will be set on other objects (not the executed context).',
                    'attributes' => 'bottomFrame="core|extensions|item|dialogs|itemConfigurationRecordInputBottomFrame.php&Class=' . $sObjectClass . '"'
                )
            );

            $attributesOnObject = $oAction->getValue('AttributeIDsOnObject');
            $attributesOnObject = explode(',', $attributesOnObject);

            if ($attributesOnObject[0] != '') {
                foreach ($attributesOnObject as $attributeOnObject) {
                    $oField = $this->getObjectByClass($this->getFieldNameByClass($sObjectClass), $attributeOnObject);
                    $Label = ($oField->getValue('Label') != null) ? $oField->getValue('Label') : $checkAttribute;
                    $type = $oField->getValue('Type');

                    if ($type == '../admin/core/cstypes/dateCSType.php') {
                        $oAction->addEditorField(
                            CS::translate('Action'),
                            (string)$attributeOnObject . '_AttributeOnObject',
                            $Label,
                            'date',
                            '',
                            array('tooltip' => 'Dynamic field based on "Attributes to set on: ". Types: Date')

                        );
                    } else {
                        $oAction->addEditorField(
                            CS::translate('Action'),
                            (string)$attributeOnObject . '_AttributeOnObject',
                            $Label,
                            'text,',
                            '',
                            array('tooltip' => 'Dynamic field based on "Attributes to set on: " Types: Texts and others')
                        );
                    }
                }
            }

            /** Attributes to reset on target object **/
            $oAction->addEditorField(
                CS::translate('Action'),
                'ResetAttributesIDsOnObjects',
                'Attribute to reset on: ' . $oAction->getValue('CheckedObjects'),
                CS::getRecord($this->getFieldNameByClass($sObjectClass)),
                '',
                array(
                    'multiple' => true,
                    'tooltip' => 'Selection of one or many attributes that will be reset on other objects. (not the executed context).'
                )
            );
        }

        /** ActiveScripts to be Executed **/
        $aActiveScripts = CSActiveScript::getActiveScripts();
        foreach ($aActiveScripts as $iActiveScriptID => $aActiveScript) {
            $aActiveScripts[$iActiveScriptID] = $aActiveScript->getLabel();
        }
        if ($aActiveScripts) {
            $oAction->addEditorField(
                CS::translate('Action'),
                'ActiveScripts',
                'Trigger ActiveScripts:',
                $aActiveScripts,
                null,
                array(
                    'multiple' => true,
                    'required' => false,
                    'tooltip' => 'Optional field: Selected active scripts to be run on workflow execution. The script can be run with the context object as root node.'
                )
            );
        }

        $sActiveScripts = $oAction->getValue('ActiveScripts');
        if ($sActiveScripts) {
            $oAction->addEditorField(CS::translate('Action'), 'AddContext', 'Handover Context', 'checkbox', false, array(
                'tooltip' => 'Hands over the current context from the workflow to the active script execution.'
            ));

            $oAction->addEditorField(
                CS::translate(
                    'Action'
                ),
                'bRunInBackground',
                'Run in Background',
                'checkbox',
                false,
                array(
                    'tooltip' => 'Starts the active scripts immediately, or queued in the background.'
                )
            );

            if ($oAction->getValue('AddContext') == 1) {
                $aContextOptions = array('This' => 'This Context', 'Static' => 'Static Context', 'RefAttr' => 'From Reference');
                $oAction->addEditorField(
                    CS::translate('Action'),
                    'ContextOptions',
                    'Context Options',
                    $aContextOptions,
                    'This',
                    array(
                        'multiple' => false,
                        'required' => false,
                    )
                );
                $selectedOption = $oAction->getValue('ContextOptions');
                if ($selectedOption) {
                    switch ($selectedOption) {
                        case 'Static':
                            $sPdmarticle = CS::getRecord('Pdmarticle');
                            break;
                        case 'RefAttr':
                            $sPdmarticle = CS::getRecord('Pdmarticleconfiguration');
                            break;
                        default:
                    }
                    if ($selectedOption != 'This') {
                        $oAction->addEditorField(
                            CS::translate('Action'),
                            'ContextForScript',
                            'Context For Script',
                            $sPdmarticle,
                            null,
                            array(
                                'multiple' => true,
                                'required' => false,
                            )
                        );
                    }
                }
            }
        }

        /** Addition Options **/
        $oAction->addEditorField(CS::translate('Options'), 'LanguageIDs', 'Languages to process: ', CS::getRecord('Language'), '', array(
            'multiple' => true,
            'tooltip' => 'Attributes to set will be processed on the selected data languages. If NO language is selected, only the current context language will be processed./n'
        ));

        $oAction->addEditorField(
            CS::translate('Options'),
            'AddCurrentLanguage',
            'Add context language to process:',
            'checkbox',
            false
        );

        //Sample Implementation
        $oAction->addEditorField(CS::translate('Samples'), 'SampleObject', 'Sample Object:', CS::getRecord($sObjectClass), '', array(
            'tooltip' => 'Selection of one sample object to check transitions.'
        ));

        $iRecord = $oAction->getValue('SampleObject');

        if ($iRecord) {
            //$oRecord = $this->getObjectByClass($sObjectClass, $iRecord);
            $oRecord = CS::getRecord($sObjectClass, $iRecord);
            $sSampleResult = $oAction->mayExecute($oRecord, false);
            if ($sSampleResult == true) {
                $sSampleNote = '<p  style="color:green;">' . ' true ' . '</p>';
            } else {
                $sSampleNote = '<p  style="color:red;">' . ' false ' . '</p>';
            }

            $oAction->addEditorField(
                CS::translate('Samples'),
                'SampleResult',
                'Sample Result:',
                'html',
                $sSampleNote
            );
        }
    }

    /**
     * Adds an note preset field to the preset for tags and values rages to show the attribute to the user
     * @param Action $oAction
     * @param string $sTagFieldName
     * @param string $sPane
     * @param string $sSection
     * @return void
     */
    public function addFieldForTagNote(Action $oAction, string $sTagFieldName, string $sPane)
    {
        $sTags = $oAction->getValue($sTagFieldName);
        if (!empty($sTags)) {
            $aTags = explode(',', $sTags);
            $iContext = $oAction->getValue("SampleObject");
            if ($iContext) {
                $oContext = CSPms::getProduct($iContext);
                $aTaggedAttributes = $this->getAttributesByTags($oContext->getNewInstance($iContext), $aTags);
                $aLabels = [];
                foreach ($aTaggedAttributes as $aAttributes) {
                    $aLabels[] = $aAttributes->getLabel();
                }
                $oAction->addEditorField(
                    $sPane,
                    'TagNote' . $sTagFieldName,
                    'Hint:',
                    'html',
                    implode(', ', $aLabels)
                );
            }
        }
    }

    /**
     * Identifies the measureunit of an attribute
     * @param CSItemApiItem $oProduct
     * @param               $aSelectedTags
     * @return array
     */
    protected function getAttributesByTags(CSItemApiItem $oProduct, $aSelectedTags): array
    {
        $aFields = $oProduct->getFields();
        foreach ($aFields as $iFieldID => $oField) {
            $sAttributeTags = $oField->getValue('Tags');
            $aAttributeTags = explode(',', $sAttributeTags);
            $aIntersection = array_intersect($aAttributeTags, $aSelectedTags);
            if (empty($aIntersection)) {
                unset($aFields[$iFieldID]);
            }
        }
        return $aFields;
    }

    /**
     * This method is called if the action is executed.
     *
     * @param Action $oAction The workflow Action record.
     * @param Record|Item $oRecord The CONTENTSERV Record or Item the action is executed on.
     * @param string $sNote A note which can be processed in the workflow plugin (e.g. to store in the history).
     *
     * @return void|bool Returning FALSE results in an execution error and the method onExecutionError
     *                   is called. Returning TRUE or VOID results in execution success.
     *
     * @access public
     * @throws CSException
     */
    public function execute($oAction, $oRecord, $sNote = '')
    {
        $this->WFRecord = $oRecord;
        //$this->WFRecord->store(); //This store on the record is necessary when using WF-Actions immediately after creation & the system utilitses system objecttypes with a class assigned to the objecttype.
        $selectedFields = $oAction->getValue('AttributeIDs');
        $selectedFields = explode(',', $selectedFields);

        $resetFields = $oAction->getValue('ResetAttributeIDs');
        $resetFields = explode(',', $resetFields);

        $resetFieldsOnObject = $oAction->getValue('ResetAttributesIDsOnObjects');
        $resetFieldsOnObject = explode(',', $resetFieldsOnObject);

        $selectedFieldsOnObject = $oAction->getValue('AttributeIDsOnObject');
        $selectedFieldsOnObject = explode(',', $selectedFieldsOnObject);

        $sCopyTags = $oAction->getValue('CopyAttributeIDs');

        $states = $oAction->getValue('CheckState');
        $states = explode(',', $states);

        //Fetch configured languageIDs, it not maintained, get context-language
        $iContextLanguageID = 0;
        $aLanguageIDs = $this->_getLanguagesForExecution($oAction, $oRecord, $iContextLanguageID);
        $iRecordID = $oRecord->getID();
        $oProductRecord = $this->getObjectByClass($oRecord->getClass(), $oRecord->getID()); //CSPms::getProduct($iRecordID);

        //Get fields to copy before running through the languages - only get the fields of a tag is selected.
        if ($sCopyTags) {
            $aCopyTags = explode(',', $sCopyTags);
            $copyFields = $this->getAttributesByTags($oProductRecord->getNewInstance($iRecordID), $aCopyTags);
        }

        //Set and reset all values of all objects for every relevant languageID
        foreach ($aLanguageIDs as $languageID) {
            //Loop through to be reset fields and reset them
            foreach ($resetFields as $resetField) {
                //Reset selected attributes from plugin configuration
                $this->setValueByOption($oRecord, $resetField, null, $languageID);
            }

            //Loop through selected fields to set their corresponding values
            foreach ($selectedFields as $selectedField) {
                $bConditionValidated = true;
                if ($oAction->getValue('PreconditionSetAttributes') && $oAction->getValue($selectedField . '_Attribute_Preconditions')) {
                    $bConditionValidated = $this->validateCondition($oRecord, $selectedField, $oAction->getValue($selectedField . '_Attribute_Preconditions'));
                }
                if ($bConditionValidated) {
                    $this->setValueByOption($oRecord, $selectedField, $oAction->getValue($selectedField . '_Attribute'), $languageID);
                }
            }

            //Loop through copy fields to copy their corresponding values to the run through languages
            if ($languageID != $iContextLanguageID && $sCopyTags) {
                $this->_copyAttributeContentToLanguage($copyFields, $oRecord, $iContextLanguageID, $languageID);
            }

            //Loop through selected fields to set and reset their corresponding values on the defined object-type
            switch ($oAction->getValue('CheckedObjects')) {
                    //Parent
                    //Kontext
                case 'Context':
                    //Set and reset defined values on context, no check on state, since pre-condition has to be true
                    foreach ($selectedFieldsOnObject as $selectedFieldOnObject) {
                        $this->setValueByOption($oRecord, $selectedFieldOnObject, $oAction->getValue($selectedFieldOnObject . '_AttributeOnObject'), $languageID);
                    }
                    foreach ($resetFieldsOnObject as $resetFieldOnObject) {
                        $this->setValueByOption($oRecord, $resetFieldOnObject, null, $languageID);
                    }

                    break;
                    //Children
                case 'Children':

                    $numberOfLevels = $oAction->getValue('numberOfLevels');
                    $Childrens = $oRecord->getApi()->getChildren($numberOfLevels);


                    //Set and reset defined values on relevant children, which are checked by state

                    foreach ($Childrens as $Child) {
                        //In case, Child is not relevant skip set / reset values entirely
                        if (!in_array($Child->getValue('StateID'), $states)) {
                            continue;
                        }


                        foreach ($states as $state) {

                            foreach ($selectedFieldsOnObject as $selectedFieldOnObject) {

                                $this->setValueByOption(
                                    $Child,
                                    $selectedFieldOnObject,
                                    $oAction->getValue($selectedFieldOnObject . '_AttributeOnObject'),
                                    $languageID
                                );
                            }
                            foreach ($resetFieldsOnObject as $resetFieldOnObject) {
                                $this->setValueByOption($Child, $resetFieldOnObject, null, $languageID);
                            }
                        }
                    }
                    break;

                case 'Parent':

                    $numberOfLevels = $oAction->getValue('numberOfLevels');
                    $Parent = $oRecord->getApi()->getParent();

                    //Set and reset defined values on relevant children, which are checked by state
                    for ($i = 0; $i < $numberOfLevels; $i++) {
                        alert($Parent->getValue('ObjecttypeID'));
                        if (!in_array($Parent->getValue('StateID'), $states)  ||  !in_array($Parent->getValue('ObjecttypeID'), array(4, 5, 6))) {
                            continue;
                        }

                        foreach ($states as $state) {

                            foreach ($selectedFieldsOnObject as $selectedFieldOnObject) {

                                $this->setValueByOption(
                                    $Parent,
                                    $selectedFieldOnObject,
                                    $oAction->getValue($selectedFieldOnObject . '_AttributeOnObject'),
                                    $languageID
                                );
                            }
                            foreach ($resetFieldsOnObject as $resetFieldOnObject) {
                                $this->setValueByOption($Parent, $resetFieldOnObject, null, $languageID);
                            }
                        }
                        $Parent->store();
                        $Parent->Checkin();
                        $Parent = $Parent->getApi()->getParent($numberOfLevels);
                    }



                    break;
            }
        }
        //Store changes on context records
        if (isset($Childrens)) {
            foreach ($Childrens as $Child) {
                $Child->store();
                $Child->Checkin();
            }
        }
        // if (isset($Parent)) {
        //     $Parent->store();
        //     $Parent->Checkin();
        // }

        //$oRecord->store();
        //update data for the changed attributes

        $sActiveScripts = $oAction->getValue('ActiveScripts');
        if ($sActiveScripts) {
            $bRunInBackground = $oAction->getValue('bRunInBackground');
            if ($bRunInBackground == null) {
                $bRunInBackground = 0;
            }
            if ($oAction->getValue('AddContext') == 1) {
                $selectedOption = $oAction->getValue('ContextOptions');
                switch ($selectedOption) {
                    case 'Static':
                        $scriptContext = $oAction->getValue('ContextForScript');
                        $this->startActiveScripts(explode(",", $sActiveScripts), $scriptContext, $bRunInBackground);
                        break;
                    case 'RefAttr':
                        $scriptContext = $oAction->getValue('ContextForScript');
                        $refIDs = $oRecord->getApi()->getReferences($scriptContext, 'IDList');
                        $sRefIDs = implode(',', $refIDs);
                        $this->startActiveScripts(explode(",", $sActiveScripts), $sRefIDs, $bRunInBackground);
                        break;
                    default:
                        $this->startActiveScripts(explode(",", $sActiveScripts), $iRecordID, $bRunInBackground);
                        break;
                }
            } else {
                $this->startActiveScripts(explode(",", $sActiveScripts), null, $bRunInBackground);
            }
        }
    }


    /**
     * This method is used to copy content from one language dimension to another for divers attribute types.
     * @param Array $copyFields The configured/identified attributed which should be copied over.
     * @param Record|Item $oRecord The CONTENTSERV Record or Item the action is executed on.
     * @param int $iContextLanguageID The given context language which is used as master from where the content is copied from.
     * @param int $languageID The target language to where the content is copied to.
     * @return void
     * @access private
     * @throws CSException
     */
    private function _copyAttributeContentToLanguage(array $copyFields, CSItemApiItem $oRecord, int $iContextLanguageID, int $languageID)
    {
        $iRecordID = $oRecord->getID();
        foreach ($copyFields as $iFieldID => $copyField) {
            if ($copyField->getType() == "table") {
                static $aTables = array();
                if (!isset($aTables[$iRecordID][$iFieldID][$languageID])) {
                    $oTable = $oRecord->getTable($iFieldID, $languageID);
                    $aTables[$iRecordID][$iFieldID][$languageID] = $oTable;
                }

                if (!isset($aTables[$iRecordID][$iFieldID][$iContextLanguageID])) {
                    $oMasterTable = $oRecord->getTable($iFieldID, $iContextLanguageID);
                    $aTables[$iRecordID][$iFieldID][$iContextLanguageID] = $oMasterTable;
                }

                static $aDeletedIDs = array();
                if (!isset($aDeletedIDs[$iRecordID][$iFieldID])) {
                    $sToBeDeletedRows = implode(',', $aTables[$iRecordID][$iFieldID][$languageID]->getRowIDs());
                    if ($sToBeDeletedRows) {
                        $oTable->deleteRows('PdmarticletableID IN(' . $sToBeDeletedRows . ')', false);
                    }
                    $aDeletedIDs[$iRecordID][$iFieldID] = true;
                }

                foreach ($aTables[$iRecordID][$iFieldID][$iContextLanguageID]->getRows() as $oToBeInsertedRow) {
                    /**
                     * @var CSItemApiTableRow $oToBeInsertedRow
                     */
                    $newRow = $oTable->addRow();
                    $newRow->setValue('ItemLanguageID', $languageID);

                    $aAllFieldIDs = array();
                    $aAllFieldIDs = $oToBeInsertedRow->getFieldIDs();
                    foreach ($aAllFieldIDs as $key => $iFieldID) {
                        $aValue = $oToBeInsertedRow->getValue($key, $iContextLanguageID, true, CSITEM_VALUES_ARRAY);
                        $this->setValueByOption($newRow, $key, $aValue, $languageID);
                    }

                    $newRow->store();
                }
            } else {
                $this->setValueByOption($oRecord, $iFieldID, $oRecord->getValue($iFieldID, $iContextLanguageID, true, CSITEM_VALUES_ARRAY), $languageID);
            }
        }
    }

    /**
     * This method is used to identify the relevant languages that need to be processed for the action component.
     * @param Action $oAction The workflow Action record.
     * @param Record|Item $oRecord The CONTENTSERV Record or Item the action is executed on.
     * @param int $iContextLanguageID Sets the context language based on the config. Is passed by reference.
     * @return array Returning the lanuages as array of integers by the languages id of the existing CSLanguages in the system.
     * @access private
     */
    private function _getLanguagesForExecution(Action $oAction, $oRecord, int &$iContextLanguageID)
    {
        //Configures languages in action component
        $sLanguageIDs = $oAction->getValue('LanguageIDs');
        $aContextLanguageID = get_object_vars($oRecord);
        $ContextLanguageID = array_keys($aContextLanguageID['currentLocales']);
        $iContextLanguageID = reset($ContextLanguageID);

        if (!$sLanguageIDs) { //If no language is selected, only the context language is identified as language.
            $aLanguageIDs[] = $iContextLanguageID;
        } else { //Add the context language to the configured languages if the context languages should be processed as well.
            $aLanguageIDs = explode(',', $sLanguageIDs);
            if ($oAction->getValue('AddCurrentLanguage') == 1) {
                $aLanguageIDs[] = $iContextLanguageID;
            }
        }
        return $aLanguageIDs;
    }

    public function mayExecute($oAction, $oRecord, $bIsExecuting = false)
    {
        /**   In case, the defined objects are in one of the defined workflow states, ...
         *    the workflow can be executed!
         **/
        $states = $oAction->getValue('CheckState');
        if ($states) {
            $states = explode(',', $states);
        }

        //Fetch configured languageIDs, it not maintained, get context-language
        $iContextLanguageID = get_object_vars($oRecord);
        $ContextLanguageID = array_keys($iContextLanguageID['currentLocales']);
        $iContextLanguageID = reset($ContextLanguageID);

        $checkAttributes = $oAction->getValue('CheckAttributeIDs');
        if ($checkAttributes) {
            $checkAttributes = explode(',', $checkAttributes);
        } else {
            //If no checkattributes are given the precondition should be true
            $checkAttributes = array();
        }
        //Gets all attributefields from the Product and checks, if the checkattributes are not classified on the product
        $aClassifiedFields = $oRecord->getApi()->getFieldIDs();
        //Indicates if all checkattributes are explicitly removed because they're not classified. Preconditions with where all fields were removed are false
        $bNoClassifiedFields = false;
        //Only checks the Attributes if checkattributes is not empty
        if (!empty($checkAttributes)) {
            foreach ($checkAttributes as $key => $iCheckAttributeID) {
                if (is_numeric($iCheckAttributeID)) {
                    if (!in_array($iCheckAttributeID, $aClassifiedFields)) {
                        unset($checkAttributes[$key]);
                    }
                }
            }
            if (empty($checkAttributes)) {
                //$bNoClassifiedFields = true;
                return false;
            }
        }

        //Check relevant objects depending on the defined object type
        switch ($oAction->getValue('CheckedObjects')) {
                //Parent
                //Kontext
            case 'Context':
                foreach ($checkAttributes as $checkAttribute) {
                    //alert('Wert des Kontexts: ' . $oRecord->getValue($checkAttribute) . ' - ' . 'Wert des InputFeldes: ' . $oAction->getValue($checkAttribute.'_CheckAttribute'));
                    $bResult = $this->validateCondition($oRecord, $checkAttribute, $oAction->getValue($checkAttribute . '_CheckAttribute'));
                    if ($bResult == false) {
                        return false;
                    }
                }
                return true;
                break;
                //Children
            case 'Children':
                if ($states) {
                    $levelOfChildren = $oAction->getValue('levelOfChildren');
                    $Children = null;
                    switch ($levelOfChildren) {
                        case 'allLevels':
                            $Children = $oRecord->getApi()->getChildren(0);
                            break;
                        case 'directChildren':
                            $Children = $oRecord->getApi()->getChildren(1);
                            break;
                        case 'endNotes':
                            $Children = $oRecord->getApi()->getChildren(0);
                            foreach ($Children as $key => $Child) {
                                if ($Child->getValue('IsFolder') != 0) {
                                    unset($Children[$key]);
                                }
                            }
                            break;
                        case 'dynamicNumberOfLayers':
                            $numberOfLevels = $oAction->getValue('numberOfLevels');
                            $Children = $oRecord->getApi()->getChildren($numberOfLevels);
                            break;
                    }

                    $numberOfChildren = $oAction->getValue('NumberOfChildren');
                    switch ($numberOfChildren) {
                        case 'minOne':
                            $minimumChildren = 1;
                            $allChildren = false;
                            break;
                        case 'dynamicNumber':
                            $minimumChildrenNumber = $oAction->getValue('minimumChildrenNumber');
                            $minimumChildren = $minimumChildrenNumber;
                            $allChildren = false;
                            break;
                        case 'allChildren':
                        default:
                            $allChildren = true;
                            break;
                    }

                    $ignoredStates = $oAction->getValue('IgnoredStates');
                    $aIgnoredStates = explode(',', $ignoredStates);

                    $minimumChildrenCounter = 0;
                    foreach ($states as $state) {
                        foreach ($Children as $Child) {
                            if ($Child->getValue('StateID') == $state && !in_array($Child->getValue('StateID'), $aIgnoredStates)) {
                                //Check all relevant attributeIds being equal to InputField value
                                foreach ($checkAttributes as $checkAttribute) {
                                    //alert('Wert des Kindes: ' . $Child->getValue($checkAttribute) . ' - ' . 'Wert des InputFeldes: ' . $action->getValue($checkAttribute.'_CheckAttribute'));
                                    $bResult = $this->validateCondition($Child, $checkAttribute, $oAction->getValue($checkAttribute . '_CheckAttribute'));
                                    if ($bResult == false && $allChildren == true) {
                                        return false;
                                    } elseif ($bResult == true) {
                                        $minimumChildrenCounter++;
                                    }
                                }
                            }
                        }
                    }
                    if ($allChildren == true || $minimumChildrenCounter >= $minimumChildren) {
                        return true;
                    }
                } else {
                    alert('Please select at least one state');
                }
                break;
            case 'Parent':
                if ($states) {
                    $levelOfChildren = $oAction->getValue('levelOfChildren');
                    $Children = null;
                    switch ($levelOfChildren) {
                        case 'allLevels':
                            $Children = $oRecord->getApi()->getParent(0);
                            break;
                        case 'directChildren':
                            $Children = $oRecord->getApi()->getParent(1);
                            break;
                        case 'endNotes':
                            $Children = $oRecord->getApi()->getParent(0);
                            foreach ($Children as $key => $Child) {
                                if ($Child->getValue('IsFolder') != 0) {
                                    unset($Children[$key]);
                                }
                            }
                            break;
                        case 'dynamicNumberOfLayers':
                            $numberOfLevels = $oAction->getValue('numberOfLevels');
                            $Children = $oRecord->getApi()->getParent($numberOfLevels);
                            break;
                    }

                    $numberOfChildren = $oAction->getValue('NumberOfChildren');
                    switch ($numberOfChildren) {
                        case 'minOne':
                            $minimumChildren = 1;
                            $allChildren = false;
                            break;
                        case 'dynamicNumber':
                            $minimumChildrenNumber = $oAction->getValue('minimumChildrenNumber');
                            $minimumChildren = $minimumChildrenNumber;
                            $allChildren = false;
                            break;
                        case 'allChildren':
                        default:
                            $allChildren = true;
                            break;
                    }

                    $ignoredStates = $oAction->getValue('IgnoredStates');
                    $aIgnoredStates = explode(',', $ignoredStates);

                    $minimumChildrenCounter = 0;
                    $Child = $oRecord->getApi()->getParent();
                    if ($oAction->getValue('CheckedObjects') != "Context") {
                        $numberOfLevels = $oAction->getValue('numberOfLevels');
                    }
                    foreach ($states as $state) {
                        for ($i = 0; $i < $numberOfLevels; $i++) {
                            if ($Child->getValue('StateID') == $state && !in_array($Child->getValue('StateID'), $aIgnoredStates)) {
                                //Check all relevant attributeIds being equal to InputField value
                                foreach ($checkAttributes as $checkAttribute) {
                                    //alert('Wert des Kindes: ' . $Child->getValue($checkAttribute) . ' - ' . 'Wert des InputFeldes: ' . $action->getValue($checkAttribute.'_CheckAttribute'));
                                    $bResult = $this->validateCondition($Child, $checkAttribute, $oAction->getValue($checkAttribute . '_CheckAttribute'));
                                    if ($bResult == false && $allChildren == true) {
                                        return false;
                                    } elseif ($bResult == true) {
                                        $minimumChildrenCounter++;
                                    }
                                }
                            }
                            $Child = $oRecord->getApi()->getParent();
                        }
                    }
                    if ($allChildren == true || $minimumChildrenCounter >= $minimumChildren) {
                        return true;
                    }
                } else {
                    alert('Please select at least one state');
                }
                break;
        }
        return false;
    }


    /**
     * This method is called to validate an attribute condition
     * @param Record|Item $record The record to handle
     * @param string $checkAttribute Configured field from the plugin configuration
     * @param string||null $sCondition Condition from the plugin configuration
     * @return bool Whether the condition is validated
     */
    public function validateCondition($record, string $checkAttribute, $sCondition): bool
    {
        if (!$sCondition) {
            return true;
        }
        $oField = $record->getAPI()->getField($checkAttribute);
        $checkValues = ($oField->getType() === 'stringfunction')
            ? $record->getFormattedValue($checkAttribute)
            : $record->getValue($checkAttribute);

        $aValues = explode('||', $sCondition);
        $aCheckedValues = [];

        foreach ($aValues as $value) {
            $sReplacedValues = $this->getReplaceValue($record, $record->getCurrentLanguageID(), $value);

            // Detect operators at the start of the string
            preg_match('/^\s*(!|<=|>=|<|>)/', $sReplacedValues, $aMatches);
            $operator = $aMatches[0] ?? '';

            // Remove the operator from the value
            $sReplacedValues = preg_replace('/^\s*(!|<=|>=|<|>)/', '', $sReplacedValues);

            // Check if the value is properly wrapped with %
            $isContains = (strpos($sReplacedValues, '%') === 0 && strrpos($sReplacedValues, '%') === (strlen($sReplacedValues) - 1));
            if ($isContains) {
                $sReplacedValues = trim($sReplacedValues, '%');
                $operator = trim($operator . self::OPERATOR_CONTAINS);
            }

            switch ($operator) {
                case self::OPERATOR_SMAlLER:
                    $aCheckedValues[] = $sReplacedValues < $checkValues ? 1 : 0;
                    break;
                case self::OPERATOR_GREATER:
                    $aCheckedValues[] = $sReplacedValues > $checkValues ? 1 : 0;
                    break;
                case self::OPERATOR_SMAlLER_EQUAL:
                    $aCheckedValues[] = $sReplacedValues <= $checkValues ? 1 : 0;
                    break;
                case self::OPERATOR_GREATER_EQUAL:
                    $aCheckedValues[] = $sReplacedValues >= $checkValues ? 1 : 0;
                    break;
                case self::OPERATOR_NOT_EQUAL:
                    $aCheckedValues[] = $sReplacedValues != $checkValues ? 1 : 0;
                    break;
                case '!' . self::OPERATOR_CONTAINS:
                    $aCheckedValues[] = strpos($checkValues, $sReplacedValues) === false ? 1 : 0;
                    break;
                case self::OPERATOR_CONTAINS:
                    $aCheckedValues[] = strpos($checkValues, $sReplacedValues) !== false ? 1 : 0;
                    break;
                default:
                    $aCheckedValues[] = $sReplacedValues == $checkValues ? 1 : 0;
                    break;
            }
        }

        return in_array(1, $aCheckedValues, true);
    }

    /**
     * This method is called to set attribute values in regard to certain options
     * @param Record|Item $record the record to handle
     * @param int $attributeID configured field out of the plugin configuration
     * @param string|array $value out of the plugin configuration
     * @param int $languageID the language to set values to
     */
    private function setValueByOption($record, $attributeID, $value, $languageID): void
    {
        //Set the value based on options and attribute types. - Added a check on !"null" to avoid empty timestamps in caption fields
        //$oField = $this->getObjectByClass($this->getFieldNameByClass($record->getClassName()), $attributeID); //Has to be adjusted to CSitemApiItem if needed.
        if ($value != null) {
            if (!is_array($value)) {
                static $aFields = [];
                //Check if the target field is a reference, if yes add the references
                if (!array_key_exists($attributeID, $aFields)) {

                    $aFields[$attributeID] = $record->getApi()->getField($attributeID);
                }

                $sReplacedValues = $this->getReplaceValue($record, $languageID, $value);

                if ($attributeID == "ClassMapping") {
                    $this->WFRecord->setConfigurationClassIds(explode(',', $sReplacedValues));
                    //$this->WFRecord->store();
                    return;
                }

                if ($aFields[$attributeID]->getType() == 'articlereference' || $aFields[$attributeID]->getType() == 'file') {

                    $aRefs = explode(',', $sReplacedValues);
                    $record->getApi()->setReferences($attributeID, $aRefs, array(), $languageID);
                } else {
                    $record->setValue($attributeID, $sReplacedValues, $languageID);
                }
            } else {
                if (isset($value["details"]["Unformatted"])) {
                    $record->setValue($attributeID, $value["details"]["Unformatted"], $languageID);
                }
                if (isset($value["details"]["Unformatted_1"])) {
                    $record->setValue($attributeID, $value["details"]["Unformatted_1"], $languageID, 1);
                }
                if (isset($value["details"]["Unformatted_2"])) {
                    $record->setValue($attributeID, $value["details"]["Unformatted_2"], $languageID, 2);
                }
            }
        } else {
            $record->setValue($attributeID, $value, $languageID);
        }
    }

    public function getReplaceValue(CSPmsProduct|Record|Item $record, int $iLanguageID, string $value = ""): string
    {

        // Step 1: Extract and preserve "{=...}" patterns to prevent them from being replaced
        $protectedPatterns = [];
        if (preg_match_all('~\{=([^\}]+)\}~', $value, $protectedMatches)) {
            foreach ($protectedMatches[0] as $match) {
                $placeholder = '__PROTECTED__' . count($protectedPatterns) . '__';
                $protectedPatterns[$placeholder] = $match;
                $value = str_replace($match, $placeholder, $value);
            }
        }

        // Step 2: Handle basic placeholders like {Today} and {null}
        $aVars = [
            '{Today}' => date('Y-m-d'),
            '{null}' => null,
        ];

        // Handle date placeholders with custom formats
        if (preg_match_all('~\{(Today#[^}]*)\}~', $value, $aDateMatches)) {
            foreach ($aDateMatches[1] as $dateMatch) {
                $aExplodedDate = explode('#', $dateMatch);
                $format = $aExplodedDate[1] ?? 'Y-m-d';
                $aVars['{' . $dateMatch . '}'] = date($format);
            }
        }

        $sReplacedValues = str_ireplace(array_keys($aVars), array_values($aVars), $value);

        // Step 3: Handle record field placeholders
        if (preg_match_all('~\{([^}]*)\}~', $sReplacedValues, $aMatches)) {
            foreach ($aMatches[1] as $sAttributeKey) {
                $oField = $record->getAPI()->getField($sAttributeKey);
                $sPlaceholderValue = '';

                if ($oField->getType() === 'stringfunction' || $oField->getType() === 'valuerange') {
                    $sPlaceholderValue = $record->getFormattedValue($sAttributeKey, $iLanguageID);
                }

                if (!$sPlaceholderValue) {
                    $sPlaceholderValue = $record->getValue($sAttributeKey, $iLanguageID);
                }

                $sReplacedValues = str_replace('{' . $sAttributeKey . '}', $sPlaceholderValue, $sReplacedValues);
            }
        }

        // Step 4: Handle value range placeholders
        if (preg_match_all('~\#([^#]*)\#~', $sReplacedValues, $aMatches)) {
            foreach ($aMatches[1] as $sValuelistvalue) {
                $oValueRange = new CSValueRangeValue($sValuelistvalue);
                $sValueRangeValue = $oValueRange->getValue($record->getCurrentLanguageID());
                $sReplacedValues = str_replace('#' . $sValuelistvalue . '#', $sValueRangeValue, $sReplacedValues);
            }
        }

        // Step 5: Restore the protected patterns back into the string
        foreach ($protectedPatterns as $placeholder => $originalPattern) {
            $sReplacedValues = str_replace($placeholder, $originalPattern, $sReplacedValues);
        }

        return $sReplacedValues;
    }


    /**
     * Is activated by a setting in the system options "SQLi/Plugin Package".
     * @return boolean whether the plugin is available for the given arguments
     */
    public function isAvailable($records = null): bool
    {
        //parent::isAvailable($records);
        //return CSSQLi::isActivated('EnableWorkflowPlugins');
        return true;
    }

    /**
     * Will deliver the instanced object by the given class name.
     * @param string $sClassName the given class
     * @param string $iContextID
     * @return mixed
     */
    public function getObjectByClass($sClassName, $iContextID)
    {
        $sClassName = strtolower($sClassName);
        if (!is_numeric($iContextID) == true) {
            $iContextID = null;
        }

        switch ($sClassName) {
            case 'pdmarticle':
                return CSPms::getProduct($iContextID);
            case 'mamfile':
                return CSMam::getFile($iContextID);
            case 'pdmarticlestructure':
                return CSPms::getView($iContextID);
            case 'pdmarticlestructureconfiguration':
            case 'pdmarticleconfiguration':
                return CSPms::getField($iContextID);
            case 'mamfileconfiguration':
                return CSMam::getField($iContextID);
        }
        return null;
    }

    /**
     * Will deliver the instanced object by the given class name.
     * @param string $sClassName the given class
     * @return string
     */
    public function getFieldNameByClass(string $sClassName): string
    {
        $sClassName = strtolower($sClassName);
        switch ($sClassName) {
            case 'pdmarticle':
            case 'pdmarticlestructure':
            case 'pdmarticleconfiguration':
            case 'pdmarticlestructureconfiguration':
            case 'cspmsproduct':
                return 'pdmarticleconfiguration';
            case 'mamfileconfiguration':
            case 'mamfile':
                return 'mamfileconfiguration';
        }
        return '';
    }

    /**
     * Returns a list of ActiveScript
     * @param string $filter optional filter
     * @param string $sortorder optional sortorder
     * @return CSActiveScript[] array with ActiveScript
     */
    private static function _getActiveScriptsForFilter($filter = '1=1', $sortorder = ''): array
    {
        $table = CS::getTable('ActiveScript', $filter, $sortorder);
        $activeScriptJobs = array();
        foreach ($table->records as $record) {
            $activeScriptJobs[$record->getID()] = CSActiveScript::getActiveScript($record->getID());
        }

        return $activeScriptJobs;
    }


    /**
     * Returns a list of ActiveScript
     * @param array $aActiveScripts
     * @param string|null $iContext Contexts for the script, can be more than one. For Example: "123,1234,12345"
     * @return void array with ActiveScript
     * @throws CSException
     */
    public function startActiveScripts(array $aActiveScripts, string|null $iContext, bool $bRunInBackground): void
    {
        $aScripts = $this->_getActiveScriptsForFilter('ActiveScriptID IN (' . implode(",", $aActiveScripts) . ')');
        foreach ($aScripts as $oScript) {
            if ($iContext) {
                $oJob = $oScript->createActiveScriptJob(
                    //Adding the input data is good, but this requires the target script to actually make use of it in its implementation.
                    new CSActiveScriptJobInputData(array(
                        'ContextIDs' => $iContext
                    ))
                );
                $oJob->setValue('ContextIDs', $iContext);
            } else {
                $oJob = $oScript->createActiveScriptJob();
            }
            $oJob->store();

            if ($bRunInBackground) {
                $oJob->start();
            } else {
                $oJob->run();
            }
        }
    }
}
