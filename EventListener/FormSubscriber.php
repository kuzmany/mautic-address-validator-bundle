<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticAddressValidatorBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticAddressValidatorBundle\AddressValidatorEvents;
use MauticPlugin\MauticAddressValidatorBundle\Helper\AddressValidatorHelper;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AddressValidatorHelper ;
     */
    protected $addressValidatorHelper;

    /**
     * @var SubmissionModel
     */
    protected $submissionModel;

    private static $validationResults = [];

    /**
     * FormSubscriber constructor.
     *
     * @param LeadModel              $leadModel
     * @param CoreParametersHelper   $coreParametersHelper
     * @param AddressValidatorHelper $addressValidatorHelper
     * @param SubmissionModel        $submissionModel
     */
    public function __construct(
        LeadModel $leadModel,
        CoreParametersHelper $coreParametersHelper,
        AddressValidatorHelper $addressValidatorHelper,
        SubmissionModel $submissionModel
    ) {
        $this->leadModel              = $leadModel;
        $this->coreParametersHelper   = $coreParametersHelper;
        $this->addressValidatorHelper = $addressValidatorHelper;
        $this->submissionModel        = $submissionModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                       => ['onFormBuilder', 0],
            FormEvents::FORM_ON_SUBMIT                      => ['onFormSubmit', 0],
            AddressValidatorEvents::ON_FORM_VALIDATE_ACTION => ['onFormValidate', 0],
            PluginEvents::ON_FORM_SUBMIT_ACTION_TRIGGERED            => [
                ['onFormSubmitTokensUpdate', 1000],
            ],
        ];
    }

    /**
     * Trigger campaign event for when a form is submitted.
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $form       = $event->getSubmission()->getForm();
        $fields     = $form->getFields();
        $lead       = $event->getLead();
        $submission = $event->getSubmission();
        $results    = $event->getResults();
        $props      = [];

        // update form results
        /** @var SubmissionRepository $repo */
        $repo             = $this->submissionModel->getRepository();
        $resultsTableName = $repo->getResultsTableName($form->getId(), $form->getAlias());
        $tableKeys        = ['submission_id' => $submission->getId()];

        foreach ($event->getFields() as $field) {
            if ($field['type'] == 'plugin.addressvalidator') {
                $addressValidatorFieldAlias = $field['alias'];
                $data                       = $event->getRequest()->get('mauticform')[$addressValidatorFieldAlias];
                if (!isset($data['addressvalidated'])) {
                    foreach ($fields as $f) {
                        if ($f->getId() == $field['id']) {
                            //  $data = $this->setValidateAddress($f, $data);
                            break;
                        }
                    }
                }
                if (empty($data['addressvalidated'])) {
                    $data['addressvalidated'] = 'No';
                }

                $dataValues = array_filter($data);

                /* @var \Mautic\FormBundle\Entity\Field $f */
                if (!empty($data)) {
                    foreach ($fields as $f) {
                        if ($f->getAlias() == $addressValidatorFieldAlias) {
                            $props = [];
                            foreach ($f->getProperties() as $key => $property) {
                                if (strpos($key, 'label') !== false || strpos($key, 'leadField') !== false) {
                                    $newKey = strtolower(str_ireplace(['label', 'leadField'], ['', ''], $key));
                                    if ($newKey) {
                                        $props[$newKey][str_ireplace($newKey, '', $key)] = $property;
                                    }
                                }
                            }
                            break;
                        }
                    }

                    // try replace validated values
                    // $updateSubmittedData = false;
                    /*     if (!empty($dataValues) && $data['addressvalidated'] == "No") {
                             $hashId = md5(serialize($data));
                             if(empty(self::$validationResults[$hashId])) {
                                 $values = $this->addressValidatorHelper->parseDataFromRequest($data);
                                 $result = $this->addressValidatorHelper->validation(false, null, $values);
                             }else{
                                 $result  = self::$validationResults[$hashId];
                             }
                             if (!empty($result)) {
                                 $result = \GuzzleHttp\json_decode($result, true);
                                 if ($result['status'] == 'VALID') {
                                     $data['addressvalidated'] = 'Yes';
                                     $updateSubmittedData = true;
                                 }
                             }
                         }*/

                    $matchedFields = [];
                    foreach ($data as $key => $value) {
                        /*
                                                $serviceReponseKey = str_replace(['address1', 'zip'], ['addressline1', 'postalcode'], $key);
                                                if (empty($result[$serviceReponseKey])) {
                                                    $result[$serviceReponseKey] = '';
                                                }
                                                if ($updateSubmittedData && !in_array($key, ['toogle', 'addressvalidated','address4'])) {
                                                    $value = $result[$serviceReponseKey];
                                                    $data[$key] = $result[$serviceReponseKey];
                                                }*/

                        if (in_array($key, array_keys($props)) && isset($props[$key]['leadField'])) {
                            $matchLeadField = $props[$key]['leadField'];
                            if ($matchLeadField) {
                                $matchedFields[$matchLeadField] = $value;
                            }
                        }
                    }

                    $this->leadModel->setFieldValues($lead, $matchedFields, true);
                    // update addres field
                    $results[$addressValidatorFieldAlias] = $results[$addressValidatorFieldAlias] = http_build_query(
                        $data,
                        ',',
                        '|'
                    );
                    $this->em
                        ->getConnection()
                        ->update($resultsTableName, $results, $tableKeys);
                }
            }
        }

        /** Lead $lead */
        if (!empty($lead->getChanges())) {
            $this->leadModel->saveEntity($lead);
        }
    }

    /**
     * Add a lead generation action to available form submit actions.
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        if ($this->addressValidatorHelper->validation(true)) {
            $action = [
                'label'          => 'mautic.plugin.field.addressvalidator',
                'formType'       => 'addressvalidator',
                'template'       => 'MauticAddressValidatorBundle:SubscribedEvents\Field:addressvalidator.html.php',
                'builderOptions' => [
                    'addLeadFieldList'       => false,
                    'addDefaultValue'        => false,
                    'addSaveResult'          => true,
                    'addShowLabel'           => true,
                    'addHelpMessage'         => false,
                    'addLabelAttributes'     => false,
                    'addInputAttributes'     => false,
                    'addBehaviorFields'      => false,
                    'addContainerAttributes' => false,
                    'allowCustomAlias'       => true,
                    'labelText'              => false,
                    'addIsRequired'          => false,
                ],
            ];

            $event->addFormField('plugin.addressvalidator', $action);

            $validator = [
                'eventName' => AddressValidatorEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.addressvalidator',
            ];

            $event->addValidator('plugin.addressvalidator.validate', $validator);
        }
    }


    public function setValidateAddress(\Mautic\FormBundle\Entity\Field $field, $data)
    {
        if ($field->getType() == 'plugin.addressvalidator') {
            $dataValues = array_filter($data);

            $forceValidation = false;
            if (!isset($field->getProperties()['validatorRequired'])) {
                $forceValidation = true;
            } elseif (!empty($field->getProperties()['validatorRequired'])) {
                $forceValidation = true;
            } elseif (empty($field->getProperties()['validatorRequired']) &&
                !empty($data['toogle']) && !empty($dataValues)
            ) {
                $forceValidation = true;
            } elseif (empty($field->getProperties()['validatorRequired']) && !empty($dataValues)) {
                $forceValidation = true;
            }

            // don't transformvalues if not force validation
            if (!$forceValidation) {
                return $data;
            }

            $hashId = md5(serialize($data));
            // pass data to validator
            $values                           = $this->addressValidatorHelper->parseDataFromRequest($data);
            $result                           = $this->addressValidatorHelper->validation(false, null, $values);
            $result                           = \GuzzleHttp\json_decode($result, true);
            self::$validationResults[$hashId] = $result;

            // if not valid, continue with regular validation
            if ($result['status'] != 'VALID') {
                return $data;
            }

            // if valid, continue replace values before save and trigger actions
            if (!empty($result)) {
                foreach ($data as $key => $value) {
                    $serviceReponseKey = str_replace(
                        ['address1', 'address2', 'zip'],
                        ['addressline1', 'addressline2', 'postalcode'],
                        $key
                    );
                    if (!in_array($key, ['toogle', 'addressvalidated', 'address4'])) {
                        $data[$key] = $result[$serviceReponseKey];
                    }
                }
            }
            // force valid
            $data['addressvalidated'] = 'Yes';

            return $data;
        }
    }

    /**
     * @param Events\ValidationEvent $event
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $field = $event->getField();

        if ($field->getType() == 'plugin.addressvalidator') {
            $data = $event->getValue();
            // spam detection
            if (!empty($data['address4'])) {
                return $event->failedValidation(
                    $this->translator->trans('plugin.addressvalidator.detect.spam')
                );
            }

            $dataValues = array_filter($data);

            // force validation, not continue anyway
            $forceValidation = false;
            // bc compatibility  - validatorRequired set to true by defautl
            if (!isset($field->getProperties()['validatorRequired'])) {
                $forceValidation = true;
            } elseif (!empty($field->getProperties()['validatorRequired'])) {
                $forceValidation = true;
            } elseif (empty($field->getProperties()['validatorRequired']) &&
                !empty($data['toogle']) && !empty($dataValues)
            ) {
                $forceValidation = true;
            } elseif (empty($field->getProperties()['validatorRequired']) && !empty($dataValues)) {
                $forceValidation = true;
            }
            // empty address
            if ($forceValidation && empty($dataValues)) {
                return $event->failedValidation(
                    $this->translator->trans('plugin.addressvalidator.form.empty')
                );
            }

            if ($forceValidation) {
                $hashId = md5(serialize($event->getValue()));
                // If we can get results from cache, try agaign
                if (empty(self::$validationResults[$hashId])) {
                    $values                           = $this->addressValidatorHelper->parseDataFromRequest(
                        $event->getValue()
                    );
                    $result                           = $this->addressValidatorHelper->validation(false, null, $values);
                    $result                           = \GuzzleHttp\json_decode($result, true);
                    self::$validationResults[$hashId] = $result;
                }
                if (!empty(self::$validationResults[$hashId])) {
                    $result = self::$validationResults[$hashId];
                    if (empty($result['status'])) {
                        $result['status'] = '';
                    }
                    if (empty($result['status']) || $result['status'] == 'INVALID') {
                        return $event->failedValidation(
                            $this->translator->trans('plugin.addressvalidator.address.is.not.valid')
                        );
                    } elseif ($result['status'] == 'SUSPECT' || ($result['status'] == 'VALID' && isset($data['addressvalidated']) && $data['addressvalidated'] != 'Yes')) {
                        if (!isset($data['correctedaddress'])) {
                            return $event->failedValidation(
                                \GuzzleHttp\json_encode($result)
                            );
                        }
                    } elseif ($result['status'] == 'VALID' && (!isset($data['addressvalidated']) || $data['addressvalidated'] != 'Yes')) {
                        $normalizeAddress            = $this->setValidateAddress($field, $data);
                        $results                     = $this->request->request->all()['mauticform'];
                        $results[$field->getAlias()] = $normalizeAddress;
                        $this->request->request->set('mauticform', $results);
                    }
                } else {
                    return $event->failedValidation(
                        $this->translator->trans('plugin.addressvalidator.form.empty')
                    );
                }
            }
        }
    }

    /**
     * @param SubmissionEvent $event
     */
    public function onFormSubmitTokensUpdate(SubmissionEvent $event)
    {
        $fields = $event->getFields();
        $tokens = $event->getTokens();
        $lead   = $event->getLead();
        $submission = $event->getSubmission();
        $fieldMatches = $event->getContactFieldMatches();
        foreach ($fields as $field) {
            if ($field['type'] == 'plugin.addressvalidator') {
                $matchedFields = $this->getAddressMatchedFields($event->getForm()->getFields(), $field);
                $this->leadModel->setFieldValues($lead, $matchedFields);
                if (!empty($lead->getChanges())) {
                    $this->leadModel->saveEntity($lead);
                }
                if (isset($tokens["{formfield={$field['alias']}}"])) {
                    $addressValidatorFieldAlias              = $field['alias'];
                    $newValue                                = $event->getRequest()->get(
                        'mauticform'
                    )[$addressValidatorFieldAlias];
                    $tokens["{formfield={$field['alias']}}"] = implode(', ', $newValue);
                    $event->setTokens($tokens);
                    return;
                }
            }
        }
    }

    /**
     * @param       $fields
     * @param array $field
     *
     * @return array
     */
    private function getAddressMatchedFields($fields, array $field)
    {
        $addressValidatorFieldAlias = $field['alias'];
        $data                       = $this->request->get('mauticform')[$addressValidatorFieldAlias];

        if (empty($data['addressvalidated'])) {
            $data['addressvalidated'] = 'No';
        }

        $dataValues = array_filter($data);

        if (!empty($data)) {
            /** @var Field $f */
            foreach ($fields as $f) {
                if ($f->getAlias() == $addressValidatorFieldAlias) {
                    $props = [];
                    foreach ($f->getProperties() as $key => $property) {
                        if (strpos($key, 'label') !== false || strpos($key, 'leadField') !== false) {
                            $newKey = strtolower(str_ireplace(['label', 'leadField'], ['', ''], $key));
                            if ($newKey) {
                                $props[$newKey][str_ireplace($newKey, '', $key)] = $property;
                            }
                        }
                    }
                    break;
                }
            }

            $matchedFields = [];
            foreach ($data as $key => $value) {
                if (in_array($key, array_keys($props)) && isset($props[$key]['leadField'])) {
                    $matchLeadField = $props[$key]['leadField'];
                    if ($matchLeadField) {
                        $matchedFields[$matchLeadField] = $value;
                    }
                }
            }
            return $matchedFields;
        }
    }
}
