<?php 
// src/AppBundle/Form/Type/LearnerPersonalType.php
namespace AppBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

//this class builds the form that is used to add/edit a learner's personal details
class LearnerPersonalType extends AbstractType
{
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		//add the form fields
		$builder
		->add('idlwd','text', array(
				'label' => 'Learner Identification Number',
				'constraints' => array(new NotBlank(), new Regex(array(
					'pattern'=>'#\d{16}#',
					'message'=>'This field must be a 16 digit number'
					)),
				)
			)
		)
		->add('first_name','text', array(
			'label' => 'First name',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('last_name', 'text', array(
			'label' => 'Last name',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('initials', 'text', array(
			'label' => 'Initials',
			'required' => false,
			)
		)
		->add('home_address', 'textarea', array(
			'label' => 'Home address',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('sex', 'choice', array(
			'label' => 'Sex',
			'choices' => array('M'=>'M','F'=>'F'),
			'constraints' => array(new NotBlank()),
			'expanded' => true,
			'multiple' => false,
			)
		)
		->add('dob', 'date', array(
			'label' => 'Date of birth',
			'widget' => 'single_text',
			'format' => 'dd-MM-yyyy',
			'constraints' => array(new NotBlank()),
			'attr' => array('class'=>'datepicker','data-date-format'=>'dd-mm-yyyy'),
			)
		)
		->add('distance_to_school', 'integer', array(
			'label' => 'Distance to school (Km)',
			'constraints' => array(
				new NotBlank(),
                                new Range(array('min'=> 1)),
				new Type(array('type'=>'integer','message'=>'Please enter a valid distance value'))
				)
			)
		)
		->add('std', 'integer', array(
			'label' => 'Standard',
			'constraints' => array(
				new NotBlank(),
				new Type(array('type'=>'integer','message'=>'Please enter a valid std value')),
				new Range(array('min'=> 1,'max'=>8, 'invalidMessage'=>'Please enter a value between 1 and 8')),
				)
			)
		)
		//The following are fields for guardian
		->add('idguardian','hidden', array(
			)
		)
		->add('gfirst_name','text', array(
			'label' => 'First name',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('glast_name', 'text', array(
			'label' => 'Last name',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('gsex', 'choice', array(
			'label' => 'Sex',
			'choices' => array('M'=>'M','F'=>'F'),
			'constraints' => array(new NotBlank()),
			'expanded' => true,
			'multiple' => false,
			)
		)
		->add('gaddress', 'textarea', array(
			'label' => 'Home address',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('gdob', 'date', array(
			'label' => 'Date of birth',
			'widget' => 'single_text',
			'format' => 'dd-MM-yyyy',
			'constraints' => array(new NotBlank()),
			'attr' => array('class'=>'datepicker','data-date-format'=>'dd-mm-yyyy'),
			)
		)
		->add('occupation','text', array(
			'label' => 'Occupation',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('district','text', array(
			'label' => 'District',
			'constraints' => array(new NotBlank()),
			)
		)
		->add('guardian_relationship', 'choice', array(
			'label' => 'Relationship',
			'choices' => array('parent'=>'parent','sibling'=>'sibling','other'=>'other'),
			'constraints' => array(new NotBlank()),
			'expanded' => true,
			'multiple' => false,
			)
		)
		->add('save','submit', array(
			'label' => 'save',
			)
		);
	}
	public function getName()
	{
		return 'learner_personal';
	}
}
?>