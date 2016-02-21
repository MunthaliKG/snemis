<?php
// src/AppBundle/Form/Type/AddUserType.php
namespace AppBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

//this class builds the form that is used to add/edit a disability
class DisabilityType extends AbstractType{
	private $categories = array();
	private $levels = array();
	private $needs = array();

	public function __construct($categories, $levels, $needs){
		foreach($categories as $category)
			$this->categories[$category['iddisability_category']] = $category['category_name'];
		foreach($levels as $level)
			$this->levels[$level['idlevel']] = $level['level_name'];
		foreach($needs as $need)
			$this->needs[$need['idneed']] = $need['needname'];
	}
	public function buildForm(FormBuilderInterface $builder, array $options){
		$builder
		->add('iddisability','hidden', array())
		->add('disability_name','text', array(
			'label'=>'Condition Name',
			'constraints' => array(new Assert\NotBlank())
			)
		)
		->add('iddisability_category', 'choice', array(
			'label' => 'Category',
			'choices' => $this->categories,
			'placeholder' => '--None--',
			'required' => false
			)
		)
		->add('teacher_speciality_required', 'choice', array(
			'label' => 'Teacher speciality',
			'placeholder' => '--Select Teacher Speciality--',
			'choices' => array(
				'VI'=>'Visual Impairment',
				'HI'=>'Hearing impairment',
				'LD'=>'Learning Difficulties',
				'DB' => 'Deaf Blind'
				),
			'constraints' => array(new Assert\NotBlank()),
			'multiple' => true,
			'expanded' => true
			)
		)
		->add('disability_description','textarea', array(
			'label'=>'Condition Description',
			'required' => false
			)
		)
		->add('levels', 'choice', array(
			'label'=>'Disability Levels',
			'choices' => $this->levels,
			'multiple' => true,
			'expanded' => false,
			'required' => false
			)
		)
		->add('needs', 'choice', array(
			'label'=>'Disability Needs',
			'choices'=> $this->needs,
			'multiple' => true,
			'expanded' => true,
			)
		)
		->add('save','submit', array(
			'label' => 'Save'
			)
		)
		;
	}
	public function getName(){
		return 'disability';
	}
}


?>