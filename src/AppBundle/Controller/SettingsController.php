<?php
/*this is the controller for the admin settings page
*it controls all links starting with admin/settings/
*/

namespace AppBundle\Controller;

use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Disability;
use AppBundle\Form\Type\DisabilityType;

class SettingsController extends Controller{
	/**
	 *@Route("/admin/settings/disabilities", name="admin_settings_disabilities")
	 */
	public function adminSettingsDisabilitiesAction(Request $request){
		return $this->render('admin/settings/admin_disabilities.html.twig');
	}
	/**
	 *@Route("/admin/finddisability", name="find_disability_form")
	 */
	public function findDisabilityFormAction(Request $request){
		$connection = $this->get('database_connection');
		$disabilities = $connection->fetchAll('SELECT iddisability, disability_name FROM disability');
		$choices = array();
		foreach($disabilities as $disability){
			$choices[$disability['iddisability']] = $disability['disability_name'];
		}
		//create the form for choosing an existing disability to edit
		$defaultData = array('disability' => $request->get('disabilityId'));
		$form = $this->createFormBuilder($defaultData, array(
			'action' => $this->generateUrl('find_disability_form')))
		->add('disability','choice', array(
			'label' => 'Choose Disability',
			'placeholder'=>'--Choose Disability--',
			'choices'=> $choices,
			))
		->getForm();

		$form->handleRequest($request);

		if($form->isValid()){
			$disabilityId = $form->getData()['disability'];
			return $this->redirectToRoute('edit_disability',array('disabilityId'=>$disabilityId));
		}
		return $this->render('admin/settings/finddisabilityform.html.twig', array(
			'form'=>$form->createView()));
	}
	/**
	 *@Route("/admin/settings/disability/{disabilityId}", name="edit_disability", requirements ={"disabilityId":"new|\d+"})
	 */
	public function adminEditDisabilityAction($disabilityId, Request $request){
		$connection = $this->get('database_connection');
		//get all the categories
		$categories = $connection->fetchAll('SELECT iddisability_category, category_name FROM disability_category');
		//get all the needs
		$needs = $connection->fetchAll('SELECT idneed, needname FROM need');
		//get all the levels
		if($request->getSession()->getFlashBag()->has('disability_levels')){
			$levels = $request->getSession()->getFlashBag()->get('disability_levels');
		}else{
			$levels = $connection->fetchAll('SELECT idlevel, level_name FROM level');
			$request->getSession()->getFlashBag()->set('disability_levels', $levels);
		}
		$dataConverter = $this->get('data_converter');

		$defaultData = array();
		if($disabilityId != 'new'){
			$defaultData = $connection->fetchAssoc('SELECT * FROM disability WHERE 
				iddisability=?',[$disabilityId]);
			$defaultData['teacher_speciality_required'] = $dataConverter->convertToArray($defaultData['teacher_speciality_required']);
			$defaultNeeds = $connection->fetchAll('SELECT idneed FROM disability_has_need WHERE 
				iddisability=?',[$disabilityId]);
			$defaultData['needs'] = array_column($defaultNeeds, 'idneed');
			$defaultLevels = $connection->fetchAll('SELECT idlevel FROM disability_has_level WHERE
				iddisability=?',[$disabilityId]);
			$defaultData['levels'] = array_column($defaultLevels, 'idlevel');
		}

		$form = $this->createForm(new DisabilityType($categories, $levels, $needs), $defaultData);

		$form->handleRequest($request);
		if($form->isValid()){
			$em = $this->getDoctrine()->getManager();
			$formData = $form->getData();
			$id = $formData['iddisability'];
			if($disabilityId == 'new')
				$disability = new Disability();
			else
			    $disability = $this->getDoctrine()->getRepository('AppBundle:Disability')->find($id);

			$disability->setDisabilityName($formData['disability_name']);
			if($formData['iddisability_category'] != null)
				$disability->setIddisabilityCategory(
					$em->getReference('AppBundle:DisabilityCategory', $formData['iddisability_category'])
				);
			$disability->setTeacherSpecialityRequired($formData['teacher_speciality_required']);
			$disability->setDisabilityDescription($formData['disability_description']);
			$em->persist($disability);
			$em->flush();

			if($disabilityId == 'new'){
				$id = $disability->getIddisability();
			}

			/*write selected levels to the database*/
			$selectedLevels = $dataConverter->arrayRemoveQuotes($formData['levels']);//remove qoutes from array of selected levels
			$commaString = $dataConverter->convertToCommaString($selectedLevels);//get a string of comma separated values from array
			$connection->executeQuery('DELETE FROM disability_has_level WHERE iddisability=? 
				AND idlevel NOT IN (?)', [$id, $commaString]);
			$writeLevels = $connection->prepare('INSERT IGNORE INTO disability_has_level SET iddisability=?, 
				idlevel=?');
			$writeLevels->bindParam(1, $id);
			foreach($selectedLevels as $selectedLevel){
				$writeLevels->bindParam(2, $selectedLevel);
				$writeLevels->execute();
			}
			$writeLevels->closeCursor();
			/*end of writing of selected levels

			/*write selected needs to the database*/
			$selectedNeeds = $dataConverter->arrayRemoveQuotes($formData['needs']);//remove qoutes from array of selected levels
			$commaString = $dataConverter->convertToCommaString($selectedNeeds);//get a string of comma separated values from array
			$connection->executeQuery('DELETE FROM disability_has_need WHERE iddisability=? 
				AND idneed NOT IN (?)', [$id, $commaString]);
			$writeNeeds = $connection->prepare('INSERT IGNORE INTO disability_has_need SET iddisability=?, 
				idneed=?');
			$writeNeeds->bindParam(1, $id);
			foreach($selectedNeeds as $selectedNeed){
				$writeNeeds->bindParam(2, $selectedNeed);
				$writeNeeds->execute();
			}
			$writeNeeds->closeCursor();
			/*end of writing of selected needs*/

			$message = 'Record '.(($disabilityId == 'new')? 'added':'updated' ). ' successfully.';
			$this->addFlash('disabilityModifiedMessage', $message);
			if($disabilityId == 'new')
				return $this->redirectToRoute('admin_settings_disabilities',array(), 301);
			else
				return $this->redirectToRoute('edit_disability',['disabilityId'=>$disabilityId], 301);

		}

		return $this->render('admin/settings/edit_disability.html.twig', array(
			'form'=>$form->createView()
			)
		);
	}
	/**
	 *@Route("/admin/add_level/{level}", name="add_level", condition="request.isXmlHttpRequest()", options={"expose":true})
	 */
	public function adminAddLevelAction(Request $request, $level){
		$connection = $this->get('database_connection');
		$session = $request->getSession();
		$response = array();

		try{
			$connection->executeQuery("INSERT INTO `level`(level_name) VALUES(?)", array($level));
			$response['idlevel'] = $connection->lastInsertId();
			$response['levelName'] = $level;
			$levels = $session->getFlashBag()->get('disability_levels');
			$levels[] = array('idlevel'=>$response['idlevel'], 'level_name' => $level);
			$session->getFlashBag()->set('disability_levels', $levels);
			$response['result'] = 'success';
		}
		catch(DBALException $e){
			$response['result'] = 'failure';
		}
		$session->invalidate();
		return new JsonResponse($response);

	}
}


?>