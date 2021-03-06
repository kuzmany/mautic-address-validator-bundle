<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticAddressValidatorBundle\Form\Type;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class FormFieldAddressValidatordType.
 */
class FormFieldAddressValidatordType extends AbstractType
{
    /**
     * @var LeadFieldModel
     */
    protected $leadFieldModel;

    /**
     * FieldModel constructor.
     *
     * @param LeadFieldModel $leadFieldModel
     */
    public function __construct(LeadFieldModel $leadFieldModel)
    {
        $this->leadFieldModel = $leadFieldModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->leadFieldModel->getFieldListWithProperties();

        $choices[]= '';
        foreach ($fields as $alias => $field) {
            if (!isset($choices[$field['group_label']])) {
                $choices[$field['group_label']] = [];
            }

            $choices[$field['group_label']][$alias] = $field['label'];
        }

        $options['leadFields']          = $choices;
        $options['leadFieldProperties'] = $fields;

        $builder->add(
            'validatorRequired',
            'yesno_button_group',
            [
                'label' => 'plugin.addressvalidator.field.required',
                'attr'  => [
                    'tooltip' => 'plugin.addressvalidator.field.required.desc',
                ],
                'data'=>isset($options['data']['validatorRequired']) ? $options['data']['validatorRequired'] : true
            ]
        );

        $builder->add(
            'placeholderAddress',
            'yesno_button_group',
            [
                'label' => 'plugin.addressvalidator.field.label.placeholder',
                'data'=>isset($options['data']['placeholderAddress']) ? $options['data']['placeholderAddress'] : false
            ]
        );

        $builder->add(
            'validatorToogle',
            'yesno_button_group',
            [
                'label' => 'plugin.addressvalidator.field.toggle',
                'data'=>isset($options['data']['validatorToogle']) ? $options['data']['validatorToogle'] : false

            ]
        );

        $builder->add(
            'labelToogle',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.toogle',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Callback(
                        function ($validateMe, ExecutionContextInterface $context) {
                            $data = $context->getRoot()->getData();
                            if (!empty($data['properties']['validatorToogle']) && empty($validateMe)) {
                                $context->buildViolation('mautic.core.value.required')->addViolation();
                            }
                        }
                    ),
                ],
            ]
        );

        $builder->add(
            'labelAddress1',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.address1',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'leadFieldAddress1',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelAddress2',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.address2',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'leadFieldAddress2',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelCity',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.city',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );


        $builder->add(
            'leadFieldCity',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelState',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.state',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'leadFieldState',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelZip',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.zip',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'leadFieldZip',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelCountry',
            'text',
            [
                'label'      => 'plugin.addressvalidator.field.label.country',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'leadFieldCountry',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'optionsCountry',
            'textarea',
            [
                'label'      => 'plugin.addressvalidator.field.label.country.options',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'rows'    => 5,
                    'tooltip' => 'plugin.addressvalidator.field.label.country.options.tooltip',
                ],
            ]
        );

        $builder->add(
            'leadFieldAddressValidated',
            'choice',
            [
                'choices'     => $options['leadFields'],
                'choice_attr' => function ($val, $key, $index) use ($options) {
                    if (!empty($options['leadFieldProperties'][$val]) && (in_array(
                                $options['leadFieldProperties'][$val]['type'],
                                FormFieldHelper::getListTypes()
                            ) || !empty($options['leadFieldProperties'][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$val]['properties']['optionlist']))
                    ) {
                        return ['data-list-type' => 1];
                    }

                    return [];
                },
                'label'      => 'mautic.form.field.form.lead_field.address_validated',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'labelAddress4',
            'hidden',
            [
                'label' => 'plugin.addressvalidator.field.label.toogle',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'addressvalidator';
    }
}
